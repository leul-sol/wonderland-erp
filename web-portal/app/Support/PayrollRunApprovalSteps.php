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
        ];
    }

    public static function currentStepKey(array $run): string
    {
        $status = (string) ($run['status'] ?? '');

        if (in_array($status, ['approved', 'locked'], true)) {
            return 'approved';
        }

        if ($status === 'pending_approval') {
            return 'pending_approval';
        }

        return 'draft';
    }
}
