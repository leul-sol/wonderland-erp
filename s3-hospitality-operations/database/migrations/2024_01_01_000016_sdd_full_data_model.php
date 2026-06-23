<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('item_categories')->nullOnDelete();
            $table->string('rotation_strategy', 10)->default('fifo')->after('unit');
            $table->boolean('is_perishable')->default(false)->after('rotation_strategy');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('contact_name', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('payment_terms', 60)->nullable();
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('po_number')->constrained('suppliers')->nullOnDelete();
            $table->unsignedBigInteger('requested_by')->nullable()->after('total_amount');
            $table->date('expected_delivery_date')->nullable()->after('approved_at');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->decimal('quantity_received', 12, 2)->default(0)->after('quantity');
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamp('received_at');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained('purchase_order_lines')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('unit_cost', 10, 4);
            $table->timestamps();
        });

        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('goods_receipt_line_id')->nullable()->constrained('goods_receipt_lines')->nullOnDelete();
            $table->string('batch_code', 40);
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('quantity_remaining', 12, 2);
            $table->decimal('unit_cost', 10, 4);
            $table->date('received_date');
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('inventory_item_id')->constrained('stock_batches')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable()->after('reference_id');
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->date('payment_date');
            $table->string('reference_number', 80)->nullable();
            $table->boolean('posted_to_finance')->default(false);
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('menu_categories')->nullOnDelete();
            $table->decimal('employee_price', 10, 2)->nullable()->after('price');
            $table->boolean('has_recipe')->default(true)->after('employee_price');
        });

        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number', 10)->unique();
            $table->unsignedTinyInteger('capacity')->default(4);
            $table->string('location', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cashier_id');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash_float', 10, 2)->nullable();
            $table->decimal('closing_cash_counted', 10, 2)->nullable();
            $table->decimal('expected_cash', 10, 2)->nullable();
            $table->decimal('variance', 10, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
        });

        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->foreignId('dining_table_id')->nullable()->after('folio_id')->constrained('dining_tables')->nullOnDelete();
            $table->string('customer_type', 30)->default('outside_cash')->after('dining_table_id');
            $table->unsignedBigInteger('customer_ref_id')->nullable()->after('customer_type');
            $table->unsignedBigInteger('cashier_id')->nullable()->after('customer_ref_id');
            $table->timestamp('opened_at')->nullable()->after('cashier_id');
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_order_id')->unique()->constrained('restaurant_orders')->cascadeOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('service_charge_rate', 5, 4);
            $table->decimal('service_charge_amount', 12, 2);
            $table->decimal('vat_rate', 5, 4);
            $table->decimal('vat_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('outstanding_balance', 12, 2);
            $table->string('status', 30)->default('unpaid');
            $table->timestamps();
        });

        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->unsignedBigInteger('cashier_id')->nullable();
            $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->timestamp('paid_at');
            $table->string('reference_number', 80)->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('employee_consumption', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('period', 7);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->boolean('pushed_to_payroll')->default(false);
            $table->timestamps();
            $table->unique(['employee_id', 'period']);
        });

        Schema::create('guest_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 120);
            $table->string('phone', 20)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('id_document_type', 40)->nullable();
            $table->string('id_document_number', 60)->nullable();
            $table->string('nationality', 60)->nullable();
            $table->string('address', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('guest_id')->nullable()->after('id')->constrained('guest_profiles')->nullOnDelete();
            $table->decimal('quoted_rate', 10, 2)->nullable()->after('check_out_date');
            $table->unsignedSmallInteger('total_nights')->nullable()->after('quoted_rate');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
        });

        Schema::table('folios', function (Blueprint $table) {
            $table->string('folio_number', 20)->nullable()->unique()->after('id');
            $table->foreignId('guest_id')->nullable()->after('reservation_id')->constrained('guest_profiles')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->after('guest_id')->constrained('rooms')->nullOnDelete();
            $table->decimal('outstanding_balance', 14, 2)->default(0)->after('total_payments');
            $table->timestamp('opened_at')->nullable()->after('settled_at');
        });

        Schema::create('folio_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('folios')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->unsignedBigInteger('cashier_id')->nullable();
            $table->foreignId('cashier_shift_id')->constrained('cashier_shifts');
            $table->timestamp('paid_at');
            $table->string('reference_number', 80)->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 80)->unique();
            $table->string('endpoint', 120);
            $table->string('request_hash', 64);
            $table->json('response_body');
            $table->unsignedSmallInteger('status_code');
            $table->timestamp('created_at');
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('folio_payments');
        Schema::dropIfExists('bill_payments');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('employee_consumption');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('stock_batches');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('cashier_shifts');
        Schema::dropIfExists('dining_tables');
        Schema::dropIfExists('menu_categories');
        Schema::dropIfExists('guest_profiles');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('item_categories');
    }
};
