<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'sort_order',
        'quantity',
        'unit_sale_price',
        'item_discount_type',
        'item_discount_value',
        'item_discount_amount',
        'cost_at_sale_time',
        'line_total',
        'line_profit',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'quantity' => 'decimal:2',
            'unit_sale_price' => 'decimal:2',
            'item_discount_value' => 'decimal:2',
            'item_discount_amount' => 'decimal:2',
            'cost_at_sale_time' => 'decimal:2',
            'line_total' => 'decimal:2',
            'line_profit' => 'decimal:2',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
