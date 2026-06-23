<?php

namespace App\Services;

use App\Models\AssetType;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use InvalidArgumentException;

class AssetService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createType(array $data): AssetType
    {
        return AssetType::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateType(AssetType $assetType, array $data): AssetType
    {
        $assetType->fill([
            'name' => $data['name'] ?? $assetType->name,
            'description' => $data['description'] ?? $assetType->description,
        ])->save();

        return $assetType->fresh();
    }

    public function deleteType(AssetType $assetType): void
    {
        if ($assetType->employeeAssets()->whereNull('returned_date')->exists()) {
            throw new InvalidArgumentException('Cannot delete asset type with outstanding assignments.');
        }

        $assetType->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(Employee $employee, array $data): EmployeeAsset
    {
        if ($employee->status === 'archived') {
            throw new InvalidArgumentException('Cannot assign assets to archived employees.');
        }

        return EmployeeAsset::query()->create([
            'employee_id' => $employee->id,
            'asset_type_id' => $data['asset_type_id'],
            'serial_number' => $data['serial_number'] ?? null,
            'assigned_date' => $data['assigned_date'] ?? now()->toDateString(),
        ])->load(['employee', 'assetType']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function returnAsset(EmployeeAsset $asset, array $data): EmployeeAsset
    {
        if ($asset->returned_date !== null) {
            throw new InvalidArgumentException('Asset has already been returned.');
        }

        $asset->update([
            'returned_date' => $data['returned_date'] ?? now()->toDateString(),
            'condition_on_return' => $data['condition_on_return'] ?? null,
        ]);

        return $asset->fresh(['employee', 'assetType']);
    }

    public function hasOutstandingAssets(int $employeeId): bool
    {
        return EmployeeAsset::query()
            ->where('employee_id', $employeeId)
            ->whereNull('returned_date')
            ->exists();
    }
}
