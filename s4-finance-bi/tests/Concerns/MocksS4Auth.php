<?php

namespace Tests\Concerns;

trait MocksS4Auth
{
    private bool $s4AuthMocked = false;

    /**
     * @param  list<string>  $permissions
     * @return array<string, string>
     */
    protected function authHeaders(array $permissions = []): array
    {
        $defaults = [
            'S4.finance.accounts.read',
            'S4.finance.journal_entries.read',
            'S4.finance.journal_entries.create',
            'S4.finance.journal_entries.approve',
            'S4.finance.journal_entries.reverse',
            'S4.finance.receivables.read',
            'S4.finance.receivables.settle',
            'S4.finance.payables.read',
            'S4.finance.payables.settle',
            'S4.finance.fiscal_periods.read',
            'S4.finance.fiscal_periods.close',
            'S4.finance.fiscal_periods.lock',
        ];

        if (! $this->s4AuthMocked) {
            $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($permissions, $defaults) {
                $mock->shouldReceive('verify')->andReturn([
                    'valid' => true,
                    'user' => [
                        'sub' => 1,
                        'permissions' => $permissions === [] ? $defaults : $permissions,
                        'roles' => ['finance_manager'],
                    ],
                ]);
            });
            $this->s4AuthMocked = true;
        }

        return ['Authorization' => 'Bearer test-token'];
    }
}
