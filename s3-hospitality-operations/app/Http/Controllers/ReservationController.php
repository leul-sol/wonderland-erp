<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Http\Requests\CheckInReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ReservationController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function __construct(private readonly ReservationService $reservations)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Reservation::query()->with(['room', 'roomType', 'folio']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $paginator = $query->orderByDesc('id')->paginate(
            min((int) $request->input('per_page', 25), 100)
        );

        return response()->json([
            'data' => $paginator->getCollection()->map(fn ($r) => $this->reservationPayload($r))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservations->create($request->validated());

        return response()->json(['data' => $this->reservationPayload($reservation)], 201);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return response()->json(['data' => $this->reservationPayload($reservation)]);
    }

    public function checkIn(CheckInReservationRequest $request, Reservation $reservation): JsonResponse
    {
        try {
            $reservation = $this->reservations->checkIn($reservation, (int) $request->validated('room_id'));
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->reservationPayload($reservation)]);
    }

    public function checkOut(Reservation $reservation): JsonResponse
    {
        try {
            $reservation = $this->reservations->checkOut($reservation);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->reservationPayload($reservation)]);
    }
}
