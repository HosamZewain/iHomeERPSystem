<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('refund_number')->unique();
            $table->date('refund_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sales_invoice_id', 'refund_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_refunds');
    }
};
