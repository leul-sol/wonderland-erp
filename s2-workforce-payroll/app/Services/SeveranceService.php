<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SeveranceCalculation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SeveranceService
{
    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
    ) {
    }

    public function calculate(Employee $employee): SeveranceCalculation
    {
        if ($employee->hire_date === null) {
            throw new InvalidArgumentException('Employee hire_date is required for severance calculation.');
        }

        return DB::transaction(function () use ($employee) {
            $hireDate = Carbon::parse($employee->hire_date);
            $months = max(1, (int) $hireDate->diffInMonths(now()));
            $monthlySalary = (float) $employee->base_salary;
            $amount = round($monthlySalary * $months * 0.5, 2);

            $calculation = SeveranceCalculation::query()->create([
                'employee_id' => $employee->id,
                'amount' => $amount,
                'months_of_service' => $months,
                'calculation_date' => now()->toDateString(),
                'status' => 'calculated',
            ]);

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
                'amount' => (string) $amount,
                'months_of_service' => $months,
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
}
