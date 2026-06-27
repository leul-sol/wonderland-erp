<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Services\Api\S1AuthClient;
use App\Support\PortalNavigationCache;
use Illuminate\Support\Facades\Session;

class PortalAuthService
{
    private const ACCESS_TOKEN = 'portal.access_token';

    private const REFRESH_TOKEN = 'portal.refresh_token';

    private const EXPIRES_AT = 'portal.expires_at';

    private const PERMISSIONS = 'portal.permissions';

    private const USER = 'portal.user';

    private bool $tokenChecked = false;

    public function __construct(
        private readonly S1AuthClient $s1,
    ) {
    }

    public function isAuthenticated(): bool
    {
        return is_string(Session::get(self::ACCESS_TOKEN)) && Session::get(self::ACCESS_TOKEN) !== '';
    }

    public function accessToken(): ?string
    {
        $token = Session::get(self::ACCESS_TOKEN);

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        $user = Session::get(self::USER);

        return is_array($user) ? $user : null;
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        $permissions = Session::get(self::PERMISSIONS, []);

        return is_array($permissions) ? array_values(array_filter($permissions, 'is_string')) : [];
    }

    public function hasAnyPermission(array $needles): bool
    {
        if ($needles === []) {
            return true;
        }

        $granted = array_flip($this->permissions());

        foreach ($needles as $permission) {
            if (isset($granted[$permission])) {
                return true;
            }
        }

        return false;
    }

    public function mustChangePassword(): bool
    {
        $user = $this->user();

        return is_array($user) && (bool) ($user['must_change_password'] ?? false);
    }

    /**
     * @throws ApiException
     */
    public function changePassword(string $currentPassword, string $newPassword): void
    {
        $token = $this->accessToken();

        if ($token === null) {
            throw new ApiException('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $this->s1->changePassword($token, $currentPassword, $newPassword);
    }

    /**
     * @throws ApiException
     */
    public function login(string $username, string $password): void
    {
        $tokens = $this->s1->login($username, $password);
        $this->storeTokens($tokens);

        $user = $tokens['user'] ?? null;

        if (is_array($user)) {
            $this->storeUserFromPayload($user);
        } else {
            $this->refreshProfile();
        }

        PortalNavigationCache::forget();
    }

    public function logout(): void
    {
        $token = $this->accessToken();

        if ($token !== null) {
            try {
                $this->s1->logout($token);
            } catch (ApiException) {
                // Session ends locally even if S1 revoke fails.
            }
        }

        PortalNavigationCache::forget();
        Session::invalidate();
        Session::regenerateToken();
        $this->tokenChecked = false;
    }

    public function ensureFreshToken(): void
    {
        if ($this->tokenChecked) {
            return;
        }

        $this->tokenChecked = true;

        if (! $this->isAuthenticated()) {
            return;
        }

        $expiresAt = (int) Session::get(self::EXPIRES_AT, 0);
        $buffer = (int) config('portal.refresh_before_expiry_seconds', 120);

        if ($expiresAt > 0 && $expiresAt - time() > $buffer) {
            return;
        }

        $this->attemptRefresh();
    }

    public function attemptRefresh(): bool
    {
        $refreshToken = Session::get(self::REFRESH_TOKEN);

        if (! is_string($refreshToken) || $refreshToken === '') {
            return false;
        }

        try {
            $tokens = $this->s1->refresh($refreshToken);
            $this->storeTokens($tokens);

            return true;
        } catch (ApiException) {
            Session::invalidate();
            Session::regenerateToken();

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $tokens
     */
    private function storeTokens(array $tokens): void
    {
        Session::put(self::ACCESS_TOKEN, (string) ($tokens['access_token'] ?? ''));
        Session::put(self::REFRESH_TOKEN, (string) ($tokens['refresh_token'] ?? ''));

        $expiresIn = (int) ($tokens['expires_in'] ?? 3600);
        Session::put(self::EXPIRES_AT, time() + max(60, $expiresIn));
    }

    /**
     * @throws ApiException
     */
    private function refreshProfile(): void
    {
        $token = $this->accessToken();

        if ($token === null) {
            throw new ApiException('UNAUTHENTICATED', 'Login did not return an access token.', 401);
        }

        $profile = $this->s1->me($token);

        Session::put(self::USER, [
            'id' => $profile['id'] ?? null,
            'username' => $profile['username'] ?? null,
            'name' => $profile['name'] ?? null,
            'email' => $profile['email'] ?? null,
            'roles' => $profile['roles'] ?? [],
            'employee_id' => $profile['employee_id'] ?? null,
            'must_change_password' => (bool) ($profile['must_change_password'] ?? false),
        ]);

        $permissions = $profile['permissions'] ?? [];
        Session::put(self::PERMISSIONS, is_array($permissions) ? $permissions : []);
    }

    /**
     * @param  array<string, mixed>  $user
     */
    private function storeUserFromPayload(array $user): void
    {
        Session::put(self::USER, [
            'id' => $user['id'] ?? null,
            'username' => $user['username'] ?? null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'roles' => $user['roles'] ?? [],
            'employee_id' => $user['employee_id'] ?? null,
            'must_change_password' => (bool) ($user['must_change_password'] ?? false),
        ]);

        $permissions = $user['permissions'] ?? [];
        Session::put(self::PERMISSIONS, is_array($permissions) ? $permissions : []);
    }
}
