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
            'S2.workforce.positions.read',
            'S2.workforce.positions.create',
            'S2.workforce.positions.update',
            'S2.workforce.positions.delete',
            'S2.workforce.offboarding.read',
            'S2.workforce.offboarding.create',
            'S2.workforce.offboarding.update',
            'S2.workforce.payroll_runs.read',
            'S2.workforce.payroll_runs.create',
            'S2.workforce.payroll_runs.approve',
            'S2.workforce.loans.read',
            'S2.workforce.loans.create',
            'S2.workforce.leave_types.read',
            'S2.workforce.leave_balances.read',
            'S2.workforce.leave_requests.read',
            'S2.workforce.leave_requests.create',
            'S2.workforce.leave_requests.approve',
            'S2.workforce.leave_requests.reject',
            'S2.workforce.overtime.read',
            'S2.workforce.overtime.create',
            'S2.workforce.overtime.update',
            'S2.workforce.overtime.approve',
            'S2.workforce.attendance.read',
            'S2.workforce.attendance.create',
            'S2.workforce.severance.read',
            'S2.workforce.severance.calculate',
            'S2.workforce.severance.pay',
            'S2.hr.departments.read',
            'S2.hr.departments.write',
            'S2.hr.disciplinary.read',
            'S2.hr.disciplinary.write',
            'S2.hr.assets.read',
            'S2.hr.assets.write',
            'S2.hr.guarantors.read',
            'S2.hr.guarantors.write',
            'S2.payroll.payslips.read',
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
