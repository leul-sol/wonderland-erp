<?php

namespace App\Support;

class PayrollRunApprovalSteps
{
    /**
     * @return list<array{key: string, label: string, hint: string}>
     */
    public static function steps(): array
    {
        return [
            ['key' => 'draft', 'label' => 'Draft', 'hint' => 'Review payroll lines'],
            ['key' => 'pending_approval', 'label' => 'Pending approval', 'hint' => 'Awaiting sign-off'],
            ['key' => 'approved', 'label' => 'Approved', 'hint' => 'Posted to finance'],
            ['key' => 'locked', 'label' => 'Locked', 'hint' => 'Immutable — payslips final'],
        ];
    }

    public static function currentStepKey(array $run): string
    {
        $status = (string) ($run['status'] ?? '');

        if ($status === 'locked') {
            return 'locked';
        }

        if ($status === 'approved') {
            return 'approved';
        }

        if ($status === 'pending_approval') {
            return 'pending_approval';
        }

        return 'draft';
    }
}
