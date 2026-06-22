<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('group_code', 20)->unique();
            $table->string('group_name', 150);
            $table->string('contact_name', 150);
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->enum('status', ['confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('confirmed');
            $table->unsignedSmallInteger('room_count')->default(0);
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('group_booking_id')
                ->nullable()
                ->after('id')
                ->constrained('group_bookings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_booking_id');
        });

        Schema::dropIfExists('group_bookings');
    }
};
