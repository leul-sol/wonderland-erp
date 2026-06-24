<?php

namespace Tests\Concerns;

trait MocksS4Auth
{
    private bool $s4AuthMocked = false;

    /** @var list<string> */
    private array $authRoles = ['finance_manager'];

    /**
     * @param  list<string>  $permissions
     * @param  list<string>  $roles
     * @return array<string, string>
     */
    protected function authHeaders(array $permissions = [], array $roles = ['finance_manager']): array
    {
        $this->authRoles = $roles;

        $defaults = [
            'S4.finance.accounts.read',
            'S4.finance.accounts.create',
            'S4.finance.accounts.update',
            'S4.finance.journal_entries.read',
            'S4.finance.journal_entries.create',
            'S4.finance.journal_entries.approve',
            'S4.finance.journal_entries.reverse',
            'S4.finance.receivables.read',
            'S4.finance.receivables.settle',
            'S4.finance.payables.read',
            'S4.finance.payables.settle',
            'S4.finance.fiscal_periods.read',
            'S4.finance.fiscal_periods.create',
            'S4.finance.fiscal_periods.close',
            'S4.finance.fiscal_periods.lock',
            'S4.finance.budgets.read',
            'S4.finance.budgets.create',
            'S4.finance.reports.read',
            'S4.bi.dashboards.read',
            'S4.bi.reports.read',
            'S4.bi.export.create',
            'S4.bi.rtm.read',
            'S4.bi.rtm.update',
            'S4.bi.uat.read',
            'S4.bi.uat.update',
        ];

        $effectivePermissions = $permissions === [] ? $defaults : $permissions;

        if (! $this->s4AuthMocked) {
            $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($effectivePermissions) {
                $mock->shouldReceive('verify')->andReturnUsing(function () use ($effectivePermissions) {
                    return [
                        'valid' => true,
                        'user' => [
                            'sub' => 1,
                            'permissions' => $effectivePermissions,
                            'roles' => $this->authRoles,
                        ],
                    ];
                });
            });
            $this->s4AuthMocked = true;
        }

        return ['Authorization' => 'Bearer test-token'];
    }
}
