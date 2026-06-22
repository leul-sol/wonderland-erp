<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesBiTracking;
use App\Http\Requests\UpdateRtmEntryRequest;
use App\Models\RtmEntry;
use App\Services\RtmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RtmController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesBiTracking;

    public function __construct(private readonly RtmService $rtm)
    {
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
}
