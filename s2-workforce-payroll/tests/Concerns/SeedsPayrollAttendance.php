<?php

namespace Tests\Concerns;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

trait SeedsPayrollAttendance
{
    protected function seedWeekdayAttendance(int $employeeId, string $periodStart, string $periodEnd): void
    {
        foreach (CarbonPeriod::create(Carbon::parse($periodStart), Carbon::parse($periodEnd)) as $day) {
            if (! $day->isWeekday()) {
                continue;
            }

            AttendanceRecord::query()->create([
                'employee_id' => $employeeId,
                'work_date' => $day->toDateString(),
                'check_in' => '08:00',
                'check_out' => '17:00',
                'hours_worked' => 9,
                'status' => 'present',
            ]);
        }
    }
}
