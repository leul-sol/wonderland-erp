<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $standardId = DB::table('room_types')->where('code', 'STD')->value('id');
        $deluxeId = DB::table('room_types')->where('code', 'DLX')->value('id');

        if ($standardId === null || $deluxeId === null) {
            return;
        }

        $rooms = [
            ['room_type_id' => $standardId, 'room_number' => '101', 'floor' => '1'],
            ['room_type_id' => $standardId, 'room_number' => '102', 'floor' => '1'],
            ['room_type_id' => $deluxeId, 'room_number' => '201', 'floor' => '2'],
            ['room_type_id' => $deluxeId, 'room_number' => '202', 'floor' => '2'],
        ];

        foreach ($rooms as $room) {
            DB::table('rooms')->updateOrInsert(
                ['room_number' => $room['room_number']],
                [
                    'room_type_id' => $room['room_type_id'],
                    'floor' => $room['floor'],
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
