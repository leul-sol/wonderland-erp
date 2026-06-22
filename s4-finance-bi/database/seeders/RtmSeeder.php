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
            ['S1', 'identity', 'S1-RBAC-001', 'Role-based permissions', 'S1 §6.1', 'implemented', 'high', '53 permissions seeded across S1–S4'],
            ['S2', 'workforce', 'S2-HR-001', 'Employee CRUD + S1 user provisioning', 'S2 §4.2', 'implemented', 'high', 'Outbox employee.created → S1'],
            ['S2', 'workforce', 'S2-PAY-001', 'Payroll run approve → S4 journal', 'S2 §5.3', 'implemented', 'critical', 'Consolidated GL posting on approve'],
            ['S3', 'hospitality', 'S3-HTL-001', 'Reservation check-in / check-out', 'S3 §4.1', 'implemented', 'high', 'Room assignment + folio lifecycle'],
            ['S3', 'hospitality', 'S3-FOL-001', 'Folio charge → S4 journal', 'S3 §4.3', 'implemented', 'critical', 'DR 1100 / CR revenue accounts'],
            ['S3', 'hospitality', 'S3-FB-001', 'F&B order with COGS posting', 'S3 §5.2', 'implemented', 'high', 'DR 5003 / CR 1200 on finalize'],
            ['S3', 'hospitality', 'S3-INV-001', 'Purchase order goods received', 'S3 §5.1', 'implemented', 'high', 'DR 1200 / CR 2001'],
            ['S4', 'finance', 'S4-GL-001', 'Balanced journal posting API', 'S4 §4.1', 'verified', 'critical', 'Idempotent POST /journal-entries'],
            ['S4', 'finance', 'S4-GL-002', 'Manual journal approve/post workflow', 'S4 §4.2', 'implemented', 'high', 'draft → approved → posted'],
            ['S4', 'finance', 'S4-AR-001', 'Receivables subledger sync', 'S4 §4.4', 'implemented', 'high', 'Auto from posted AR lines'],
            ['S4', 'finance', 'S4-AP-001', 'Payables subledger sync', 'S4 §4.4', 'implemented', 'medium', 'Auto from posted AP lines'],
            ['S4', 'finance', 'S4-FP-001', 'Fiscal period close and lock', 'S4 §4.3', 'implemented', 'high', 'Blocks posting when closed'],
            ['S4', 'finance', 'S4-RPT-001', 'Trial balance and P&L reports', 'S4 §5.1', 'implemented', 'high', 'Posted journals only'],
            ['S4', 'bi', 'S4-BI-001', 'Executive and operations dashboards', 'S4 §6.1', 'implemented', 'medium', 'S2/S3 cached reads'],
            ['S4', 'bi', 'S4-BI-002', 'CSV report export', 'S4 §6.2', 'implemented', 'medium', 'POST /bi/exports'],
            ['S4', 'bi', 'S4-BI-003', 'RTM and UAT tracking', 'S4 §6.3', 'implemented', 'medium', 'GET/PATCH /bi/rtm and /bi/uat'],
            ['S4', 'bi', 'S4-BI-004', 'PDF export and full report catalog', 'S4 §6.2', 'planned', 'low', '24+ operational reports'],
            ['S2', 'workforce', 'S2-LEAVE-001', 'Leave management', 'S2 §4.4', 'planned', 'medium', 'Not yet implemented'],
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
