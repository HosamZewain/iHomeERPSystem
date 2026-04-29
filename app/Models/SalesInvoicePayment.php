<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'receipt_number',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'remaining_amount_after',
        'received_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'remaining_amount_after' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesInvoicePayment $payment) {
            if (blank($payment->receipt_number)) {
                $payment->receipt_number = self::nextReceiptNumber();
            }
        });
    }

    public static function nextReceiptNumber(): string
    {
        $prefix = Setting::get('sales_payment_receipt_prefix', 'RCV') ?: 'RCV';
        $year = now()->format('Y');
        $base = $prefix . '-' . $year . '-';

        $lastNumber = self::query()
            ->where('receipt_number', 'like', $base . '%')
            ->orderByDesc('id')
            ->value('receipt_number');

        $next = $lastNumber ? ((int) str_replace($base, '', $lastNumber)) + 1 : 1;

        return $base . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public static function methods(): array
    {
        return collect(PaymentMethod::cases())
            ->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->label()])
            ->all();
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
