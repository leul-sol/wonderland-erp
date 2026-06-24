<?php

namespace App\Support;

class JournalApprovalSteps
{
    public const GM_THRESHOLD = 50000.0;

    /**
     * @return list<array{key: string, label: string, hint: string}>
     */
    public static function steps(bool $requiresGm): array
    {
        $steps = [
            ['key' => 'draft', 'label' => 'Draft', 'hint' => 'Balanced manual entry'],
            ['key' => 'approved', 'label' => 'Finance approval', 'hint' => 'Accountant or finance manager'],
        ];

        if ($requiresGm) {
            $steps[] = ['key' => 'gm_approval', 'label' => 'GM approval', 'hint' => 'Required for large entries'];
        }

        $steps[] = ['key' => 'posted', 'label' => 'Posted', 'hint' => 'In general ledger'];

        return $steps;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    public static function requiresGm(array $entry): bool
    {
        return (float) ($entry['total_debit'] ?? 0) >= self::GM_THRESHOLD;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    public static function currentStepKey(array $entry): string
    {
        $status = (string) ($entry['status'] ?? 'draft');

        if ($status === 'posted') {
            return 'posted';
        }

        if ($status === 'approved') {
            if (self::requiresGm($entry) && empty($entry['second_approved_by'])) {
                return 'gm_approval';
            }

            return self::requiresGm($entry) ? 'gm_approval' : 'posted';
        }

        return 'draft';
    }
}
