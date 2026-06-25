<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        try {
            $this->auth->login($credentials['username'], $credentials['password']);
        } catch (ApiException $exception) {
            return $this->loginFailed($request, $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return $this->loginFailed(
                $request,
                'Unable to reach the sign-in service. Make sure Docker is running, then try again.',
            );
        }

        $request->session()->regenerate();

        if ($this->auth->mustChangePassword()) {
            return redirect()->route('account.change-password.create');
        }

        return redirect()->intended(route('dashboard'));
    }

    private function loginFailed(Request $request, string $message): RedirectResponse
    {
        return back()
            ->withInput($request->only('username'))
            ->withErrors(['login' => $message])
            ->with('error', $message);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->auth->logout();

        return redirect()->route('login');
    }
}
