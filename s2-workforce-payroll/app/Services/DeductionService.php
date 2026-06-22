<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDeduction;
use InvalidArgumentException;

class DeductionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(Employee $employee, array $data, string $idempotencyKey): EmployeeDeduction
    {
        if ($employee->status !== 'active') {
            throw new InvalidArgumentException('Deductions require an active employee.');
        }

        $existing = EmployeeDeduction::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            return $existing->load('employee');
        }

        $amount = round((float) $data['amount'], 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Deduction amount must be greater than zero.');
        }

        return EmployeeDeduction::query()->create([
            'employee_id' => $employee->id,
            'deduction_type' => $data['deduction_type'] ?? 'staff_meal',
            'amount' => $amount,
            'description' => $data['description'] ?? null,
            'source_reference' => $data['source_reference'] ?? null,
            'idempotency_key' => $idempotencyKey,
            'status' => 'applied',
        ])->load('employee');
    }
}
