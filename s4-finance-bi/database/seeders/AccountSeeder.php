<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1001', 'name' => 'Cash and Cash Equivalents', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1002', 'name' => 'Bank Account — CBE', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1003', 'name' => 'Mobile Banking Clearing', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1004', 'name' => 'POS Clearing', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1005', 'name' => 'VISA Clearing', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1100', 'name' => 'AR — Hotel Guests', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1101', 'name' => 'AR — Events', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1102', 'name' => 'Staff Loans Receivable', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1200', 'name' => 'Inventory — F&B', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1201', 'name' => 'Inventory — Supplies', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '2001', 'name' => 'AP — Suppliers', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2100', 'name' => 'Salaries Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2101', 'name' => 'Employee Pension Payable (7%)', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2102', 'name' => 'Employer Pension Payable (11%)', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2200', 'name' => 'Income Tax Withheld Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2300', 'name' => 'VAT Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '4001', 'name' => 'Room Revenue', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4002', 'name' => 'F&B Revenue', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4003', 'name' => 'Service Charge Income', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4004', 'name' => 'Event Revenue', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '5001', 'name' => 'Salaries and Wages Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5002', 'name' => 'Pension Contribution Expense (Employer)', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5003', 'name' => 'Cost of Food Sold', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5004', 'name' => 'Supplies and Consumables Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5005', 'name' => 'Severance Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'normal_balance' => $account['normal_balance'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
