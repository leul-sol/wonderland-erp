<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uat_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('scenario_key', 40)->unique();
            $table->enum('system', ['S1', 'S2', 'S3', 'S4']);
            $table->string('title', 200);
            $table->string('requirement_key', 40)->nullable()->index();
            $table->text('preconditions')->nullable();
            $table->text('steps');
            $table->text('expected_outcome');
            $table->enum('status', ['pending', 'passed', 'failed', 'blocked', 'skipped'])->default('pending');
            $table->unsignedBigInteger('executed_by')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['system', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uat_scenarios');
    }
};
