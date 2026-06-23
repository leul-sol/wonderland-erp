<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use App\Models\Role;
use App\Models\User;
use App\Services\EmployeeEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class S1IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.internal_key_current' => 'test-service-key']);
        $this->seed();
    }

    public function test_openapi_document_is_available(): void
    {
        $this->getJson('/api/v1/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonStructure(['paths' => ['/auth/verify'], 'components' => ['schemas' => ['LoginRequest', 'TokenResponse']]]);
    }

    public function test_permissions_by_domain_route(): void
    {
        $token = $this->loginToken();

        $this->getJson('/api/v1/permissions/identity', $this->authHeaders($token))
            ->assertOk()
            ->assertJsonPath('meta.total', 15);
    }

    public function test_audit_logs_by_user_route(): void
    {
        $token = $this->loginToken();
        $userId = User::query()->where('username', 'super.admin')->value('id');

        $this->getJson('/api/v1/audit-logs/user/'.$userId, $this->authHeaders($token))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_system_role_delete_returns_forbidden(): void
    {
        $token = $this->loginToken();
        $roleId = Role::query()->where('name', 'super_admin')->value('id');

        $this->deleteJson('/api/v1/roles/'.$roleId, [], $this->authHeaders($token))
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_role_permission_sync_writes_outbox_row(): void
    {
        $token = $this->loginToken();
        $role = Role::query()->where('name', 'report_viewer')->firstOrFail();
        $permissionIds = $role->permissions()->pluck('permissions.id')->take(2)->all();

        $this->putJson('/api/v1/roles/'.$role->id.'/permissions', [
            'permission_ids' => $permissionIds,
        ], $this->authHeaders($token))->assertOk();

        $this->assertDatabaseHas('event_outbox', [
            'event' => config('events.channels.permission_changed'),
            'status' => 'pending',
        ]);
    }

    public function test_employee_created_event_provisions_user(): void
    {
        app(EmployeeEventService::class)->handleCreated([
            'employee_id' => 5001,
            'full_name' => 'Jane Reception',
            'department_id' => 10,
            'default_role' => 'receptionist',
        ]);

        $this->assertDatabaseHas('users', [
            'employee_id' => 5001,
            'display_name' => 'Jane Reception',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.provisioned',
        ]);
    }

    public function test_employee_archived_event_deactivates_user(): void
    {
        $service = app(EmployeeEventService::class);
        $service->handleCreated([
            'employee_id' => 5002,
            'full_name' => 'Temp Staff',
            'department_id' => null,
            'default_role' => 'cashier',
        ]);

        $service->handleArchived([
            'employee_id' => 5002,
            'reason' => 'Offboarded',
        ]);

        $this->assertDatabaseHas('users', [
            'employee_id' => 5002,
            'is_active' => false,
        ]);
    }

    public function test_general_manager_has_read_permissions_after_seed(): void
    {
        $roleId = Role::query()->where('name', 'general_manager')->value('id');

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $roleId,
        ]);

        $readPermissionId = \App\Models\Permission::query()
            ->where('action', 'S1.identity.users.read')
            ->value('id');

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $roleId,
            'permission_id' => $readPermissionId,
        ]);
    }

    private function loginToken(): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'username' => 'super.admin',
            'password' => $this->superAdminPassword(),
        ])->json('access_token');
    }

    private function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
