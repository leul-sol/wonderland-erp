<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('head_employee_id')->nullable()->after('name');
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 80);
            $table->foreignId('department_id')->constrained('departments');
            $table->string('grade', 10)->nullable();
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
        });

        Schema::create('offboarding_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->date('initiated_date');
            $table->enum('reason', ['resignation', 'termination', 'retirement', 'end_of_contract', 'death']);
            $table->date('last_working_day');
            $table->enum('clearance_status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->decimal('severance_amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 80)->unique();
            $table->string('endpoint', 120);
            $table->string('request_hash', 64);
            $table->json('response_body');
            $table->unsignedSmallInteger('status_code');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('offboarding_records');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
        });

        Schema::dropIfExists('positions');

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('head_employee_id');
        });
    }
};
