#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$config = require $root.'/s4-finance-bi/config/reports.php';
$output = $root.'/postman/Wonderland-S4-Finance.postman_collection.json';

$reportItems = [];
foreach ($config['reports'] as $report) {
    $name = (string) ($report['name'] ?? $report['slug']);
    $slug = (string) $report['slug'];
    $reportItems[] = [
        'name' => $name,
        'request' => [
            'method' => 'GET',
            'header' => [
                ['key' => 'Authorization', 'value' => 'Bearer {{accessToken}}'],
            ],
            'url' => '{{baseUrl}}/bi/reports/'.$slug.'?fiscal_period_id={{fiscalPeriodId}}',
        ],
    ];
}

$collection = [
    'info' => [
        '_postman_id' => 'wonderland-s4-finance-v1',
        'name' => 'Wonderland ERP — S4 Finance & BI',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        'description' => 'Auto-generated: one GET /bi/reports/{slug} per catalog entry ('.count($reportItems).' reports).',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost/s4/api/v1'],
        ['key' => 'serviceKey', 'value' => 'dev-internal-key-change-in-prod'],
        ['key' => 'accessToken', 'value' => ''],
        ['key' => 'fiscalPeriodId', 'value' => '12'],
        ['key' => 'uatFiscalPeriodId', 'value' => '1'],
    ],
    'item' => [
        [
            'name' => 'Health',
            'request' => ['method' => 'GET', 'url' => '{{baseUrl}}/health'],
        ],
        [
            'name' => 'Reports',
            'item' => array_merge([
                [
                    'name' => 'Report Catalog',
                    'request' => [
                        'method' => 'GET',
                        'header' => [['key' => 'Authorization', 'value' => 'Bearer {{accessToken}}']],
                        'url' => '{{baseUrl}}/bi/reports',
                    ],
                ],
                [
                    'name' => 'Trial Balance (finance route)',
                    'request' => [
                        'method' => 'GET',
                        'header' => [['key' => 'Authorization', 'value' => 'Bearer {{accessToken}}']],
                        'url' => '{{baseUrl}}/reports/trial-balance?fiscal_period_id={{fiscalPeriodId}}',
                    ],
                ],
            ], $reportItems),
        ],
        [
            'name' => 'Export PDF',
            'request' => [
                'method' => 'POST',
                'header' => [
                    ['key' => 'Authorization', 'value' => 'Bearer {{accessToken}}'],
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                ],
                'body' => [
                    'mode' => 'raw',
                    'raw' => "{\n  \"report\": \"trial_balance\",\n  \"format\": \"pdf\",\n  \"fiscal_period_id\": {{fiscalPeriodId}}\n}",
                ],
                'url' => '{{baseUrl}}/bi/exports',
            ],
        ],
        [
            'name' => 'RTM nested UAT',
            'request' => [
                'method' => 'GET',
                'header' => [['key' => 'Authorization', 'value' => 'Bearer {{accessToken}}']],
                'url' => '{{baseUrl}}/rtm/1/uat',
            ],
        ],
        [
            'name' => 'Operational Events',
            'request' => [
                'method' => 'GET',
                'header' => [['key' => 'Authorization', 'value' => 'Bearer {{accessToken}}']],
                'url' => '{{baseUrl}}/bi/operational-events',
            ],
        ],
        [
            'name' => 'Budgets',
            'request' => [
                'method' => 'POST',
                'header' => [
                    ['key' => 'Authorization', 'value' => 'Bearer {{accessToken}}'],
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                ],
                'body' => [
                    'mode' => 'raw',
                    'raw' => "{\n  \"fiscal_period_id\": {{fiscalPeriodId}},\n  \"account_code\": \"4001\",\n  \"amount\": 100000\n}",
                ],
                'url' => '{{baseUrl}}/finance/budgets',
            ],
        ],
    ],
];

file_put_contents($output, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
fwrite(STDOUT, 'Wrote '.count($reportItems)." report requests to {$output}\n");
