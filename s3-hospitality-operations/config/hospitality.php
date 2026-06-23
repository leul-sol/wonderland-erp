<?php

return [
    'service_charge_rate' => (float) env('SC_RATE', 0.10),
    'vat_rate' => (float) env('VAT_RATE', 0.15),
    'po_dept_head_threshold' => (float) env('PO_DEPT_HEAD_THRESHOLD', 5000),
    'po_finance_threshold' => (float) env('PO_FINANCE_THRESHOLD', 50000),
    'expiry_alert_days' => (int) env('EXPIRY_ALERT_DAYS', 14),
    'accounts' => [
        'ar_guest' => '1100',
        'cash' => '1001',
        'inventory_fb' => '1200',
        'ap_suppliers' => '2001',
        'room_revenue' => '4001',
        'fb_revenue' => '4002',
        'service_charge_revenue' => '4003',
        'vat_payable' => '2300',
        'cogs_food' => '5003',
    ],
];
