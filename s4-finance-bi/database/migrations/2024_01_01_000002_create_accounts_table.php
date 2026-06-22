<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->string('sub_type', 60)->nullable();
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
