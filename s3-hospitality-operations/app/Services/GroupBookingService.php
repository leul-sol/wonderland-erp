<?php

namespace App\Services;

use App\Models\GroupBooking;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GroupBookingService
{
    public function __construct(private readonly ReservationService $reservations)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GroupBooking
    {
        $rooms = $data['rooms'] ?? [];

        if (! is_array($rooms) || $rooms === []) {
            throw new InvalidArgumentException('At least one room reservation is required for a group booking.');
        }

        $checkIn = Carbon::parse($data['check_in_date']);
        $checkOut = Carbon::parse($data['check_out_date']);

        if ($checkIn->gte($checkOut)) {
            throw new InvalidArgumentException('check_out_date must be after check_in_date.');
        }

        return DB::transaction(function () use ($data, $rooms, $checkIn, $checkOut) {
            $group = GroupBooking::query()->create([
                'group_code' => $this->nextGroupCode(),
                'group_name' => $data['group_name'],
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'check_in_date' => $checkIn->toDateString(),
                'check_out_date' => $checkOut->toDateString(),
                'status' => 'confirmed',
                'room_count' => count($rooms),
            ]);

            foreach ($rooms as $roomRequest) {
                $this->reservations->create([
                    'guest_name' => $roomRequest['guest_name'],
                    'guest_email' => $roomRequest['guest_email'] ?? $data['contact_email'] ?? null,
                    'guest_phone' => $roomRequest['guest_phone'] ?? $data['contact_phone'] ?? null,
                    'room_type_id' => (int) $roomRequest['room_type_id'],
                    'check_in_date' => $checkIn->toDateString(),
                    'check_out_date' => $checkOut->toDateString(),
                    'adults' => $roomRequest['adults'] ?? 1,
                    'notes' => $roomRequest['notes'] ?? ('Group '.$group->group_code),
                    'group_booking_id' => $group->id,
                ]);
            }

            return $group->fresh('reservations.roomType');
        });
    }

    /**
     * @param  array<int, array{reservation_id: int, room_id: int}>  $assignments
     */
    public function checkIn(GroupBooking $group, array $assignments): GroupBooking
    {
        if ($group->status !== 'confirmed') {
            throw new InvalidArgumentException('Only confirmed group bookings can be checked in.');
        }

        return DB::transaction(function () use ($group, $assignments) {
            $group->loadMissing('reservations');

            foreach ($assignments as $assignment) {
                $reservation = $group->reservations->firstWhere('id', (int) $assignment['reservation_id']);

                if ($reservation === null) {
                    throw new InvalidArgumentException('Reservation does not belong to this group booking.');
                }

                $room = Room::query()->findOrFail((int) $assignment['room_id']);
                $this->reservations->checkIn($reservation, $room->id);
            }

            $group->update(['status' => 'checked_in']);

            return $group->fresh('reservations.room');
        });
    }

    public function checkOut(GroupBooking $group): GroupBooking
    {
        if ($group->status !== 'checked_in') {
            throw new InvalidArgumentException('Only checked-in group bookings can be checked out.');
        }

        return DB::transaction(function () use ($group) {
            $group->loadMissing('reservations.folio');

            foreach ($group->reservations as $reservation) {
                if ($reservation->status === 'checked_in') {
                    $this->reservations->checkOut($reservation);
                }
            }

            $group->update(['status' => 'checked_out']);

            return $group->fresh('reservations.room');
        });
    }

    private function nextGroupCode(): string
    {
        do {
            $code = 'GRP-'.strtoupper(Str::random(6));
        } while (GroupBooking::query()->where('group_code', $code)->exists());

        return $code;
    }
}
