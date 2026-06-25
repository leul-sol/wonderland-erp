<?php

namespace Tests\Feature;

use App\Services\Api\S1AdminClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S1.identity.users.read',
            'S1.identity.users.update',
            'S1.identity.users.reset_password',
            'S1.identity.users.force_logout',
            'S1.identity.users.assign_role',
            'S1.identity.roles.read',
            'S1.identity.roles.create',
            'S1.identity.roles.update',
            'S1.identity.roles.delete',
            'S1.identity.roles.sync_permissions',
        ]);
    }

    public function test_user_reset_password_posts_to_s1(): void
    {
        $this->withoutMiddleware();

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resetUserPassword')->once()->with(7, [
                'password' => 'NewSecurePass!10',
                'must_change_password' => true,
            ])->andReturn(['message' => 'Password reset.']);
        });

        $response = $this->post('/admin/users/7/reset-password', [
            'password' => 'NewSecurePass!10',
            'password_confirmation' => 'NewSecurePass!10',
            'must_change_password' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Password reset. The user must sign in with the new password.');
    }

    public function test_user_force_logout_posts_to_s1(): void
    {
        $this->withoutMiddleware();

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('forceLogoutUser')->once()->with(7)->andReturn(['message' => 'Active sessions revoked.']);
        });

        $response = $this->post('/admin/users/7/force-logout');

        $response->assertRedirect();
        $response->assertSessionHas('success', 'All active sessions for this user were revoked.');
    }

    public function test_user_update_reactivates_account(): void
    {
        $this->withoutMiddleware();

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateUser')->once()->with(7, [
                'email' => 'staff@wonderlandhotel.local',
                'display_name' => 'Reactivated Staff',
                'employee_id' => 42,
                'is_active' => true,
            ])->andReturn(['data' => ['id' => 7]]);
        });

        $response = $this->put('/admin/users/7', [
            'email' => 'staff@wonderlandhotel.local',
            'display_name' => 'Reactivated Staff',
            'employee_id' => 42,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.users.show', 7));
        $response->assertSessionHas('success', 'User updated.');
    }

    public function test_role_store_posts_to_s1(): void
    {
        $this->withoutMiddleware();

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createRole')->once()->with([
                'name' => 'uat_custom',
                'display_name' => 'UAT Custom',
                'description' => 'Phase 6 test role',
            ])->andReturn(['data' => ['id' => 99]]);
        });

        $response = $this->post('/admin/roles', [
            'name' => 'uat_custom',
            'display_name' => 'UAT Custom',
            'description' => 'Phase 6 test role',
        ]);

        $response->assertRedirect(route('admin.roles.show', 99));
        $response->assertSessionHas('success', 'Role created. Assign permissions below.');
    }

    public function test_role_update_posts_to_s1(): void
    {
        $this->withoutMiddleware();

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateRole')->once()->with(9, [
                'display_name' => 'Updated Custom',
                'description' => 'Updated description',
            ])->andReturn(['data' => ['id' => 9]]);
        });

        $response = $this->put('/admin/roles/9', [
            'display_name' => 'Updated Custom',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('admin.roles.show', 9));
        $response->assertSessionHas('success', 'Role updated.');
    }

    public function test_role_destroy_posts_to_s1(): void
    {
        $this->withoutMiddleware();

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deleteRole')->once()->with(9)->andReturn(['message' => 'Role deleted.']);
        });

        $response = $this->delete('/admin/roles/9');

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success', 'Role deleted.');
    }
}
