<?php

namespace Database\Seeders;

use App\Models\UatScenario;
use Illuminate\Database\Seeder;

class UatSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = [
            [
                'UAT-S1-001', 'S1', 'Super admin can log in',
                'S1-AUTH-001',
                'super.admin user exists from seed',
                "1. POST /s1/api/v1/auth/login with super.admin credentials\n2. Call GET /s1/api/v1/users with Bearer token",
                '200 OK with user list',
                'pending',
            ],
            [
                'UAT-S3-001', 'S3', 'Guest check-in to folio charge',
                'S3-HTL-001',
                'Rooms seeded; JWT with receptionist permissions',
                "1. Create reservation\n2. Check in with room\n3. Post room charge on folio",
                'Folio shows charge; S4 journal posted',
                'pending',
            ],
            [
                'UAT-S3-002', 'S3', 'Folio settle and check-out',
                'S3-FOL-001',
                'Guest checked in with open folio balance',
                "1. Settle folio with cash\n2. Check out reservation",
                'Folio settled; guest checked out',
                'pending',
            ],
            [
                'UAT-S3-003', 'S3', 'F&B order with inventory depletion',
                'S3-FB-001',
                'Inventory received via PO; guest folio open',
                "1. Create order on folio\n2. Add menu item\n3. Finalize order",
                'COGS journal posted; stock reduced',
                'pending',
            ],
            [
                'UAT-S2-001', 'S2', 'Payroll run approval posts to GL',
                'S2-PAY-001',
                'Active employees exist',
                "1. Create payroll run\n2. Approve payroll run",
                'S4 receives consolidated payroll journal',
                'pending',
            ],
            [
                'UAT-S4-001', 'S4', 'Trial balance debits equal credits',
                'S4-RPT-001',
                'Posted journals in current fiscal period',
                'GET /s4/api/v1/reports/trial-balance?fiscal_period_id=current',
                'Total debits equal total credits',
                'pending',
            ],
            [
                'UAT-S4-002', 'S4', 'Manual journal approve and post',
                'S4-GL-002',
                'Finance manager JWT',
                "1. POST manual journal (draft)\n2. Approve\n3. Post",
                'Entry status becomes posted',
                'pending',
            ],
            [
                'UAT-S4-003', 'S4', 'Operations dashboard shows cross-system KPIs',
                'S4-BI-001',
                'S2 and S3 services healthy',
                'GET /s4/api/v1/dashboards/operations',
                'Returns occupancy, payroll, and finance KPIs',
                'pending',
            ],
            [
                'UAT-S4-004', 'S4', 'CSV export of income statement',
                'S4-BI-002',
                'Posted revenue in period',
                'POST /s4/api/v1/bi/exports with income_statement',
                'CSV file downloads with net income row',
                'pending',
            ],
            [
                'UAT-E2E-001', 'S4', 'End-to-end hotel day',
                'S3-FOL-001',
                'Full platform running via docker compose',
                "1. Check in guest\n2. Room + F&B charges\n3. Settle folio\n4. View income statement in S4",
                'Revenue appears on P&L; guest checked out',
                'pending',
            ],
        ];

        foreach ($scenarios as [$key, $system, $title, $requirement, $preconditions, $steps, $expected, $status]) {
            UatScenario::query()->updateOrCreate(
                ['scenario_key' => $key],
                [
                    'system' => $system,
                    'title' => $title,
                    'requirement_key' => $requirement,
                    'preconditions' => $preconditions,
                    'steps' => $steps,
                    'expected_outcome' => $expected,
                    'status' => $status,
                ]
            );
        }
    }
}
