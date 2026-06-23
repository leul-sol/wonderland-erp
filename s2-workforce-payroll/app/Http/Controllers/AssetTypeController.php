<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreAssetTypeRequest;
use App\Http\Requests\UpdateAssetTypeRequest;
use App\Models\AssetType;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class AssetTypeController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly AssetService $assets)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => AssetType::query()->orderBy('name')->get()
                ->map(fn ($t) => $this->assetTypePayload($t))->values(),
        ]);
    }

    public function store(StoreAssetTypeRequest $request): JsonResponse
    {
        $type = $this->assets->createType($request->validated());

        return response()->json(['data' => $this->assetTypePayload($type)], 201);
    }

    public function show(AssetType $assetType): JsonResponse
    {
        return response()->json(['data' => $this->assetTypePayload($assetType)]);
    }

    public function update(UpdateAssetTypeRequest $request, AssetType $assetType): JsonResponse
    {
        $type = $this->assets->updateType($assetType, $request->validated());

        return response()->json(['data' => $this->assetTypePayload($type)]);
    }

    public function destroy(AssetType $assetType): JsonResponse
    {
        try {
            $this->assets->deleteType($assetType);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(null, 204);
    }
}
