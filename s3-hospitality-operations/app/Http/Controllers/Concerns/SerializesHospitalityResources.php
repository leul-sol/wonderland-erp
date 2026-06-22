<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Folio;
use App\Models\Reservation;
use App\Models\Room;

trait SerializesHospitalityResources
{
    protected function roomPayload(Room $room): array
    {
        $room->loadMissing('roomType');

        return [
            'id' => $room->id,
            'room_number' => $room->room_number,
            'floor' => $room->floor,
            'status' => $room->status,
            'room_type' => $room->roomType ? [
                'id' => $room->roomType->id,
                'code' => $room->roomType->code,
                'name' => $room->roomType->name,
                'base_rate' => (string) $room->roomType->base_rate,
            ] : null,
        ];
    }

    protected function reservationPayload(Reservation $reservation): array
    {
        $reservation->loadMissing(['room', 'roomType', 'folio']);

        return [
            'id' => $reservation->id,
            'confirmation_code' => $reservation->confirmation_code,
            'guest_name' => $reservation->guest_name,
            'guest_email' => $reservation->guest_email,
            'guest_phone' => $reservation->guest_phone,
            'status' => $reservation->status,
            'check_in_date' => $reservation->check_in_date?->toDateString(),
            'check_out_date' => $reservation->check_out_date?->toDateString(),
            'checked_in_at' => $reservation->checked_in_at?->toIso8601String(),
            'checked_out_at' => $reservation->checked_out_at?->toIso8601String(),
            'adults' => $reservation->adults,
            'room_type_id' => $reservation->room_type_id,
            'room_id' => $reservation->room_id,
            'room' => $reservation->room ? $this->roomPayload($reservation->room) : null,
            'folio_id' => $reservation->folio?->id,
        ];
    }

    protected function folioPayload(Folio $folio): array
    {
        $folio->loadMissing('lines');

        return [
            'id' => $folio->id,
            'reservation_id' => $folio->reservation_id,
            'status' => $folio->status,
            'total_charges' => (string) $folio->total_charges,
            'total_payments' => (string) $folio->total_payments,
            'balance' => number_format($folio->balance(), 2, '.', ''),
            'currency' => $folio->currency,
            'settled_at' => $folio->settled_at?->toIso8601String(),
            'lines' => $folio->lines->map(fn ($line) => [
                'id' => $line->id,
                'line_type' => $line->line_type,
                'charge_category' => $line->charge_category,
                'description' => $line->description,
                'amount' => (string) $line->amount,
                'payment_method' => $line->payment_method,
                's4_journal_entry_id' => $line->s4_journal_entry_id,
                'posted_at' => $line->posted_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
