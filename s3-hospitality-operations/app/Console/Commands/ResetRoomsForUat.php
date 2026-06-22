<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Console\Command;

class ResetRoomsForUat extends Command
{
    protected $signature = 'hospitality:reset-rooms';

    protected $description = 'Mark rooms available when no active checked-in reservation holds them';

    public function handle(): int
    {
        $occupiedRoomIds = Reservation::query()
            ->where('status', 'checked_in')
            ->whereNotNull('room_id')
            ->pluck('room_id');

        $updated = Room::query()
            ->whereNotIn('id', $occupiedRoomIds)
            ->where('status', '!=', 'available')
            ->update(['status' => 'available']);

        $this->info("Reset {$updated} room(s) to available.");

        return self::SUCCESS;
    }
}
