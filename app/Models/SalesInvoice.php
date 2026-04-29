<?php

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\InvoicePaymentStatus;
use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesInvoice extends Model
{
    use HasFactory;

    public const DISCOUNT_FIXED = 'fixed';
    public const DISCOUNT_PERCENTAGE = 'percentage';
    public const INSTALLATION_FIXED = 'fixed';
    public const INSTALLATION_PERCENTAGE = 'percentage';
    public const INSTALLATION_PARTY_NONE = 'none';
    public const INSTALLATION_PARTY_INTERNAL = 'internal';
    public const INSTALLATION_PARTY_TECHNICIAN = 'technician';
    public const INSTALLATION_PARTY_EXTERNAL_COMPANY = 'external_company';
    public const INSTALLATION_PARTY_EMPLOYEE = 'employee';

    protected $fillable = [
        'invoice_number',
        'quotation_id',
        'customer_id',
        'sales_channel',
        'partner_id',
        'invoice_date',
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
        'installation_party_type',
        'installation_party_reference',
        'installation_payout_amount',
        'installation_profit',
        'product_profit',
        'installation_notes',
        'gross_total',
        'partner_commission_type',
        'partner_commission_value',
        'partner_commission_amount',
        'net_revenue_after_partner_commission',
        'total_cost',
        'total_profit',
        'status',
        'payment_status',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'return_reason',
        'confirmed_at',
        'cancelled_at',
        'returned_at',
        'created_by',
        'confirmed_by',
        'returned_by',
    ];

    protected function casts(): array
    {
        return [
            'sales_channel' => SalesChannel::class,
            'invoice_date' => 'date',
            'subtotal' => 'decimal:2',
            'invoice_discount_value' => 'decimal:2',
            'invoice_discount_amount' => 'decimal:2',
            'installation_enabled' => 'boolean',
            'installation_percentage_value' => 'decimal:2',
            'installation_fixed_amount' => 'decimal:2',
            'installation_total' => 'decimal:2',
            'installation_payout_amount' => 'decimal:2',
            'installation_profit' => 'decimal:2',
            'product_profit' => 'decimal:2',
            'gross_total' => 'decimal:2',
            'partner_commission_value' => 'decimal:2',
            'partner_commission_amount' => 'decimal:2',
            'net_revenue_after_partner_commission' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_profit' => 'decimal:2',
            'status' => SalesInvoiceStatus::class,
            'payment_status' => InvoicePaymentStatus::class,
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'due_date' => 'date',
            'return_reason' => 'string',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesInvoice $invoice) {
            $invoice->sales_channel ??= SalesChannel::Direct;
            $invoice->status ??= SalesInvoiceStatus::Draft;
            $invoice->invoice_discount_type ??= self::DISCOUNT_FIXED;
            $invoice->partner_commission_type ??= self::DISCOUNT_FIXED;
            $invoice->installation_pricing_mode ??= self::INSTALLATION_FIXED;
            $invoice->installation_party_type ??= self::INSTALLATION_PARTY_NONE;
            $invoice->paid_amount ??= 0;
            $invoice->remaining_amount = round(max((float) $invoice->gross_total - (float) $invoice->paid_amount, 0), 2);
            $invoice->payment_status = InvoicePaymentStatus::fromAmounts((float) $invoice->paid_amount, (float) $invoice->gross_total);
        });
    }

    public static function nextInvoiceNumber(): string
    {
        $prefix = Setting::get('sales_invoice_prefix', 'INV') ?: 'INV';
        $year = now()->format('Y');
        $base = $prefix . '-' . $year . '-';

        $lastNumber = self::query()
            ->where('invoice_number', 'like', $base . '%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = $lastNumber ? ((int) str_replace($base, '', $lastNumber)) + 1 : 1;

        return $base . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesInvoicePayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function canConfirm(): bool
    {
        return $this->status === SalesInvoiceStatus::Draft && $this->items()->exists();
    }

    public function canCancel(): bool
    {
        return $this->status === SalesInvoiceStatus::Draft;
    }

    public function canReceivePayments(): bool
    {
        return $this->status === SalesInvoiceStatus::Confirmed
            && round((float) $this->remaining_amount, 2) > 0;
    }

    public function canReverseConfirmed(): bool
    {
        return $this->status === SalesInvoiceStatus::Confirmed
            && round((float) $this->paid_amount, 2) <= 0;
    }

    public function syncPaymentSummary(): void
    {
        [$paidAmount, $remainingAmount, $paymentStatus] = $this->calculatedPaymentSummary();

        $this->forceFill([
            'payment_status' => $paymentStatus,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
        ])->saveQuietly();
    }

    public function syncPaymentSummaryIfNeeded(): void
    {
        [$paidAmount, $remainingAmount, $paymentStatus] = $this->calculatedPaymentSummary();

        if (
            round((float) $this->paid_amount, 2) === $paidAmount
            && round((float) $this->remaining_amount, 2) === $remainingAmount
            && $this->payment_status === $paymentStatus
        ) {
            return;
        }

        $this->forceFill([
            'payment_status' => $paymentStatus,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
        ])->saveQuietly();
    }

    public function recordPayment(array $attributes, ?User $user = null): SalesInvoicePayment
    {
        return DB::transaction(function () use ($attributes, $user) {
            $invoice = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->withSum('payments', 'amount')
                ->firstOrFail();

            if ($invoice->status === SalesInvoiceStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'payment' => 'لا يمكن تسجيل دفعات على فاتورة بيع ملغاة.',
                ]);
            }

            if ($invoice->status !== SalesInvoiceStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'payment' => 'يمكن تسجيل الدفعات على فواتير البيع المؤكدة فقط.',
                ]);
            }

            $paidAmount = round((float) ($invoice->payments_sum_amount ?? 0), 2);
            $remainingAmount = round(max((float) $invoice->gross_total - $paidAmount, 0), 2);
            $amount = round((float) ($attributes['amount'] ?? 0), 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'قيمة الدفعة يجب أن تكون أكبر من صفر.',
                ]);
            }

            if ($remainingAmount <= 0) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'الفاتورة مسددة بالكامل بالفعل.',
                ]);
            }

            if ($amount > $remainingAmount) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'قيمة الدفعة أكبر من الرصيد المتبقي على الفاتورة.',
                ]);
            }

            $payment = $invoice->payments()->create([
                'receipt_number' => $attributes['receipt_number'] ?? null,
                'payment_date' => $attributes['payment_date'],
                'amount' => $amount,
                'payment_method' => $attributes['payment_method'],
                'reference_number' => $attributes['reference_number'] ?: null,
                'notes' => $attributes['notes'] ?: null,
                'remaining_amount_after' => round($remainingAmount - $amount, 2),
                'received_by' => $attributes['received_by'] ?? $user?->id,
                'created_by' => $user?->id,
            ]);

            $invoice->syncPaymentSummary();
            $this->refresh();

            return $payment->load(['creator', 'receiver', 'salesInvoice.customer']);
        });
    }

    public function confirm(?User $user = null): void
    {
        DB::transaction(function () use ($user) {
            $invoice = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->with('items.product')
                ->firstOrFail();

            if ($invoice->status !== SalesInvoiceStatus::Draft) {
                throw ValidationException::withMessages([
                    'invoice' => 'يمكن تأكيد فواتير البيع المسودة فقط.',
                ]);
            }

            if ($invoice->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'invoice' => 'أضف بندًا واحدًا على الأقل قبل تأكيد فاتورة البيع.',
                ]);
            }

            if ($invoice->sales_channel === SalesChannel::Partner && ! $invoice->partner_id) {
                throw ValidationException::withMessages([
                    'partner_id' => 'اختر الشريك قبل تأكيد فاتورة بيع من خلال شريك.',
                ]);
            }

            $productIds = $invoice->items->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()
                ->whereKey($productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $requiredQuantities = $invoice->items
                ->groupBy('product_id')
                ->map(fn ($items) => $items->sum(fn ($item) => (float) $item->quantity));

            foreach ($requiredQuantities as $productId => $requiredQuantity) {
                $product = $products->get((int) $productId);

                if (! $product || $product->current_stock_quantity < $requiredQuantity) {
                    throw ValidationException::withMessages([
                        'stock' => 'المخزون غير كافٍ للمنتج: ' . ($product?->name ?: 'غير معروف'),
                    ]);
                }
            }

            $balances = [];
            $totalCost = 0.0;

            foreach ($invoice->items as $item) {
                $product = $products->get($item->product_id);
                $currentQuantity = $balances[$product->id] ?? $product->current_stock_quantity;
                $quantity = (float) $item->quantity;
                $costAtSale = (float) $product->current_average_cost;
                $lineCost = round($quantity * $costAtSale, 2);
                $lineProfit = round((float) $item->line_total - $lineCost, 2);
                $balanceAfter = round($currentQuantity - $quantity, 2);

                $item->update([
                    'cost_at_sale_time' => round($costAtSale, 2),
                    'line_profit' => $lineProfit,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => StockMovement::TYPE_SALE_OUT,
                    'source_type' => StockMovement::SOURCE_SALES_ITEM,
                    'source_id' => $item->id,
                    'created_by' => $user?->id,
                    'quantity' => -$quantity,
                    'balance_after' => $balanceAfter,
                    'unit_cost' => $costAtSale,
                    'total_cost' => $lineCost,
                    'movement_date' => $invoice->invoice_date,
                    'notes' => 'فاتورة بيع ' . $invoice->invoice_number,
                ]);

                $balances[$product->id] = $balanceAfter;
                $totalCost += $lineCost;
            }

            $subtotal = round($invoice->items->sum(fn ($item) => (float) $item->line_total), 2);
            $invoiceDiscountAmount = self::discountAmount($subtotal, $invoice->invoice_discount_type, (float) $invoice->invoice_discount_value);
            $netProductsTotal = round(max($subtotal - $invoiceDiscountAmount, 0), 2);
            $installationTotal = self::installationAmount(
                (bool) $invoice->installation_enabled,
                $invoice->installation_pricing_mode,
                $subtotal,
                (float) $invoice->installation_percentage_value,
                (float) $invoice->installation_fixed_amount,
            );
            $grossTotal = round($netProductsTotal + $installationTotal, 2);
            $partnerCommissionAmount = $invoice->sales_channel === SalesChannel::Partner
                ? self::commissionAmount($grossTotal, $invoice->partner_commission_type, (float) $invoice->partner_commission_value)
                : 0.0;
            $netRevenue = round(max($grossTotal - $partnerCommissionAmount, 0), 2);
            $installationPayout = (float) $invoice->installation_payout_amount;
            $productProfit = round($netProductsTotal - $totalCost, 2);
            $installationProfit = round($installationTotal - $installationPayout, 2);

            $invoice->update([
                'subtotal' => $subtotal,
                'invoice_discount_amount' => $invoiceDiscountAmount,
                'installation_total' => $installationTotal,
                'installation_profit' => $installationProfit,
                'product_profit' => $productProfit,
                'gross_total' => $grossTotal,
                'partner_commission_amount' => $partnerCommissionAmount,
                'net_revenue_after_partner_commission' => $netRevenue,
                'total_cost' => round($totalCost, 2),
                'total_profit' => round($productProfit + $installationProfit - $partnerCommissionAmount, 2),
                'status' => SalesInvoiceStatus::Confirmed,
                'payment_status' => InvoicePaymentStatus::fromAmounts((float) $invoice->paid_amount, $grossTotal),
                'remaining_amount' => round(max($grossTotal - (float) $invoice->paid_amount, 0), 2),
                'confirmed_at' => now(),
                'confirmed_by' => $user?->id,
            ]);

            $this->refresh();
        });
    }

    public function cancelDraft(): void
    {
        if (! $this->canCancel()) {
            throw ValidationException::withMessages([
                'invoice' => 'لا يمكن إلغاء فواتير البيع المؤكدة من هنا لأن المخزون تغيّر بالفعل. استخدم مرتجع بيع أو تسوية مخزون من مسار مخصص.',
            ]);
        }

        $this->update([
            'status' => SalesInvoiceStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function reverseConfirmed(string $reason, ?User $user = null): void
    {
        DB::transaction(function () use ($reason, $user) {
            $invoice = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->with(['items.product'])
                ->withSum('payments', 'amount')
                ->firstOrFail();

            if ($invoice->status !== SalesInvoiceStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'invoice' => 'يمكن تنفيذ مرتجع فقط على فواتير البيع المؤكدة.',
                ]);
            }

            if ($invoice->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'invoice' => 'لا يمكن تنفيذ المرتجع لأن الفاتورة لا تحتوي على بنود.',
                ]);
            }

            if (round((float) ($invoice->payments_sum_amount ?? 0), 2) > 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'لا يمكن تنفيذ مرتجع كامل لفاتورة يوجد عليها تحصيل مسجل. عالج الدفعات أولًا من مسار مخصص.',
                ]);
            }

            $productIds = $invoice->items->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()
                ->whereKey($productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $balances = [];
            $movementDate = now()->toDateString();

            foreach ($invoice->items as $item) {
                $product = $products->get($item->product_id);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'invoice' => 'تعذر العثور على المنتج المرتبط بأحد بنود الفاتورة لتنفيذ المرتجع.',
                    ]);
                }

                $currentQuantity = $balances[$product->id] ?? $product->current_stock_quantity;
                $quantity = round((float) $item->quantity, 2);
                $costAtSale = round((float) ($item->cost_at_sale_time ?: $product->current_average_cost), 2);
                $lineCost = round($quantity * $costAtSale, 2);
                $balanceAfter = round($currentQuantity + $quantity, 2);

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => StockMovement::TYPE_RETURN_IN,
                    'source_type' => StockMovement::SOURCE_RETURN,
                    'source_id' => $item->id,
                    'created_by' => $user?->id,
                    'quantity' => $quantity,
                    'balance_after' => $balanceAfter,
                    'unit_cost' => $costAtSale,
                    'total_cost' => $lineCost,
                    'movement_date' => $movementDate,
                    'notes' => 'مرتجع فاتورة بيع ' . $invoice->invoice_number . ($reason !== '' ? ' - ' . $reason : ''),
                ]);

                $balances[$product->id] = $balanceAfter;
            }

            $invoice->update([
                'status' => SalesInvoiceStatus::Returned,
                'remaining_amount' => 0,
                'return_reason' => $reason,
                'returned_at' => now(),
                'returned_by' => $user?->id,
            ]);

            $this->refresh();
        });
    }

    public static function discountTypes(): array
    {
        return [
            self::DISCOUNT_FIXED => __('ui.discount_types.fixed'),
            self::DISCOUNT_PERCENTAGE => __('ui.discount_types.percentage'),
        ];
    }

    public static function commissionTypes(): array
    {
        return [
            CommissionType::Fixed->value => CommissionType::Fixed->label(),
            CommissionType::Percentage->value => CommissionType::Percentage->label(),
        ];
    }

    public static function installationPricingModes(): array
    {
        return [
            self::INSTALLATION_FIXED => 'مبلغ ثابت',
            self::INSTALLATION_PERCENTAGE => 'نسبة من إجمالي المنتجات',
        ];
    }

    public static function paymentStatuses(): array
    {
        return collect(InvoicePaymentStatus::cases())
            ->mapWithKeys(fn (InvoicePaymentStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public static function installationPartyTypes(): array
    {
        return [
            self::INSTALLATION_PARTY_NONE => 'بدون طرف محدد',
            self::INSTALLATION_PARTY_INTERNAL => 'داخلي',
            self::INSTALLATION_PARTY_TECHNICIAN => 'فني',
            self::INSTALLATION_PARTY_EXTERNAL_COMPANY => 'شركة خارجية',
            self::INSTALLATION_PARTY_EMPLOYEE => 'موظف',
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

    public static function commissionAmount(float $grossTotal, string $type, float $value): float
    {
        return self::discountAmount($grossTotal, $type, $value);
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

    private function calculatedPaymentSummary(): array
    {
        $paidAmount = round((float) $this->payments()->sum('amount'), 2);
        $remainingAmount = in_array($this->status, [SalesInvoiceStatus::Cancelled, SalesInvoiceStatus::Returned], true)
            ? 0.0
            : round(max((float) $this->gross_total - $paidAmount, 0), 2);
        $paymentStatus = InvoicePaymentStatus::fromAmounts($paidAmount, (float) $this->gross_total);

        return [$paidAmount, $remainingAmount, $paymentStatus];
    }
}
