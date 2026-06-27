<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesBiTracking;
use App\Http\Requests\RecordUatResultRequest;
use App\Http\Requests\UpdateRtmEntryRequest;
use App\Models\RtmEntry;
use App\Models\UatScenario;
use App\Services\RtmService;
use App\Services\UatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RtmController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesBiTracking;

    public function __construct(
        private readonly RtmService $rtm,
        private readonly UatService $uat,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->rtm->list(
            $request->input('system'),
            $request->input('status'),
        );

        return response()->json([
            'data' => $result['data']->map(fn ($e) => $this->rtmPayload($e))->values(),
            'meta' => $result['meta'],
        ]);
    }

    public function show(RtmEntry $rtmEntry): JsonResponse
    {
        return response()->json(['data' => $this->rtmPayload($rtmEntry)]);
    }

    public function update(UpdateRtmEntryRequest $request, RtmEntry $rtmEntry): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);
        $entry = $this->rtm->update($rtmEntry, $request->validated(), $userId);

        return response()->json(['data' => $this->rtmPayload($entry)]);
    }

    public function uatChecklist(RtmEntry $rtmEntry): JsonResponse
    {
        $scenarios = UatScenario::query()
            ->where('requirement_key', $rtmEntry->requirement_key)
            ->orderBy('scenario_key')
            ->get();

        return response()->json([
            'data' => [
                'rtm_entry' => $this->rtmPayload($rtmEntry),
                'uat_scenarios' => $scenarios->map(fn ($s) => $this->uatPayload($s))->values(),
            ],
        ]);
    }

    public function recordUatResult(
        RecordUatResultRequest $request,
        RtmEntry $rtmEntry,
        UatScenario $uatScenario,
    ): JsonResponse {
        if ($uatScenario->requirement_key !== $rtmEntry->requirement_key) {
            return $this->error('VALIDATION_ERROR', 'UAT scenario does not belong to this RTM entry.', 422);
        }

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
