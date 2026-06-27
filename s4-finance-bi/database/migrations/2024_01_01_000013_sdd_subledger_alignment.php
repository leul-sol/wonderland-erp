<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->enum('customer_type', ['hotel_guest', 'event'])->nullable()->after('account_id');
            $table->unsignedBigInteger('customer_ref_id')->nullable()->after('customer_type');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('account_id');
            $table->date('due_date')->nullable()->after('balance');
        });

        DB::statement("ALTER TABLE payables MODIFY status ENUM('open', 'partial', 'settled') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropColumn(['customer_type', 'customer_ref_id']);
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->dropColumn(['supplier_id', 'due_date']);
        });

        DB::statement("ALTER TABLE payables MODIFY status ENUM('open', 'settled') NOT NULL DEFAULT 'open'");
    }
};
