<?php

namespace App\Services;

use App\Models\UatScenario;
use Illuminate\Support\Collection;

class UatService
{
    /**
     * @return array{data: Collection<int, UatScenario>, meta: array<string, mixed>}
     */
    public function list(?string $system = null, ?string $status = null): array
    {
        $query = UatScenario::query()->orderBy('scenario_key');

        if ($system !== null && $system !== '') {
            $query->where('system', $system);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $scenarios = $query->get();

        return [
            'data' => $scenarios,
            'meta' => $this->summary($scenarios),
        ];
    }

    public function recordResult(UatScenario $scenario, string $status, ?string $notes, int $userId): UatScenario
    {
        $scenario->update([
            'status' => $status,
            'notes' => $notes,
            'executed_by' => $userId,
            'executed_at' => now(),
        ]);

        if (in_array($status, ['passed', 'failed'], true) && $scenario->requirement_key !== null) {
            $this->syncLinkedRequirement($scenario->requirement_key, $status);
        }

        return $scenario->fresh();
    }

    private function syncLinkedRequirement(string $requirementKey, string $uatStatus): void
    {
        $entry = \App\Models\RtmEntry::query()->where('requirement_key', $requirementKey)->first();

        if ($entry === null) {
            return;
        }

        if ($uatStatus === 'passed' && $entry->status === 'implemented') {
            $entry->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);
        }
    }

    /**
     * @param  Collection<int, UatScenario>  $scenarios
     * @return array<string, mixed>
     */
    private function summary(Collection $scenarios): array
    {
        $total = $scenarios->count();
        $passed = $scenarios->where('status', 'passed')->count();
        $failed = $scenarios->where('status', 'failed')->count();
        $pending = $scenarios->where('status', 'pending')->count();
        $executed = $total - $pending;

        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'pending' => $pending,
            'pass_rate_percent' => $executed > 0 ? number_format(($passed / $executed) * 100, 1, '.', '') : '0.0',
            'by_status' => $scenarios->groupBy('status')->map->count()->all(),
            'by_system' => $scenarios->groupBy('system')->map->count()->all(),
        ];
    }
}
