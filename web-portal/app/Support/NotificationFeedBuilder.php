<?php

namespace App\Support;

use App\Exceptions\ApiException;
use App\Services\Api\S4FinanceClient;
use App\Services\Auth\PortalAuthService;
use Carbon\Carbon;

class NotificationFeedBuilder
{
    /** @var array<string, array<string, mixed>|null> */
    private array $snapshot = [];

    public function __construct(
        private readonly PortalAuthService $auth,
        private readonly S4FinanceClient $gateway,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(): array
    {
        $this->loadSnapshot();

        return array_merge(
            $this->systemMessages(),
            $this->leaveRequests(),
            $this->purchaseOrders(),
            $this->payrollRuns(),
            $this->journalEntries(),
            $this->fiscalPeriodReminders(),
            $this->stockAlerts(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forDashboardApprovals(int $limit = 6): array
    {
        return array_map(
            fn (array $item): array => [
                'type' => (string) ($item['category_label'] ?? 'Task'),
                'title' => (string) ($item['title'] ?? ''),
                'meta' => (string) ($item['body'] ?? ''),
                'href' => (string) ($item['href'] ?? '#'),
                'status' => 'pending',
            ],
            array_slice($this->build(), 0, $limit),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function systemMessages(): array
    {
        $items = [];

        if ($this->auth->mustChangePassword()) {
            $items[] = $this->feedItem(
                sourceKey: 'system:must_change_password',
                type: 'system',
                category: 'account',
                categoryLabel: 'Account security',
                title: 'Change your password',
                body: 'Your administrator requires a new password before you continue.',
                href: route('account.change-password.create'),
                priority: 'high',
            );
        }

        $configured = config('portal.notifications.system_messages', []);

        if (! is_array($configured)) {
            return $items;
        }

        foreach ($configured as $index => $message) {
            if (! is_array($message)) {
                continue;
            }

            $title = trim((string) ($message['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $permissions = $message['permissions'] ?? [];

            if (is_array($permissions) && $permissions !== [] && ! $this->auth->hasAnyPermission($permissions)) {
                continue;
            }

            $items[] = $this->feedItem(
                sourceKey: 'system:config:'.(string) ($message['key'] ?? $index),
                type: 'system',
                category: 'system',
                categoryLabel: (string) ($message['category_label'] ?? 'System message'),
                title: $title,
                body: (string) ($message['body'] ?? ''),
                href: (string) ($message['href'] ?? '/'),
                priority: (string) ($message['priority'] ?? 'normal'),
            );
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function leaveRequests(): array
    {
        if (! $this->auth->hasAnyPermission(['S2.workforce.leave_requests.read'])) {
            return [];
        }

        $items = [];

        foreach ($this->snapshotList('s2_leave') as $leave) {
            if (! is_array($leave)) {
                continue;
            }

            $id = (int) ($leave['id'] ?? 0);
            $name = (string) ($leave['employee']['full_name'] ?? $leave['employee_name'] ?? 'Employee');
            $leaveType = (string) ($leave['leave_type'] ?? 'Leave');

            $items[] = $this->feedItem(
                sourceKey: 'leave:'.$id,
                type: 'approval',
                category: 'leave',
                categoryLabel: 'Leave request',
                title: $name,
                body: $leaveType,
                href: route('hr.leave.index'),
            );
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function purchaseOrders(): array
    {
        if (! $this->auth->hasAnyPermission(['S3.inventory.purchase_orders.read'])) {
            return [];
        }

        $items = [];

        foreach ($this->snapshotList('s3_purchase_orders') as $po) {
            if (! is_array($po)) {
                continue;
            }

            $status = (string) ($po['status'] ?? '');

            if (! in_array($status, ['pending_dept_head', 'pending_finance', 'pending_gm'], true)) {
                continue;
            }

            $id = (int) ($po['id'] ?? 0);

            $items[] = $this->feedItem(
                sourceKey: 'purchase_order:'.$id,
                type: 'approval',
                category: 'purchase_order',
                categoryLabel: 'Purchase order',
                title: (string) ($po['po_number'] ?? 'PO #'.$id),
                body: str_replace('_', ' ', $status),
                href: route('inventory.purchase-orders.index'),
            );
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function payrollRuns(): array
    {
        if (! $this->auth->hasAnyPermission(['S2.workforce.payroll_runs.read'])) {
            return [];
        }

        $items = [];

        foreach ($this->snapshotList('s2_payroll') as $run) {
            if (! is_array($run)) {
                continue;
            }

            $id = (int) ($run['id'] ?? 0);

            $items[] = $this->feedItem(
                sourceKey: 'payroll_run:'.$id,
                type: 'approval',
                category: 'payroll',
                categoryLabel: 'Payroll run',
                title: (string) ($run['run_number'] ?? 'Run #'.$id),
                body: (string) ($run['pay_period_label'] ?? 'Pending approval'),
                href: route('payroll.runs.index'),
            );
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function journalEntries(): array
    {
        if (! $this->auth->hasAnyPermission(['S4.finance.journal_entries.approve'])) {
            return [];
        }

        $items = [];

        foreach ($this->snapshotList('s4_journals_draft') as $entry) {
            if (! is_array($entry) || (string) ($entry['source_module'] ?? '') !== 'manual') {
                continue;
            }

            if ((string) ($entry['status'] ?? '') !== 'draft') {
                continue;
            }

            $id = (int) ($entry['id'] ?? 0);

            $items[] = $this->feedItem(
                sourceKey: 'journal:draft:'.$id,
                type: 'approval',
                category: 'journal',
                categoryLabel: 'Journal entry',
                title: (string) ($entry['entry_number'] ?? 'JE #'.$id),
                body: (string) ($entry['description'] ?? 'Awaiting finance approval'),
                href: route('finance.journals.show', $id),
            );
        }

        foreach ($this->snapshotList('s4_journals_approved') as $entry) {
            if (! is_array($entry) || (string) ($entry['source_module'] ?? '') !== 'manual') {
                continue;
            }

            if ((string) ($entry['status'] ?? '') !== 'approved') {
                continue;
            }

            if (! JournalApprovalSteps::requiresGm($entry) || ! empty($entry['second_approved_by'])) {
                continue;
            }

            $id = (int) ($entry['id'] ?? 0);

            $items[] = $this->feedItem(
                sourceKey: 'journal:gm:'.$id,
                type: 'approval',
                category: 'journal',
                categoryLabel: 'Journal entry',
                title: (string) ($entry['entry_number'] ?? 'JE #'.$id),
                body: 'Large entry awaiting GM approval',
                href: route('finance.journals.show', $id),
                priority: 'high',
            );
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fiscalPeriodReminders(): array
    {
        if (! $this->auth->hasAnyPermission(['S4.finance.fiscal_periods.close'])) {
            return [];
        }

        $items = [];
        $reminderDays = max(1, (int) config('portal.notifications.fiscal_period_reminder_days', 7));
        $today = Carbon::today();
        $deadline = $today->copy()->addDays($reminderDays);

        foreach ($this->snapshotList('s4_fiscal_periods') as $period) {
            if (! is_array($period)) {
                continue;
            }

            $id = (int) ($period['id'] ?? 0);
            $status = (string) ($period['status'] ?? '');
            $year = (int) ($period['year'] ?? 0);
            $periodNumber = (int) ($period['period_number'] ?? 0);
            $label = $year > 0 && $periodNumber > 0
                ? sprintf('%d P%02d', $year, $periodNumber)
                : 'Fiscal period #'.$id;

            if ($status === 'closing') {
                $items[] = $this->feedItem(
                    sourceKey: 'fiscal_period:closing:'.$id,
                    type: 'reminder',
                    category: 'fiscal_period',
                    categoryLabel: 'Fiscal period',
                    title: $label,
                    body: 'Period is in closing — complete month-end close.',
                    href: route('finance.fiscal-periods.index'),
                    priority: 'high',
                );

                continue;
            }

            if ($status !== 'open') {
                continue;
            }

            $endDate = Carbon::parse((string) ($period['end_date'] ?? ''))->startOfDay();

            if ($endDate->lt($today) || $endDate->gt($deadline)) {
                continue;
            }

            $daysLeft = $today->diffInDays($endDate);

            $items[] = $this->feedItem(
                sourceKey: 'fiscal_period:ending:'.$id,
                type: 'reminder',
                category: 'fiscal_period',
                categoryLabel: 'Fiscal period',
                title: $label,
                body: $daysLeft === 0
                    ? 'Period ends today — prepare for close.'
                    : ($daysLeft === 1
                        ? 'Period ends tomorrow — prepare for close.'
                        : "Period ends in {$daysLeft} days — prepare for close."),
                href: route('finance.fiscal-periods.index'),
                priority: $daysLeft <= 2 ? 'high' : 'normal',
            );
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function stockAlerts(): array
    {
        if (! $this->auth->hasAnyPermission(['S3.inventory.items.read'])) {
            return [];
        }

        $items = [];
        $lowStock = $this->snapshotList('s3_low_stock');
        $expiry = $this->snapshotList('s3_expiry');

        if ($lowStock !== []) {
            $count = count($lowStock);
            $items[] = $this->feedItem(
                sourceKey: 'stock:low_stock',
                type: 'alert',
                category: 'stock',
                categoryLabel: 'Inventory alert',
                title: 'Low stock',
                body: $count === 1 ? '1 item is below reorder level.' : "{$count} items are below reorder level.",
                href: route('inventory.alerts.index'),
                priority: 'high',
            );
        }

        if ($expiry !== []) {
            $count = count($expiry);
            $items[] = $this->feedItem(
                sourceKey: 'stock:expiry',
                type: 'alert',
                category: 'stock',
                categoryLabel: 'Inventory alert',
                title: 'Expiry warning',
                body: $count === 1 ? '1 batch is nearing expiry.' : "{$count} batches are nearing expiry.",
                href: route('inventory.alerts.index'),
                priority: 'high',
            );
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function feedItem(
        string $sourceKey,
        string $type,
        string $category,
        string $categoryLabel,
        string $title,
        string $body,
        string $href,
        string $priority = 'normal',
    ): array {
        return [
            'source_key' => $sourceKey,
            'type' => $type,
            'category' => $category,
            'category_label' => $categoryLabel,
            'title' => $title,
            'body' => $body,
            'href' => $href,
            'priority' => $priority,
        ];
    }

    private function loadSnapshot(): void
    {
        $requests = [];

        if ($this->auth->hasAnyPermission(['S2.workforce.leave_requests.read'])) {
            $requests['s2_leave'] = ['path' => '/s2/api/v1/leave-requests', 'query' => ['status' => 'pending']];
        }

        if ($this->auth->hasAnyPermission(['S2.workforce.payroll_runs.read'])) {
            $requests['s2_payroll'] = ['path' => '/s2/api/v1/payroll-runs', 'query' => ['status' => 'pending_approval']];
        }

        if ($this->auth->hasAnyPermission(['S3.inventory.purchase_orders.read'])) {
            $requests['s3_purchase_orders'] = ['path' => '/s3/api/v1/purchase-orders', 'query' => ['per_page' => 50]];
        }

        if ($this->auth->hasAnyPermission(['S3.inventory.items.read'])) {
            $requests['s3_low_stock'] = ['path' => '/s3/api/v1/stock/low-stock-alerts', 'query' => []];
            $requests['s3_expiry'] = ['path' => '/s3/api/v1/stock/expiry-alerts', 'query' => []];
        }

        if ($this->auth->hasAnyPermission(['S4.finance.journal_entries.approve'])) {
            $requests['s4_journals_draft'] = [
                'path' => '/s4/api/v1/journal-entries',
                'query' => ['status' => 'draft', 'source_module' => 'manual', 'per_page' => 50],
            ];
            $requests['s4_journals_approved'] = [
                'path' => '/s4/api/v1/journal-entries',
                'query' => ['status' => 'approved', 'source_module' => 'manual', 'per_page' => 50],
            ];
        }

        if ($this->auth->hasAnyPermission(['S4.finance.fiscal_periods.close'])) {
            $requests['s4_fiscal_periods'] = ['path' => '/s4/api/v1/fiscal-periods', 'query' => []];
        }

        if ($requests === []) {
            $this->snapshot = [];

            return;
        }

        try {
            $this->snapshot = $this->gateway->fetchMany($requests);
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

        $data = $payload['data'] ?? $payload;

        return is_array($data) ? array_values($data) : [];
    }
}
