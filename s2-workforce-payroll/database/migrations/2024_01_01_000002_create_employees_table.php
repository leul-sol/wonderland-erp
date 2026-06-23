<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 20)->unique();
            $table->string('full_name', 150);
            $table->string('email', 150)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('job_title', 100)->nullable();
            $table->decimal('base_salary', 12, 2);
            $table->enum('pension_category', ['covered', 'not_covered'])->default('covered');
            $table->string('default_role', 50)->default('report_viewer');
            $table->enum('status', ['active', 'on_leave', 'suspended', 'archived'])->default('active');
            $table->date('hire_date')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
