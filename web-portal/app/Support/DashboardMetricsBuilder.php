<?php

namespace App\Support;

use App\Exceptions\ApiException;
use App\Services\Api\S1AdminClient;
use App\Services\Api\S2WorkforceClient;
use App\Services\Api\S3HospitalityClient;
use App\Services\Api\S4FinanceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Support\Facades\Route;

class DashboardMetricsBuilder
{
    /** @var array<string, array{label: string, priority: int}> */
    private const PERSONAS = [
        'super_admin' => ['label' => 'Platform administration', 'priority' => 100],
        'general_manager' => ['label' => 'Executive operations', 'priority' => 90],
        'finance_manager' => ['label' => 'Finance & accounting', 'priority' => 85],
        'accountant' => ['label' => 'Finance & accounting', 'priority' => 80],
        'hr_manager' => ['label' => 'Human resources', 'priority' => 75],
        'payroll_officer' => ['label' => 'Payroll operations', 'priority' => 70],
        'inventory_manager' => ['label' => 'Inventory & procurement', 'priority' => 65],
        'restaurant_manager' => ['label' => 'Restaurant & F&B', 'priority' => 60],
        'receptionist' => ['label' => 'Front desk operations', 'priority' => 55],
        'cashier' => ['label' => 'Cashier & billing', 'priority' => 50],
        'department_head' => ['label' => 'Department management', 'priority' => 45],
        'report_viewer' => ['label' => 'Business intelligence', 'priority' => 40],
    ];

    /** @var array<string, list<string>> */
    private const KPI_GROUP_ORDER = [
        'executive' => ['finance', 'hospitality', 'workforce', 'inventory', 'fb', 'admin'],
        'finance' => ['finance', 'hospitality', 'workforce', 'inventory', 'fb', 'admin'],
        'front_desk' => ['hospitality', 'finance', 'fb', 'workforce', 'inventory', 'admin'],
        'hr' => ['workforce', 'finance', 'hospitality', 'inventory', 'fb', 'admin'],
        'inventory' => ['inventory', 'finance', 'hospitality', 'workforce', 'fb', 'admin'],
        'fb' => ['fb', 'hospitality', 'finance', 'inventory', 'workforce', 'admin'],
        'admin' => ['admin', 'finance', 'hospitality', 'workforce', 'inventory', 'fb'],
        'default' => ['finance', 'hospitality', 'workforce', 'inventory', 'fb', 'admin'],
    ];

    public function __construct(
        private readonly PortalAuthService $auth,
        private readonly S4FinanceClient $s4,
        private readonly S3HospitalityClient $s3,
        private readonly S2WorkforceClient $s2,
        private readonly S1AdminClient $s1,
        private readonly NotificationFeedBuilder $notificationFeed,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    /** @var array<string, array<string, mixed>|null> */
    private array $snapshot = [];

    public function build(?string $from = null, ?string $to = null): array
    {
        $from = $from ?: now()->startOfMonth()->toDateString();
        $to = $to ?: now()->toDateString();

        $this->loadSnapshot($from, $to);

        $user = $this->auth->user() ?? [];
        $roles = $this->normalizeRoles($user['roles'] ?? []);
        $persona = $this->resolvePersona($roles);
        $kpiGroups = $this->collectKpiGroups($from, $to);
        $orderedKpis = $this->orderKpis($kpiGroups, $persona['view']);

        return [
            'persona' => $persona['key'],
            'persona_label' => $persona['label'],
            'user_name' => (string) ($user['name'] ?? $user['username'] ?? 'User'),
            'roles' => $roles,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'label' => $this->formatDateRangeLabel($from, $to),
            ],
            'kpis' => array_slice($orderedKpis, 0, 4),
            'secondary_kpis' => array_slice($orderedKpis, 4, 4),
            'quick_links' => $this->buildQuickLinks(),
            'approvals' => $this->buildApprovals(),
            'notices' => $this->buildNotices($roles),
            'occupancy' => $kpiGroups['hospitality']['occupancy'] ?? null,
            'attendance' => $kpiGroups['workforce']['attendance'] ?? null,
            'revenue_chart' => $this->buildRevenueChart($from, $to),
        ];
    }

    /**
     * @param  mixed  $roles
     * @return list<string>
     */
    private function normalizeRoles(mixed $roles): array
    {
        if (! is_array($roles)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($role) {
            if (is_string($role)) {
                return $role;
            }

            if (is_array($role)) {
                return $role['name'] ?? $role['display_name'] ?? null;
            }

            return null;
        }, $roles))));
    }

    /**
     * @param  list<string>  $roles
     * @return array{key: string, label: string, view: string}
     */
    private function resolvePersona(array $roles): array
    {
        $bestKey = 'default';
        $bestPriority = -1;
        $bestLabel = 'Operations overview';

        foreach ($roles as $role) {
            $slug = strtolower(str_replace([' ', '-'], '_', $role));
            $meta = self::PERSONAS[$slug] ?? null;

            if ($meta === null) {
                continue;
            }

            if ($meta['priority'] > $bestPriority) {
                $bestPriority = $meta['priority'];
                $bestKey = $slug;
                $bestLabel = $meta['label'];
            }
        }

        $view = match ($bestKey) {
            'super_admin' => 'admin',
            'general_manager' => 'executive',
            'finance_manager', 'accountant' => 'finance',
            'hr_manager', 'department_head' => 'hr',
            'payroll_officer' => 'hr',
            'inventory_manager' => 'inventory',
            'restaurant_manager' => 'fb',
            'receptionist', 'cashier' => 'front_desk',
            'report_viewer' => 'executive',
            default => 'default',
        };

        return [
            'key' => $bestKey,
            'label' => $bestLabel,
            'view' => $view,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function collectKpiGroups(?string $from = null, ?string $to = null): array
    {
        $groups = [];

        if ($this->auth->hasAnyPermission(['S4.bi.dashboards.read', 'S4.bi.reports.read'])) {
            $groups['finance'] = $this->financeKpis($from, $to);
        }

        if ($this->auth->hasAnyPermission(['S3.hotel.rooms.read', 'S3.hotel.folios.read', 'S3.hotel.reservations.read'])) {
            $groups['hospitality'] = $this->hospitalityKpis();
        }

        if ($this->auth->hasAnyPermission(['S2.workforce.employees.read', 'S2.workforce.leave_requests.read', 'S2.workforce.attendance.read'])) {
            $groups['workforce'] = $this->workforceKpis();
        }

        if ($this->auth->hasAnyPermission(['S3.inventory.items.read', 'S3.inventory.purchase_orders.read'])) {
            $groups['inventory'] = $this->inventoryKpis();
        }

        if ($this->auth->hasAnyPermission(['S3.restaurant.menu.read', 'S3.restaurant.orders.read'])) {
            $groups['fb'] = $this->fbKpis();
        }

        if ($this->auth->hasAnyPermission(['S1.identity.users.read', 'S1.identity.audit_logs.read'])) {
            $groups['admin'] = $this->adminKpis();
        }

        return $groups;
    }

    /**
     * @param  array<string, array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function orderKpis(array $groups, string $view): array
    {
        $order = self::KPI_GROUP_ORDER[$view] ?? self::KPI_GROUP_ORDER['default'];
        $items = [];

        foreach ($order as $groupKey) {
            foreach ($groups[$groupKey]['cards'] ?? [] as $card) {
                $items[] = $card;
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function financeKpis(?string $from = null, ?string $to = null): array
    {
        $cards = [];
        $response = $this->snapshot['s4_executive'] ?? null;
        $kpis = is_array($response) ? ($response['data']['kpis'] ?? []) : [];

        $periodLabel = $from && $to ? 'period' : 'MTD';

        $map = [
            'revenue' => ['label' => "Revenue ({$periodLabel})", 'icon' => 'trending-up', 'tone' => 'indigo', 'prefix' => 'ETB '],
            'net_income' => ['label' => 'Net income', 'icon' => 'wallet', 'tone' => 'emerald', 'prefix' => 'ETB '],
            'cash_position' => ['label' => 'Cash position', 'icon' => 'landmark', 'tone' => 'sky', 'prefix' => 'ETB '],
            'ar_outstanding' => ['label' => 'AR outstanding', 'icon' => 'arrow-down-circle', 'tone' => 'amber', 'prefix' => 'ETB '],
            'ap_outstanding' => ['label' => 'AP outstanding', 'icon' => 'arrow-up-circle', 'tone' => 'rose', 'prefix' => 'ETB '],
            'expenses' => ['label' => "Expenses ({$periodLabel})", 'icon' => 'receipt', 'tone' => 'slate', 'prefix' => 'ETB '],
        ];

        foreach ($map as $key => $meta) {
            if (! isset($kpis[$key])) {
                continue;
            }

            $cards[] = [
                'key' => $key,
                'group' => 'finance',
                'label' => $meta['label'],
                'value' => $meta['prefix'].(string) $kpis[$key],
                'icon' => $meta['icon'],
                'tone' => $meta['tone'],
                'href' => $key === 'ar_outstanding' ? route('finance.receivables.index') : ($key === 'ap_outstanding' ? route('finance.payables.index') : route('finance.dashboard.executive')),
            ];
        }

        return ['cards' => $cards];
    }

    /**
     * @return array<string, mixed>
     */
    private function hospitalityKpis(): array
    {
        $rooms = $this->snapshotList('s3_rooms');
        $folios = $this->snapshotList('s3_folios');
        $reservations = $this->snapshotList('s3_reservations');

        $total = count($rooms);
        $occupied = count(array_filter($rooms, fn ($room) => ($room['status'] ?? '') === 'occupied'));
        $available = count(array_filter($rooms, fn ($room) => ($room['status'] ?? '') === 'available'));
        $maintenance = count(array_filter($rooms, fn ($room) => ($room['status'] ?? '') === 'maintenance'));
        $occupancyRate = $total > 0 ? round(($occupied / $total) * 100, 1) : 0.0;

        $cards = [
            [
                'key' => 'occupancy_rate',
                'group' => 'hospitality',
                'label' => 'Occupancy rate',
                'value' => number_format($occupancyRate, 1).'%',
                'icon' => 'bed-double',
                'tone' => 'indigo',
                'href' => route('front-desk.rooms.index'),
                'breakdown' => [
                    ['label' => 'Occupied', 'value' => (string) $occupied, 'tone' => 'success'],
                    ['label' => 'Available', 'value' => (string) $available, 'tone' => 'muted'],
                    ['label' => 'Maint.', 'value' => (string) $maintenance, 'tone' => 'warning'],
                ],
            ],
            [
                'key' => 'in_house_guests',
                'group' => 'hospitality',
                'label' => 'In-house guests',
                'value' => (string) count($reservations),
                'icon' => 'users-round',
                'tone' => 'teal',
                'href' => route('front-desk.folios.index'),
                'breakdown' => [
                    ['label' => 'Open folios', 'value' => (string) count($folios), 'tone' => 'info'],
                    ['label' => 'Rooms', 'value' => (string) $total, 'tone' => 'muted'],
                ],
            ],
            [
                'key' => 'open_folios',
                'group' => 'hospitality',
                'label' => 'Open folios',
                'value' => (string) count($folios),
                'icon' => 'book-open',
                'tone' => 'amber',
                'href' => route('front-desk.folios.index'),
            ],
        ];

        return [
            'cards' => $cards,
            'occupancy' => [
                'rate' => $occupancyRate,
                'occupied' => $occupied,
                'available' => $available,
                'maintenance' => $maintenance,
                'total' => $total,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workforceKpis(): array
    {
        $employees = $this->snapshotList('s2_employees');
        $leave = $this->snapshotList('s2_leave');
        $attendance = $this->snapshotList('s2_attendance');

        $present = count(array_filter($attendance, fn ($row) => ($row['status'] ?? '') === 'present'));
        $absent = count(array_filter($attendance, fn ($row) => ($row['status'] ?? '') === 'absent'));

        $cards = [
            [
                'key' => 'active_employees',
                'group' => 'workforce',
                'label' => 'Active employees',
                'value' => (string) count($employees),
                'icon' => 'users',
                'tone' => 'indigo',
                'href' => route('hr.employees.index'),
            ],
            [
                'key' => 'pending_leave',
                'group' => 'workforce',
                'label' => 'Pending leave',
                'value' => (string) count($leave),
                'icon' => 'calendar-range',
                'tone' => 'amber',
                'href' => route('hr.leave.index'),
            ],
            [
                'key' => 'attendance_today',
                'group' => 'workforce',
                'label' => 'Attendance today',
                'value' => (string) count($attendance),
                'icon' => 'clipboard-list',
                'tone' => 'emerald',
                'href' => route('hr.attendance.index'),
                'breakdown' => [
                    ['label' => 'Present', 'value' => (string) $present, 'tone' => 'success'],
                    ['label' => 'Absent', 'value' => (string) $absent, 'tone' => 'danger'],
                ],
            ],
        ];

        if ($this->auth->hasAnyPermission(['S2.workforce.payroll_runs.read'])) {
            $runs = $this->snapshotList('s2_payroll');
            $cards[] = [
                'key' => 'payroll_pending',
                'group' => 'workforce',
                'label' => 'Payroll pending approval',
                'value' => (string) count($runs),
                'icon' => 'wallet',
                'tone' => 'rose',
                'href' => route('payroll.runs.index'),
            ];
        }

        return [
            'cards' => $cards,
            'attendance' => [
                'total' => count($attendance),
                'present' => $present,
                'absent' => $absent,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventoryKpis(): array
    {
        $items = $this->snapshotList('s3_items');
        $orders = $this->snapshotList('s3_purchase_orders');

        $lowStock = count(array_filter($items, function ($item) {
            $onHand = (float) ($item['quantity_on_hand'] ?? 0);
            $reorder = (float) ($item['reorder_level'] ?? 0);

            return $reorder > 0 && $onHand <= $reorder;
        }));

        $pendingApproval = count(array_filter($orders, fn ($po) => in_array($po['status'] ?? '', ['pending_dept_head', 'pending_finance', 'pending_gm'], true)));
        $awaitingReceipt = count(array_filter($orders, fn ($po) => ($po['status'] ?? '') === 'approved'));

        return [
            'cards' => [
                [
                    'key' => 'inventory_items',
                    'group' => 'inventory',
                    'label' => 'Stock items',
                    'value' => (string) count($items),
                    'icon' => 'package',
                    'tone' => 'indigo',
                    'href' => route('inventory.items.index'),
                    'breakdown' => [
                        ['label' => 'Low stock', 'value' => (string) $lowStock, 'tone' => $lowStock > 0 ? 'danger' : 'success'],
                    ],
                ],
                [
                    'key' => 'purchase_orders',
                    'group' => 'inventory',
                    'label' => 'Purchase orders',
                    'value' => (string) count($orders),
                    'icon' => 'truck',
                    'tone' => 'amber',
                    'href' => route('inventory.purchase-orders.index'),
                    'breakdown' => [
                        ['label' => 'Pending approval', 'value' => (string) $pendingApproval, 'tone' => 'warning'],
                        ['label' => 'To receive', 'value' => (string) $awaitingReceipt, 'tone' => 'info'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fbKpis(): array
    {
        $menu = $this->snapshotList('s3_menu');

        return [
            'cards' => [
                [
                    'key' => 'menu_items',
                    'group' => 'fb',
                    'label' => 'Active menu items',
                    'value' => (string) count($menu),
                    'icon' => 'utensils-crossed',
                    'tone' => 'rose',
                    'href' => route('fb.menu.index'),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminKpis(): array
    {
        $users = $this->snapshotList('s1_users');
        $roles = $this->snapshotList('s1_roles');

        $activeUsers = count(array_filter($users, fn ($user) => (bool) ($user['is_active'] ?? false)));
        $inactiveUsers = count($users) - $activeUsers;

        return [
            'cards' => [
                [
                    'key' => 'platform_users',
                    'group' => 'admin',
                    'label' => 'Platform users',
                    'value' => (string) count($users),
                    'icon' => 'shield-check',
                    'tone' => 'slate',
                    'href' => route('admin.users.index'),
                    'breakdown' => [
                        ['label' => 'Active', 'value' => (string) $activeUsers, 'tone' => 'success'],
                        ['label' => 'Inactive', 'value' => (string) $inactiveUsers, 'tone' => 'muted'],
                    ],
                ],
                [
                    'key' => 'roles',
                    'group' => 'admin',
                    'label' => 'Roles configured',
                    'value' => (string) count($roles),
                    'icon' => 'user-cog',
                    'tone' => 'indigo',
                    'href' => route('admin.roles.index'),
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildQuickLinks(): array
    {
        $links = [];

        foreach (config('portal.modules', []) as $module) {
            if (! is_array($module) || ($module['key'] ?? '') === 'dashboard') {
                continue;
            }

            $permissions = is_array($module['permissions'] ?? null) ? $module['permissions'] : [];

            if ($permissions !== [] && ! $this->auth->hasAnyPermission($permissions)) {
                continue;
            }

            $routeName = (string) ($module['route'] ?? '');
            $label = (string) ($module['label'] ?? 'Module');
            $icon = (string) ($module['icon'] ?? 'layout-grid');

            if ($routeName !== '' && Route::has($routeName)) {
                $links[] = [
                    'label' => $label,
                    'href' => route($routeName),
                    'icon' => $icon,
                ];
            }

            if (count($links) >= 6) {
                break;
            }
        }

        return $links;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildApprovals(): array
    {
        return $this->notificationFeed->forDashboardApprovals();
    }

    /**
     * @param  list<string>  $roles
     * @return list<array<string, mixed>>
     */
    private function buildNotices(array $roles): array
    {
        $notices = [
            [
                'title' => 'Wonderland ERP pilot',
                'body' => 'Use module quick links for daily operations. Metrics refresh on each dashboard load.',
                'tone' => 'info',
            ],
        ];

        if (in_array('receptionist', $roles, true) || in_array('cashier', $roles, true)) {
            $notices[] = [
                'title' => 'Front desk golden path',
                'body' => 'Check in → post folio charges → settle → check out.',
                'tone' => 'teal',
            ];
        }

        if (in_array('finance_manager', $roles, true) || in_array('accountant', $roles, true)) {
            $notices[] = [
                'title' => 'Month-end close',
                'body' => 'Review open AP/AR, post journals, then close the fiscal period.',
                'tone' => 'amber',
            ];
        }

        return array_slice($notices, 0, 4);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<array<string, mixed>>
     */
    private function list(array $response): array
    {
        $data = $response['data'] ?? $response;

        return is_array($data) ? array_values($data) : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildRevenueChart(?string $from, ?string $to): ?array
    {
        if (! $this->auth->hasAnyPermission(['S4.bi.dashboards.read', 'S4.bi.reports.read'])) {
            return null;
        }

        $response = $this->snapshot['s4_operations'] ?? null;
        $lines = is_array($response) ? ($response['data']['revenue_by_source'] ?? []) : [];

        $bars = [];

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $source = (string) ($line['source_module'] ?? $line['source'] ?? 'Unknown');
            $amount = (float) ($line['volume'] ?? $line['amount'] ?? 0);

            $bars[] = [
                'source' => ucwords(str_replace('_', ' ', $source)),
                'amount' => $amount,
                'amount_label' => number_format($amount, 2, '.', ','),
            ];
        }

        if ($bars === []) {
            return [
                'from' => $from,
                'to' => $to,
                'bars' => [],
                'total' => '0.00',
            ];
        }

        $max = max(array_column($bars, 'amount')) ?: 1.0;

        foreach ($bars as &$bar) {
            $bar['percent'] = round(($bar['amount'] / $max) * 100, 1);
        }
        unset($bar);

        $total = array_sum(array_column($bars, 'amount'));

        return [
            'from' => $from,
            'to' => $to,
            'bars' => $bars,
            'total' => number_format($total, 2, '.', ','),
        ];
    }

    private function formatDateRangeLabel(string $from, string $to): string
    {
        $fromDate = \Illuminate\Support\Carbon::parse($from);
        $toDate = \Illuminate\Support\Carbon::parse($to);

        if ($fromDate->isSameDay($toDate)) {
            return $fromDate->format('j M Y');
        }

        return $fromDate->format('j M Y').' - '.$toDate->format('j M Y');
    }

    private function loadSnapshot(?string $from, ?string $to): void
    {
        $requests = [];
        $dateQuery = array_filter([
            'from' => $from,
            'to' => $to,
        ]);

        if ($this->auth->hasAnyPermission(['S4.bi.dashboards.read', 'S4.bi.reports.read'])) {
            $requests['s4_executive'] = ['path' => '/s4/api/v1/dashboards/executive', 'query' => $dateQuery];
            $requests['s4_operations'] = ['path' => '/s4/api/v1/dashboards/operations', 'query' => $dateQuery];
        }

        if ($this->auth->hasAnyPermission(['S3.hotel.rooms.read', 'S3.hotel.folios.read', 'S3.hotel.reservations.read'])) {
            $requests['s3_rooms'] = ['path' => '/s3/api/v1/rooms', 'query' => []];
            $requests['s3_folios'] = ['path' => '/s3/api/v1/folios', 'query' => ['status' => 'open', 'per_page' => 50]];
            $requests['s3_reservations'] = ['path' => '/s3/api/v1/reservations', 'query' => ['status' => 'checked_in']];
        }

        if ($this->auth->hasAnyPermission(['S2.workforce.employees.read'])) {
            $requests['s2_employees'] = ['path' => '/s2/api/v1/employees', 'query' => ['status' => 'active']];
        }

        if ($this->auth->hasAnyPermission(['S2.workforce.leave_requests.read'])) {
            $requests['s2_leave'] = ['path' => '/s2/api/v1/leave-requests', 'query' => ['status' => 'pending']];
        }

        if ($this->auth->hasAnyPermission(['S2.workforce.attendance.read'])) {
            $requests['s2_attendance'] = ['path' => '/s2/api/v1/attendance-records', 'query' => ['date' => now()->toDateString()]];
        }

        if ($this->auth->hasAnyPermission(['S2.workforce.payroll_runs.read'])) {
            $requests['s2_payroll'] = ['path' => '/s2/api/v1/payroll-runs', 'query' => ['status' => 'pending_approval']];
        }

        if ($this->auth->hasAnyPermission(['S3.inventory.items.read'])) {
            $requests['s3_items'] = ['path' => '/s3/api/v1/items', 'query' => []];
        }

        if ($this->auth->hasAnyPermission(['S3.inventory.purchase_orders.read'])) {
            $requests['s3_purchase_orders'] = ['path' => '/s3/api/v1/purchase-orders', 'query' => ['per_page' => 50]];
        }

        if ($this->auth->hasAnyPermission(['S3.restaurant.menu.read', 'S3.restaurant.orders.read'])) {
            $requests['s3_menu'] = ['path' => '/s3/api/v1/menu-items', 'query' => ['active_only' => true]];
        }

        if ($this->auth->hasAnyPermission(['S1.identity.users.read', 'S1.identity.audit_logs.read'])) {
            if ($this->auth->hasAnyPermission(['S1.identity.users.read'])) {
                $requests['s1_users'] = ['path' => '/s1/api/v1/users', 'query' => ['per_page' => 100]];
            }

            $requests['s1_roles'] = ['path' => '/s1/api/v1/roles', 'query' => []];
        }

        if ($requests === []) {
            $this->snapshot = [];

            return;
        }

        try {
            $this->snapshot = $this->s4->fetchMany($requests);
        } catch (ApiException) {
            $this->snapshot = array_fill_keys(array_keys($requests), null);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function snapshotList(string $key): array
    {
        $payload = $this->snapshot[$key] ?? null;

        if (! is_array($payload)) {
            return [];
        }

        return $this->list($payload);
    }
}
