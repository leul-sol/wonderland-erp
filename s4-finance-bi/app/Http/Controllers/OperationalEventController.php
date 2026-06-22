<?php

namespace App\Http\Controllers;

use App\Models\OperationalEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperationalEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OperationalEvent::query()->orderByDesc('occurred_at');

        if ($request->filled('channel')) {
            $query->where('channel', $request->string('channel'));
        }

        if ($request->filled('source_system')) {
            $query->where('source_system', $request->string('source_system'));
        }

        $limit = min((int) $request->input('limit', 50), 200);

        return response()->json([
            'data' => $query->limit($limit)->get()->map(fn (OperationalEvent $event) => [
                'id' => $event->id,
                'channel' => $event->channel,
                'source_system' => $event->source_system,
                'request_id' => $event->request_id,
                'payload' => $event->payload,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
