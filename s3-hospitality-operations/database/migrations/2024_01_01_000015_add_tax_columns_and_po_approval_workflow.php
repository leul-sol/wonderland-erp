<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folio_lines', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->nullable()->after('description');
            $table->decimal('service_charge_rate', 5, 4)->nullable()->after('subtotal');
            $table->decimal('service_charge_amount', 12, 2)->nullable()->after('service_charge_rate');
            $table->decimal('vat_rate', 5, 4)->nullable()->after('service_charge_amount');
            $table->decimal('vat_amount', 12, 2)->nullable()->after('vat_rate');
        });

        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->decimal('service_charge_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('vat_amount', 12, 2)->default(0)->after('service_charge_amount');
            $table->decimal('total_amount', 12, 2)->default(0)->after('vat_amount');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE folio_lines SET subtotal = amount, service_charge_amount = 0, vat_amount = 0, service_charge_rate = 0, vat_rate = 0 WHERE subtotal IS NULL');
            DB::statement('UPDATE restaurant_orders SET total_amount = subtotal WHERE total_amount = 0');
        } else {
            DB::table('folio_lines')->whereNull('subtotal')->update([
                'subtotal' => DB::raw('amount'),
                'service_charge_amount' => 0,
                'vat_amount' => 0,
                'service_charge_rate' => 0,
                'vat_rate' => 0,
            ]);
            DB::table('restaurant_orders')->where('total_amount', 0)->update([
                'total_amount' => DB::raw('subtotal'),
            ]);
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('approval_tier')->default(1)->after('total_amount');
        });

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('status', 30)->default('draft')->change();
            });
        } else {
            DB::statement("ALTER TABLE purchase_orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'draft'");
        }

        DB::table('purchase_orders')->where('status', 'approved')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('approval_tier');
        });

        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->dropColumn(['service_charge_amount', 'vat_amount', 'total_amount']);
        });

        Schema::table('folio_lines', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'service_charge_rate',
                'service_charge_amount',
                'vat_rate',
                'vat_amount',
            ]);
        });
    }
};
