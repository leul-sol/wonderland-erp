<?php

namespace Tests\Feature;

use App\Services\Api\S1AdminClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S1.identity.users.read',
            'S1.identity.users.create',
            'S1.identity.users.deactivate',
            'S1.identity.users.assign_role',
            'S1.identity.users.update',
            'S1.identity.roles.read',
            'S1.identity.roles.create',
            'S1.identity.roles.update',
            'S1.identity.roles.delete',
            'S1.identity.roles.sync_permissions',
            'S1.identity.permissions.read',
            'S1.identity.audit_logs.read',
        ]);
    }

    public function test_users_index_renders_paginated_list(): void
    {
        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('users')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'username' => 'finance.manager',
                    'display_name' => 'Finance Manager',
                    'email' => 'finance@wonderlandhotel.local',
                    'is_active' => true,
                    'roles' => [['name' => 'finance_manager', 'display_name' => 'Finance Manager']],
                ]],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 25,
                    'total' => 1,
                ],
            ]);
        });

        $response = $this->get('/admin/users');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->where('canCreate', true)
            ->where('canAssignRoles', true)
        );
        $this->assertDeferredInertia($response, fn ($page) => $page->has('pageLoad.users', 1));
    }

    public function test_user_show_includes_role_catalog(): void
    {
        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('user')->once()->with(3)->andReturn([
                'data' => [
                    'id' => 3,
                    'username' => 'e2e.staff',
                    'display_name' => 'E2E Payroll Staff',
                    'email' => 'e2e.staff@wonderlandhotel.local',
                    'is_active' => true,
                    'roles' => [['id' => 5, 'name' => 'cashier', 'display_name' => 'Cashier']],
                ],
            ]);
            $mock->shouldReceive('roles')->once()->andReturn([
                'data' => [
                    ['id' => 5, 'name' => 'cashier', 'display_name' => 'Cashier'],
                    ['id' => 6, 'name' => 'report_viewer', 'display_name' => 'Report Viewer'],
                ],
            ]);
            $mock->shouldReceive('auditLogsForUser')->once()->with(3, ['per_page' => 25, 'page' => 1])->andReturn([
                'data' => [[
                    'id' => 50,
                    'event' => 'user.login',
                    'created_at' => '2026-06-01T10:00:00+00:00',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'PHPUnit',
                ]],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 25,
                    'total' => 1,
                ],
            ]);
        });

        $response = $this->get('/admin/users/3');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Show')
            ->where('user.id', 3)
            ->where('assignedRoleIds', [5])
            ->has('roles', 2)
            ->where('canAssignRoles', true)
            ->where('canViewAudit', true)
            ->has('auditLogs', 1)
        );
    }

    public function test_user_role_sync_posts_to_s1(): void
    {
        $this->withoutMiddleware();

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('assignUserRoles')->once()->with(3, [
                ['role_id' => 5],
                ['role_id' => 6],
            ])->andReturn(['data' => ['id' => 3]]);
        });

        $response = $this->post('/admin/users/3/roles', [
            'role_ids' => [5, 6],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User roles updated.');
    }

    public function test_user_create_redirects_to_index(): void
    {
        $response = $this->get('/admin/users/create');

        $response->assertRedirect(route('admin.users.index', ['open' => 'create']));
    }

    public function test_roles_show_includes_permission_catalog(): void
    {
        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('role')->once()->with(2)->andReturn([
                'data' => [
                    'id' => 2,
                    'name' => 'hr_manager',
                    'display_name' => 'HR Manager',
                    'permissions' => [['id' => 10, 'action' => 'S2.workforce.employees.read']],
                ],
            ]);
            $mock->shouldReceive('permissions')->once()->andReturn([
                'data' => [[
                    'id' => 10,
                    'domain' => 'workforce',
                    'action' => 'S2.workforce.employees.read',
                    'display_name' => 'Read employees',
                ]],
            ]);
        });

        $response = $this->get('/admin/roles/2');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Roles/Show')
            ->where('role.id', 2)
            ->has('permissions', 1)
        );
    }

    public function test_audit_log_page_renders(): void
    {
        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('auditLogs')->once()->andReturn([
                'data' => [[
                    'id' => 100,
                    'event' => 'user.created',
                    'user_id' => 1,
                    'user' => ['username' => 'super.admin'],
                    'ip_address' => '127.0.0.1',
                    'created_at' => '2026-06-01T10:00:00+00:00',
                ]],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 25,
                    'total' => 1,
                ],
            ]);
        });

        $response = $this->get('/admin/audit-logs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Audit/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('pageLoad.auditLogs', 1));
    }

    public function test_audit_log_export_returns_csv(): void
    {
        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('auditLogs')->once()->andReturn([
                'data' => [[
                    'id' => 100,
                    'event' => 'user.created',
                    'user_id' => 1,
                    'user' => ['username' => 'super.admin'],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'PHPUnit',
                    'created_at' => '2026-06-01T10:00:00+00:00',
                ]],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 100,
                    'total' => 1,
                ],
            ]);
        });

        $response = $this->get('/admin/audit-logs/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('user.created', $response->streamedContent());
        $this->assertStringContainsString('super.admin', $response->streamedContent());
    }

    public function test_audit_log_index_shows_inline_error_when_api_fails(): void
    {
        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('auditLogs')->once()->andThrow(
                new \App\Exceptions\ApiException('S1_UNAVAILABLE', 'Identity service is unreachable.', 503),
            );
        });

        $response = $this->get('/admin/audit-logs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Audit/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page
            ->where('pageLoad.loadError', 'Identity service is unreachable.')
            ->where('pageLoad.loadErrorCode', 'S1_UNAVAILABLE')
            ->where('pageLoad.loadFailed', true)
        );
    }

    public function test_user_edit_renders(): void
    {
        Session::put('portal.permissions', array_merge(Session::get('portal.permissions', []), [
            'S1.identity.users.update',
        ]));

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('user')->once()->with(3)->andReturn([
                'data' => [
                    'id' => 3,
                    'username' => 'e2e.staff',
                    'display_name' => 'E2E Payroll Staff',
                    'email' => 'e2e.staff@wonderlandhotel.local',
                    'is_active' => true,
                    'employee_id' => 12,
                ],
            ]);
        });

        $response = $this->get('/admin/users/3/edit');

        $response->assertRedirect(route('admin.users.show', ['user' => 3, 'open' => 'edit']));
    }

    public function test_role_create_redirects_to_index(): void
    {
        $response = $this->get('/admin/roles/create');

        $response->assertRedirect(route('admin.roles.index', ['open' => 'create']));
    }

    public function test_role_edit_renders(): void
    {
        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('role')->once()->with(9)->andReturn([
                'data' => [
                    'id' => 9,
                    'name' => 'custom_role',
                    'display_name' => 'Custom Role',
                    'description' => 'Test',
                    'is_system' => false,
                ],
            ]);
        });

        $response = $this->get('/admin/roles/9/edit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Roles/Edit'));
    }

    public function test_permissions_index_renders(): void
    {
        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('permissions')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'domain' => 'identity',
                    'action' => 'S1.identity.users.read',
                    'display_name' => 'Read users',
                ]],
                'meta' => ['total' => 1],
            ]);
        });

        $response = $this->get('/admin/permissions');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Permissions/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('pageLoad.permissions', 1));
    }
}
