<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class S4FinanceClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function postJournal(array $payload, string $idempotencyKey): array
    {
        $url = rtrim((string) config('services.s4_url'), '/').'/api/v1/journal-entries';

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Service-Key' => (string) config('services.internal_key_current'),
                    'Idempotency-Key' => $idempotencyKey,
                ])
                ->post($url, $payload)
                ->throw();
        } catch (RequestException $exception) {
            $body = $exception->response?->json();
            $message = is_array($body) ? ($body['error']['message'] ?? $exception->getMessage()) : $exception->getMessage();

            throw new RuntimeException('S4 journal post failed: '.$message, 0, $exception);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('S4 journal post returned invalid response.');
        }

        return $json;
    }
}
