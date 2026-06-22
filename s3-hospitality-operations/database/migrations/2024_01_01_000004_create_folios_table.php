<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations');
            $table->enum('status', ['open', 'settled'])->default('open');
            $table->decimal('total_charges', 12, 2)->default(0);
            $table->decimal('total_payments', 12, 2)->default(0);
            $table->string('currency', 3)->default('ETB');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
