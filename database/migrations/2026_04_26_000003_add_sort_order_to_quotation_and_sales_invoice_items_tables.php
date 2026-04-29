<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('product_id');
            $table->index(['quotation_id', 'sort_order']);
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('product_id');
            $table->index(['sales_invoice_id', 'sort_order']);
        });

        $this->backfillSortOrder('quotation_items', 'quotation_id');
        $this->backfillSortOrder('sales_invoice_items', 'sales_invoice_id');
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropIndex(['quotation_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropIndex(['sales_invoice_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }

    private function backfillSortOrder(string $table, string $parentColumn): void
    {
        $parentIds = DB::table($table)
            ->select($parentColumn)
            ->distinct()
            ->orderBy($parentColumn)
            ->pluck($parentColumn);

        foreach ($parentIds as $parentId) {
            $itemIds = DB::table($table)
                ->where($parentColumn, $parentId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($itemIds as $index => $itemId) {
                DB::table($table)
                    ->where('id', $itemId)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }
};
