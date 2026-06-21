<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeEventService
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokens,
    ) {
    }

    public function handleCreated(array $payload): void
    {
        $employeeId = (int) ($payload['employee_id'] ?? 0);

        if ($employeeId <= 0) {
            return;
        }

        if (User::query()->where('employee_id', $employeeId)->exists()) {
            return;
        }

        DB::transaction(function () use ($payload, $employeeId) {
            $fullName = (string) ($payload['full_name'] ?? 'Employee');
            $username = $this->deriveUsername($fullName);
            $tempPassword = $this->generateTempPassword();

            $user = User::query()->create([
                'employee_id' => $employeeId,
                'username' => $username,
                'email' => $username.'@wonderlandhotel.local',
                'password' => Hash::make($tempPassword),
                'display_name' => $fullName,
                'is_active' => true,
                'must_change_password' => true,
                'password_changed_at' => now(),
            ]);

            $roleName = (string) ($payload['default_role'] ?? 'report_viewer');
            $role = Role::query()->where('name', $roleName)->first();

            if ($role !== null) {
                $user->roles()->attach($role->id, [
                    'department_id' => $payload['department_id'] ?? null,
                    'assigned_at' => now(),
                ]);
            }

            AuditService::log(
                'user.provisioned',
                null,
                '0.0.0.0',
                's2-event-bus',
                [
                    'employee_id' => $employeeId,
                    'user_id' => $user->id,
                    'username' => $username,
                    'default_role' => $roleName,
                ],
            );
        });
    }

    public function handleUpdated(array $payload): void
    {
        $employeeId = (int) ($payload['employee_id'] ?? 0);

        if ($employeeId <= 0) {
            return;
        }

        $user = User::query()->where('employee_id', $employeeId)->first();

        if ($user === null) {
            $this->handleCreated($payload);

            return;
        }

        DB::transaction(function () use ($user, $payload, $employeeId) {
            if (isset($payload['full_name'])) {
                $user->display_name = (string) $payload['full_name'];
            }

            $user->save();

            if (array_key_exists('department_id', $payload)) {
                DB::table('user_roles')
                    ->where('user_id', $user->id)
                    ->update(['department_id' => $payload['department_id']]);
            }

            AuditService::log(
                'user.updated',
                null,
                '0.0.0.0',
                's2-event-bus',
                [
                    'employee_id' => $employeeId,
                    'user_id' => $user->id,
                ],
            );
        });
    }

    public function handleArchived(array $payload): void
    {
        $employeeId = (int) ($payload['employee_id'] ?? 0);

        if ($employeeId <= 0) {
            return;
        }

        $user = User::query()->where('employee_id', $employeeId)->first();

        if ($user === null || ! $user->is_active) {
            return;
        }

        DB::transaction(function () use ($user, $payload, $employeeId) {
            $user->is_active = false;
            $user->save();
            $this->refreshTokens->revokeAllForUser($user->id);

            AuditService::log(
                'user.deactivated',
                null,
                '0.0.0.0',
                's2-event-bus',
                [
                    'employee_id' => $employeeId,
                    'user_id' => $user->id,
                    'reason' => $payload['reason'] ?? null,
                ],
            );
        });
    }

    private function deriveUsername(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = Str::slug($parts[0] ?? 'user', '.');
        $last = Str::slug($parts[count($parts) - 1] ?? 'employee', '.');
        $base = trim($first.'.'.$last, '.') ?: 'user.employee';
        $username = $base;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.$suffix;
            $suffix++;
        }

        return $username;
    }

    private function generateTempPassword(): string
    {
        return 'Temp'.Str::upper(Str::random(4)).'!'.random_int(10, 99);
    }
}
