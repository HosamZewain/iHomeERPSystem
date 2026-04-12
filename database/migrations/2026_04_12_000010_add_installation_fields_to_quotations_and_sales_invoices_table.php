<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->boolean('installation_enabled')->default(false)->after('invoice_discount_amount');
            $table->string('installation_pricing_mode', 20)->default('fixed')->after('installation_enabled');
            $table->decimal('installation_percentage_value', 12, 2)->default(0)->after('installation_pricing_mode');
            $table->decimal('installation_fixed_amount', 12, 2)->default(0)->after('installation_percentage_value');
            $table->decimal('installation_total', 12, 2)->default(0)->after('installation_fixed_amount');
            $table->text('installation_notes')->nullable()->after('installation_total');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->boolean('installation_enabled')->default(false)->after('invoice_discount_amount');
            $table->string('installation_pricing_mode', 20)->default('fixed')->after('installation_enabled');
            $table->decimal('installation_percentage_value', 12, 2)->default(0)->after('installation_pricing_mode');
            $table->decimal('installation_fixed_amount', 12, 2)->default(0)->after('installation_percentage_value');
            $table->decimal('installation_total', 12, 2)->default(0)->after('installation_fixed_amount');
            $table->string('installation_party_type', 30)->default('none')->after('installation_total');
            $table->string('installation_party_reference')->nullable()->after('installation_party_type');
            $table->decimal('installation_payout_amount', 12, 2)->default(0)->after('installation_party_reference');
            $table->decimal('installation_profit', 12, 2)->default(0)->after('installation_payout_amount');
            $table->decimal('product_profit', 12, 2)->default(0)->after('installation_profit');
            $table->text('installation_notes')->nullable()->after('product_profit');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'installation_enabled',
                'installation_pricing_mode',
                'installation_percentage_value',
                'installation_fixed_amount',
                'installation_total',
                'installation_party_type',
                'installation_party_reference',
                'installation_payout_amount',
                'installation_profit',
                'product_profit',
                'installation_notes',
            ]);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'installation_enabled',
                'installation_pricing_mode',
                'installation_percentage_value',
                'installation_fixed_amount',
                'installation_total',
                'installation_notes',
            ]);
        });
    }
};
