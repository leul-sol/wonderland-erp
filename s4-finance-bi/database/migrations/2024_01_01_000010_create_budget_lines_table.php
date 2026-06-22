<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods');
            $table->string('account_code', 20);
            $table->decimal('budget_amount', 14, 2);
            $table->timestamps();

            $table->unique(['fiscal_period_id', 'account_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
