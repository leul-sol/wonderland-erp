<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Auth\Access\AuthorizationException;

class RefreshTokenService
{
    public function issue(User $user, Request $request): string
    {
        $raw = Str::random(64);

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addMinutes(config('jwt.refresh_ttl')),
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return $raw;
    }

    public function rotate(string $rawToken, Request $request): array
    {
        $stored = $this->findValid($rawToken);

        $stored->update(['revoked_at' => now()]);

        $user = $stored->user()->firstOrFail();

        return [$user, $this->issue($user, $request)];
    }

    public function findValid(string $rawToken): RefreshToken
    {
        $stored = RefreshToken::query()
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();

        if ($stored === null || ! $stored->isValid()) {
            throw new AuthorizationException('Invalid refresh token.');
        }

        return $stored;
    }

    public function revokeAllForUser(int $userId): void
    {
        RefreshToken::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
