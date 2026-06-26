<?php

namespace App\Http\Controllers\GroupBookings;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use App\Support\GroupBookingLifecycleSteps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupBookingController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $tab = $request->string('tab')->toString() ?: 'all';
        $status = match ($tab) {
            'confirmed', 'checked_in', 'checked_out' => $tab,
            default => null,
        };

        try {
            $response = $this->s3->groupBookings($status);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('GroupBookings/Index', [
            'groupBookings' => $response['data'] ?? [],
            'filters' => ['tab' => $tab],
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        try {
            $roomTypes = $this->s3->roomTypes();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'group-bookings.index');
        }

        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        return Inertia::render('GroupBookings/Create', [
            'roomTypes' => $roomTypes['data'] ?? [],
            'defaultCheckIn' => $today,
            'defaultCheckOut' => $tomorrow,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.guest_name' => ['required', 'string', 'max:150'],
            'rooms.*.room_type_id' => ['required', 'integer'],
        ]);

        $rooms = collect($data['rooms'])->map(fn (array $room) => [
            'guest_name' => $room['guest_name'],
            'room_type_id' => (int) $room['room_type_id'],
        ])->values()->all();

        try {
            $response = $this->s3->createGroupBooking([
                'group_name' => $data['group_name'],
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'] ?? null,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'rooms' => $rooms,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $groupId = (int) ($response['data']['id'] ?? 0);

        if ($groupId <= 0) {
            return back()->with('error', 'Group booking was not created.');
        }

        return redirect()
            ->route('group-bookings.show', $groupId)
            ->with('success', 'Group booking created. Assign rooms and check in.');
    }

    public function show(int $groupBooking): Response|RedirectResponse
    {
        try {
            $response = $this->s3->groupBooking($groupBooking);
            $rooms = $this->s3->rooms('available');
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'group-bookings.index');
        }

        $group = $response['data'] ?? [];
        $folios = [];

        foreach ($group['reservations'] ?? [] as $reservation) {
            $folioId = (int) ($reservation['folio_id'] ?? 0);

            if ($folioId > 0) {
                try {
                    $folioResponse = $this->s3->folio($folioId);
                    $folios[$folioId] = $folioResponse['data'] ?? [];
                } catch (ApiException) {
                    // Folio may not exist until check-in.
                }
            }
        }

        return Inertia::render('GroupBookings/Show', [
            'groupBooking' => $group,
            'availableRooms' => $rooms['data'] ?? [],
            'folios' => $folios,
            'lifecycleSteps' => GroupBookingLifecycleSteps::forGroup(),
            'lifecycleCurrentStep' => GroupBookingLifecycleSteps::currentStepKey($group, $folios),
            'allFoliosSettled' => GroupBookingLifecycleSteps::allFoliosSettled($group, $folios),
        ]);
    }

    public function checkIn(Request $request, int $groupBooking): RedirectResponse
    {
        $data = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.reservation_id' => ['required', 'integer'],
            'assignments.*.room_id' => ['required', 'integer'],
        ]);

        $assignments = collect($data['assignments'])->map(fn (array $row) => [
            'reservation_id' => (int) $row['reservation_id'],
            'room_id' => (int) $row['room_id'],
        ])->values()->all();

        try {
            $this->s3->checkInGroupBooking($groupBooking, $assignments);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Group checked in. Settle folios before group check-out.');
    }

    public function settleFolio(Request $request, int $groupBooking, int $folio): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->s3->settleFolio($folio, [
                'amount' => (float) $data['amount'],
                'payment_method' => $data['payment_method'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', "Folio #{$folio} settled.");
    }

    public function checkOut(int $groupBooking): RedirectResponse
    {
        try {
            $this->s3->checkOutGroupBooking($groupBooking);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('group-bookings.index')
            ->with('success', 'Group checked out successfully.');
    }
}
