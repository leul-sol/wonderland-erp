<?php

namespace App\Services;

use App\Models\RtmEntry;
use Illuminate\Support\Collection;

class RtmService
{
    /**
     * @return array{data: Collection<int, RtmEntry>, meta: array<string, mixed>}
     */
    public function list(?string $system = null, ?string $status = null): array
    {
        $query = RtmEntry::query()->orderBy('system')->orderBy('requirement_key');

        if ($system !== null && $system !== '') {
            $query->where('system', $system);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $entries = $query->get();

        return [
            'data' => $entries,
            'meta' => $this->summary($entries),
        ];
    }

    public function update(RtmEntry $entry, array $data, int $userId): RtmEntry
    {
        $payload = array_intersect_key($data, array_flip([
            'status', 'priority', 'notes', 'description',
        ]));

        if (($payload['status'] ?? null) === 'verified') {
            $payload['verified_at'] = now();
        }

        $payload['updated_by'] = $userId;
        $entry->update($payload);

        return $entry->fresh();
    }

    /**
     * @param  Collection<int, RtmEntry>  $entries
     * @return array<string, mixed>
     */
    private function summary(Collection $entries): array
    {
        $total = $entries->count();
        $implemented = $entries->whereIn('status', ['implemented', 'verified'])->count();
        $verified = $entries->where('status', 'verified')->count();

        $byStatus = $entries->groupBy('status')->map->count();
        $bySystem = $entries->groupBy('system')->map->count();

        return [
            'total' => $total,
            'implemented' => $implemented,
            'verified' => $verified,
            'coverage_percent' => $total > 0 ? number_format(($implemented / $total) * 100, 1, '.', '') : '0.0',
            'by_status' => $byStatus->all(),
            'by_system' => $bySystem->all(),
        ];
    }
}
