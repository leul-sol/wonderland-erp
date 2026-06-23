<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OffboardingRecord;
use App\Models\SeveranceCalculation;
use App\Services\Leave\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SeveranceService
{
    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
        private readonly LeaveBalanceService $leaveBalances,
    ) {
    }

    public function calculate(Employee $employee, ?OffboardingRecord $offboarding = null): SeveranceCalculation
    {
        if ($employee->hire_date === null) {
            throw new InvalidArgumentException('Employee hire_date is required for severance calculation.');
        }

        return DB::transaction(function () use ($employee, $offboarding) {
            $hireDate = Carbon::parse($employee->hire_date);
            $completedYears = max(0, (int) floor($hireDate->floatDiffInYears(now())));
            $dailyRate = (float) $employee->base_salary / (int) config('payroll.working_days_per_month', 26);
            $severanceDays = $this->severanceDays($completedYears);
            $severancePay = round($dailyRate * $severanceDays, 2);
            $leaveEncashment = round($dailyRate * $this->leaveBalances->unusedAnnualLeaveDays($employee), 2);
            $amount = round($severancePay + $leaveEncashment, 2);

            $calculation = SeveranceCalculation::query()->create([
                'employee_id' => $employee->id,
                'amount' => $amount,
                'months_of_service' => max(1, (int) $hireDate->diffInMonths(now())),
                'calculation_date' => now()->toDateString(),
                'status' => 'calculated',
            ]);

            if ($offboarding !== null) {
                $offboarding->update(['severance_amount' => $amount]);
            }

            $journal = $this->s4->postJournal([
                'description' => 'Severance accrual employee '.$employee->id,
                'source_module' => 's2',
                'source_reference' => 'SEVERANCE-'.$calculation->id,
                'lines' => [
                    ['account_code' => '5005', 'debit' => $amount, 'credit' => 0],
                    ['account_code' => '2100', 'debit' => 0, 'credit' => $amount],
                ],
            ], 'severance-'.$calculation->id);

            $calculation->update([
                's4_journal_entry_id' => (string) ($journal['data']['id'] ?? ''),
            ]);

            $this->outbox->enqueue(config('events.channels.severance_calculated'), [
                'severance_id' => $calculation->id,
                'employee_id' => $employee->id,
                'offboarding_record_id' => $offboarding?->id,
                'amount' => (string) $amount,
                'months_of_service' => $calculation->months_of_service,
                's4_journal_entry_id' => $calculation->s4_journal_entry_id,
            ]);

            return $calculation->fresh(['employee.department']);
        });
    }

    public function pay(SeveranceCalculation $calculation): SeveranceCalculation
    {
        if ($calculation->status !== 'calculated') {
            throw new InvalidArgumentException('Only calculated severance can be paid.');
        }

        return DB::transaction(function () use ($calculation) {
            $amount = (float) $calculation->amount;

            $journal = $this->s4->postJournal([
                'description' => 'Severance payout '.$calculation->id,
                'source_module' => 's2',
                'source_reference' => 'SEVERANCE-PAY-'.$calculation->id,
                'lines' => [
                    ['account_code' => '2100', 'debit' => $amount, 'credit' => 0],
                    ['account_code' => '1001', 'debit' => 0, 'credit' => $amount],
                ],
            ], 'severance-pay-'.$calculation->id);

            $calculation->update([
                'status' => 'paid',
                'paid_at' => now(),
                's4_payout_journal_entry_id' => (string) ($journal['data']['id'] ?? ''),
            ]);

            $this->outbox->enqueue(config('events.channels.severance_paid'), [
                'severance_id' => $calculation->id,
                'employee_id' => $calculation->employee_id,
                'amount' => (string) $amount,
                's4_payout_journal_entry_id' => $calculation->s4_payout_journal_entry_id,
            ]);

            return $calculation->fresh(['employee.department']);
        });
    }

    private function severanceDays(int $completedYears): int
    {
        foreach (config('payroll.severance_schedule', []) as $row) {
            if ($completedYears >= $row['min_years'] && $completedYears <= $row['max_years']) {
                return (int) $row['days'];
            }
        }

        return 0;
    }
}
