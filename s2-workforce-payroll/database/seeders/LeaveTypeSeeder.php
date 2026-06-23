<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'AL', 'name' => 'Annual Leave', 'max_days_per_year' => 30, 'paid' => true],
            ['code' => 'SL', 'name' => 'Sick Leave', 'max_days_per_year' => null, 'paid' => true],
            ['code' => 'ML', 'name' => 'Maternity Leave', 'max_days_per_year' => 90, 'paid' => true],
            ['code' => 'CL', 'name' => 'Casual Leave', 'max_days_per_year' => null, 'paid' => true],
            ['code' => 'UL', 'name' => 'Unpaid Leave', 'max_days_per_year' => null, 'paid' => false],
            ['code' => 'TA', 'name' => 'Travel Allowance Leave', 'max_days_per_year' => null, 'paid' => true],
            ['code' => 'OT', 'name' => 'Other Leave', 'max_days_per_year' => null, 'paid' => true],
        ];

        foreach ($types as $type) {
            DB::table('leave_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'max_days_per_year' => $type['max_days_per_year'],
                    'paid' => $type['paid'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
