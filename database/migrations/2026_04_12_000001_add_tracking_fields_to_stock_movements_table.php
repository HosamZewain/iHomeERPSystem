<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_movements', 'created_by')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('source_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('stock_movements', 'balance_after')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->decimal('balance_after', 12, 2)->nullable()->after('quantity');
            });

            $balances = [];

            $movements = DB::table('stock_movements')
                ->select(['id', 'product_id', 'quantity'])
                ->orderBy('product_id')
                ->orderBy('movement_date')
                ->orderBy('id')
                ->get();

            foreach ($movements as $movement) {
                $balances[$movement->product_id] = ($balances[$movement->product_id] ?? 0) + (float) $movement->quantity;

                DB::table('stock_movements')
                    ->where('id', $movement->id)
                    ->update(['balance_after' => round($balances[$movement->product_id], 2)]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_movements', 'created_by')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasColumn('stock_movements', 'balance_after')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropColumn('balance_after');
            });
        }
    }
};
