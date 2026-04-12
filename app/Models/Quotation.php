<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Quotation extends Model
{
    use HasFactory;

    public const DISCOUNT_FIXED = 'fixed';
    public const DISCOUNT_PERCENTAGE = 'percentage';
    public const INSTALLATION_FIXED = 'fixed';
    public const INSTALLATION_PERCENTAGE = 'percentage';

    protected $fillable = [
        'quotation_number',
        'customer_id',
        'quotation_date',
        'notes',
        'subtotal',
        'invoice_discount_type',
        'invoice_discount_value',
        'invoice_discount_amount',
        'installation_enabled',
        'installation_pricing_mode',
        'installation_percentage_value',
        'installation_fixed_amount',
        'installation_total',
        'installation_notes',
        'total',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'subtotal' => 'decimal:2',
            'invoice_discount_value' => 'decimal:2',
            'invoice_discount_amount' => 'decimal:2',
            'installation_enabled' => 'boolean',
            'installation_percentage_value' => 'decimal:2',
            'installation_fixed_amount' => 'decimal:2',
            'installation_total' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => QuotationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation) {
            $quotation->status ??= QuotationStatus::Draft;
            $quotation->invoice_discount_type ??= self::DISCOUNT_FIXED;
            $quotation->installation_pricing_mode ??= self::INSTALLATION_FIXED;
        });
    }

    public static function nextQuotationNumber(): string
    {
        $prefix = Setting::get('quotation_prefix', 'QUO') ?: 'QUO';
        $year = now()->format('Y');
        $base = $prefix . '-' . $year . '-';

        $lastNumber = self::query()
            ->where('quotation_number', 'like', $base . '%')
            ->orderByDesc('id')
            ->value('quotation_number');

        $next = $lastNumber ? ((int) str_replace($base, '', $lastNumber)) + 1 : 1;

        return $base . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function salesInvoice(): HasOne
    {
        return $this->hasOne(SalesInvoice::class);
    }

    public function canEdit(): bool
    {
        return $this->status !== QuotationStatus::Converted;
    }

    public function canConvert(): bool
    {
        $hasItems = array_key_exists('items_count', $this->attributes)
            ? (int) $this->attributes['items_count'] > 0
            : $this->items()->exists();

        $hasSalesInvoice = $this->relationLoaded('salesInvoice')
            ? $this->salesInvoice !== null
            : $this->salesInvoice()->exists();

        return $this->status !== QuotationStatus::Converted
            && $hasItems
            && ! $hasSalesInvoice;
    }

    public function convertToSalesInvoice(?User $user = null): SalesInvoice
    {
        return DB::transaction(function () use ($user) {
            $quotation = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->with(['items'])
                ->withCount('items')
                ->firstOrFail();

            if (! $quotation->canConvert()) {
                throw ValidationException::withMessages([
                    'quotation' => 'لا يمكن تحويل عرض السعر هذا. ربما تم تحويله بالفعل أو لا يحتوي على بنود.',
                ]);
            }

            $invoice = SalesInvoice::create([
                'invoice_number' => SalesInvoice::nextInvoiceNumber(),
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'sales_channel' => SalesChannel::Direct,
                'partner_id' => null,
                'invoice_date' => now()->toDateString(),
                'notes' => $quotation->notes,
                'subtotal' => (float) $quotation->subtotal,
                'invoice_discount_type' => $quotation->invoice_discount_type,
                'invoice_discount_value' => (float) $quotation->invoice_discount_value,
                'invoice_discount_amount' => (float) $quotation->invoice_discount_amount,
                'installation_enabled' => (bool) $quotation->installation_enabled,
                'installation_pricing_mode' => $quotation->installation_pricing_mode,
                'installation_percentage_value' => (float) $quotation->installation_percentage_value,
                'installation_fixed_amount' => (float) $quotation->installation_fixed_amount,
                'installation_total' => (float) $quotation->installation_total,
                'installation_party_type' => SalesInvoice::INSTALLATION_PARTY_NONE,
                'installation_party_reference' => null,
                'installation_payout_amount' => 0,
                'installation_profit' => (float) $quotation->installation_total,
                'product_profit' => 0,
                'installation_notes' => $quotation->installation_notes,
                'gross_total' => (float) $quotation->total,
                'partner_commission_type' => SalesInvoice::DISCOUNT_FIXED,
                'partner_commission_value' => 0,
                'partner_commission_amount' => 0,
                'net_revenue_after_partner_commission' => (float) $quotation->total,
                'total_cost' => 0,
                'total_profit' => 0,
                'status' => SalesInvoiceStatus::Draft,
                'created_by' => $user?->id ?? $quotation->created_by,
            ]);

            foreach ($quotation->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'unit_sale_price' => (float) $item->unit_sale_price,
                    'item_discount_type' => $item->item_discount_type,
                    'item_discount_value' => (float) $item->item_discount_value,
                    'item_discount_amount' => (float) $item->item_discount_amount,
                    'cost_at_sale_time' => 0,
                    'line_total' => (float) $item->line_total,
                    'line_profit' => 0,
                ]);
            }

            $quotation->update(['status' => QuotationStatus::Converted]);
            $this->refresh();

            return $invoice->load(['items.product', 'quotation']);
        });
    }

    public static function discountTypes(): array
    {
        return [
            self::DISCOUNT_FIXED => __('ui.discount_types.fixed'),
            self::DISCOUNT_PERCENTAGE => __('ui.discount_types.percentage'),
        ];
    }

    public static function installationPricingModes(): array
    {
        return [
            self::INSTALLATION_FIXED => 'مبلغ ثابت',
            self::INSTALLATION_PERCENTAGE => 'نسبة من إجمالي المنتجات',
        ];
    }

    public static function discountAmount(float $baseAmount, string $type, float $value): float
    {
        $baseAmount = max($baseAmount, 0);
        $value = max($value, 0);

        $discount = $type === self::DISCOUNT_PERCENTAGE
            ? $baseAmount * min($value, 100) / 100
            : $value;

        return round(min($discount, $baseAmount), 2);
    }

    public static function lineTotal(float $quantity, float $unitSalePrice, string $discountType, float $discountValue): float
    {
        $gross = max($quantity, 0) * max($unitSalePrice, 0);
        $discount = self::discountAmount($gross, $discountType, $discountValue);

        return round(max($gross - $discount, 0), 2);
    }

    public static function installationAmount(bool $enabled, string $pricingMode, float $productsSubtotal, float $percentageValue, float $fixedAmount): float
    {
        if (! $enabled) {
            return 0.0;
        }

        if ($pricingMode === self::INSTALLATION_PERCENTAGE) {
            return round(max($productsSubtotal, 0) * min(max($percentageValue, 0), 100) / 100, 2);
        }

        return round(max($fixedAmount, 0), 2);
    }
}
