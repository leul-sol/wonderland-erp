<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class PositionController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly PositionService $positions)
    {
    }

    public function index(): JsonResponse
    {
        $items = Position::query()->with('department')->orderBy('title')->get();

        return response()->json([
            'data' => $items->map(fn ($p) => $this->positionPayload($p))->values(),
        ]);
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = $this->positions->create($request->validated());

        return response()->json(['data' => $this->positionPayload($position)], 201);
    }

    public function show(Position $position): JsonResponse
    {
        return response()->json(['data' => $this->positionPayload($position)]);
    }

    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $position = $this->positions->update($position, $request->validated());

        return response()->json(['data' => $this->positionPayload($position)]);
    }

    public function destroy(Position $position): JsonResponse
    {
        try {
            $this->positions->delete($position);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(null, 204);
    }
}
