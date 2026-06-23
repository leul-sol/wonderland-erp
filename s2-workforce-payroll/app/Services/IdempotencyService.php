<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdempotencyService
{
    public function findReplay(string $key, string $endpoint, string $requestHash): ?IdempotencyKey
    {
        $record = IdempotencyKey::query()
            ->where('idempotency_key', $key)
            ->where('endpoint', $endpoint)
            ->first();

        if ($record === null) {
            return null;
        }

        if ($record->request_hash !== $requestHash) {
            return null;
        }

        if ($record->expires_at->isPast()) {
            return null;
        }

        return $record;
    }

    public function store(
        string $key,
        string $endpoint,
        string $requestHash,
        array $responseBody,
        int $statusCode,
        int $ttlDays = 7,
    ): void {
        IdempotencyKey::query()->create([
            'idempotency_key' => $key,
            'endpoint' => $endpoint,
            'request_hash' => $requestHash,
            'response_body' => $responseBody,
            'status_code' => $statusCode,
            'created_at' => now(),
            'expires_at' => now()->addDays($ttlDays),
        ]);
    }

    public function requestHash(Request $request): string
    {
        $payload = json_encode($request->all(), JSON_THROW_ON_ERROR);

        return hash('sha256', $request->method().'|'.$request->path().'|'.$payload);
    }

    public function replayResponse(IdempotencyKey $record): JsonResponse
    {
        return response()->json($record->response_body, $record->status_code);
    }
}
