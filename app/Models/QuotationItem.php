<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    public const TYPE_PRODUCT = 'product';
    public const TYPE_SECTION = 'section';

    protected $fillable = [
        'quotation_id',
        'row_type',
        'product_id',
        'section_title',
        'description',
        'sort_order',
        'quantity',
        'unit_sale_price',
        'item_discount_type',
        'item_discount_value',
        'item_discount_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'row_type' => 'string',
            'sort_order' => 'integer',
            'quantity' => 'decimal:2',
            'unit_sale_price' => 'decimal:2',
            'item_discount_value' => 'decimal:2',
            'item_discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuotationItem $item) {
            $item->row_type ??= self::TYPE_PRODUCT;
        });
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isSection(): bool
    {
        return $this->row_type === self::TYPE_SECTION;
    }

    public function isProduct(): bool
    {
        return $this->row_type === self::TYPE_PRODUCT;
    }
}
