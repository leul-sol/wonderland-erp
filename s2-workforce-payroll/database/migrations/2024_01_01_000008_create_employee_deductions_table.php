<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->enum('deduction_type', ['staff_meal', 'uniform', 'advance', 'other'])->default('staff_meal');
            $table->decimal('amount', 12, 2);
            $table->string('description', 255)->nullable();
            $table->string('source_reference', 80)->nullable();
            $table->string('idempotency_key', 80)->unique();
            $table->enum('status', ['pending', 'applied'])->default('applied');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
