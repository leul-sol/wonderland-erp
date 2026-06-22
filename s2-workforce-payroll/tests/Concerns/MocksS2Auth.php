<?php

namespace Tests\Concerns;

trait MocksS2Auth
{
    private bool $s2AuthMocked = false;

    /**
     * @param  list<string>  $permissions
     * @return array<string, string>
     */
    protected function authHeaders(array $permissions = []): array
    {
        $defaults = [
            'S2.workforce.employees.read',
            'S2.workforce.employees.create',
            'S2.workforce.employees.update',
            'S2.workforce.employees.archive',
            'S2.workforce.payroll_runs.read',
            'S2.workforce.payroll_runs.create',
            'S2.workforce.payroll_runs.approve',
            'S2.workforce.leave_requests.read',
            'S2.workforce.leave_requests.create',
            'S2.workforce.leave_requests.approve',
            'S2.workforce.leave_requests.reject',
            'S2.workforce.attendance.read',
            'S2.workforce.attendance.create',
            'S2.workforce.severance.read',
            'S2.workforce.severance.calculate',
        ];

        if (! $this->s2AuthMocked) {
            $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($permissions, $defaults) {
                $mock->shouldReceive('verify')->andReturn([
                    'valid' => true,
                    'user' => [
                        'sub' => 1,
                        'permissions' => $permissions === [] ? $defaults : $permissions,
                        'roles' => ['payroll_officer'],
                    ],
                ]);
            });
            $this->s2AuthMocked = true;
        }

        return ['Authorization' => 'Bearer test-token'];
    }
}
