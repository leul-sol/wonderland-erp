<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreOffboardingRequest;
use App\Http\Requests\UpdateOffboardingRequest;
use App\Models\Employee;
use App\Models\OffboardingRecord;
use App\Services\OffboardingService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class OffboardingController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly OffboardingService $offboarding)
    {
    }

    public function index(): JsonResponse
    {
        $records = OffboardingRecord::query()->with('employee')->orderByDesc('id')->get();

        return response()->json([
            'data' => $records->map(fn ($r) => $this->offboardingPayload($r))->values(),
        ]);
    }

    public function show(OffboardingRecord $offboardingRecord): JsonResponse
    {
        return response()->json(['data' => $this->offboardingPayload($offboardingRecord)]);
    }

    public function store(StoreOffboardingRequest $request, Employee $employee): JsonResponse
    {
        try {
            $record = $this->offboarding->initiate($employee, $request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->offboardingPayload($record)], 201);
    }

    public function update(UpdateOffboardingRequest $request, OffboardingRecord $offboardingRecord): JsonResponse
    {
        try {
            $record = $this->offboarding->update($offboardingRecord, $request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->offboardingPayload($record)]);
    }
}
