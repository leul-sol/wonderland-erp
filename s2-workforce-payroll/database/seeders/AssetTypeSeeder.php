<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Laptop', 'description' => 'Company laptop'],
            ['name' => 'Uniform', 'description' => 'Staff uniform'],
            ['name' => 'Access Card', 'description' => 'Building access card'],
            ['name' => 'Mobile Phone', 'description' => 'Company mobile phone'],
        ];

        foreach ($types as $type) {
            DB::table('asset_types')->updateOrInsert(
                ['name' => $type['name']],
                [
                    'description' => $type['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
