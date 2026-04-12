<?php

namespace App\Livewire\SalesInvoices;

use App\Enums\CommissionType;
use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SalesInvoiceCreate extends Component
{
    public ?SalesInvoice $invoice = null;
    public bool $isEditing = false;
    public string $invoice_number = '';
    public string $customer_id = '';
    public string $customerSearch = '';
    public array $productSearch = [];
    public string $sales_channel = 'direct';
    public string $partner_id = '';
    public string $invoice_date = '';
    public string $notes = '';
    public string $invoice_discount_type = SalesInvoice::DISCOUNT_FIXED;
    public string $invoice_discount_value = '0';
    public bool $installation_enabled = false;
    public string $installation_pricing_mode = SalesInvoice::INSTALLATION_FIXED;
    public string $installation_percentage_value = '0';
    public string $installation_fixed_amount = '0';
    public string $installation_party_type = SalesInvoice::INSTALLATION_PARTY_NONE;
    public string $installation_party_reference = '';
    public string $installation_payout_amount = '0';
    public string $installation_notes = '';
    public string $partner_commission_type = SalesInvoice::DISCOUNT_FIXED;
    public string $partner_commission_value = '0';
    public array $items = [];

    public function mount(?SalesInvoice $salesInvoice = null): void
    {
        if ($salesInvoice?->exists) {
            abort_unless($salesInvoice->status === SalesInvoiceStatus::Draft, 403);

            $this->invoice = $salesInvoice->load(['customer', 'items.product']);
            $this->isEditing = true;
            $this->invoice_number = $salesInvoice->invoice_number;
            $this->customer_id = $salesInvoice->customer_id ? (string) $salesInvoice->customer_id : '';
            $this->customerSearch = $salesInvoice->customer ? $this->customerLabel($salesInvoice->customer) : '';
            $this->sales_channel = $salesInvoice->sales_channel->value;
            $this->partner_id = $salesInvoice->partner_id ? (string) $salesInvoice->partner_id : '';
            $this->invoice_date = $salesInvoice->invoice_date->toDateString();
            $this->notes = $salesInvoice->notes ?? '';
            $this->invoice_discount_type = $salesInvoice->invoice_discount_type;
            $this->invoice_discount_value = (string) $salesInvoice->invoice_discount_value;
            $this->installation_enabled = (bool) $salesInvoice->installation_enabled;
            $this->installation_pricing_mode = $salesInvoice->installation_pricing_mode;
            $this->installation_percentage_value = (string) $salesInvoice->installation_percentage_value;
            $this->installation_fixed_amount = (string) $salesInvoice->installation_fixed_amount;
            $this->installation_party_type = $salesInvoice->installation_party_type;
            $this->installation_party_reference = $salesInvoice->installation_party_reference ?? '';
            $this->installation_payout_amount = (string) $salesInvoice->installation_payout_amount;
            $this->installation_notes = $salesInvoice->installation_notes ?? '';
            $this->partner_commission_type = $salesInvoice->partner_commission_type;
            $this->partner_commission_value = (string) $salesInvoice->partner_commission_value;
            $this->items = $salesInvoice->items->map(fn ($item) => [
                'product_id' => (string) $item->product_id,
                'quantity' => (string) $item->quantity,
                'unit_sale_price' => (string) $item->unit_sale_price,
                'item_discount_type' => $item->item_discount_type,
                'item_discount_value' => (string) $item->item_discount_value,
            ])->values()->all();
            $this->productSearch = $salesInvoice->items
                ->map(fn ($item) => $this->productLabel($item->product))
                ->values()
                ->all();

            if ($this->items === []) {
                $this->addItem();
            }

            return;
        }

        $this->invoice_number = SalesInvoice::nextInvoiceNumber();
        $this->invoice_date = now()->toDateString();
        $this->addItem();
    }

    protected function rules(): array
    {
        return [
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sales_invoices', 'invoice_number')->ignore($this->invoice?->id),
            ],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'sales_channel' => ['required', Rule::in(array_column(SalesChannel::cases(), 'value'))],
            'partner_id' => [
                Rule::requiredIf($this->sales_channel === SalesChannel::Partner->value),
                'nullable',
                'integer',
                'exists:partners,id',
            ],
            'invoice_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'invoice_discount_type' => ['required', Rule::in(array_keys(SalesInvoice::discountTypes()))],
            'invoice_discount_value' => ['required', 'numeric', 'min:0', $this->invoice_discount_type === SalesInvoice::DISCOUNT_PERCENTAGE ? 'max:100' : 'max:999999999.99'],
            'installation_enabled' => ['boolean'],
            'installation_pricing_mode' => ['required', Rule::in(array_keys(SalesInvoice::installationPricingModes()))],
            'installation_percentage_value' => ['required', 'numeric', 'min:0', $this->installation_pricing_mode === SalesInvoice::INSTALLATION_PERCENTAGE ? 'max:100' : 'max:999999999.99'],
            'installation_fixed_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'installation_party_type' => ['required', Rule::in(array_keys(SalesInvoice::installationPartyTypes()))],
            'installation_party_reference' => ['nullable', 'string', 'max:255'],
            'installation_payout_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'installation_notes' => ['nullable', 'string', 'max:2000'],
            'partner_commission_type' => ['required', Rule::in(array_keys(SalesInvoice::commissionTypes()))],
            'partner_commission_value' => ['required', 'numeric', 'min:0', $this->partner_commission_type === SalesInvoice::DISCOUNT_PERCENTAGE ? 'max:100' : 'max:999999999.99'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'items.*.unit_sale_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.item_discount_type' => ['required', Rule::in(array_keys(SalesInvoice::discountTypes()))],
            'items.*.item_discount_value' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }

    public function updated(string $property): void
    {
        if ($property === 'customerSearch' && $this->customer_id) {
            $customer = Customer::query()->find($this->customer_id);

            if (! $customer || $this->customerSearch !== $this->customerLabel($customer)) {
                $this->customer_id = '';
            }
        }

        if (preg_match('/^productSearch\.(\d+)$/', $property, $matches)) {
            $index = (int) $matches[1];
            $product = Product::query()->find($this->items[$index]['product_id'] ?? null);

            if (! $product || ($this->productSearch[$index] ?? '') !== $this->productLabel($product)) {
                $this->items[$index]['product_id'] = '';
            }
        }

        if ($property === 'sales_channel' && $this->sales_channel === SalesChannel::Direct->value) {
            $this->partner_id = '';
            $this->partner_commission_type = SalesInvoice::DISCOUNT_FIXED;
            $this->partner_commission_value = '0';
        }

        if ($property === 'partner_id') {
            $partner = Partner::query()->find($this->partner_id);

            if ($partner) {
                $this->partner_commission_type = $partner->default_commission_type->value;
                $this->partner_commission_value = (string) $partner->default_commission_value;
            }
        }

        if (preg_match('/^items\.(\d+)\.product_id$/', $property, $matches)) {
            $index = (int) $matches[1];
            $product = Product::query()->find($this->items[$index]['product_id'] ?? null);

            if ($product && (float) ($this->items[$index]['unit_sale_price'] ?? 0) <= 0) {
                $this->items[$index]['unit_sale_price'] = (string) $product->sale_price;
            }
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => '',
            'quantity' => '1',
            'unit_sale_price' => '0',
            'item_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'item_discount_value' => '0',
        ];
        $this->productSearch[] = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        unset($this->productSearch[$index]);
        $this->items = array_values($this->items);
        $this->productSearch = array_values($this->productSearch);

        if ($this->items === []) {
            $this->addItem();
        }
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);
        $this->customer_id = (string) $customer->id;
        $this->customerSearch = $this->customerLabel($customer);
    }

    public function selectProduct(int $index, int $productId): void
    {
        $product = Product::query()->where('is_active', true)->findOrFail($productId);

        $this->items[$index]['product_id'] = (string) $product->id;
        $this->productSearch[$index] = $this->productLabel($product);

        if ((float) ($this->items[$index]['unit_sale_price'] ?? 0) <= 0) {
            $this->items[$index]['unit_sale_price'] = (string) $product->sale_price;
        }
    }

    public function save(bool $confirm = false): void
    {
        if ($this->invoice && $this->invoice->status !== SalesInvoiceStatus::Draft) {
            session()->flash('error', 'لا يمكن تعديل فاتورة بيع غير مسودة.');
            $this->redirect(route('sales-invoices.show', $this->invoice), navigate: true);
            return;
        }

        $data = $this->validate();
        $this->ensureValidItemDiscounts($data['items']);
        $this->ensureDistinctProducts();

        if ($confirm) {
            $this->ensureSufficientStock($data['items']);
        }

        $invoice = DB::transaction(function () use ($data) {
            $isPartnerSale = $data['sales_channel'] === SalesChannel::Partner->value;
            $subtotal = $this->subtotal();
            $invoiceDiscountAmount = $this->invoiceDiscountAmount();
            $installationTotal = $this->installationTotal();
            $grossTotal = $this->grossTotal();
            $partnerCommissionAmount = $isPartnerSale ? $this->partnerCommissionAmount() : 0.0;
            $netRevenue = round(max($grossTotal - $partnerCommissionAmount, 0), 2);
            $installationProfit = $this->installationProfit();

            $invoice = $this->invoice
                ? SalesInvoice::query()->whereKey($this->invoice->id)->lockForUpdate()->firstOrFail()
                : new SalesInvoice();

            if ($invoice->exists && $invoice->status !== SalesInvoiceStatus::Draft) {
                throw ValidationException::withMessages([
                    'invoice' => 'لا يمكن تعديل فاتورة بيع غير مسودة.',
                ]);
            }

            $invoice->fill([
                'invoice_number' => $data['invoice_number'],
                'customer_id' => $data['customer_id'] ?: null,
                'sales_channel' => $data['sales_channel'],
                'partner_id' => $isPartnerSale ? $data['partner_id'] : null,
                'invoice_date' => $data['invoice_date'],
                'notes' => $data['notes'] ?: null,
                'subtotal' => $subtotal,
                'invoice_discount_type' => $data['invoice_discount_type'],
                'invoice_discount_value' => (float) $data['invoice_discount_value'],
                'invoice_discount_amount' => $invoiceDiscountAmount,
                'installation_enabled' => (bool) $data['installation_enabled'],
                'installation_pricing_mode' => $data['installation_pricing_mode'],
                'installation_percentage_value' => (float) $data['installation_percentage_value'],
                'installation_fixed_amount' => (float) $data['installation_fixed_amount'],
                'installation_total' => $installationTotal,
                'installation_party_type' => (bool) $data['installation_enabled'] ? $data['installation_party_type'] : SalesInvoice::INSTALLATION_PARTY_NONE,
                'installation_party_reference' => (bool) $data['installation_enabled'] ? ($data['installation_party_reference'] ?: null) : null,
                'installation_payout_amount' => (bool) $data['installation_enabled'] ? (float) $data['installation_payout_amount'] : 0,
                'installation_profit' => $installationProfit,
                'product_profit' => 0,
                'installation_notes' => (bool) $data['installation_enabled'] ? ($data['installation_notes'] ?: null) : null,
                'gross_total' => $grossTotal,
                'partner_commission_type' => $isPartnerSale ? $data['partner_commission_type'] : SalesInvoice::DISCOUNT_FIXED,
                'partner_commission_value' => $isPartnerSale ? (float) $data['partner_commission_value'] : 0,
                'partner_commission_amount' => $partnerCommissionAmount,
                'net_revenue_after_partner_commission' => $netRevenue,
                'total_cost' => 0,
                'total_profit' => 0,
            ]);
            $invoice->created_by = $invoice->exists ? $invoice->created_by : auth()->id();
            $invoice->save();

            $invoice->items()->delete();

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitSalePrice = (float) $item['unit_sale_price'];
                $discountValue = (float) $item['item_discount_value'];
                $gross = $quantity * $unitSalePrice;
                $itemDiscountAmount = SalesInvoice::discountAmount($gross, $item['item_discount_type'], $discountValue);

                $invoice->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_sale_price' => $unitSalePrice,
                    'item_discount_type' => $item['item_discount_type'],
                    'item_discount_value' => $discountValue,
                    'item_discount_amount' => $itemDiscountAmount,
                    'cost_at_sale_time' => 0,
                    'line_total' => SalesInvoice::lineTotal($quantity, $unitSalePrice, $item['item_discount_type'], $discountValue),
                    'line_profit' => 0,
                ]);
            }

            return $invoice;
        });

        if ($confirm) {
            try {
                $invoice->confirm(auth()->user());
                session()->flash('success', $this->isEditing
                    ? 'تم تحديث فاتورة البيع وتأكيدها. تم خصم المخزون وتثبيت تكلفة البيع.'
                    : 'تم إنشاء فاتورة البيع وتأكيدها. تم خصم المخزون وتثبيت تكلفة البيع.');
            } catch (ValidationException $exception) {
                session()->flash('error', 'تم حفظ الفاتورة كمسودة، لكن لم يتم تأكيدها: ' . collect($exception->errors())->flatten()->first());
            }
        } else {
            session()->flash('success', $this->isEditing ? 'تم تحديث مسودة فاتورة البيع.' : 'تم حفظ فاتورة البيع كمسودة.');
        }

        $this->redirect(route('sales-invoices.show', $invoice), navigate: true);
    }

    public function saveDraft(): void
    {
        $this->save();
    }

    public function saveAndConfirm(): void
    {
        $this->save(confirm: true);
    }

    public function lineTotalFor(int $index): float
    {
        $item = $this->items[$index] ?? [];

        return SalesInvoice::lineTotal(
            (float) ($item['quantity'] ?? 0),
            (float) ($item['unit_sale_price'] ?? 0),
            $item['item_discount_type'] ?? SalesInvoice::DISCOUNT_FIXED,
            (float) ($item['item_discount_value'] ?? 0),
        );
    }

    public function itemDiscountAmountFor(int $index): float
    {
        $item = $this->items[$index] ?? [];
        $gross = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_sale_price'] ?? 0);

        return SalesInvoice::discountAmount(
            $gross,
            $item['item_discount_type'] ?? SalesInvoice::DISCOUNT_FIXED,
            (float) ($item['item_discount_value'] ?? 0),
        );
    }

    public function subtotal(): float
    {
        return round(collect(array_keys($this->items))->sum(fn (int $index) => $this->lineTotalFor($index)), 2);
    }

    public function invoiceDiscountAmount(): float
    {
        return SalesInvoice::discountAmount($this->subtotal(), $this->invoice_discount_type, (float) $this->invoice_discount_value);
    }

    public function grossTotal(): float
    {
        return round($this->netProductsTotal() + $this->installationTotal(), 2);
    }

    public function netProductsTotal(): float
    {
        return round(max($this->subtotal() - $this->invoiceDiscountAmount(), 0), 2);
    }

    public function installationTotal(): float
    {
        return SalesInvoice::installationAmount(
            $this->installation_enabled,
            $this->installation_pricing_mode,
            $this->subtotal(),
            (float) $this->installation_percentage_value,
            (float) $this->installation_fixed_amount,
        );
    }

    public function installationProfit(): float
    {
        return round($this->installationTotal() - ($this->installation_enabled ? (float) $this->installation_payout_amount : 0), 2);
    }

    public function partnerCommissionAmount(): float
    {
        if ($this->sales_channel !== SalesChannel::Partner->value) {
            return 0.0;
        }

        return SalesInvoice::commissionAmount($this->grossTotal(), $this->partner_commission_type, (float) $this->partner_commission_value);
    }

    public function netRevenue(): float
    {
        return round(max($this->grossTotal() - $this->partnerCommissionAmount(), 0), 2);
    }

    private function ensureValidItemDiscounts(array $items): void
    {
        foreach ($items as $index => $item) {
            if ($item['item_discount_type'] === SalesInvoice::DISCOUNT_PERCENTAGE && (float) $item['item_discount_value'] > 100) {
                throw ValidationException::withMessages([
                    'items.' . $index . '.item_discount_value' => 'خصم النسبة لا يمكن أن يتجاوز 100%.',
                ]);
            }
        }
    }

    private function ensureDistinctProducts(): void
    {
        $productIds = collect($this->items)->pluck('product_id')->filter()->values();

        if ($productIds->count() !== $productIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'استخدم كل منتج مرة واحدة في فاتورة البيع. زِد الكمية في السطر الموجود بدلًا من تكرار المنتج.',
            ]);
        }
    }

    private function ensureSufficientStock(array $items): void
    {
        $requiredQuantities = collect($items)
            ->groupBy('product_id')
            ->map(fn ($items) => $items->sum(fn ($item) => (float) $item['quantity']));

        $products = Product::query()
            ->withStockQuantity()
            ->whereKey($requiredQuantities->keys())
            ->get()
            ->keyBy('id');

        foreach ($requiredQuantities as $productId => $requiredQuantity) {
            $product = $products->get((int) $productId);

            if (! $product || $product->current_stock_quantity < $requiredQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'المخزون غير كافٍ للمنتج: ' . ($product?->name ?: 'غير معروف'),
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.sales-invoices.sales-invoice-create', [
            'partners' => Partner::query()->where('is_active', true)->orderBy('name')->get(),
            'hasActiveProducts' => Product::query()->where('is_active', true)->exists(),
            'channels' => SalesChannel::cases(),
            'discountTypes' => SalesInvoice::discountTypes(),
            'commissionTypes' => SalesInvoice::commissionTypes(),
            'installationPricingModes' => SalesInvoice::installationPricingModes(),
            'installationPartyTypes' => SalesInvoice::installationPartyTypes(),
            'subtotal' => $this->subtotal(),
            'invoiceDiscountAmount' => $this->invoiceDiscountAmount(),
            'netProductsTotal' => $this->netProductsTotal(),
            'installationTotal' => $this->installationTotal(),
            'installationProfit' => $this->installationProfit(),
            'grossTotal' => $this->grossTotal(),
            'partnerCommissionAmount' => $this->partnerCommissionAmount(),
            'netRevenue' => $this->netRevenue(),
            'showProfit' => auth()->user()->hasPermission('sales.view_profit'),
        ])->layout('layouts.app', ['header' => $this->isEditing ? 'تعديل مسودة فاتورة بيع' : 'إنشاء فاتورة بيع']);
    }

    public function customerOptions()
    {
        $term = trim($this->customerSearch);

        return Customer::query()
            ->when($this->customerSearch !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->customerSearch.'%')
                        ->orWhere('phone', 'like', '%'.$this->customerSearch.'%')
                        ->orWhere('email', 'like', '%'.$this->customerSearch.'%');
                });
            })
            ->orderBy('name')
            ->limit($term === '' ? 20 : 50)
            ->get();
    }

    public function productOptionsFor(int $index)
    {
        $term = trim($this->productSearch[$index] ?? '');

        return Product::query()
            ->withStockQuantity()
            ->where('is_active', true)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', '%'.$term.'%')
                        ->orWhere('internal_sku', 'like', '%'.$term.'%')
                        ->orWhere('barcode', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->limit($term === '' ? 25 : 80)
            ->get();
    }

    private function customerLabel(Customer $customer): string
    {
        return trim($customer->name.' - '.$customer->phone);
    }

    private function productLabel(Product $product): string
    {
        return trim($product->name.' ('.$product->internal_sku.')');
    }
}
