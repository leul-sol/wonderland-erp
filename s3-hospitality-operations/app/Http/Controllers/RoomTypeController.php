<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    use RespondsWithApiErrors;

    public function index(): JsonResponse
    {
        return response()->json(['data' => RoomType::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'code' => ['required', 'string', 'max:10'],
            'base_rate' => ['required', 'numeric', 'gte:0'],
            'max_occupancy' => ['required', 'integer', 'min:1'],
        ]);

        $type = RoomType::query()->create($data + ['is_active' => true]);

        return response()->json(['data' => $type], 201);
    }

    public function update(Request $request, RoomType $roomType): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'base_rate' => ['sometimes', 'numeric', 'gte:0'],
            'max_occupancy' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $roomType->update($data);

        return response()->json(['data' => $roomType->fresh()]);
    }
}
