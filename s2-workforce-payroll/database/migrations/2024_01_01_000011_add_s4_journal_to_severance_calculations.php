<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('severance_calculations', function (Blueprint $table) {
            $table->string('s4_journal_entry_id', 50)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('severance_calculations', function (Blueprint $table) {
            $table->dropColumn('s4_journal_entry_id');
        });
    }
};
