<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Http\Requests\CheckInGroupBookingRequest;
use App\Http\Requests\StoreGroupBookingRequest;
use App\Models\GroupBooking;
use App\Services\GroupBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupBookingController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function __construct(private readonly GroupBookingService $groups)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = GroupBooking::query()->with('reservations.roomType')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($group) => $this->groupBookingPayload($group))->values(),
        ]);
    }

    public function store(StoreGroupBookingRequest $request): JsonResponse
    {
        try {
            $group = $this->groups->create($request->validated());
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->groupBookingPayload($group)], 201);
    }

    public function show(GroupBooking $groupBooking): JsonResponse
    {
        return response()->json(['data' => $this->groupBookingPayload($groupBooking->load('reservations.roomType', 'reservations.room'))]);
    }

    public function checkIn(CheckInGroupBookingRequest $request, GroupBooking $groupBooking): JsonResponse
    {
        try {
            $group = $this->groups->checkIn($groupBooking, $request->validated('assignments'));
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->groupBookingPayload($group)]);
    }

    public function checkOut(GroupBooking $groupBooking): JsonResponse
    {
        try {
            $group = $this->groups->checkOut($groupBooking);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->groupBookingPayload($group)]);
    }
}
