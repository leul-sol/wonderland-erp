<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cache_log', function (Blueprint $table) {
            $table->id();
            $table->string('report_key', 120);
            $table->enum('event', ['hit', 'invalidated']);
            $table->unsignedInteger('ttl_seconds')->nullable();
            $table->string('source_event', 120)->nullable();
            $table->timestamp('cached_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['report_key', 'event']);
            $table->index('invalidated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cache_log');
    }
};
