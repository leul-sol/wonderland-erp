<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait SerializesUsers
{
    protected function userPayload(User $user, bool $detailed = false): array
    {
        $user->loadMissing('roles');

        $payload = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'display_name' => $user->display_name,
            'employee_id' => $user->employee_id,
            'is_active' => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'roles' => $user->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'department_id' => $role->pivot->department_id,
            ])->values()->all(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];

        if ($detailed) {
            $payload['last_login_at'] = $user->last_login_at?->toIso8601String();
            $payload['last_login_ip'] = $user->last_login_ip;
            $payload['password_changed_at'] = $user->password_changed_at?->toIso8601String();
            $payload['locked_until'] = $user->locked_until?->toIso8601String();
        }

        return $payload;
    }

    protected function paginatedUsers(LengthAwarePaginator $paginator, bool $detailed = false): array
    {
        return [
            'data' => $paginator->getCollection()
                ->map(fn (User $user) => $this->userPayload($user, $detailed))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
