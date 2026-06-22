<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesBiTracking;
use App\Http\Requests\RecordUatResultRequest;
use App\Models\UatScenario;
use App\Services\UatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UatController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesBiTracking;

    public function __construct(private readonly UatService $uat)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->uat->list(
            $request->input('system'),
            $request->input('status'),
        );

        return response()->json([
            'data' => $result['data']->map(fn ($s) => $this->uatPayload($s))->values(),
            'meta' => $result['meta'],
        ]);
    }

    public function show(UatScenario $uatScenario): JsonResponse
    {
        return response()->json(['data' => $this->uatPayload($uatScenario)]);
    }

    public function recordResult(RecordUatResultRequest $request, UatScenario $uatScenario): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);
        $scenario = $this->uat->recordResult(
            $uatScenario,
            $request->validated('status'),
            $request->validated('notes'),
            $userId,
        );

        return response()->json(['data' => $this->uatPayload($scenario)]);
    }
}
