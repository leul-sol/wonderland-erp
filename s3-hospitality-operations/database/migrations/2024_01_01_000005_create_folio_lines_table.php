<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('folios');
            $table->enum('line_type', ['charge', 'payment']);
            $table->enum('charge_category', ['room', 'fb', 'other'])->nullable();
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30)->nullable();
            $table->string('s4_journal_entry_id', 50)->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamp('posted_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_lines');
    }
};
