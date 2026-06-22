<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->foreignId('employee_consumption_period_id')
                ->nullable()
                ->after('folio_id')
                ->constrained('employee_consumption_periods')
                ->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE restaurant_orders MODIFY payment_context ENUM('folio', 'cash', 'employee_meal') NOT NULL DEFAULT 'cash'");
        }
    }

    public function down(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_consumption_period_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE restaurant_orders MODIFY payment_context ENUM('folio', 'cash') NOT NULL DEFAULT 'cash'");
        }
    }
};
