<?php

namespace App\Services\Api;

class S1IdentityClient extends GatewayClient
{
    /**
     * @return array<string, mixed>
     */
    public function usersByEmployeeId(int $employeeId): array
    {
        return $this->json('GET', '/s1/api/v1/users', [
            'employee_id' => $employeeId,
            'per_page' => 1,
        ]);
    }
}
