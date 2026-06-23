<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SupplierService
{
    public function __construct(private readonly S4FinanceClient $s4)
    {
    }

    public function create(array $data): Supplier
    {
        return Supplier::query()->create([
            'name' => $data['name'],
            'contact_name' => $data['contact_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'outstanding_balance' => 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update([
            'name' => $data['name'] ?? $supplier->name,
            'contact_name' => $data['contact_name'] ?? $supplier->contact_name,
            'phone' => $data['phone'] ?? $supplier->phone,
            'email' => $data['email'] ?? $supplier->email,
            'address' => $data['address'] ?? $supplier->address,
            'payment_terms' => $data['payment_terms'] ?? $supplier->payment_terms,
            'is_active' => $data['is_active'] ?? $supplier->is_active,
        ]);

        return $supplier->fresh();
    }

    public function find(int $id): Supplier
    {
        return Supplier::query()->findOrFail($id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Supplier>
     */
    public function listActive(): \Illuminate\Database\Eloquent\Collection
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function deactivate(Supplier $supplier): Supplier
    {
        $supplier->update(['is_active' => false]);

        return $supplier->fresh();
    }

    public function recordPayment(Supplier $supplier, array $data, string $idempotencyKey): SupplierPayment
    {
        $existing = SupplierPayment::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }

        if ($amount > (float) $supplier->outstanding_balance) {
            throw new InvalidArgumentException('Payment exceeds supplier outstanding balance.');
        }

        $paymentMethod = (string) ($data['payment_method'] ?? 'cash');
        $accounts = config('hospitality.accounts');

        $cashAccount = match ($paymentMethod) {
            'bank' => '1002',
            'pos' => '1004',
            default => $accounts['cash'],
        };

        return DB::transaction(function () use ($supplier, $data, $idempotencyKey, $amount, $paymentMethod, $accounts, $cashAccount) {
            $payment = SupplierPayment::query()->create([
                'supplier_id' => $supplier->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference_number' => $data['reference_number'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'posted_to_finance' => false,
            ]);

            try {
                $this->s4->postJournal([
                    'description' => 'Supplier payment '.$supplier->name,
                    'source_module' => 's3',
                    'source_reference' => 'SUPPLIER-PAY-'.$payment->id,
                    'lines' => [
                        ['account_code' => $accounts['ap_suppliers'], 'debit' => $amount, 'credit' => 0],
                        ['account_code' => $cashAccount, 'debit' => 0, 'credit' => $amount],
                    ],
                ], $idempotencyKey);
            } catch (RuntimeException $e) {
                throw $e;
            }

            $supplier->decrement('outstanding_balance', $amount);
            $payment->update(['posted_to_finance' => true]);

            return $payment->fresh();
        });
    }
}
