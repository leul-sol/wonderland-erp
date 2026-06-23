<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OvertimeRecord;
use InvalidArgumentException;

class OvertimeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Employee $employee, array $data): OvertimeRecord
    {
        if ($employee->status !== 'active') {
            throw new InvalidArgumentException('Overtime can only be recorded for active employees.');
        }

        return OvertimeRecord::query()->create([
            'employee_id' => $employee->id,
            'work_date' => $data['work_date'],
            'hours' => $data['hours'],
            'category' => $data['category'],
            'status' => 'pending',
        ])->load('employee');
    }

    public function approve(OvertimeRecord $record): OvertimeRecord
    {
        if ($record->status !== 'pending') {
            throw new InvalidArgumentException('Only pending overtime records can be approved.');
        }

        $record->update(['status' => 'approved']);

        return $record->fresh('employee');
    }
}
