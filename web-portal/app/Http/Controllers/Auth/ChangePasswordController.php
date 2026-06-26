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

class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly PortalAuthService $auth,
    ) {
    }

    public function create(): Response|RedirectResponse
    {
        if (! $this->auth->isAuthenticated()) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/ChangePassword', [
            'required' => $this->auth->mustChangePassword(),
            'username' => $this->auth->user()['username'] ?? '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->auth->user();
        $username = is_array($user) ? (string) ($user['username'] ?? '') : '';

        if ($username === '') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        try {
            $this->auth->changePassword($data['current_password'], $data['password']);
            $this->auth->login($username, $data['password']);
        } catch (ApiException $exception) {
            $friendly = PortalUserMessage::fromApiException($exception);
            $field = $exception->errorCode === 'UNAUTHENTICATED' ? 'current_password' : 'password';

            return back()
                ->withErrors([$field => $friendly['message']])
                ->with('error', $friendly['message'])
                ->with('error_detail', $friendly);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Unable to update password. Try again.');
        }

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Password updated successfully.');
    }
}
