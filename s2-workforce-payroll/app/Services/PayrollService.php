<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\PayrollLine;
use App\Models\PayrollRun;
use App\Services\Payroll\OvertimeCalculatorService;
use App\Services\Payroll\PensionService;
use App\Services\Payroll\TaxCalculatorService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollService
{
    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
        private readonly TaxCalculatorService $taxCalculator,
        private readonly PensionService $pensionService,
        private readonly OvertimeCalculatorService $overtimeCalculator,
        private readonly LoanService $loanService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRun(array $data): PayrollRun
    {
        $employees = Employee::query()
            ->with('position')
            ->where('status', 'active')
            ->get();

        if ($employees->isEmpty()) {
            throw new InvalidArgumentException('No active employees available for payroll.');
        }

        $periodStart = (string) $data['period_start'];
        $periodEnd = (string) $data['period_end'];

        return DB::transaction(function () use ($data, $employees, $periodStart, $periodEnd) {
            $run = PayrollRun::query()->create([
                'run_number' => $this->nextRunNumber(),
                'period_start' => $data['period_start'],
                'period_end' => $periodEnd,
                'status' => 'draft',
            ]);

            $totalGross = 0.0;
            $totalNet = 0.0;
            $overtimeRecordIds = [];

            foreach ($employees as $employee) {
                $attendanceFactor = $this->attendancePayFactor($employee, $periodStart, $periodEnd);
                $basicComponent = round((float) $employee->base_salary * $attendanceFactor, 2);
                $allowances = $this->positionAllowances($employee, $attendanceFactor);
                $overtime = $this->overtimeCalculator->calculateForPeriod($employee, $periodStart, $periodEnd);
                $overtimeRecordIds = array_merge($overtimeRecordIds, $overtime['record_ids']);

                $gross = round($basicComponent + $allowances + $overtime['amount'], 2);
                $otherDeductions = $this->unappliedDeductionTotal($employee->id, $periodEnd);
                $loan = $this->loanService->repaymentForPayroll($employee);
                $amounts = $this->calculateAmounts(
                    $employee,
                    $gross,
                    $overtime['amount'],
                    $loan['amount'],
                    $otherDeductions,
                );

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

            $run->setRelation('lines', PayrollLine::query()->where('payroll_run_id', $run->id)->with('employee')->get());
            $run->setAttribute('_overtime_record_ids', $overtimeRecordIds);

            return $run;
        });
    }

    public function submit(PayrollRun $run): PayrollRun
    {
        if ($run->status !== 'draft') {
            throw new InvalidArgumentException('Only draft payroll runs can be submitted.');
        }

        $run->update(['status' => 'pending_approval']);

        return $run->fresh('lines.employee');
    }

    public function approve(PayrollRun $run, int $approvedBy): PayrollRun
    {
        if ($run->status !== 'pending_approval') {
            throw new InvalidArgumentException('Only pending_approval payroll runs can be approved.');
        }

        $run->load('lines');

        if ($run->lines->isEmpty()) {
            throw new InvalidArgumentException('Payroll run has no lines.');
        }

        $gross = round((float) $run->lines->sum('gross_salary'), 2);
        $employeePension = round((float) $run->lines->sum('employee_pension'), 2);
        $employerPension = round((float) $run->lines->sum('employer_pension'), 2);
        $incomeTax = round((float) $run->lines->sum('income_tax'), 2);
        $otherDeductions = round((float) $run->lines->sum('other_deductions'), 2);
        $loanRepayment = round((float) $run->lines->sum('loan_repayment'), 2);
        $net = round((float) $run->lines->sum('net_pay'), 2);

        return DB::transaction(function () use ($run, $approvedBy, $gross, $employeePension, $employerPension, $incomeTax, $otherDeductions, $loanRepayment, $net) {
            $lines = [
                ['account_code' => '5001', 'debit' => $gross, 'credit' => 0],
                ['account_code' => '5002', 'debit' => $employerPension, 'credit' => 0],
                ['account_code' => '2100', 'debit' => 0, 'credit' => $net],
                ['account_code' => '2101', 'debit' => 0, 'credit' => $employeePension],
                ['account_code' => '2200', 'debit' => 0, 'credit' => $incomeTax],
                ['account_code' => '2102', 'debit' => 0, 'credit' => $employerPension],
            ];

            if ($otherDeductions > 0) {
                $lines[] = ['account_code' => '1102', 'debit' => 0, 'credit' => $otherDeductions];
            }

            if ($loanRepayment > 0) {
                $lines[] = ['account_code' => '1102', 'debit' => $loanRepayment, 'credit' => 0];
            }

            $journal = $this->s4->postJournal([
                'description' => 'Payroll '.$run->run_number,
                'source_module' => 's2',
                'source_reference' => $run->run_number,
                'lines' => $lines,
            ], 'payroll-run-'.$run->id);

            $journalId = (string) ($journal['data']['id'] ?? '');

            $run->update([
                'status' => 'approved',
                's4_journal_entry_id' => $journalId,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            $this->linkDeductionsToRun($run);
            $this->applyLoanRepayments($run);
            $this->markOvertimePaid($run);

            $this->outbox->enqueue(config('events.channels.payroll_run_approved'), [
                'payroll_run_id' => $run->id,
                'run_number' => $run->run_number,
                'total_gross' => $gross,
                'total_net' => $net,
                'total_other_deductions' => $otherDeductions,
                's4_journal_entry_id' => $journalId,
            ]);

            return $run->fresh('lines.employee');
        });
    }

    public function lock(PayrollRun $run): PayrollRun
    {
        if ($run->status !== 'approved') {
            throw new InvalidArgumentException('Only approved payroll runs can be locked.');
        }

        $run->update(['status' => 'locked']);

        return $run->fresh('lines.employee');
    }

    private function markOvertimePaid(PayrollRun $run): void
    {
        $employeeIds = $run->lines->pluck('employee_id')->all();

        \App\Models\OvertimeRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereNull('payroll_run_id')
            ->whereDate('work_date', '>=', $run->period_start)
            ->whereDate('work_date', '<=', $run->period_end)
            ->update([
                'status' => 'paid',
                'payroll_run_id' => $run->id,
            ]);
    }

    private function applyLoanRepayments(PayrollRun $run): void
    {
        foreach ($run->lines as $line) {
            if ((float) $line->loan_repayment <= 0) {
                continue;
            }

            $loan = \App\Models\LoanRecord::query()
                ->where('employee_id', $line->employee_id)
                ->where('status', 'active')
                ->orderBy('id')
                ->first();

            if ($loan !== null) {
                $this->loanService->applyRepayment($loan->id, (float) $line->loan_repayment);
            }
        }
    }

    private function positionAllowances(Employee $employee, float $attendanceFactor): float
    {
        $position = $employee->position;

        if ($position === null) {
            return 0.0;
        }

        $transport = (float) $position->transport_allowance;
        $housing = (float) $position->housing_allowance;

        return round(($transport + $housing) * $attendanceFactor, 2);
    }

    private function attendancePayFactor(Employee $employee, string $periodStart, string $periodEnd): float
    {
        $expectedDays = 0;
        $paidUnits = 0.0;

        foreach (CarbonPeriod::create(Carbon::parse($periodStart), Carbon::parse($periodEnd)) as $day) {
            if (! $day->isWeekday()) {
                continue;
            }

            $expectedDays++;
            $record = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', $day->toDateString())
                ->first();

            if ($record === null) {
                throw new InvalidArgumentException(
                    'Missing attendance for '.$employee->full_name.' on '.$day->toDateString().'.'
                );
            }

            $paidUnits += match ($record->status) {
                'present', 'leave' => 1.0,
                'half_day' => 0.5,
                default => 0.0,
            };
        }

        if ($expectedDays === 0) {
            return 1.0;
        }

        return round($paidUnits / $expectedDays, 4);
    }

    private function unappliedDeductionTotal(int $employeeId, string $periodEnd): float
    {
        return round((float) EmployeeDeduction::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'applied')
            ->whereNull('payroll_run_id')
            ->whereDate('created_at', '<=', $periodEnd)
            ->sum('amount'), 2);
    }

    private function linkDeductionsToRun(PayrollRun $run): void
    {
        $employeeIds = $run->lines->pluck('employee_id')->all();

        EmployeeDeduction::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'applied')
            ->whereNull('payroll_run_id')
            ->whereDate('created_at', '<=', $run->period_end?->toDateString())
            ->update(['payroll_run_id' => $run->id]);
    }

    /**
     * @return array{gross_salary: float, overtime_pay: float, loan_repayment: float, employee_pension: float, employer_pension: float, income_tax: float, other_deductions: float, net_pay: float}
     */
    private function calculateAmounts(
        Employee $employee,
        float $gross,
        float $overtimePay,
        float $loanRepayment,
        float $otherDeductions,
    ): array {
        $pension = $this->pensionService->calculate(
            $employee->pension_category ?? 'covered',
            (float) $employee->base_salary,
        );

        $gross = round($gross, 2);
        $overtimePay = round($overtimePay, 2);
        $loanRepayment = round(max(0, $loanRepayment), 2);
        $otherDeductions = round(max(0, $otherDeductions), 2);
        $employeePension = $pension['employee'];
        $employerPension = $pension['employer'];
        $taxable = $gross - $employeePension;
        $incomeTax = $this->taxCalculator->calculate($taxable);
        $net = round($gross - $employeePension - $incomeTax - $otherDeductions - $loanRepayment, 2);

        if ($net < 0) {
            throw new InvalidArgumentException('Employee deductions exceed net pay for gross salary '.$gross.'.');
        }

        return [
            'gross_salary' => $gross,
            'overtime_pay' => $overtimePay,
            'loan_repayment' => $loanRepayment,
            'employee_pension' => $employeePension,
            'employer_pension' => $employerPension,
            'income_tax' => $incomeTax,
            'other_deductions' => $otherDeductions,
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
