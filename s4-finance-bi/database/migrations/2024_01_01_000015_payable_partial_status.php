<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payables MODIFY status ENUM('open', 'partial', 'settled') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payables MODIFY status ENUM('open', 'settled') NOT NULL DEFAULT 'open'");
    }
};
