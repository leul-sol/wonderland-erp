<?php

namespace App\Support;

class HospitalityPaymentMethods
{
    public static function requiresCashierShift(string $method): bool
    {
        return $method === 'cash';
    }

    public static function toS3(string $portalMethod): string
    {
        return match ($portalMethod) {
            'card' => 'pos',
            'mobile_money' => 'pos',
            default => $portalMethod,
        };
    }
}
