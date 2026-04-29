<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained('expense_categories');
            $table->foreignId('generated_from_expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('expense_date');
            $table->string('expense_type')->default('one_time');
            $table->string('recurring_frequency')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->string('vendor_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['expense_date', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
