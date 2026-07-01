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
            Schema::table('folio_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('cashier_shift_id')->nullable()->change();
            });

            return;
        }

        Schema::table('folio_payments', function (Blueprint $table) {
            $table->dropForeign(['cashier_shift_id']);
        });

        DB::statement('ALTER TABLE folio_payments MODIFY cashier_shift_id BIGINT UNSIGNED NULL');

        Schema::table('folio_payments', function (Blueprint $table) {
            $table->foreign('cashier_shift_id')->references('id')->on('cashier_shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
    }
};
