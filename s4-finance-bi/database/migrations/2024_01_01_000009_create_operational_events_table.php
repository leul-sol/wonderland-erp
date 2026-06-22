<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_events', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 80);
            $table->string('source_system', 10);
            $table->uuid('request_id')->nullable();
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['channel', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_events');
    }
};
