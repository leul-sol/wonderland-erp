<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.internal_key_current' => 'test-service-key']);
        $this->seed();

        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => 'ChangeMeNow!10',
        ]);

        $login->assertOk();
        $this->token = $login->json('access_token');
    }

    public function test_users_index_requires_auth(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    public function test_users_index_with_jwt(): void
    {
        $this->getJson('/api/v1/users', $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total']]);
    }

    public function test_users_index_with_service_key(): void
    {
        $this->getJson('/api/v1/users', [
            'X-Service-Key' => 'test-service-key',
        ])->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_create_user(): void
    {
        $response = $this->postJson('/api/v1/users', [
            'username' => 'frontdesk.user',
            'email' => 'frontdesk@wonderlandhotel.local',
            'password' => 'Welcome123!',
            'display_name' => 'Front Desk User',
        ], $this->authHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.username', 'frontdesk.user');
    }

    public function test_assign_roles_to_user(): void
    {
        $user = User::query()->where('username', 'frontdesk.user')->first();

        if ($user === null) {
            $user = User::query()->create([
                'username' => 'frontdesk.user',
                'email' => 'frontdesk@wonderlandhotel.local',
                'password' => bcrypt('Welcome123!'),
                'display_name' => 'Front Desk User',
                'is_active' => true,
                'password_changed_at' => now(),
            ]);
        }

        $roleId = \App\Models\Role::query()->where('name', 'receptionist')->value('id');

        $this->putJson('/api/v1/users/'.$user->id.'/roles', [
            'roles' => [
                ['role_id' => $roleId, 'department_id' => null],
            ],
        ], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.roles.0.name', 'receptionist');
    }

    public function test_roles_and_permissions_index(): void
    {
        $this->getJson('/api/v1/roles', $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->getJson('/api/v1/permissions', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('meta.total', 75);
    }

    public function test_audit_logs_index(): void
    {
        $this->getJson('/api/v1/audit-logs', $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_forbidden_without_permission(): void
    {
        $user = User::query()->create([
            'username' => 'limited.user',
            'email' => 'limited@wonderlandhotel.local',
            'password' => bcrypt('Welcome123!'),
            'display_name' => 'Limited User',
            'is_active' => true,
            'password_changed_at' => now(),
        ]);

        $roleId = \App\Models\Role::query()->where('name', 'report_viewer')->value('id');
        $user->roles()->sync([$roleId => ['assigned_at' => now()]]);

        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'limited.user',
            'password' => 'Welcome123!',
        ])->assertOk();

        $this->postJson('/api/v1/users', [
            'username' => 'another.user',
            'email' => 'another@wonderlandhotel.local',
            'password' => 'Welcome123!',
        ], [
            'Authorization' => 'Bearer '.$login->json('access_token'),
        ])->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
        ];
    }
}
