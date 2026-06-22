<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'FO', 'name' => 'Front Office'],
            ['code' => 'FN', 'name' => 'Finance'],
            ['code' => 'FB', 'name' => 'Food & Beverage'],
            ['code' => 'HK', 'name' => 'Housekeeping'],
        ];

        foreach ($departments as $department) {
            DB::table('departments')->updateOrInsert(
                ['code' => $department['code']],
                [
                    'name' => $department['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
