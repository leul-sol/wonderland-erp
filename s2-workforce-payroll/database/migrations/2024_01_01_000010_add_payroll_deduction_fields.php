<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_lines', function (Blueprint $table) {
            $table->decimal('other_deductions', 12, 2)->default(0)->after('income_tax');
        });

        Schema::table('employee_deductions', function (Blueprint $table) {
            $table->foreignId('payroll_run_id')->nullable()->after('status')->constrained('payroll_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payroll_run_id');
        });

        Schema::table('payroll_lines', function (Blueprint $table) {
            $table->dropColumn('other_deductions');
        });
    }
};
