<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Auth\PortalAuthService;
use Illuminate\Http\Request;

trait ProvidesCheckInModalData
{
    /**
     * @return array<string, mixed>
     */
    protected function checkInModalProps(Request $request): array
    {
        if (! app(PortalAuthService::class)->hasAnyPermission([
            'S3.hotel.checkinout.write',
            'S3.hotel.reservations.write',
        ])) {
            return [];
        }

        return [
            'checkInGuestId' => $request->integer('guest_id') ?: null,
            'checkInLoad' => $this->deferPageLoad(function () {
                $roomTypes = $this->s3->roomTypes();
                $rooms = $this->s3->rooms('available');
                $guestsResponse = $this->s3->guestProfiles();
                $paginator = $guestsResponse['data'] ?? [];
                $guests = is_array($paginator['data'] ?? null) ? $paginator['data'] : [];

                return [
                    'roomTypes' => $roomTypes['data'] ?? [],
                    'availableRooms' => $rooms['data'] ?? [],
                    'guests' => $guests,
                ];
            }),
        ];
    }
}
