<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('folio_lines', function (Blueprint $table) {
                $table->string('charge_category', 30)->nullable()->change();
            });

            return;
        }

        DB::statement("ALTER TABLE folio_lines MODIFY charge_category ENUM('room', 'fb', 'minibar', 'laundry', 'event', 'other') NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE folio_lines SET charge_category = 'other' WHERE charge_category NOT IN ('room', 'fb', 'other')");
        DB::statement("ALTER TABLE folio_lines MODIFY charge_category ENUM('room', 'fb', 'other') NULL");
    }
};
