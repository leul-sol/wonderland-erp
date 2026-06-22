<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'STD', 'name' => 'Standard Room', 'base_rate' => 2500, 'max_occupancy' => 2],
            ['code' => 'DLX', 'name' => 'Deluxe Room', 'base_rate' => 4000, 'max_occupancy' => 3],
            ['code' => 'STE', 'name' => 'Suite', 'base_rate' => 7500, 'max_occupancy' => 4],
        ];

        foreach ($types as $type) {
            DB::table('room_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'base_rate' => $type['base_rate'],
                    'max_occupancy' => $type['max_occupancy'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
