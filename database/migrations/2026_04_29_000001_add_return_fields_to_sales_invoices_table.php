<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('cancelled_at');
            $table->foreignId('returned_by')->nullable()->after('confirmed_by')->constrained('users')->nullOnDelete();
            $table->text('return_reason')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn(['returned_at', 'return_reason']);
        });
    }
};
