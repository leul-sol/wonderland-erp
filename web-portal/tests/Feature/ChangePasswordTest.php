<?php

namespace Tests\Feature;

use App\Services\Api\S1AuthClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    public function test_login_redirects_to_change_password_when_required(): void
    {
        $this->mock(S1AuthClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('login')->once()->andReturn([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
                'must_change_password' => true,
            ]);
            $mock->shouldReceive('me')->once()->andReturn([
                'id' => 1,
                'username' => 'new.user',
                'name' => 'New User',
                'email' => 'new@example.com',
                'roles' => [],
                'permissions' => [],
                'must_change_password' => true,
            ]);
        });

        $this->get('/login');

        $response = $this->post('/login', [
            '_token' => session()->token(),
            'username' => 'new.user',
            'password' => 'TempPass123!',
        ]);

        $response->assertRedirect(route('account.change-password.create'));
    }

    public function test_dashboard_redirects_when_password_change_required(): void
    {
        Session::put('portal.access_token', 'access-token');
        Session::put('portal.user', [
            'id' => 1,
            'username' => 'new.user',
            'must_change_password' => true,
        ]);

        $response = $this->get('/');

        $response->assertRedirect(route('account.change-password.create'));
    }

    public function test_change_password_page_renders_for_authenticated_user(): void
    {
        Session::put('portal.access_token', 'access-token');
        Session::put('portal.user', [
            'id' => 1,
            'username' => 'new.user',
            'must_change_password' => true,
        ]);

        $response = $this->get('/account/change-password');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/ChangePassword')
            ->where('required', true)
            ->where('username', 'new.user')
        );
    }

    public function test_successful_password_change_redirects_to_dashboard(): void
    {
        Session::put('portal.access_token', 'access-token');
        Session::put('portal.user', [
            'id' => 1,
            'username' => 'new.user',
            'must_change_password' => true,
        ]);

        $this->mock(S1AuthClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('changePassword')->once()->with('access-token', 'OldPass123!', 'NewPass123!');
            $mock->shouldReceive('login')->once()->andReturn([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
            ]);
            $mock->shouldReceive('me')->once()->andReturn([
                'id' => 1,
                'username' => 'new.user',
                'name' => 'New User',
                'email' => 'new@example.com',
                'roles' => ['super_admin'],
                'permissions' => ['S1.identity.users.read'],
                'must_change_password' => false,
            ]);
        });

        $this->get('/account/change-password');

        $response = $this->post('/account/change-password', [
            '_token' => session()->token(),
            'current_password' => 'OldPass123!',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertFalse(app(PortalAuthService::class)->mustChangePassword());
    }
}
