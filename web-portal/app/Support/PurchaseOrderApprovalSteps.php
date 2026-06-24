<?php

namespace App\Support;

class PurchaseOrderApprovalSteps
{
    /**
     * @return list<array{key: string, label: string, hint: string}>
     */
    public static function forPo(array $po): array
    {
        $tier = (int) ($po['approval_tier'] ?? 1);

        $steps = [
            ['key' => 'pending_dept_head', 'label' => 'Department head', 'hint' => 'Tier 1 approval'],
        ];

        if ($tier >= 2) {
            $steps[] = ['key' => 'pending_finance', 'label' => 'Finance manager', 'hint' => 'Tier 2 approval'];
        }

        if ($tier >= 3) {
            $steps[] = ['key' => 'pending_gm', 'label' => 'General manager', 'hint' => 'Tier 3 approval'];
        }

        $steps[] = ['key' => 'approved', 'label' => 'Approved', 'hint' => 'Ready for goods receipt'];

        return $steps;
    }

    public static function currentStepKey(array $po): string
    {
        $status = (string) ($po['status'] ?? '');

        if ($status === 'approved' || $status === 'received') {
            return 'approved';
        }

        if (in_array($status, ['pending_dept_head', 'pending_finance', 'pending_gm'], true)) {
            return $status;
        }

        return 'draft';
    }
}
