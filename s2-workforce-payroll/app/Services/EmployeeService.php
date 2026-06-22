<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeService
{
    public function __construct(private readonly OutboxService $outbox)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::query()->create([
                'employee_number' => $this->nextEmployeeNumber(),
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'base_salary' => $data['base_salary'],
                'default_role' => $data['default_role'] ?? 'report_viewer',
                'status' => 'active',
                'hire_date' => $data['hire_date'] ?? now()->toDateString(),
            ]);

            $this->outbox->enqueue(config('events.channels.employee_created'), [
                'employee_id' => $employee->id,
                'full_name' => $employee->full_name,
                'department_id' => $employee->department_id,
                'default_role' => $employee->default_role,
            ]);

            return $employee->load('department');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        if ($employee->status === 'archived') {
            throw new InvalidArgumentException('Cannot update an archived employee.');
        }

        return DB::transaction(function () use ($employee, $data) {
            $employee->fill([
                'full_name' => $data['full_name'] ?? $employee->full_name,
                'email' => $data['email'] ?? $employee->email,
                'department_id' => $data['department_id'] ?? $employee->department_id,
                'job_title' => $data['job_title'] ?? $employee->job_title,
                'base_salary' => $data['base_salary'] ?? $employee->base_salary,
                'default_role' => $data['default_role'] ?? $employee->default_role,
            ])->save();

            $this->outbox->enqueue(config('events.channels.employee_updated'), [
                'employee_id' => $employee->id,
                'full_name' => $employee->full_name,
                'department_id' => $employee->department_id,
            ]);

            return $employee->fresh('department');
        });
    }

    public function archive(Employee $employee, ?string $reason = null): Employee
    {
        if ($employee->status === 'archived') {
            throw new InvalidArgumentException('Employee is already archived.');
        }

        return DB::transaction(function () use ($employee, $reason) {
            $employee->update(['status' => 'archived']);

            $this->outbox->enqueue(config('events.channels.employee_archived'), [
                'employee_id' => $employee->id,
                'reason' => $reason,
            ]);

            return $employee->fresh('department');
        });
    }

    private function nextEmployeeNumber(): string
    {
        $last = Employee::query()->orderByDesc('id')->value('employee_number');
        $sequence = 1;

        if (is_string($last) && preg_match('/EMP-(\d+)/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'EMP-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
