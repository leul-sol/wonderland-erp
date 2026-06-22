<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;
use InvalidArgumentException;

class AttendanceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): AttendanceRecord
    {
        $employee = Employee::query()->findOrFail($data['employee_id']);

        if ($employee->status !== 'active') {
            throw new InvalidArgumentException('Attendance can only be recorded for active employees.');
        }

        $workDate = Carbon::parse($data['work_date'])->toDateString();
        $hoursWorked = isset($data['hours_worked'])
            ? (float) $data['hours_worked']
            : $this->calculateHours($data['check_in'] ?? null, $data['check_out'] ?? null);

        return AttendanceRecord::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'work_date' => $workDate],
            [
                'check_in' => $data['check_in'] ?? null,
                'check_out' => $data['check_out'] ?? null,
                'hours_worked' => $hoursWorked,
                'status' => $data['status'] ?? 'present',
                'notes' => $data['notes'] ?? null,
            ]
        )->load('employee.department');
    }

    private function calculateHours(?string $checkIn, ?string $checkOut): float
    {
        if ($checkIn === null || $checkOut === null) {
            return 0.0;
        }

        $start = Carbon::createFromFormat('H:i', substr($checkIn, 0, 5));
        $end = Carbon::createFromFormat('H:i', substr($checkOut, 0, 5));

        if ($end->lessThanOrEqualTo($start)) {
            return 0.0;
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }
}
