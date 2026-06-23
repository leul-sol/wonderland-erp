<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['working_day', 'sunday', 'holiday', 'night'])->unique();
            $table->decimal('multiplier', 4, 2);
            $table->timestamps();
        });

        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->date('work_date');
            $table->decimal('hours', 4, 2);
            $table->enum('category', ['working_day', 'sunday', 'holiday', 'night']);
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->foreignId('payroll_run_id')->nullable()->constrained('payroll_runs')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('loan_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('monthly_repayment', 12, 2);
            $table->decimal('remaining_balance', 12, 2);
            $table->enum('status', ['active', 'completed', 'written_off'])->default('active');
            $table->date('disbursed_at');
            $table->string('s4_journal_entry_id', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_records');
        Schema::dropIfExists('overtime_records');
        Schema::dropIfExists('overtime_rates');
    }
};
