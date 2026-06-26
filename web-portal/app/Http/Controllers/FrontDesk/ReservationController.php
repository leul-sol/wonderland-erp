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

class ReservationController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $status = $request->string('status')->toString() ?: null;

        try {
            $response = $this->s3->reservations($status);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('FrontDesk/Reservations/Index', [
            'reservations' => $response['data'] ?? [],
            'filters' => ['status' => $status ?? ''],
        ]);
    }

    public function show(int $reservation): Response|RedirectResponse
    {
        try {
            $reservationResponse = $this->s3->reservation($reservation);
            $reservationData = $reservationResponse['data'] ?? [];
            $rooms = $this->s3->rooms('available');
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'front-desk.reservations.index');
        }

        $availableRooms = collect($rooms['data'] ?? [])
            ->filter(fn (array $room): bool => (int) ($room['room_type']['id'] ?? 0) === (int) ($reservationData['room_type_id'] ?? 0))
            ->values()
            ->all();

        return Inertia::render('FrontDesk/Reservations/Show', [
            'reservation' => $reservationData,
            'availableRooms' => $availableRooms,
        ]);
    }

    public function checkIn(Request $request, int $reservation): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer'],
        ]);

        try {
            $checkedIn = $this->s3->checkIn($reservation, (int) $data['room_id']);
            $folioId = (int) ($checkedIn['data']['folio_id'] ?? 0);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        if ($folioId <= 0) {
            return back()->with('error', 'Check-in succeeded but no folio was returned.');
        }

        return redirect()
            ->route('front-desk.folios.show', $folioId)
            ->with('success', 'Guest checked in. Folio is ready.');
    }

    public function cancel(int $reservation): RedirectResponse
    {
        try {
            $this->s3->cancelReservation($reservation);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Reservation cancelled.');
    }

    public function noShow(int $reservation): RedirectResponse
    {
        try {
            $this->s3->noShowReservation($reservation);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Reservation marked as no-show.');
    }
}
