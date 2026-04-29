<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('payment_status', 30)->default('unpaid')->after('status');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_status');
            $table->decimal('remaining_amount', 12, 2)->default(0)->after('paid_amount');
            $table->date('due_date')->nullable()->after('remaining_amount');

            $table->index(['payment_status', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'status']);
            $table->dropIndex(['due_date']);
            $table->dropColumn([
                'payment_status',
                'paid_amount',
                'remaining_amount',
                'due_date',
            ]);
        });
    }
};
