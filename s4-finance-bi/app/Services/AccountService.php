<?php

namespace App\Services;

use App\Models\Account;
use InvalidArgumentException;

class AccountService
{
    public function __construct(private readonly FinanceAuditLogger $audit)
    {
    }

    public function create(array $data): Account
    {
        $type = (string) $data['type'];
        $normalBalance = (string) $data['normal_balance'];

        $this->assertTypeBalance($type, $normalBalance);

        if (Account::query()->where('code', $data['code'])->exists()) {
            throw new InvalidArgumentException('Account code already exists.');
        }

        $account = Account::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $type,
            'sub_type' => $data['sub_type'] ?? null,
            'normal_balance' => $normalBalance,
            'is_active' => $data['is_active'] ?? true,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        $this->audit->log(
            'account.create',
            'account',
            $account->id,
            (int) (request()->attributes->get('auth_user_id', 0)),
            ['code' => $account->code],
        );

        return $account;
    }

    public function update(Account $account, array $data): Account
    {
        if (isset($data['type'], $data['normal_balance'])) {
            $this->assertTypeBalance($data['type'], $data['normal_balance']);
        }

        $account->update(array_intersect_key($data, array_flip([
            'name', 'type', 'sub_type', 'normal_balance', 'is_active', 'parent_id',
        ])));

        $this->audit->log(
            'account.update',
            'account',
            $account->id,
            (int) (request()->attributes->get('auth_user_id', 0)),
            ['code' => $account->code],
        );

        return $account->fresh();
    }

    private function assertTypeBalance(string $type, string $normalBalance): void
    {
        $expected = in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';

        if ($type === 'equity') {
            $expected = 'credit';
        }

        if ($normalBalance !== $expected) {
            throw new InvalidArgumentException("Account type {$type} expects normal balance {$expected}.");
        }
    }
}
