<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->paginateLogs($this->filteredQuery($request), $request));
    }

    public function byUser(Request $request, int $user): JsonResponse
    {
        $request->merge(['user_id' => $user]);

        return response()->json($this->paginateLogs($this->filteredQuery($request), $request));
    }

    private function filteredQuery(Request $request)
    {
        $query = AuditLog::query()->with('user:id,username,display_name');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        return $query->orderByDesc('id');
    }

    private function paginateLogs($query, Request $request): array
    {
        $paginator = $query->paginate(min((int) $request->input('per_page', 25), 100));

        return [
            'data' => $paginator->getCollection()->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'username' => $log->user->username,
                    'display_name' => $log->user->display_name,
                ] : null,
                'event' => $log->event,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'payload' => $log->payload,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
