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
        $tierHint = self::tierLabel($po);

        $steps = [
            ['key' => 'pending_dept_head', 'label' => 'Department head', 'hint' => $tier === 1 ? $tierHint : 'Required for all POs'],
        ];

        if ($tier >= 2) {
            $steps[] = ['key' => 'pending_finance', 'label' => 'Finance manager', 'hint' => $tier === 2 ? $tierHint : 'Tier 2+ approval'];
        }

        if ($tier >= 3) {
            $steps[] = ['key' => 'pending_gm', 'label' => 'General manager', 'hint' => $tierHint];
        }

        $steps[] = ['key' => 'approved', 'label' => 'Approved', 'hint' => 'Ready for goods receipt'];

        return $steps;
    }

    public static function tierLabel(array $po): string
    {
        $tier = (int) ($po['approval_tier'] ?? 1);

        return match ($tier) {
            1 => 'Under ETB 5,000 — dept head only',
            2 => 'ETB 5,000–50,000 — dept head + finance',
            3 => 'ETB 50,000+ — dept head + finance + GM',
            default => "Tier {$tier}",
        };
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

    /**
     * Mirrors S3 PurchaseOrderService approval-role rules for portal UI gating.
     *
     * @param  list<string>  $roleSlugs
     */
    public static function userCanApproveCurrentStep(array $po, array $roleSlugs): bool
    {
        $status = (string) ($po['status'] ?? '');

        if (! in_array($status, ['pending_dept_head', 'pending_finance', 'pending_gm'], true)) {
            return false;
        }

        if (in_array('super_admin', $roleSlugs, true)) {
            return true;
        }

        return match ($status) {
            'pending_dept_head' => self::hasAnyRole($roleSlugs, ['department_head', 'general_manager']),
            'pending_finance' => self::hasAnyRole($roleSlugs, ['finance_manager']),
            'pending_gm' => self::hasAnyRole($roleSlugs, ['general_manager']),
            default => false,
        };
    }

    /**
     * @param  list<string>  $roleSlugs
     * @param  list<string>  $allowed
     */
    private static function hasAnyRole(array $roleSlugs, array $allowed): bool
    {
        foreach ($allowed as $role) {
            if (in_array($role, $roleSlugs, true)) {
                return true;
            }
        }

        return false;
    }
}
