<?php

namespace App\Http\Middleware;

use App\Services\IdempotencyService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotent
{
    public function __construct(private readonly IdempotencyService $idempotency)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if ($key === null || $key === '') {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Idempotency-Key header is required.',
                ],
            ], 422);
        }

        $endpoint = $request->method().' '.$request->path();
        $hash = $this->idempotency->requestHash($request);
        $replay = $this->idempotency->findReplay($key, $endpoint, $hash);

        if ($replay !== null) {
            return $this->idempotency->replayResponse($replay);
        }

        $response = $next($request);

        if ($response instanceof JsonResponse && $response->isSuccessful()) {
            $body = $response->getData(true);
            if (is_array($body)) {
                $this->idempotency->store($key, $endpoint, $hash, $body, $response->getStatusCode());
            }
        }

        return $response;
    }
}
