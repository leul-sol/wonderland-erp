<?php

namespace App\Support;

use Carbon\Carbon;

class SubledgerAging
{
    /**
     * @return array{bucket: string, days_overdue: int}
     */
    public static function classify(?Carbon $dueDate, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        if ($dueDate === null) {
            return ['bucket' => 'current', 'days_overdue' => 0];
        }

        $daysOverdue = (int) $dueDate->copy()->startOfDay()->diffInDays($asOf, false);

        if ($daysOverdue <= 0) {
            return ['bucket' => 'current', 'days_overdue' => 0];
        }

        if ($daysOverdue <= 30) {
            return ['bucket' => '1_30', 'days_overdue' => $daysOverdue];
        }

        if ($daysOverdue <= 60) {
            return ['bucket' => '31_60', 'days_overdue' => $daysOverdue];
        }

        if ($daysOverdue <= 90) {
            return ['bucket' => '61_90', 'days_overdue' => $daysOverdue];
        }

        return ['bucket' => '90_plus', 'days_overdue' => $daysOverdue];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public static function applyBucketFilter($query, string $bucket, string $dueDateColumn = 'due_date', ?Carbon $asOf = null): void
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $validBuckets = array_keys(self::emptyBucketTotals());

        if (! in_array($bucket, $validBuckets, true)) {
            return;
        }

        if ($bucket === 'current') {
            $query->where(function ($inner) use ($dueDateColumn, $asOf) {
                $inner->whereNull($dueDateColumn)
                    ->orWhereDate($dueDateColumn, '>=', $asOf->toDateString());
            });

            return;
        }

        $ranges = [
            '1_30' => [1, 30],
            '31_60' => [31, 60],
            '61_90' => [61, 90],
        ];

        if (isset($ranges[$bucket])) {
            [$minDays, $maxDays] = $ranges[$bucket];
            $oldest = $asOf->copy()->subDays($maxDays)->toDateString();
            $newest = $asOf->copy()->subDays($minDays)->toDateString();
            $query->whereDate($dueDateColumn, '>=', $oldest)
                ->whereDate($dueDateColumn, '<=', $newest);

            return;
        }

        $query->whereDate($dueDateColumn, '<', $asOf->copy()->subDays(90)->toDateString());
    }

    /**
     * @return array<string, string>
     */
    public static function emptyBucketTotals(): array
    {
        return [
            'current' => '0.00',
            '1_30' => '0.00',
            '31_60' => '0.00',
            '61_90' => '0.00',
            '90_plus' => '0.00',
        ];
    }

    /**
     * @param  iterable<mixed>  $items
     * @param  callable(mixed): float  $balanceResolver
     * @param  callable(mixed): ?Carbon  $dueDateResolver
     * @return array<string, string>
     */
    public static function bucketTotals(iterable $items, callable $balanceResolver, callable $dueDateResolver, ?Carbon $asOf = null): array
    {
        $totals = array_fill_keys(array_keys(self::emptyBucketTotals()), 0.0);

        foreach ($items as $item) {
            $bucket = self::classify($dueDateResolver($item), $asOf)['bucket'];
            $totals[$bucket] += $balanceResolver($item);
        }

        return array_map(
            fn (float $amount) => number_format($amount, 2, '.', ''),
            $totals
        );
    }
}
