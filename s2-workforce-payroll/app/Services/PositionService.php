<?php

namespace App\Services;

use App\Models\Position;
use InvalidArgumentException;

class PositionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Position
    {
        return Position::query()->create([
            'title' => $data['title'],
            'department_id' => $data['department_id'],
            'grade' => $data['grade'] ?? null,
            'transport_allowance' => $data['transport_allowance'] ?? 0,
            'housing_allowance' => $data['housing_allowance'] ?? 0,
        ])->load('department');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Position $position, array $data): Position
    {
        $position->fill([
            'title' => $data['title'] ?? $position->title,
            'department_id' => $data['department_id'] ?? $position->department_id,
            'grade' => $data['grade'] ?? $position->grade,
            'transport_allowance' => $data['transport_allowance'] ?? $position->transport_allowance,
            'housing_allowance' => $data['housing_allowance'] ?? $position->housing_allowance,
        ])->save();

        return $position->fresh('department');
    }

    public function delete(Position $position): void
    {
        if ($position->employees()->exists()) {
            throw new InvalidArgumentException('Cannot delete a position assigned to employees.');
        }

        $position->delete();
    }
}
