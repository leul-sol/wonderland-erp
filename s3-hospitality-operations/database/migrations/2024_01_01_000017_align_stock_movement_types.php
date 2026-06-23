<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type VARCHAR(20) NOT NULL");
        } else {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->string('movement_type', 20)->change();
            });
        }

        DB::table('stock_movements')->where('movement_type', 'sale')->update(['movement_type' => 'dispatch']);
    }

    public function down(): void
    {
        DB::table('stock_movements')->where('movement_type', 'dispatch')->update(['movement_type' => 'sale']);
    }
};
