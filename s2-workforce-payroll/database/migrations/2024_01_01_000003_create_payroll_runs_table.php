<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_number', 30)->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'approved', 'posted'])->default('draft');
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->string('s4_journal_entry_id', 50)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
