<?php

return [
    'gateway_url' => rtrim((string) env('GATEWAY_INTERNAL_URL', 'http://wh-gateway'), '/'),

    'refresh_before_expiry_seconds' => (int) env('PORTAL_TOKEN_REFRESH_BUFFER', 120),

    /*
    | Sidebar modules — filtered by user permissions (any match shows module).
    | Routes without Phase 1+ pages render Modules/Placeholder until implemented.
    */
    'modules' => [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'permissions' => ['S4.bi.dashboards.read', 'S4.bi.reports.read'],
            'phase' => 0,
        ],
        [
            'key' => 'front_desk',
            'label' => 'Front desk',
            'route' => 'front-desk.rooms.index',
            'permissions' => ['S3.hotel.rooms.read', 'S3.hotel.reservations.read', 'S3.hotel.folios.read'],
            'phase' => 1,
        ],
        [
            'key' => 'fb',
            'label' => 'Restaurant and F&B',
            'route' => 'fb.menu.index',
            'permissions' => ['S3.restaurant.orders.read', 'S3.restaurant.menu.read'],
            'phase' => 2,
        ],
        [
            'key' => 'inventory',
            'label' => 'Inventory and procurement',
            'route' => 'inventory.items.index',
            'permissions' => ['S3.inventory.items.read', 'S3.inventory.purchase_orders.read'],
            'phase' => 3,
        ],
        [
            'key' => 'hr',
            'label' => 'HR',
            'route' => 'modules.placeholder',
            'route_params' => ['module' => 'hr'],
            'permissions' => ['S2.hr.employees.read', 'S2.leave.requests.read'],
            'phase' => 6,
        ],
        [
            'key' => 'payroll',
            'label' => 'Payroll',
            'route' => 'modules.placeholder',
            'route_params' => ['module' => 'payroll'],
            'permissions' => ['S2.payroll.runs.read'],
            'phase' => 6,
        ],
        [
            'key' => 'finance',
            'label' => 'Finance',
            'route' => 'finance.payables.index',
            'permissions' => ['S4.finance.payables.read', 'S4.finance.reports.read', 'S4.finance.journal_entries.read'],
            'phase' => 7,
        ],
        [
            'key' => 'admin',
            'label' => 'Administration',
            'route' => 'modules.placeholder',
            'route_params' => ['module' => 'admin'],
            'permissions' => ['S1.admin.users.read', 'S1.admin.roles.read'],
            'phase' => 8,
        ],
    ],

    /*
    | Task-first shortcuts — shown above module nav when route exists and user has permission.
    */
    'tasks' => [
        [
            'key' => 'check_in_guest',
            'label' => 'Check in guest',
            'route' => 'front-desk.check-in.create',
            'module' => 'front_desk',
            'permissions' => ['S3.hotel.checkinout.write', 'S3.hotel.reservations.write'],
        ],
        [
            'key' => 'settle_folio',
            'label' => 'Settle folio',
            'route' => 'front-desk.folios.index',
            'module' => 'front_desk',
            'permissions' => ['S3.hotel.folios.read', 'S3.hotel.folios.write'],
        ],
        [
            'key' => 'room_status',
            'label' => 'Room status',
            'route' => 'front-desk.rooms.index',
            'module' => 'front_desk',
            'permissions' => ['S3.hotel.rooms.read'],
        ],
        [
            'key' => 'approve_po',
            'label' => 'Approve purchase order',
            'route' => 'inventory.purchase-orders.index',
            'module' => 'inventory',
            'permissions' => ['S3.inventory.purchase_orders.approve'],
        ],
        [
            'key' => 'create_po',
            'label' => 'Create purchase order',
            'route' => 'inventory.purchase-orders.create',
            'module' => 'inventory',
            'permissions' => ['S3.inventory.purchase_orders.write'],
        ],
        [
            'key' => 'settle_payables',
            'label' => 'Settle payables',
            'route' => 'finance.payables.index',
            'module' => 'finance',
            'permissions' => ['S4.finance.payables.settle', 'S4.finance.payables.read'],
        ],
        [
            'key' => 'post_fb_to_folio',
            'label' => 'Post F&B to folio',
            'route' => 'fb.orders.create',
            'module' => 'fb',
            'permissions' => ['S3.restaurant.orders.write'],
        ],
        [
            'key' => 'view_menu',
            'label' => 'View menu',
            'route' => 'fb.menu.index',
            'module' => 'fb',
            'permissions' => ['S3.restaurant.menu.read'],
        ],
    ],
];
