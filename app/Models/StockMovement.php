<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_PURCHASE_IN = 'purchase_in';
    public const TYPE_SALE_OUT = 'sale_out';
    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';
    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';
    public const TYPE_RETURN_IN = 'return_in';
    public const TYPE_RETURN_OUT = 'return_out';

    public const SOURCE_PURCHASE_ITEM = 'purchase_invoice_item';
    public const SOURCE_SALES_ITEM = 'sales_invoice_item';
    public const SOURCE_ADJUSTMENT = 'stock_adjustment';
    public const SOURCE_RETURN = 'stock_return';

    protected $fillable = [
        'product_id',
        'movement_type',
        'source_type',
        'source_id',
        'reference_type',
        'reference_id',
        'created_by',
        'quantity',
        'balance_after',
        'unit_cost',
        'total_cost',
        'movement_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'movement_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function referenceType(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->source_type,
            set: fn ($value) => ['source_type' => $value],
        );
    }

    protected function referenceId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->source_id,
            set: fn ($value) => ['source_id' => $value],
        );
    }

    public function getQuantityInAttribute(): float
    {
        return max((float) $this->quantity, 0);
    }

    public function getQuantityOutAttribute(): float
    {
        return abs(min((float) $this->quantity, 0));
    }

    public function isIncrease(): bool
    {
        return (float) $this->quantity > 0;
    }

    public function movementTypeLabel(): string
    {
        return self::labelForMovementType($this->movement_type);
    }

    public static function labelForMovementType(string $movementType): string
    {
        return match ($movementType) {
            self::TYPE_PURCHASE_IN => 'شراء',
            self::TYPE_SALE_OUT => 'بيع',
            self::TYPE_ADJUSTMENT_IN => 'تسوية زيادة',
            self::TYPE_ADJUSTMENT_OUT => 'تسوية نقص',
            self::TYPE_RETURN_IN => 'مرتجع داخل',
            self::TYPE_RETURN_OUT => 'مرتجع خارج',
            default => $movementType,
        };
    }

    public function movementTypeColor(): string
    {
        return $this->isIncrease() ? 'green' : 'red';
    }

    public function referenceTypeLabel(): string
    {
        return match ($this->source_type) {
            self::SOURCE_PURCHASE_ITEM => 'فاتورة شراء',
            self::SOURCE_SALES_ITEM => 'فاتورة بيع',
            self::SOURCE_ADJUSTMENT => 'تسوية مخزون',
            self::SOURCE_RETURN => 'مرتجع',
            default => $this->source_type,
        };
    }
}
