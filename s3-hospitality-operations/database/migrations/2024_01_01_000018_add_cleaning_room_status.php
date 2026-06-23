<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available', 'occupied', 'maintenance', 'cleaning') NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        DB::table('rooms')->where('status', 'cleaning')->update(['status' => 'maintenance']);

        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available'");
    }
};
