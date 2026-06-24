<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class S1AuthService
{
    /**
     * @return array{valid: bool, user?: array<string, mixed>}|null
     */
    public function verify(string $bearerToken): ?array
    {
        $url = rtrim((string) config('services.s1_url'), '/').'/api/v1/auth/verify';

        try {
            $response = Http::timeout(8)
                ->retry(2, 200, throw: false)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$bearerToken,
                    'X-Service-Key' => (string) config('services.internal_key_current'),
                ])
                ->post($url)
                ->throw();
        } catch (RequestException) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || ! ($payload['valid'] ?? false)) {
            return null;
        }

        return $payload;
    }
}
