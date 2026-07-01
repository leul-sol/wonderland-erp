<?php

namespace App\Http\Controllers\Concerns;

use App\Services\FrontDesk\CashierShiftResolver;
use App\Support\HospitalityPaymentMethods;
use Illuminate\Validation\ValidationException;

trait ResolvesCashierShiftPayments
{
    /**
     * @param  array{amount: float|int|string, payment_method: string}  $data
     * @return array{amount: float, payment_method: string, cashier_shift_id?: int}
     */
    protected function folioPaymentPayload(array $data): array
    {
        $portalMethod = (string) ($data['payment_method'] ?? 'cash');
        $payload = [
            'amount' => (float) $data['amount'],
            'payment_method' => HospitalityPaymentMethods::toS3($portalMethod),
        ];

        if (HospitalityPaymentMethods::requiresCashierShift($portalMethod)) {
            $shiftId = app(CashierShiftResolver::class)->openShiftIdForCashCollection();
            if ($shiftId === null) {
                throw ValidationException::withMessages([
                    'payment_method' => 'No cashier shift is open. Ask front desk or cashier to open a shift (Front desk → Cashier shifts).',
                ]);
            }

            $payload['cashier_shift_id'] = $shiftId;
        }

        return $payload;
    }

    public function requireOpenCashierShiftIdForCash(): int
    {
        $shiftId = app(CashierShiftResolver::class)->openShiftIdForCashCollection();
        if ($shiftId === null) {
            throw ValidationException::withMessages([
                'cashier_shift' => 'No cashier shift is open. Ask front desk or cashier to open a shift (Front desk → Cashier shifts).',
            ]);
        }

        return $shiftId;
    }
}
