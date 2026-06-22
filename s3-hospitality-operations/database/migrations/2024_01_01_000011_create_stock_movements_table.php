<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->enum('movement_type', ['receipt', 'sale', 'adjustment']);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('reference_type', 30);
            $table->unsignedBigInteger('reference_id');
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
