<?php

namespace App\Livewire\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuotationForm extends Component
{
    public ?Quotation $quotation = null;
    public bool $isEditing = false;
    public string $quotation_number = '';
    public string $customer_id = '';
    public string $customerSearch = '';
    public array $productSearch = [];
    public string $quotation_date = '';
    public string $notes = '';
    public string $invoice_discount_type = Quotation::DISCOUNT_FIXED;
    public string $invoice_discount_value = '0';
    public bool $installation_enabled = false;
    public string $installation_pricing_mode = Quotation::INSTALLATION_FIXED;
    public string $installation_percentage_value = '0';
    public string $installation_fixed_amount = '0';
    public string $installation_notes = '';
    public string $status = 'draft';
    public array $items = [];

    public function mount(?Quotation $quotation = null): void
    {
        if ($quotation?->exists) {
            $this->quotation = $quotation->load(['customer', 'items.product']);
            $this->isEditing = true;
            $this->quotation_number = $quotation->quotation_number;
            $this->customer_id = (string) $quotation->customer_id;
            $this->customerSearch = $this->customerLabel($quotation->customer);
            $this->quotation_date = $quotation->quotation_date->toDateString();
            $this->notes = $quotation->notes ?? '';
            $this->invoice_discount_type = $quotation->invoice_discount_type;
            $this->invoice_discount_value = (string) $quotation->invoice_discount_value;
            $this->installation_enabled = (bool) $quotation->installation_enabled;
            $this->installation_pricing_mode = $quotation->installation_pricing_mode;
            $this->installation_percentage_value = (string) $quotation->installation_percentage_value;
            $this->installation_fixed_amount = (string) $quotation->installation_fixed_amount;
            $this->installation_notes = $quotation->installation_notes ?? '';
            $this->status = $quotation->status->value;
            $this->items = $quotation->items->map(fn ($item) => [
                'product_id' => (string) $item->product_id,
                'quantity' => (string) $item->quantity,
                'unit_sale_price' => (string) $item->unit_sale_price,
                'item_discount_type' => $item->item_discount_type,
                'item_discount_value' => (string) $item->item_discount_value,
            ])->values()->all();
            $this->productSearch = $quotation->items
                ->map(fn ($item) => $this->productLabel($item->product))
                ->values()
                ->all();

            return;
        }

        $this->quotation_number = Quotation::nextQuotationNumber();
        $this->quotation_date = now()->toDateString();
        $this->addItem();
    }

    protected function rules(): array
    {
        return [
            'quotation_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('quotations', 'quotation_number')->ignore($this->quotation?->id),
            ],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'quotation_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'invoice_discount_type' => ['required', Rule::in(array_keys(Quotation::discountTypes()))],
            'invoice_discount_value' => ['required', 'numeric', 'min:0', $this->invoice_discount_type === Quotation::DISCOUNT_PERCENTAGE ? 'max:100' : 'max:999999999.99'],
            'installation_enabled' => ['boolean'],
            'installation_pricing_mode' => ['required', Rule::in(array_keys(Quotation::installationPricingModes()))],
            'installation_percentage_value' => ['required', 'numeric', 'min:0', $this->installation_pricing_mode === Quotation::INSTALLATION_PERCENTAGE ? 'max:100' : 'max:999999999.99'],
            'installation_fixed_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'installation_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_column(QuotationStatus::cases(), 'value'))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'items.*.unit_sale_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.item_discount_type' => ['required', Rule::in(array_keys(Quotation::discountTypes()))],
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
            'item_discount_type' => Quotation::DISCOUNT_FIXED,
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

    public function save(): void
    {
        if ($this->quotation && ! $this->quotation->canEdit()) {
            session()->flash('error', 'لا يمكن تعديل عرض سعر تم تحويله.');
            $this->redirect(route('quotations.show', $this->quotation), navigate: true);
            return;
        }

        $data = $this->validate();
        $this->ensureValidItemDiscounts($data['items']);
        $this->ensureDistinctProducts();

        $quotation = DB::transaction(function () use ($data) {
            $subtotal = $this->subtotal();
            $invoiceDiscountAmount = $this->invoiceDiscountAmount();
            $installationTotal = $this->installationTotal();
            $total = $this->total();

            $quotation = $this->quotation ?: new Quotation();
            $quotation->fill([
                'quotation_number' => $data['quotation_number'],
                'customer_id' => $data['customer_id'],
                'quotation_date' => $data['quotation_date'],
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
                'installation_notes' => $data['installation_notes'] ?: null,
                'total' => $total,
                'status' => $data['status'],
                'created_by' => $quotation->exists ? $quotation->created_by : auth()->id(),
            ]);
            $quotation->save();

            $quotation->items()->delete();

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitSalePrice = (float) $item['unit_sale_price'];
                $discountValue = (float) $item['item_discount_value'];
                $gross = $quantity * $unitSalePrice;
                $itemDiscountAmount = Quotation::discountAmount($gross, $item['item_discount_type'], $discountValue);

                $quotation->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_sale_price' => $unitSalePrice,
                    'item_discount_type' => $item['item_discount_type'],
                    'item_discount_value' => $discountValue,
                    'item_discount_amount' => $itemDiscountAmount,
                    'line_total' => Quotation::lineTotal($quantity, $unitSalePrice, $item['item_discount_type'], $discountValue),
                ]);
            }

            return $quotation;
        });

        session()->flash('success', $this->isEditing ? 'تم تحديث عرض السعر.' : 'تم إنشاء عرض السعر.');
        $this->redirect(route('quotations.show', $quotation), navigate: true);
    }

    public function lineTotalFor(int $index): float
    {
        $item = $this->items[$index] ?? [];

        return Quotation::lineTotal(
            (float) ($item['quantity'] ?? 0),
            (float) ($item['unit_sale_price'] ?? 0),
            $item['item_discount_type'] ?? Quotation::DISCOUNT_FIXED,
            (float) ($item['item_discount_value'] ?? 0),
        );
    }

    public function itemDiscountAmountFor(int $index): float
    {
        $item = $this->items[$index] ?? [];
        $gross = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_sale_price'] ?? 0);

        return Quotation::discountAmount(
            $gross,
            $item['item_discount_type'] ?? Quotation::DISCOUNT_FIXED,
            (float) ($item['item_discount_value'] ?? 0),
        );
    }

    public function subtotal(): float
    {
        return round(collect(array_keys($this->items))->sum(fn (int $index) => $this->lineTotalFor($index)), 2);
    }

    public function invoiceDiscountAmount(): float
    {
        return Quotation::discountAmount($this->subtotal(), $this->invoice_discount_type, (float) $this->invoice_discount_value);
    }

    public function total(): float
    {
        return round($this->netProductsTotal() + $this->installationTotal(), 2);
    }

    public function netProductsTotal(): float
    {
        return round(max($this->subtotal() - $this->invoiceDiscountAmount(), 0), 2);
    }

    public function installationTotal(): float
    {
        return Quotation::installationAmount(
            $this->installation_enabled,
            $this->installation_pricing_mode,
            $this->subtotal(),
            (float) $this->installation_percentage_value,
            (float) $this->installation_fixed_amount,
        );
    }

    private function ensureValidItemDiscounts(array $items): void
    {
        foreach ($items as $index => $item) {
            if ($item['item_discount_type'] === Quotation::DISCOUNT_PERCENTAGE && (float) $item['item_discount_value'] > 100) {
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
                'items' => 'استخدم كل منتج مرة واحدة في عرض السعر. زِد الكمية في السطر الموجود بدلًا من تكرار المنتج.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.quotations.quotation-form', [
            'hasCustomers' => Customer::query()->exists(),
            'hasActiveProducts' => Product::query()->where('is_active', true)->exists(),
            'statuses' => QuotationStatus::cases(),
            'discountTypes' => Quotation::discountTypes(),
            'installationPricingModes' => Quotation::installationPricingModes(),
            'subtotal' => $this->subtotal(),
            'invoiceDiscountAmount' => $this->invoiceDiscountAmount(),
            'netProductsTotal' => $this->netProductsTotal(),
            'installationTotal' => $this->installationTotal(),
            'total' => $this->total(),
        ])->layout('layouts.app', ['header' => $this->isEditing ? 'تعديل عرض سعر' : 'إنشاء عرض سعر']);
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
