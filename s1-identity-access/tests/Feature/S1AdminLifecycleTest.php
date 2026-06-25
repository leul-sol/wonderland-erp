<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class S1AdminLifecycleTest extends TestCase
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
            'password' => $this->superAdminPassword(),
        ]);

        $login->assertOk();
        $this->token = $login->json('access_token');
    }

    public function test_user_reactivation_via_update(): void
    {
        $user = User::query()->create([
            'username' => 'inactive.staff',
            'email' => 'inactive@wonderlandhotel.local',
            'password' => bcrypt('Welcome123!'),
            'display_name' => 'Inactive Staff',
            'is_active' => false,
            'password_changed_at' => now(),
        ]);

        $this->patchJson('/api/v1/users/'.$user->id, [
            'is_active' => true,
        ], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'inactive.staff',
            'password' => 'Welcome123!',
        ])->assertOk();
    }

    public function test_deactivate_blocks_subsequent_login(): void
    {
        $user = User::query()->create([
            'username' => 'deactivate.me',
            'email' => 'deactivate@wonderlandhotel.local',
            'password' => bcrypt('Welcome123!'),
            'display_name' => 'Deactivate Me',
            'is_active' => true,
            'password_changed_at' => now(),
        ]);

        $this->postJson('/api/v1/users/'.$user->id.'/deactivate', [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'deactivate.me',
            'password' => 'Welcome123!',
        ])->assertStatus(403)
            ->assertJsonPath('error.message', 'Account is deactivated.');
    }

    public function test_department_scoped_role_assignment_persists(): void
    {
        $user = User::query()->create([
            'username' => 'dept.head',
            'email' => 'dept@wonderlandhotel.local',
            'password' => bcrypt('Welcome123!'),
            'display_name' => 'Department Head',
            'is_active' => true,
            'password_changed_at' => now(),
        ]);

        $roleId = Role::query()->where('name', 'department_head')->value('id');

        $this->putJson('/api/v1/users/'.$user->id.'/roles', [
            'roles' => [
                ['role_id' => $roleId, 'department_id' => 10],
            ],
        ], $this->authHeaders())->assertOk();

        $pivot = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame(10, (int) $pivot->department_id);
    }

    public function test_reset_password_and_force_logout(): void
    {
        $user = User::query()->create([
            'username' => 'reset.target',
            'email' => 'reset@wonderlandhotel.local',
            'password' => bcrypt('Welcome123!'),
            'display_name' => 'Reset Target',
            'is_active' => true,
            'password_changed_at' => now(),
        ]);

        $this->postJson('/api/v1/users/'.$user->id.'/reset-password', [
            'password' => 'AnotherPass!10',
            'must_change_password' => true,
        ], $this->authHeaders())->assertOk();

        $user->refresh();
        $this->assertTrue($user->must_change_password);

        $this->postJson('/api/v1/users/'.$user->id.'/force-logout', [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('message', 'Active sessions revoked.');
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
        ];
    }
}
