<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 20)->unique();
            $table->date('entry_date');
            $table->string('description', 255);
            $table->enum('source_module', ['s2', 's3', 'manual']);
            $table->string('source_reference', 100)->nullable();
            $table->enum('status', ['draft', 'approved', 'posted', 'reversed'])->default('draft');
            $table->decimal('total_debit', 14, 2)->default(0);
            $table->decimal('total_credit', 14, 2)->default(0);
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('fiscal_period_id')->constrained('fiscal_periods');
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->string('description', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
    }
};
