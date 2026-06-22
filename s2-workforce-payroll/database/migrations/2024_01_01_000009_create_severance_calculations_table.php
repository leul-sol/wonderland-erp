<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('severance_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('months_of_service');
            $table->date('calculation_date');
            $table->enum('status', ['calculated', 'paid'])->default('calculated');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('severance_calculations');
    }
};
