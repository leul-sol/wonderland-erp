<?php

namespace App\Services;

use App\Models\DisciplinaryRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DisciplinaryService
{
    public function __construct(
        private readonly OffboardingService $offboarding,
        private readonly EmployeeService $employees,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Employee $employee, array $data, int $issuedBy): DisciplinaryRecord
    {
        if ($employee->status === 'archived') {
            throw new InvalidArgumentException('Cannot record disciplinary action for archived employee.');
        }

        return DB::transaction(function () use ($employee, $data, $issuedBy) {
            $record = DisciplinaryRecord::query()->create([
                'employee_id' => $employee->id,
                'action_type' => $data['action_type'],
                'reason' => $data['reason'],
                'effective_date' => $data['effective_date'],
                'suspension_days' => $data['suspension_days'] ?? null,
                'issued_by' => $issuedBy,
            ]);

            match ($data['action_type']) {
                'suspension' => $this->applySuspension($employee, $data),
                'termination' => $this->startOffboarding($employee, 'termination', false),
                'immediate_dismissal' => $this->startOffboarding($employee, 'termination', true),
                default => null,
            };

            return $record->fresh('employee');
        });
    }

    public function releaseExpiredSuspensions(): int
    {
        $released = 0;
        $employees = Employee::query()->where('status', 'suspended')->get();

        foreach ($employees as $employee) {
            $latest = DisciplinaryRecord::query()
                ->where('employee_id', $employee->id)
                ->where('action_type', 'suspension')
                ->orderByDesc('effective_date')
                ->first();

            if ($latest === null || $latest->suspension_days === null) {
                continue;
            }

            $endDate = Carbon::parse($latest->effective_date)->addDays((int) $latest->suspension_days);

            if (now()->greaterThanOrEqualTo($endDate)) {
                $employee->update(['status' => 'active']);
                $released++;
            }
        }

        return $released;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applySuspension(Employee $employee, array $data): void
    {
        if (empty($data['suspension_days'])) {
            throw new InvalidArgumentException('suspension_days is required for suspension actions.');
        }

        $employee->update(['status' => 'suspended']);
    }

    private function startOffboarding(Employee $employee, string $reason, bool $skipSeverance): void
    {
        if (\App\Models\OffboardingRecord::query()->where('employee_id', $employee->id)->exists()) {
            return;
        }

        $record = $this->offboarding->initiate($employee, [
            'reason' => $reason,
            'last_working_day' => now()->toDateString(),
            'initiated_date' => now()->toDateString(),
            'notes' => $skipSeverance ? 'immediate_dismissal — severance skipped' : null,
            'calculate_severance' => ! $skipSeverance,
        ]);

        if ($skipSeverance) {
            $record->update(['severance_amount' => 0]);
        }
    }
}
