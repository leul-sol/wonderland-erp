<?php

namespace App\Services;

use App\Models\Department;
use InvalidArgumentException;

class DepartmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Department
    {
        return Department::query()->create([
            'code' => strtoupper((string) $data['code']),
            'name' => $data['name'],
            'head_employee_id' => $data['head_employee_id'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Department $department, array $data): Department
    {
        $department->fill([
            'code' => isset($data['code']) ? strtoupper((string) $data['code']) : $department->code,
            'name' => $data['name'] ?? $department->name,
            'head_employee_id' => $data['head_employee_id'] ?? $department->head_employee_id,
        ])->save();

        return $department->fresh();
    }

    public function delete(Department $department): void
    {
        if ($department->employees()->exists()) {
            throw new InvalidArgumentException('Cannot delete a department with assigned employees.');
        }

        $department->delete();
    }
}
