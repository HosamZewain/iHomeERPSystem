<?php

namespace App\Models;

use App\Enums\PurchaseInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'invoice_date',
        'notes',
        'subtotal',
        'total',
        'status',
        'confirmed_at',
        'cancelled_at',
        'created_by',
        'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => PurchaseInvoiceStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseInvoice $invoice) {
            $invoice->status ??= PurchaseInvoiceStatus::Draft;
        });
    }

    public static function nextInvoiceNumber(): string
    {
        $prefix = Setting::get('purchase_invoice_prefix', 'PUR') ?: 'PUR';
        $year = now()->format('Y');
        $base = $prefix . '-' . $year . '-';

        $lastNumber = self::query()
            ->where('invoice_number', 'like', $base . '%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = $lastNumber ? ((int) str_replace($base, '', $lastNumber)) + 1 : 1;

        return $base . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function canEdit(): bool
    {
        return $this->status === PurchaseInvoiceStatus::Draft;
    }

    public function canConfirm(): bool
    {
        return $this->status === PurchaseInvoiceStatus::Draft && $this->items()->exists();
    }

    public function canCancel(): bool
    {
        return $this->status === PurchaseInvoiceStatus::Draft;
    }

    public function confirm(?User $user = null): void
    {
        DB::transaction(function () use ($user) {
            $invoice = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->with('items.product')
                ->firstOrFail();

            if ($invoice->status !== PurchaseInvoiceStatus::Draft) {
                throw ValidationException::withMessages([
                    'invoice' => 'يمكن تأكيد فواتير الشراء المسودة فقط.',
                ]);
            }

            if ($invoice->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'invoice' => 'أضف بندًا واحدًا على الأقل قبل تأكيد فاتورة الشراء.',
                ]);
            }

            foreach ($invoice->items as $item) {
                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $currentQuantity = $product->current_stock_quantity;
                $quantity = (float) $item->quantity;
                $unitCost = (float) $item->unit_cost;
                $currentAverageCost = (float) $product->current_average_cost;
                $newQuantity = $currentQuantity + $quantity;

                $newAverageCost = $newQuantity > 0
                    ? (($currentQuantity * $currentAverageCost) + ($quantity * $unitCost)) / $newQuantity
                    : $unitCost;

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => StockMovement::TYPE_PURCHASE_IN,
                    'source_type' => StockMovement::SOURCE_PURCHASE_ITEM,
                    'source_id' => $item->id,
                    'created_by' => $user?->id,
                    'quantity' => $quantity,
                    'balance_after' => $newQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => (float) $item->line_total,
                    'movement_date' => $invoice->invoice_date,
                    'notes' => 'فاتورة شراء ' . $invoice->invoice_number,
                ]);

                $product->update(['current_average_cost' => round($newAverageCost, 2)]);
            }

            $invoice->update([
                'status' => PurchaseInvoiceStatus::Confirmed,
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
                'invoice' => 'لا يمكن إلغاء فواتير الشراء المؤكدة من هنا لأن المخزون تغيّر بالفعل. استخدم حركة تسوية مخزون أو مرتجع مورد من مسار مخصص.',
            ]);
        }

        $this->update([
            'status' => PurchaseInvoiceStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
