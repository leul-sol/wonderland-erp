<?php

namespace App\Support;

class GroupBookingLifecycleSteps
{
    /**
     * @return list<array{key: string, label: string, hint: string}>
     */
    public static function forGroup(): array
    {
        return [
            ['key' => 'confirmed', 'label' => 'Confirmed', 'hint' => 'Rooming list and dates locked'],
            ['key' => 'checked_in', 'label' => 'Checked in', 'hint' => 'Rooms assigned — guests in-house'],
            ['key' => 'settled', 'label' => 'Folios settled', 'hint' => 'All guest balances paid'],
            ['key' => 'checked_out', 'label' => 'Checked out', 'hint' => 'Group departed'],
        ];
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  array<int, array<string, mixed>>  $folios
     */
    public static function currentStepKey(array $group, array $folios): string
    {
        $status = (string) ($group['status'] ?? '');

        if ($status === 'checked_out') {
            return 'checked_out';
        }

        if ($status === 'checked_in') {
            return self::allFoliosSettled($group, $folios) ? 'settled' : 'checked_in';
        }

        return 'confirmed';
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  array<int, array<string, mixed>>  $folios
     */
    public static function allFoliosSettled(array $group, array $folios): bool
    {
        $reservations = $group['reservations'] ?? [];

        if ($reservations === []) {
            return false;
        }

        foreach ($reservations as $reservation) {
            $folioId = (int) ($reservation['folio_id'] ?? 0);

            if ($folioId <= 0) {
                return false;
            }

            if (($folios[$folioId]['status'] ?? '') !== 'settled') {
                return false;
            }
        }

        return true;
    }
}
