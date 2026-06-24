<?php

namespace App\Services\Api;

use App\Exceptions\ApiException;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GatewayClient
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function json(string $method, string $path, array $payload = [], array $headers = []): array
    {
        $response = $this->send($method, $path, $payload, $headers);

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ApiException
     */
    public function send(string $method, string $path, array $payload = [], array $headers = []): Response
    {
        $this->auth->ensureFreshToken();

        $token = $this->auth->accessToken();

        if ($token === null) {
            throw new ApiException('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $response = $this->request($method, $path, $token, $payload, $headers);

        if ($response->status() === 401 && $this->auth->attemptRefresh()) {
            $token = $this->auth->accessToken();

            if ($token !== null) {
                $response = $this->request($method, $path, $token, $payload, $headers);
            }
        }

        if (! $response->successful()) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    private function request(string $method, string $path, string $token, array $payload, array $headers): Response
    {
        $client = Http::baseUrl(config('portal.gateway_url'))
            ->acceptJson()
            ->timeout(60)
            ->withToken($token)
            ->withHeaders(array_merge([
                'X-Request-Id' => (string) Str::uuid(),
            ], $headers));

        $path = '/'.ltrim($path, '/');

        return match (strtoupper($method)) {
            'GET' => $client->get($path, $payload),
            'POST' => $client->post($path, $payload),
            'PUT' => $client->put($path, $payload),
            'PATCH' => $client->patch($path, $payload),
            'DELETE' => $client->delete($path, $payload),
            default => throw new ApiException('INVALID_METHOD', "Unsupported HTTP method: {$method}", 500),
        };
    }
}
