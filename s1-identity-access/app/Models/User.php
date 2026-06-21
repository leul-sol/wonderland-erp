<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'username',
        'email',
        'password',
        'display_name',
        'is_active',
        'must_change_password',
        'password_changed_at',
        'last_login_at',
        'last_login_ip',
        'failed_login_count',
        'locked_until',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'failed_login_count' => 'integer',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['department_id', 'assigned_at', 'assigned_by']);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function roleSlugs(): array
    {
        return $this->roles()->pluck('name')->all();
    }

    public function permissionStrings(): array
    {
        return Permission::query()
            ->select('permissions.action')
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $this->id)
            ->distinct()
            ->pluck('permissions.action')
            ->all();
    }

    public function departmentScope(): ?string
    {
        $departmentId = $this->roles()
            ->whereNotNull('user_roles.department_id')
            ->value('user_roles.department_id');

        return $departmentId !== null ? (string) $departmentId : null;
    }
}
