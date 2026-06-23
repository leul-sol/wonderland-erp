<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OvertimeRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['category' => 'working_day', 'multiplier' => 1.25],
            ['category' => 'sunday', 'multiplier' => 2.00],
            ['category' => 'holiday', 'multiplier' => 2.50],
            ['category' => 'night', 'multiplier' => 1.25],
        ];

        foreach ($rates as $rate) {
            DB::table('overtime_rates')->updateOrInsert(
                ['category' => $rate['category']],
                [
                    'multiplier' => $rate['multiplier'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
