<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('second_approved_by')->nullable()->after('approved_at');
            $table->timestamp('second_approved_at')->nullable()->after('second_approved_by');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('balance');
        });

        DB::statement("ALTER TABLE receivables MODIFY status ENUM('open', 'partial', 'settled', 'written_off') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });

        DB::statement("ALTER TABLE receivables MODIFY status ENUM('open', 'settled') NOT NULL DEFAULT 'open'");

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['second_approved_by', 'second_approved_at']);
        });
    }
};
