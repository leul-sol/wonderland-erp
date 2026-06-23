<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->enum('action_type', [
                'oral_warning',
                'first_written_warning',
                'final_written_warning',
                'suspension',
                'termination',
                'immediate_dismissal',
            ]);
            $table->text('reason');
            $table->date('effective_date');
            $table->unsignedSmallInteger('suspension_days')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('employee_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('asset_type_id')->constrained('asset_types');
            $table->string('serial_number', 80)->nullable();
            $table->date('assigned_date');
            $table->date('returned_date')->nullable();
            $table->string('condition_on_return', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->string('full_name', 120);
            $table->string('national_id', 40);
            $table->string('phone', 20);
            $table->string('address', 255);
            $table->string('relationship', 60)->nullable();
            $table->string('letter_path', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantors');
        Schema::dropIfExists('employee_assets');
        Schema::dropIfExists('asset_types');
        Schema::dropIfExists('disciplinary_records');
    }
};
