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
                'UAT-S1-002', 'S1', 'Account lockout after failed logins',
                'S1-SEC-001',
                'Dedicated test user exists',
                "1. Fail login 5 times with wrong password\n2. Attempt login with correct password",
                '403 Account is temporarily locked',
                'pending',
            ],
            [
                'UAT-S1-003', 'S1', 'Deactivated user cannot sign in',
                'S1-USER-001',
                'Active test user exists',
                "1. POST /users/{id}/deactivate\n2. POST /auth/login as that user",
                '403 Account is deactivated',
                'pending',
            ],
            [
                'UAT-S1-004', 'S1', 'Role permission sync audited',
                'S1-RBAC-001',
                'super.admin JWT with sync permission',
                "1. PUT /roles/{id}/permissions\n2. GET /audit-logs?event=permission.changed",
                'permission.changed audit entry present',
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
            [
                'UAT-S2-002', 'S2', 'Leave request approval',
                'S2-LEAVE-001',
                'Active employee exists',
                "1. Create leave request\n2. Approve leave request",
                'Leave status approved; outbox event emitted',
                'pending',
            ],
            [
                'UAT-S2-003', 'S2', 'Attendance record for employee',
                'S2-ATT-001',
                'Active employee exists',
                'POST /s2/api/v1/attendance-records with check-in/out times',
                'Attendance saved with hours_worked',
                'pending',
            ],
            [
                'UAT-S4-005', 'S4', 'Budget variance with targets',
                'S4-BUD-001',
                'Budget lines seeded for current period',
                'GET /s4/api/v1/bi/reports/budget_variance?fiscal_period_id=current',
                'Returns actual and budget net income',
                'pending',
            ],
            [
                'UAT-S3-004', 'S3', 'Employee consumption close posts deduction',
                'S3-CON-001',
                'S2 healthy; consumption period with total_amount',
                "1. Create consumption period\n2. Add employee meal order\n3. Close period",
                'S2 deduction applied; outbox event emitted',
                'pending',
            ],
            [
                'UAT-S3-005', 'S3', 'Group booking bulk check-in and check-out',
                'S3-GRP-001',
                'Two available rooms; JWT with group booking permissions',
                "1. Create group booking with rooming list\n2. Bulk check-in\n3. Settle folios\n4. Bulk check-out",
                'Group status checked_out; all reservations completed',
                'pending',
            ],
            [
                'UAT-S2-004', 'S2', 'Severance calculation posts S4 accrual journal',
                'S2-SEV-001',
                'Employee with hire_date exists',
                'POST /s2/api/v1/employees/{id}/severance/calculate',
                'Severance calculated; DR 5005 / CR 2100 journal in S4',
                'pending',
            ],
            [
                'UAT-S4-006', 'S4', 'Fiscal period close and lock',
                'S4-FP-001',
                'Open fiscal period available',
                "1. POST /fiscal-periods/{id}/close\n2. POST /fiscal-periods/{id}/lock",
                'Period status becomes locked; posting blocked',
                'pending',
            ],
            [
                'UAT-S3-006', 'S3', 'Purchase order tiered approval',
                'S3-PO-001',
                'PO total >= 50,000 ETB; super_admin JWT',
                "1. Create PO\n2. Submit\n3. Approve through dept/finance/GM steps",
                'PO status approved before goods receipt',
                'pending',
            ],
            [
                'UAT-S3-007', 'S3', 'Folio charge includes SC and VAT',
                'S3-TAX-001',
                'Guest folio open',
                'POST folio charge with subtotal amount',
                'Journal splits revenue, SC (4003), and VAT (2300)',
                'pending',
            ],
            [
                'UAT-S2-005', 'S2', 'Severance payout settles accrual',
                'S2-SEV-002',
                'Calculated severance exists',
                'POST /severance-calculations/{id}/pay',
                'Status paid; DR 2100 / CR 1001 journal in S4',
                'pending',
            ],
            [
                'UAT-S2-006', 'S2', 'Employee created provisions S1 user',
                'S2-HR-001',
                'S1 events:consume-s2 running',
                'POST /employees with unique name; poll S1 /users',
                'S1 user with matching employee_id',
                'pending',
            ],
            [
                'UAT-S4-007', 'S4', 'Executive dashboard KPIs',
                'S4-BI-001',
                'Posted journals in current period',
                'GET /dashboards/executive?fiscal_period_id=current',
                'Returns revenue, expenses, and net income summary',
                'pending',
            ],
            [
                'UAT-S4-008', 'S4', 'Receivable settlement',
                'S4-AR-001',
                'Open AR from guest folio charges',
                'POST /receivables/{id}/settle',
                'Receivable balance reduced; cash journal posted',
                'pending',
            ],
            [
                'UAT-S4-009', 'S4', 'Payable settlement',
                'S4-AP-001',
                'Open AP from goods received PO',
                'POST /payables/{id}/settle',
                'Payable balance reduced; cash journal posted',
                'pending',
            ],
        ];

        foreach ($scenarios as [$key, $system, $title, $requirement, $preconditions, $steps, $expected, $status]) {
            $existing = UatScenario::query()->where('scenario_key', $key)->first();

            $attributes = [
                'system' => $system,
                'title' => $title,
                'requirement_key' => $requirement,
                'preconditions' => $preconditions,
                'steps' => $steps,
                'expected_outcome' => $expected,
            ];

            if ($existing === null) {
                UatScenario::query()->create([
                    'scenario_key' => $key,
                    ...$attributes,
                    'status' => $status,
                ]);

                continue;
            }

            $existing->update($attributes);
        }
    }
}
