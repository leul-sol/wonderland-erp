<?php

namespace App\Services;

use App\Models\Folio;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReservationService
{
    public function __construct(private readonly OutboxService $outbox)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Reservation
    {
        return Reservation::query()->create([
            'confirmation_code' => $this->nextConfirmationCode(),
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'] ?? null,
            'guest_phone' => $data['guest_phone'] ?? null,
            'room_type_id' => $data['room_type_id'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'adults' => $data['adults'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
        ]);
    }

    public function checkIn(Reservation $reservation, int $roomId): Reservation
    {
        if ($reservation->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed reservations can be checked in.');
        }

        $room = Room::query()->findOrFail($roomId);

        if ($room->status !== 'available') {
            throw new InvalidArgumentException('Room is not available.');
        }

        if ($room->room_type_id !== $reservation->room_type_id) {
            throw new InvalidArgumentException('Room type does not match reservation.');
        }

        return DB::transaction(function () use ($reservation, $room) {
            $room->update(['status' => 'occupied']);

            $reservation->update([
                'room_id' => $room->id,
                'status' => 'checked_in',
                'checked_in_at' => now(),
            ]);

            Folio::query()->create([
                'reservation_id' => $reservation->id,
                'status' => 'open',
            ]);

            $this->outbox->enqueue(config('events.channels.guest_checked_in'), [
                'reservation_id' => $reservation->id,
                'room_id' => $room->id,
                'guest_name' => $reservation->guest_name,
                'checked_in_at' => now()->toIso8601String(),
            ]);

            return $reservation->fresh(['room', 'roomType', 'folio']);
        });
    }

    public function checkOut(Reservation $reservation): Reservation
    {
        if ($reservation->status !== 'checked_in') {
            throw new InvalidArgumentException('Only checked-in reservations can be checked out.');
        }

        $folio = $reservation->folio;
        if ($folio !== null && $folio->status !== 'settled') {
            throw new InvalidArgumentException('Folio must be settled before check-out.');
        }

        return DB::transaction(function () use ($reservation) {
            if ($reservation->room_id !== null) {
                Room::query()->whereKey($reservation->room_id)->update(['status' => 'available']);
            }

            $reservation->update([
                'status' => 'checked_out',
                'checked_out_at' => now(),
            ]);

            $this->outbox->enqueue(config('events.channels.guest_checked_out'), [
                'reservation_id' => $reservation->id,
                'room_id' => $reservation->room_id,
                'checked_out_at' => now()->toIso8601String(),
            ]);

            return $reservation->fresh(['room', 'roomType', 'folio']);
        });
    }

    private function nextConfirmationCode(): string
    {
        do {
            $code = 'WH-'.strtoupper(Str::random(6));
        } while (Reservation::query()->where('confirmation_code', $code)->exists());

        return $code;
    }
}
