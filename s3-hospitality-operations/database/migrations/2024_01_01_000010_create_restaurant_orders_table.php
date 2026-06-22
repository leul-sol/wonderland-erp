<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('folio_id')->nullable()->constrained('folios')->nullOnDelete();
            $table->enum('status', ['open', 'finalized', 'cancelled'])->default('open');
            $table->enum('payment_context', ['folio', 'cash'])->default('cash');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('cogs_total', 12, 2)->default(0);
            $table->string('revenue_journal_entry_id', 50)->nullable();
            $table->string('cogs_journal_entry_id', 50)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_order_id')->constrained('restaurant_orders')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_lines');
        Schema::dropIfExists('restaurant_orders');
    }
};
