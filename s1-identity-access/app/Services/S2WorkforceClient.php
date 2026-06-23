<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class S2WorkforceClient
{
    /**
     * @return array<string, mixed>
     */
    public function getEmployee(int $employeeId): array
    {
        $url = rtrim((string) config('services.s2_url'), '/')."/api/v1/internal/employees/{$employeeId}";

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Service-Key' => (string) config('services.internal_key_current'),
                ])
                ->get($url)
                ->throw();
        } catch (RequestException $exception) {
            $body = $exception->response?->json();
            $message = is_array($body) ? ($body['error']['message'] ?? $exception->getMessage()) : $exception->getMessage();

            throw new RuntimeException('S2 employee fetch failed: '.$message, 0, $exception);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('S2 employee fetch returned invalid response.');
        }

        return $json;
    }
}
