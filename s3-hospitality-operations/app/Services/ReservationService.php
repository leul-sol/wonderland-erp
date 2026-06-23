<?php

namespace App\Services;

use App\Models\Folio;
use App\Models\GuestProfile;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReservationService
{
    public function __construct(
        private readonly OutboxService $outbox,
        private readonly FolioService $folios,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Reservation
    {
        $checkIn = Carbon::parse($data['check_in_date']);
        $checkOut = Carbon::parse($data['check_out_date']);
        $totalNights = max(1, $checkIn->diffInDays($checkOut));

        $quotedRate = isset($data['quoted_rate'])
            ? (float) $data['quoted_rate']
            : (float) (\App\Models\RoomType::query()->findOrFail($data['room_type_id'])->base_rate ?? 0);

        return Reservation::query()->create([
            'confirmation_code' => $this->nextConfirmationCode(),
            'guest_id' => $data['guest_id'] ?? null,
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'] ?? null,
            'guest_phone' => $data['guest_phone'] ?? null,
            'room_type_id' => $data['room_type_id'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'quoted_rate' => $quotedRate,
            'total_nights' => $totalNights,
            'adults' => $data['adults'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
            'group_booking_id' => $data['group_booking_id'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function update(Reservation $reservation, array $data): Reservation
    {
        if ($reservation->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed reservations can be updated.');
        }

        $reservation->update([
            'check_in_date' => $data['check_in_date'] ?? $reservation->check_in_date,
            'check_out_date' => $data['check_out_date'] ?? $reservation->check_out_date,
            'quoted_rate' => $data['quoted_rate'] ?? $reservation->quoted_rate,
        ]);

        if (isset($data['check_in_date'], $data['check_out_date'])) {
            $nights = max(1, Carbon::parse($reservation->check_in_date)->diffInDays(Carbon::parse($reservation->check_out_date)));
            $reservation->update(['total_nights' => $nights]);
        }

        return $reservation->fresh(['room', 'roomType', 'folio']);
    }

    public function cancel(Reservation $reservation): Reservation
    {
        if ($reservation->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed reservations can be cancelled.');
        }

        $reservation->update(['status' => 'cancelled']);

        return $reservation->fresh();
    }

    public function markNoShow(Reservation $reservation): Reservation
    {
        if ($reservation->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed reservations can be marked no-show.');
        }

        $reservation->update(['status' => 'no_show']);

        return $reservation->fresh();
    }

    public function checkIn(Reservation $reservation, int $roomId): Reservation
    {
        if ($reservation->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed reservations can be checked in.');
        }

        $room = Room::query()->with('roomType')->findOrFail($roomId);

        if ($room->status !== 'available') {
            throw new InvalidArgumentException('Room is not available.');
        }

        if ($room->room_type_id !== $reservation->room_type_id) {
            throw new InvalidArgumentException('Room type does not match reservation.');
        }

        return DB::transaction(function () use ($reservation, $room) {
            $guestId = $reservation->guest_id;
            if ($guestId === null) {
                $guest = GuestProfile::query()->create([
                    'full_name' => $reservation->guest_name,
                    'email' => $reservation->guest_email,
                    'phone' => $reservation->guest_phone,
                ]);
                $guestId = $guest->id;
            }

            $nightlyRate = (float) ($reservation->quoted_rate ?? $room->roomType?->base_rate ?? 0);
            $nights = (int) ($reservation->total_nights ?? 1);

            $room->update(['status' => 'occupied']);

            $reservation->update([
                'guest_id' => $guestId,
                'room_id' => $room->id,
                'status' => 'checked_in',
                'checked_in_at' => now(),
                'quoted_rate' => $nightlyRate,
                'total_nights' => $nights,
            ]);

            $folio = Folio::query()->create([
                'folio_number' => $this->nextFolioNumber(),
                'reservation_id' => $reservation->id,
                'guest_id' => $guestId,
                'room_id' => $room->id,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            for ($night = 1; $night <= $nights; $night++) {
                $this->folios->addCharge(
                    $folio,
                    'Room rent night '.$night.' — '.$room->room_number,
                    $nightlyRate,
                    'room',
                );
            }

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
                Room::query()->whereKey($reservation->room_id)->update(['status' => 'cleaning']);
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

    private function nextFolioNumber(): string
    {
        $last = Folio::query()->orderByDesc('id')->value('folio_number');
        $sequence = 1;

        if (is_string($last) && preg_match('/FOL-(\d+)/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'FOL-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
