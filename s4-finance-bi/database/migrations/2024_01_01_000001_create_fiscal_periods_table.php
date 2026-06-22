<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('period_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closing', 'closed', 'locked'])->default('open');
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['year', 'period_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
