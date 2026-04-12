<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'internal_sku',
        'barcode',
        'image_path',
        'category_id',
        'supplier_id',
        'sale_price',
        'current_average_cost',
        'minimum_stock_alert_level',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'current_average_cost' => 'decimal:2',
            'minimum_stock_alert_level' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function salesInvoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getCurrentStockQuantityAttribute(): float
    {
        if (array_key_exists('current_stock_quantity', $this->attributes)) {
            return (float) $this->attributes['current_stock_quantity'];
        }

        if (! Schema::hasTable('stock_movements') || ! Schema::hasColumn('stock_movements', 'product_id')) {
            return 0;
        }

        return (float) DB::table('stock_movements')
            ->where('product_id', $this->getKey())
            ->sum('quantity');
    }

    public function scopeWithStockQuantity(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select('products.*');
        }

        return $query->addSelect([
            'current_stock_quantity' => StockMovement::query()
                ->selectRaw('COALESCE(SUM(quantity), 0)')
                ->whereColumn('stock_movements.product_id', 'products.id'),
        ]);
    }

    public static function stockQuantitySubquerySql(string $productTable = 'products'): string
    {
        return '(select coalesce(sum(quantity), 0) from stock_movements where stock_movements.product_id = '.$productTable.'.id)';
    }

    public function getStockValueAtAverageCostAttribute(): float
    {
        return round($this->current_stock_quantity * (float) $this->current_average_cost, 2);
    }

    public function getStockValueAtSalePriceAttribute(): float
    {
        return round($this->current_stock_quantity * (float) $this->sale_price, 2);
    }

    public function isLowStock(): bool
    {
        return (float) $this->minimum_stock_alert_level > 0
            && $this->current_stock_quantity <= (float) $this->minimum_stock_alert_level;
    }

    public function canDelete(): bool
    {
        $links = [
            ['quotation_items', 'product_id'],
            ['sales_invoice_items', 'product_id'],
            ['purchase_invoice_items', 'product_id'],
            ['stock_movements', 'product_id'],
        ];

        foreach ($links as [$table, $column]) {
            if ($this->hasLinkedRecords($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasLinkedRecords(string $table, string $column): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, $column)
            && DB::table($table)->where($column, $this->getKey())->exists();
    }
}
