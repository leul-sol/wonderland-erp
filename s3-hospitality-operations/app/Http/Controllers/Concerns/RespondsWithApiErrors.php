<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithApiErrors
{
    protected function error(string $code, string $message, int $status, mixed $details = null): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details ?? new \stdClass,
            ],
        ], $status);
    }
}
