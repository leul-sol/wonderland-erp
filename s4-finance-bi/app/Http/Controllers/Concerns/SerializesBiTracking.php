<?php

namespace App\Http\Controllers\Concerns;

use App\Models\RtmEntry;
use App\Models\UatScenario;

trait SerializesBiTracking
{
    protected function rtmPayload(RtmEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'requirement_key' => $entry->requirement_key,
            'system' => $entry->system,
            'domain' => $entry->domain,
            'title' => $entry->title,
            'description' => $entry->description,
            'spec_section' => $entry->spec_section,
            'status' => $entry->status,
            'priority' => $entry->priority,
            'notes' => $entry->notes,
            'updated_by' => $entry->updated_by,
            'verified_at' => $entry->verified_at?->toIso8601String(),
            'updated_at' => $entry->updated_at?->toIso8601String(),
        ];
    }

    protected function uatPayload(UatScenario $scenario): array
    {
        return [
            'id' => $scenario->id,
            'scenario_key' => $scenario->scenario_key,
            'system' => $scenario->system,
            'title' => $scenario->title,
            'requirement_key' => $scenario->requirement_key,
            'preconditions' => $scenario->preconditions,
            'steps' => $scenario->steps,
            'expected_outcome' => $scenario->expected_outcome,
            'status' => $scenario->status,
            'executed_by' => $scenario->executed_by,
            'executed_at' => $scenario->executed_at?->toIso8601String(),
            'notes' => $scenario->notes,
            'updated_at' => $scenario->updated_at?->toIso8601String(),
        ];
    }
}
