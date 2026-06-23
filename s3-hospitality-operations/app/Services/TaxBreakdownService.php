<?php

namespace App\Services;

class TaxBreakdownService
{
    /**
     * @return array{subtotal: float, service_charge_rate: float, service_charge_amount: float, vat_rate: float, vat_amount: float, total_amount: float}
     */
    public function compute(float $subtotal): array
    {
        $subtotal = round($subtotal, 2);

        if ($subtotal <= 0) {
            throw new \InvalidArgumentException('Subtotal must be positive.');
        }

        $scRate = (float) config('hospitality.service_charge_rate', 0.10);
        $vatRate = (float) config('hospitality.vat_rate', 0.15);

        $serviceCharge = round($subtotal * $scRate, 2);
        $vat = round(($subtotal + $serviceCharge) * $vatRate, 2);
        $total = round($subtotal + $serviceCharge + $vat, 2);

        return [
            'subtotal' => $subtotal,
            'service_charge_rate' => $scRate,
            'service_charge_amount' => $serviceCharge,
            'vat_rate' => $vatRate,
            'vat_amount' => $vat,
            'total_amount' => $total,
        ];
    }

    /**
     * @return array<int, array{account_code: string, debit: float, credit: float}>
     */
    public function revenueJournalLines(string $debitAccount, string $revenueAccount, array $breakdown): array
    {
        $accounts = config('hospitality.accounts');

        return [
            ['account_code' => $debitAccount, 'debit' => $breakdown['total_amount'], 'credit' => 0],
            ['account_code' => $revenueAccount, 'debit' => 0, 'credit' => $breakdown['subtotal']],
            ['account_code' => $accounts['service_charge_revenue'], 'debit' => 0, 'credit' => $breakdown['service_charge_amount']],
            ['account_code' => $accounts['vat_payable'], 'debit' => 0, 'credit' => $breakdown['vat_amount']],
        ];
    }
}
