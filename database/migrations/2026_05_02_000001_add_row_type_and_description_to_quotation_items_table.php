<?php

use App\Models\QuotationItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('row_type', 20)->default(QuotationItem::TYPE_PRODUCT)->after('quotation_id');
            $table->string('section_title')->nullable()->after('product_id');
            $table->text('description')->nullable()->after('section_title');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->index(['quotation_id', 'row_type']);
        });

        DB::table('quotation_items')->update([
            'row_type' => QuotationItem::TYPE_PRODUCT,
        ]);
    }

    public function down(): void
    {
        DB::table('quotation_items')
            ->whereNull('product_id')
            ->delete();

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropIndex(['quotation_id', 'row_type']);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
            $table->dropColumn(['row_type', 'section_title', 'description']);
        });
    }
};
