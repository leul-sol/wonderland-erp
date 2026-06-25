<?php

namespace App\Services\Api;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class S1AuthClient
{
    public function __construct(
        private readonly string $gatewayUrl = '',
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function login(string $username, string $password): array
    {
        $response = $this->http()->post('/s1/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            throw ApiException::fromResponse($response);
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function refresh(string $refreshToken): array
    {
        $response = $this->http()->post('/s1/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            throw ApiException::fromResponse($response);
        }

        return $response->json() ?? [];
    }

    /**
     * @throws ApiException
     */
    public function logout(string $accessToken): void
    {
        $response = $this->http($accessToken)->post('/s1/api/v1/auth/logout');

        if (! $response->successful()) {
            throw ApiException::fromResponse($response);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function me(string $accessToken): array
    {
        $response = $this->http($accessToken)->get('/s1/api/v1/auth/me');

        if (! $response->successful()) {
            throw ApiException::fromResponse($response);
        }

        return $response->json() ?? [];
    }

    /**
     * @throws ApiException
     */
    public function changePassword(string $accessToken, string $currentPassword, string $password): void
    {
        $response = $this->http($accessToken)->post('/s1/api/v1/auth/change-password', [
            'current_password' => $currentPassword,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        if (! $response->successful()) {
            throw ApiException::fromResponse($response);
        }
    }

    private function http(?string $accessToken = null): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::baseUrl($this->gatewayUrl !== '' ? $this->gatewayUrl : config('portal.gateway_url'))
            ->acceptJson()
            ->timeout(30)
            ->withHeaders([
                'X-Request-Id' => (string) Str::uuid(),
            ]);

        if ($accessToken !== null) {
            $request = $request->withToken($accessToken);
        }

        return $request;
    }
}
