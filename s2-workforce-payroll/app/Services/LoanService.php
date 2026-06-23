<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LoanRecord;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanService
{
    public function __construct(private readonly S4FinanceClient $s4)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function disburse(Employee $employee, array $data, string $idempotencyKey): LoanRecord
    {
        if ($employee->status !== 'active') {
            throw new InvalidArgumentException('Loans require an active employee.');
        }

        $principal = round((float) $data['principal_amount'], 2);
        $monthly = round((float) $data['monthly_repayment'], 2);

        if ($principal <= 0 || $monthly <= 0) {
            throw new InvalidArgumentException('Loan amounts must be greater than zero.');
        }

        return DB::transaction(function () use ($employee, $data, $principal, $monthly, $idempotencyKey) {
            $journal = $this->s4->postJournal([
                'description' => 'Staff loan disbursement employee '.$employee->id,
                'source_module' => 's2',
                'source_reference' => 'LOAN-'.$employee->id.'-'.$idempotencyKey,
                'lines' => [
                    ['account_code' => '1102', 'debit' => $principal, 'credit' => 0],
                    ['account_code' => '1001', 'debit' => 0, 'credit' => $principal],
                ],
            ], $idempotencyKey);

            return LoanRecord::query()->create([
                'employee_id' => $employee->id,
                'principal_amount' => $principal,
                'monthly_repayment' => $monthly,
                'remaining_balance' => $principal,
                'status' => 'active',
                'disbursed_at' => $data['disbursed_at'] ?? now()->toDateString(),
                's4_journal_entry_id' => (string) ($journal['data']['id'] ?? ''),
            ])->load('employee');
        });
    }

    /**
     * @return array{amount: float, loan_id: int|null}
     */
    public function repaymentForPayroll(Employee $employee): array
    {
        $loan = LoanRecord::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('remaining_balance', '>', 0)
            ->orderBy('id')
            ->first();

        if ($loan === null) {
            return ['amount' => 0.0, 'loan_id' => null];
        }

        $amount = min((float) $loan->monthly_repayment, (float) $loan->remaining_balance);

        return ['amount' => round($amount, 2), 'loan_id' => $loan->id];
    }

    public function applyRepayment(int $loanId, float $amount): void
    {
        $loan = LoanRecord::query()->findOrFail($loanId);
        $remaining = max(0, round((float) $loan->remaining_balance - $amount, 2));

        $loan->update([
            'remaining_balance' => $remaining,
            'status' => $remaining <= 0 ? 'completed' : 'active',
        ]);
    }
}
