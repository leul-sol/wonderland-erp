<?php

namespace Tests\Feature;

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
            'password' => 'ChangeMeNow!10',
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

    public function test_verify_requires_service_key(): void
    {
        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => 'ChangeMeNow!10',
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

    public function test_me_requires_valid_jwt(): void
    {
        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => 'ChangeMeNow!10',
        ]);

        $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$login->json('access_token'),
        ])->assertOk()
            ->assertJsonPath('username', 'super.admin');
    }
}
