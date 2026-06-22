<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollLine;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollService
{
    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRun(array $data): PayrollRun
    {
        $employees = Employee::query()->where('status', 'active')->get();

        if ($employees->isEmpty()) {
            throw new InvalidArgumentException('No active employees available for payroll.');
        }

        return DB::transaction(function () use ($data, $employees) {
            $run = PayrollRun::query()->create([
                'run_number' => $this->nextRunNumber(),
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'status' => 'draft',
            ]);

            $totalGross = 0.0;
            $totalNet = 0.0;

            foreach ($employees as $employee) {
                $amounts = $this->calculateAmounts((float) $employee->base_salary);

                PayrollLine::query()->create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    ...$amounts,
                ]);

                $totalGross += $amounts['gross_salary'];
                $totalNet += $amounts['net_pay'];
            }

            $run->update([
                'total_gross' => round($totalGross, 2),
                'total_net' => round($totalNet, 2),
            ]);

            return $run->fresh('lines.employee');
        });
    }

    public function approve(PayrollRun $run, int $approvedBy): PayrollRun
    {
        if ($run->status !== 'draft') {
            throw new InvalidArgumentException('Only draft payroll runs can be approved.');
        }

        $run->load('lines');

        if ($run->lines->isEmpty()) {
            throw new InvalidArgumentException('Payroll run has no lines.');
        }

        $gross = round((float) $run->lines->sum('gross_salary'), 2);
        $employeePension = round((float) $run->lines->sum('employee_pension'), 2);
        $employerPension = round((float) $run->lines->sum('employer_pension'), 2);
        $incomeTax = round((float) $run->lines->sum('income_tax'), 2);
        $net = round((float) $run->lines->sum('net_pay'), 2);

        return DB::transaction(function () use ($run, $approvedBy, $gross, $employeePension, $employerPension, $incomeTax, $net) {
            $journal = $this->s4->postJournal([
                'description' => 'Payroll '.$run->run_number,
                'source_module' => 's2',
                'source_reference' => $run->run_number,
                'lines' => [
                    ['account_code' => '5001', 'debit' => $gross, 'credit' => 0],
                    ['account_code' => '5002', 'debit' => $employerPension, 'credit' => 0],
                    ['account_code' => '2100', 'debit' => 0, 'credit' => $net],
                    ['account_code' => '2101', 'debit' => 0, 'credit' => $employeePension],
                    ['account_code' => '2200', 'debit' => 0, 'credit' => $incomeTax],
                    ['account_code' => '2102', 'debit' => 0, 'credit' => $employerPension],
                ],
            ], 'payroll-run-'.$run->id);

            $journalId = (string) ($journal['data']['id'] ?? '');

            $run->update([
                'status' => 'posted',
                's4_journal_entry_id' => $journalId,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            $this->outbox->enqueue(config('events.channels.payroll_run_approved'), [
                'payroll_run_id' => $run->id,
                'run_number' => $run->run_number,
                'total_gross' => $gross,
                'total_net' => $net,
                's4_journal_entry_id' => $journalId,
            ]);

            return $run->fresh('lines.employee');
        });
    }

    /**
     * @return array{gross_salary: float, employee_pension: float, employer_pension: float, income_tax: float, net_pay: float}
     */
    private function calculateAmounts(float $gross): array
    {
        $employeeRate = config('payroll.employee_pension_rate');
        $employerRate = config('payroll.employer_pension_rate');
        $taxRate = config('payroll.income_tax_rate');

        $gross = round($gross, 2);
        $employeePension = round($gross * $employeeRate, 2);
        $employerPension = round($gross * $employerRate, 2);
        $taxable = $gross - $employeePension;
        $incomeTax = round($taxable * $taxRate, 2);
        $net = round($gross - $employeePension - $incomeTax, 2);

        return [
            'gross_salary' => $gross,
            'employee_pension' => $employeePension,
            'employer_pension' => $employerPension,
            'income_tax' => $incomeTax,
            'net_pay' => $net,
        ];
    }

    private function nextRunNumber(): string
    {
        $prefix = 'PR-'.now()->format('Ym');
        $last = PayrollRun::query()
            ->where('run_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('run_number');

        $sequence = 1;
        if (is_string($last) && preg_match('/PR-\d{6}-(\d+)/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
