<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtm_entries', function (Blueprint $table) {
            $table->id();
            $table->string('requirement_key', 40)->unique();
            $table->enum('system', ['S1', 'S2', 'S3', 'S4']);
            $table->string('domain', 50);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('spec_section', 80)->nullable();
            $table->enum('status', ['planned', 'in_progress', 'implemented', 'verified', 'deferred'])->default('planned');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['system', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtm_entries');
    }
};
