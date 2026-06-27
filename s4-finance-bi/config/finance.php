<?php

return [
    'manual_journal_gm_threshold' => (float) env('S4_MANUAL_JOURNAL_GM_THRESHOLD', 50000),
    'ar_account_codes' => ['1100', '1101'],
    'ap_account_codes' => ['2001'],
    'revenue_account_codes' => ['4001', '4002', '4003', '4004'],
    'cogs_account_codes' => ['5003'],
    'pdf_letterhead_path' => env('PDF_LETTERHEAD_PATH', base_path('resources/exports/letterhead/wonderland_hotel.png')),
];
