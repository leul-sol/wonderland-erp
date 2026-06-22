<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs');
            $table->foreignId('employee_id')->constrained('employees');
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('employee_pension', 12, 2);
            $table->decimal('employer_pension', 12, 2);
            $table->decimal('income_tax', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_lines');
    }
};
