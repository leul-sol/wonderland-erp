<?php

namespace App\Http\Controllers\FrontDesk;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckInController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function create(): Response|RedirectResponse
    {
        try {
            $roomTypes = $this->s3->roomTypes();
            $rooms = $this->s3->rooms('available');
            $guestsResponse = $this->s3->guestProfiles();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $paginator = $guestsResponse['data'] ?? [];
        $guests = is_array($paginator['data'] ?? null) ? $paginator['data'] : [];

        $selectedGuestId = request()->integer('guest_id') ?: null;

        return Inertia::render('FrontDesk/CheckIn/Create', [
            'roomTypes' => $roomTypes['data'] ?? [],
            'availableRooms' => $rooms['data'] ?? [],
            'guests' => $guests,
            'selectedGuestId' => $selectedGuestId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => ['nullable', 'integer'],
            'guest_name' => ['required', 'string', 'max:200'],
            'guest_email' => ['nullable', 'email', 'max:200'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'room_type_id' => ['required', 'integer'],
            'room_id' => ['required', 'integer'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
        ]);

        try {
            $payload = [
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'] ?? null,
                'guest_phone' => $data['guest_phone'] ?? null,
                'room_type_id' => (int) $data['room_type_id'],
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
            ];

            if (! empty($data['guest_id'])) {
                $payload['guest_id'] = (int) $data['guest_id'];
            }

            $reservation = $this->s3->createReservation($payload);

            $reservationId = (int) ($reservation['data']['id'] ?? 0);
            $checkedIn = $this->s3->checkIn($reservationId, (int) $data['room_id']);
            $folioId = (int) ($checkedIn['data']['folio_id'] ?? 0);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        if ($folioId <= 0) {
            return back()->with('error', 'Check-in succeeded but no folio was created.');
        }

        return redirect()
            ->route('front-desk.folios.show', $folioId)
            ->with('success', 'Guest checked in. Folio is ready.');
    }
}
