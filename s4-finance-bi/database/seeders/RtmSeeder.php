<?php

namespace Database\Seeders;

use App\Models\RtmEntry;
use Illuminate\Database\Seeder;

class RtmSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['S1', 'identity', 'S1-AUTH-001', 'User login with JWT', 'S1 §4.1', 'implemented', 'critical', 'POST /auth/login issues access + refresh tokens'],
            ['S1', 'identity', 'S1-AUTH-002', 'Service key token verify', 'S1 §5.2', 'implemented', 'critical', 'S2/S3/S4 call POST /auth/verify'],
            ['S1', 'identity', 'S1-RBAC-001', 'Role-based permissions', 'S1 §6.1', 'implemented', 'high', '79 permissions seeded across S1–S4'],
            ['S2', 'workforce', 'S2-HR-001', 'Employee CRUD + S1 user provisioning', 'S2 §4.2', 'implemented', 'high', 'Outbox employee.created → S1'],
            ['S2', 'workforce', 'S2-PAY-001', 'Payroll run approve → S4 journal', 'S2 §5.3', 'implemented', 'critical', 'Consolidated GL posting; staff meal deductions reduce net pay'],
            ['S2', 'workforce', 'S2-LEAVE-001', 'Leave management', 'S2 §4.4', 'implemented', 'medium', 'Dept-scoped approval for department_head via dept_scope'],
            ['S2', 'workforce', 'S2-ATT-001', 'Attendance records', 'S2 §4.5', 'implemented', 'medium', 'Daily attendance for active employees'],
            ['S2', 'workforce', 'S2-DED-001', 'Staff meal deductions', 'S2 §5.4', 'implemented', 'medium', 'S3 posts deductions via service key'],
            ['S2', 'workforce', 'S2-SEV-001', 'Severance calculation', 'S2 §5.5', 'implemented', 'low', 'Emits wh.events.s2.severance.calculated'],
            ['S2', 'workforce', 'S2-SEV-002', 'Severance payout', 'S2 §5.6', 'implemented', 'low', 'POST /severance-calculations/{id}/pay → DR 2100 / CR cash'],
            ['S3', 'hospitality', 'S3-HTL-001', 'Reservation check-in / check-out', 'S3 §4.1', 'implemented', 'high', 'Room assignment + folio lifecycle'],
            ['S3', 'hospitality', 'S3-FOL-001', 'Folio charge → S4 journal', 'S3 §4.3', 'implemented', 'critical', 'DR 1100 / CR revenue accounts'],
            ['S3', 'hospitality', 'S3-FB-001', 'F&B order with COGS posting', 'S3 §5.2', 'implemented', 'high', 'DR 5003 / CR 1200 on finalize'],
            ['S3', 'hospitality', 'S3-INV-001', 'Purchase order goods received', 'S3 §5.1', 'implemented', 'high', 'DR 1200 / CR 2001'],
            ['S3', 'hospitality', 'S3-PO-001', 'PO tiered approval workflow', 'S3 §5.3', 'implemented', 'high', 'pending_dept_head → finance → GM by amount'],
            ['S3', 'hospitality', 'S3-TAX-001', 'Service charge and VAT on charges', 'S3 §5.4', 'implemented', 'high', '10% SC + 15% VAT on folio and F&B'],
            ['S3', 'hospitality', 'S3-CON-001', 'Employee consumption period close', 'S3 §5.4', 'implemented', 'medium', 'Closes period → S2 deduction + outbox'],
            ['S3', 'hospitality', 'S3-GRP-001', 'Group booking bulk check-in/out', 'S3 §4.2', 'implemented', 'medium', 'POST /group-bookings with rooming list'],
            ['S4', 'finance', 'S4-GL-001', 'Balanced journal posting API', 'S4 §4.1', 'verified', 'critical', 'Idempotent POST /journal-entries'],
            ['S4', 'finance', 'S4-GL-002', 'Manual journal approve/post workflow', 'S4 §4.2', 'implemented', 'high', 'draft → approved → posted'],
            ['S4', 'finance', 'S4-AR-001', 'Receivables subledger sync', 'S4 §4.4', 'verified', 'high', 'Auto from posted AR lines'],
            ['S4', 'finance', 'S4-AP-001', 'Payables subledger sync', 'S4 §4.4', 'verified', 'medium', 'Auto from posted AP lines'],
            ['S4', 'finance', 'S4-FP-001', 'Fiscal period close and lock', 'S4 §4.3', 'implemented', 'high', 'Blocks posting when closed'],
            ['S4', 'finance', 'S4-RPT-001', 'Trial balance and P&L reports', 'S4 §5.1', 'implemented', 'high', 'Posted journals only'],
            ['S4', 'finance', 'S4-BUD-001', 'Budget lines and variance report', 'S4 §5.2', 'implemented', 'medium', 'Budget targets per fiscal period'],
            ['S4', 'bi', 'S4-BI-001', 'Executive and operations dashboards', 'S4 §6.1', 'verified', 'medium', 'S2/S3 cached reads'],
            ['S4', 'bi', 'S4-BI-002', 'CSV and PDF report export', 'S4 §6.2', 'implemented', 'medium', 'POST /bi/exports'],
            ['S4', 'bi', 'S4-BI-003', 'RTM and UAT tracking', 'S4 §6.3', 'implemented', 'medium', 'GET/PATCH /bi/rtm and /bi/uat'],
            ['S4', 'bi', 'S4-BI-004', 'Full 24-report catalog', 'S4 §6.2', 'implemented', 'medium', 'GET /bi/reports and /bi/reports/{slug}'],
            ['S4', 'platform', 'S4-EVT-001', 'Cross-system event consumer', 'S0 §5.2', 'implemented', 'high', 'php artisan events:consume logs + cache bust'],
        ];

        foreach ($entries as [$system, $domain, $key, $title, $spec, $status, $priority, $description]) {
            RtmEntry::query()->updateOrCreate(
                ['requirement_key' => $key],
                [
                    'system' => $system,
                    'domain' => $domain,
                    'title' => $title,
                    'spec_section' => $spec,
                    'status' => $status,
                    'priority' => $priority,
                    'description' => $description,
                    'verified_at' => $status === 'verified' ? now() : null,
                ]
            );
        }
    }
}
