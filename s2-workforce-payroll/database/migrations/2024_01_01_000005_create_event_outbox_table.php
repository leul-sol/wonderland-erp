<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('event', 120);
            $table->json('payload');
            $table->enum('status', ['pending', 'published', 'failed'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_outbox');
    }
};
