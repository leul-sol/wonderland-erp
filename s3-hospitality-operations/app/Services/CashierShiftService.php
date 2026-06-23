<?php

namespace App\Services;

use App\Models\BillPayment;
use App\Models\CashierShift;
use App\Models\FolioPayment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashierShiftService
{
    public function open(int $cashierId, ?float $openingCashFloat = null): CashierShift
    {
        $existing = CashierShift::query()
            ->where('cashier_id', $cashierId)
            ->where('status', 'open')
            ->first();

        if ($existing !== null) {
            throw new InvalidArgumentException('Cashier already has an open shift.');
        }

        return CashierShift::query()->create([
            'cashier_id' => $cashierId,
            'opened_at' => now(),
            'opening_cash_float' => $openingCashFloat,
            'status' => 'open',
        ]);
    }

    public function close(CashierShift $shift, float $closingCashCounted): CashierShift
    {
        if ($shift->status !== 'open') {
            throw new InvalidArgumentException('Shift is not open.');
        }

        return DB::transaction(function () use ($shift, $closingCashCounted) {
            $expectedCash = $this->calculateExpectedCash($shift);
            $variance = round($closingCashCounted - $expectedCash, 2);

            $shift->update([
                'closed_at' => now(),
                'closing_cash_counted' => $closingCashCounted,
                'expected_cash' => $expectedCash,
                'variance' => $variance,
                'status' => 'closed',
            ]);

            return $shift->fresh();
        });
    }

    public function calculateExpectedCash(CashierShift $shift): float
    {
        $folioCash = (float) FolioPayment::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->sum('amount');

        $billCash = (float) BillPayment::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->sum('amount');

        $float = (float) ($shift->opening_cash_float ?? 0);

        return round($float + $folioCash + $billCash, 2);
    }
}
