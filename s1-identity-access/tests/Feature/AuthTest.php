<?php

namespace Tests\Feature;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.internal_key_current' => 'test-service-key']);
        $this->seed();
    }

    public function test_login_returns_tokens_for_super_admin(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => $this->superAdminPassword(),
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_expired_password_restricts_protected_routes(): void
    {
        $user = User::query()->where('username', 'super.admin')->firstOrFail();
        $user->password_changed_at = now()->subDays(120);
        $user->must_change_password = true;
        $user->save();

        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => $this->superAdminPassword(),
        ])->assertOk()
            ->assertJsonPath('must_change_password', true);

        $token = $login->json('access_token');

        $this->postJson('/api/v1/roles', [
            'name' => 'temp_auditor',
            'display_name' => 'Temp Auditor',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(403)
            ->assertJsonPath('error.code', 'PASSWORD_CHANGE_REQUIRED');

        $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();
    }

    public function test_verify_requires_service_key(): void
    {
        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => $this->superAdminPassword(),
        ]);

        $token = $login->json('access_token');

        $this->postJson('/api/v1/auth/verify', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(401);

        $this->postJson('/api/v1/auth/verify', [], [
            'Authorization' => 'Bearer '.$token,
            'X-Service-Key' => 'test-service-key',
        ])->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonStructure(['user' => ['roles', 'permissions', 'name']]);
    }

    public function test_verify_rejects_deactivated_user(): void
    {
        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => $this->superAdminPassword(),
        ]);

        $token = $login->json('access_token');
        $user = User::query()->where('username', 'super.admin')->firstOrFail();
        $user->is_active = false;
        $user->save();

        $this->postJson('/api/v1/auth/verify', [], [
            'Authorization' => 'Bearer '.$token,
            'X-Service-Key' => 'test-service-key',
        ])->assertStatus(401)
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'deactivated');
    }

    public function test_me_requires_valid_jwt(): void
    {
        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => $this->superAdminPassword(),
        ]);

        $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$login->json('access_token'),
        ])->assertOk()
            ->assertJsonPath('username', 'super.admin');
    }

    public function test_change_password_clears_must_change_flag_and_trims_history(): void
    {
        $currentPassword = $this->superAdminPassword();
        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => $currentPassword,
        ])->assertOk();

        $token = $login->json('access_token');
        $user = User::query()->where('username', 'super.admin')->firstOrFail();
        $user->must_change_password = true;
        $user->save();

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => $currentPassword,
            'password' => 'NewSecurePass!10',
            'password_confirmation' => 'NewSecurePass!10',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', 'Password updated.');

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertSame(1, PasswordHistory::query()->where('user_id', $user->id)->count());
    }
}
