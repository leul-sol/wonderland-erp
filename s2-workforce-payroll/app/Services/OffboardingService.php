<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OffboardingRecord;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OffboardingService
{
    public function __construct(
        private readonly SeveranceService $severance,
        private readonly EmployeeService $employees,
        private readonly AssetService $assets,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function initiate(Employee $employee, array $data): OffboardingRecord
    {
        if ($employee->status === 'archived') {
            throw new InvalidArgumentException('Employee is already archived.');
        }

        if (OffboardingRecord::query()->where('employee_id', $employee->id)->exists()) {
            throw new InvalidArgumentException('Offboarding record already exists for this employee.');
        }

        return DB::transaction(function () use ($employee, $data) {
            $record = OffboardingRecord::query()->create([
                'employee_id' => $employee->id,
                'initiated_date' => $data['initiated_date'] ?? now()->toDateString(),
                'reason' => $data['reason'],
                'last_working_day' => $data['last_working_day'],
                'clearance_status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['calculate_severance'])) {
                $calculation = $this->severance->calculate($employee, $record);
                $record->update(['severance_amount' => $calculation->amount]);
            }

            if (! empty($data['archive_now'])) {
                $this->employees->archive($employee, 'offboarding:'.$data['reason']);
            }

            return $record->fresh('employee');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OffboardingRecord $record, array $data): OffboardingRecord
    {
        $newStatus = $data['clearance_status'] ?? $record->clearance_status;

        if ($newStatus === 'completed' && $this->assets->hasOutstandingAssets($record->employee_id)) {
            throw new InvalidArgumentException('Cannot complete clearance while outstanding assets remain unreturned.');
        }

        $record->fill([
            'clearance_status' => $newStatus,
            'notes' => $data['notes'] ?? $record->notes,
            'severance_amount' => $data['severance_amount'] ?? $record->severance_amount,
        ])->save();

        $record->loadMissing('employee');

        if (
            $newStatus === 'completed'
            && $record->employee !== null
            && $record->employee->status !== 'archived'
        ) {
            $this->employees->archive($record->employee, 'offboarding completed');
        }

        return $record->fresh('employee');
    }
}
