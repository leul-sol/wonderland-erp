<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
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

    public function show(Room $room): JsonResponse
    {
        return response()->json(['data' => $this->roomPayload($room)]);
    }
}
