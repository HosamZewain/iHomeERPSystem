<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'refund_number',
        'refund_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'refund_date' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesInvoiceRefund $refund) {
            if (blank($refund->refund_number)) {
                $refund->refund_number = self::nextRefundNumber();
            }
        });
    }

    public static function nextRefundNumber(): string
    {
        $prefix = Setting::get('sales_refund_receipt_prefix', 'RFD') ?: 'RFD';
        $year = now()->format('Y');
        $base = $prefix . '-' . $year . '-';

        $lastNumber = self::query()
            ->where('refund_number', 'like', $base . '%')
            ->orderByDesc('id')
            ->value('refund_number');

        $next = $lastNumber ? ((int) str_replace($base, '', $lastNumber)) + 1 : 1;

        return $base . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
