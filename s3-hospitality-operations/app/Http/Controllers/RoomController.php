<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Models\MenuItem;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RoomController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function index(Request $request): JsonResponse
    {
        $query = Room::query()->with('roomType')->orderBy('room_number');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', (int) $request->input('room_type_id'));
        }

        return response()->json([
            'data' => $query->get()->map(fn (Room $room) => $this->roomPayload($room))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:10', 'unique:rooms,room_number'],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'floor' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'in:available,occupied,maintenance,cleaning'],
        ]);

        $room = Room::query()->create($data + ['status' => $data['status'] ?? 'available']);

        return response()->json(['data' => $this->roomPayload($room)], 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json(['data' => $this->roomPayload($room)]);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'room_number' => ['sometimes', 'string', 'max:10'],
            'room_type_id' => ['sometimes', 'integer', 'exists:room_types,id'],
            'floor' => ['nullable', 'string', 'max:10'],
        ]);

        $room->update($data);

        return response()->json(['data' => $this->roomPayload($room->fresh('roomType'))]);
    }

    public function updateStatus(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:available,maintenance,cleaning'],
        ]);

        if ($room->status === 'occupied') {
            return $this->error('INVALID_STATE', 'Cannot change status of an occupied room via this endpoint.', 422);
        }

        $room->update(['status' => $data['status']]);

        return response()->json(['data' => $this->roomPayload($room->fresh('roomType'))]);
    }
}
