<?php

namespace Database\Seeders;

use App\Models\FiscalPeriod;
use App\Services\BudgetService;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $period = FiscalPeriod::query()->orderBy('year')->orderBy('period_number')->first();

        if ($period === null) {
            return;
        }

        $budgets = app(BudgetService::class);

        foreach ([
            ['account_code' => '4001', 'budget_amount' => 50000],
            ['account_code' => '4002', 'budget_amount' => 15000],
            ['account_code' => '5001', 'budget_amount' => 20000],
            ['account_code' => '5003', 'budget_amount' => 8000],
        ] as $line) {
            $budgets->upsertLine([
                'fiscal_period_id' => $period->id,
                'account_code' => $line['account_code'],
                'budget_amount' => $line['budget_amount'],
            ]);
        }
    }
}
