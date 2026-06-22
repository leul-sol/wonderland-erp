<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('confirmation_code', 20)->unique();
            $table->string('guest_name', 150);
            $table->string('guest_email', 150)->nullable();
            $table->string('guest_phone', 30)->nullable();
            $table->foreignId('room_type_id')->constrained('room_types');
            $table->foreignId('room_id')->nullable()->constrained('rooms');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->enum('status', ['confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('confirmed');
            $table->unsignedInteger('adults')->default(1);
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
