<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('vendor_name', 150)->nullable();
            $table->string('source_reference', 100);
            $table->string('source_module', 10)->default('s3');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->enum('status', ['open', 'settled'])->default('open');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->unique(['source_reference', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
