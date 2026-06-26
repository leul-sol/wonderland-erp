<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\Auth\PortalAuthService;
use App\Support\PortalUserMessage;
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
            $friendly = PortalUserMessage::fromApiException($exception);

            return $this->loginFailed($request, $friendly['message'], $friendly);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->loginFailed(
                $request,
                'We could not reach the sign-in service. Check your connection and try again.',
            );
        }

        $request->session()->regenerate();

        if ($this->auth->mustChangePassword()) {
            return redirect()->route('account.change-password.create');
        }

        return redirect()->intended(route('dashboard'));
    }

    private function loginFailed(Request $request, string $message, ?array $errorDetail = null): RedirectResponse
    {
        $redirect = back()
            ->withInput($request->only('username'))
            ->withErrors(['login' => $message])
            ->with('error', $message);

        if ($errorDetail !== null) {
            $redirect->with('error_detail', $errorDetail);
        }

        return $redirect;
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->auth->logout();

        return redirect()->route('login');
    }
}
