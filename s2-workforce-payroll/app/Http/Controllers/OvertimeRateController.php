<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\UpdateOvertimeRateRequest;
use App\Models\OvertimeRate;
use Illuminate\Http\JsonResponse;

class OvertimeRateController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => OvertimeRate::query()->orderBy('category')->get()
                ->map(fn ($r) => $this->overtimeRatePayload($r))->values(),
        ]);
    }

    public function update(UpdateOvertimeRateRequest $request, OvertimeRate $overtimeRate): JsonResponse
    {
        $overtimeRate->update(['multiplier' => $request->validated('multiplier')]);

        return response()->json(['data' => $this->overtimeRatePayload($overtimeRate->fresh())]);
    }
}
