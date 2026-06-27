<?php

namespace App\Http\Controllers\FrontDesk;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Concerns\LoadsGatewayDataInParallel;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;
    use LoadsGatewayDataInParallel;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $status = $request->string('status')->toString() ?: null;
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        return Inertia::render('FrontDesk/Reservations/Index', [
            'filters' => ['status' => $status ?? ''],
            'defaultCheckIn' => $today,
            'defaultCheckOut' => $tomorrow,
            'pageLoad' => $this->deferPageLoad(function () use ($status) {
                $results = $this->fetchGatewayInParallel($this->s3, [
                    'reservations' => ['path' => '/s3/api/v1/reservations', 'query' => array_filter(['status' => $status])],
                    'roomTypes' => ['path' => '/s3/api/v1/room-types', 'query' => []],
                    'guests' => ['path' => '/s3/api/v1/guest-profiles', 'query' => ['per_page' => 50]],
                ]);
                $response = $this->requireParallelResult($results, 'reservations');
                $roomTypes = $results['roomTypes'] ?? ['data' => []];
                $guestsResponse = $results['guests'] ?? ['data' => ['data' => []]];
                $paginator = $guestsResponse['data'] ?? [];
                $guests = is_array($paginator['data'] ?? null) ? $paginator['data'] : [];

                return [
                    'reservations' => $response['data'] ?? [],
                    'roomTypes' => $roomTypes['data'] ?? [],
                    'guests' => $guests,
                ];
            }),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('front-desk.reservations.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => ['nullable', 'integer'],
            'guest_name' => ['required', 'string', 'max:150'],
            'guest_email' => ['nullable', 'email', 'max:150'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'room_type_id' => ['required', 'integer'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

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

        if (! empty($data['adults'])) {
            $payload['adults'] = (int) $data['adults'];
        }

        if (! empty($data['notes'])) {
            $payload['notes'] = $data['notes'];
        }

        try {
            $response = $this->s3->createReservation($payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $reservationId = (int) ($response['data']['id'] ?? 0);

        return redirect()
            ->route('front-desk.reservations.show', $reservationId)
            ->with('success', 'Reservation created. Check in when the guest arrives.');
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
