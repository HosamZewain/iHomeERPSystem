<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('sales_channel', 20)->default('direct');
            $table->foreignId('partner_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('invoice_date');
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('invoice_discount_type', 20)->default('fixed');
            $table->decimal('invoice_discount_value', 12, 2)->default(0);
            $table->decimal('invoice_discount_amount', 12, 2)->default(0);
            $table->decimal('gross_total', 12, 2)->default(0);
            $table->string('partner_commission_type', 20)->default('fixed');
            $table->decimal('partner_commission_value', 12, 2)->default(0);
            $table->decimal('partner_commission_amount', 12, 2)->default(0);
            $table->decimal('net_revenue_after_partner_commission', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('total_profit', 12, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sales_channel', 'status']);
            $table->index(['invoice_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
