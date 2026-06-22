<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->decimal('price', 10, 2);
            $table->string('category', 50)->default('food');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_item_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->decimal('quantity', 10, 3);
            $table->unique(['menu_item_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_ingredients');
        Schema::dropIfExists('menu_items');
    }
};
