<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S1AdminClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S1AdminClient $s1,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $query = array_filter([
            'search' => $request->input('search'),
            'page' => $request->input('page'),
            'per_page' => 25,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->s1->users($query);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $response['data'] ?? [],
            'meta' => $response['meta'] ?? null,
            'search' => $request->input('search', ''),
            'canCreate' => $this->auth->hasAnyPermission(['S1.identity.users.create']),
            'canDeactivate' => $this->auth->hasAnyPermission(['S1.identity.users.deactivate']),
            'canAssignRoles' => $this->auth->hasAnyPermission(['S1.identity.users.assign_role']),
        ]);
    }

    public function show(int $user): Response|RedirectResponse
    {
        try {
            $userResponse = $this->s1->user($user);
            $rolesResponse = $this->s1->roles(['per_page' => 50]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'admin.users.index');
        }

        $userData = $userResponse['data'] ?? [];
        $assignedRoleIds = collect($userData['roles'] ?? [])->pluck('id')->all();

        return Inertia::render('Admin/Users/Show', [
            'user' => $userData,
            'roles' => $rolesResponse['data'] ?? [],
            'assignedRoleIds' => $assignedRoleIds,
            'canAssignRoles' => $this->auth->hasAnyPermission(['S1.identity.users.assign_role']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:10'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $this->s1->createUser([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'display_name' => $data['display_name'] ?? null,
                'employee_id' => isset($data['employee_id']) ? (int) $data['employee_id'] : null,
                'is_active' => true,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Platform user created.');
    }

    public function deactivate(int $user): RedirectResponse
    {
        try {
            $this->s1->deactivateUser($user);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'User deactivated.');
    }

    public function syncRoles(Request $request, int $user): RedirectResponse
    {
        $data = $request->validate([
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'min:1'],
        ]);

        $roles = collect($data['role_ids'])
            ->map(fn ($id) => ['role_id' => (int) $id])
            ->values()
            ->all();

        try {
            $this->s1->assignUserRoles($user, $roles);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'User roles updated.');
    }
}
