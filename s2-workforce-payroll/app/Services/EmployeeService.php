<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Services\Leave\LeaveBalanceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeService
{
    public function __construct(
        private readonly OutboxService $outbox,
        private readonly LeaveBalanceService $leaveBalances,
    ) {
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
                'position_id' => $data['position_id'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'base_salary' => $data['base_salary'],
                'pension_category' => $data['pension_category'] ?? 'covered',
                'default_role' => $data['default_role'] ?? 'report_viewer',
                'status' => 'active',
                'hire_date' => $data['hire_date'] ?? now()->toDateString(),
            ]);

            $this->leaveBalances->initialiseForEmployee($employee);

            $this->outbox->enqueue(config('events.channels.employee_created'), [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'default_role' => $employee->default_role,
                'status' => $employee->status,
            ]);

            return $employee->load(['department', 'position']);
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
                'position_id' => $data['position_id'] ?? $employee->position_id,
                'job_title' => $data['job_title'] ?? $employee->job_title,
                'base_salary' => $data['base_salary'] ?? $employee->base_salary,
                'pension_category' => $data['pension_category'] ?? $employee->pension_category,
                'default_role' => $data['default_role'] ?? $employee->default_role,
            ])->save();

            $this->outbox->enqueue(config('events.channels.employee_updated'), [
                'employee_id' => $employee->id,
                'full_name' => $employee->full_name,
                'department_id' => $employee->department_id,
                'status' => $employee->status,
            ]);

            return $employee->fresh(['department', 'position']);
        });
    }

    public function archive(Employee $employee, ?string $reason = null): Employee
    {
        if ($employee->status === 'archived') {
            throw new InvalidArgumentException('Employee is already archived.');
        }

        return DB::transaction(function () use ($employee, $reason) {
            $employee->update([
                'status' => 'archived',
                'archived_at' => now(),
            ]);

            $this->leaveBalances->closeForEmployee($employee);

            $this->outbox->enqueue(config('events.channels.employee_archived'), [
                'employee_id' => $employee->id,
                'archived_at' => $employee->archived_at?->toIso8601String(),
                'reason' => $reason,
            ]);

            return $employee->fresh(['department', 'position']);
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
