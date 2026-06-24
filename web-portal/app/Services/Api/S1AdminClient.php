<?php

namespace App\Services\Api;

class S1AdminClient extends GatewayClient
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function users(array $query = []): array
    {
        return $this->json('GET', '/s1/api/v1/users', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function usersByEmployeeId(int $employeeId): array
    {
        return $this->users([
            'employee_id' => $employeeId,
            'per_page' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createUser(array $payload): array
    {
        return $this->json('POST', '/s1/api/v1/users', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function deactivateUser(int $userId): array
    {
        return $this->json('POST', "/s1/api/v1/users/{$userId}/deactivate");
    }

    /**
     * @return array<string, mixed>
     */
    public function user(int $userId): array
    {
        return $this->json('GET', "/s1/api/v1/users/{$userId}");
    }

    /**
     * @param  list<array{role_id: int, department_id?: int|null}>  $roles
     * @return array<string, mixed>
     */
    public function assignUserRoles(int $userId, array $roles): array
    {
        return $this->json('POST', "/s1/api/v1/users/{$userId}/roles", [
            'roles' => $roles,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function roles(array $query = []): array
    {
        return $this->json('GET', '/s1/api/v1/roles', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function role(int $roleId): array
    {
        return $this->json('GET', "/s1/api/v1/roles/{$roleId}");
    }

    /**
     * @param  list<int>  $permissionIds
     * @return array<string, mixed>
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): array
    {
        return $this->json('POST', "/s1/api/v1/roles/{$roleId}/permissions", [
            'permission_ids' => $permissionIds,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function permissions(array $query = []): array
    {
        return $this->json('GET', '/s1/api/v1/permissions', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function auditLogs(array $query = []): array
    {
        return $this->json('GET', '/s1/api/v1/audit-logs', $query);
    }
}
