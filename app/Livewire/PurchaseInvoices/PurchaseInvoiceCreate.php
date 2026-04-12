<?php

namespace App\Livewire\PurchaseInvoices;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PurchaseInvoiceCreate extends Component
{
    public string $invoice_number = '';
    public string $supplier_id = '';
    public string $invoice_date = '';
    public string $notes = '';
    public array $items = [];

    public function mount(): void
    {
        $this->invoice_number = PurchaseInvoice::nextInvoiceNumber();
        $this->invoice_date = now()->toDateString();
        $this->addItem();
    }

    protected function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:255', 'unique:purchase_invoices,invoice_number'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'invoice_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => '',
            'quantity' => '1',
            'unit_cost' => '0',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->addItem();
        }
    }

    public function save(bool $confirm = false): void
    {
        $data = $this->validate();
        $this->ensureDistinctProducts();

        $invoice = DB::transaction(function () use ($data) {
            $subtotal = $this->subtotal();

            $invoice = PurchaseInvoice::create([
                'invoice_number' => $data['invoice_number'],
                'supplier_id' => $data['supplier_id'],
                'invoice_date' => $data['invoice_date'],
                'notes' => $data['notes'] ?: null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];

                $invoice->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => round($quantity * $unitCost, 2),
                ]);
            }

            return $invoice;
        });

        if ($confirm) {
            $invoice->confirm(auth()->user());
            session()->flash('success', 'تم إنشاء فاتورة الشراء وتأكيدها.');
        } else {
            session()->flash('success', 'تم حفظ فاتورة الشراء كمسودة.');
        }

        $this->redirect(route('purchase-invoices.show', $invoice), navigate: true);
    }

    public function saveDraft(): void
    {
        $this->save();
    }

    public function saveAndConfirm(): void
    {
        $this->save(confirm: true);
    }

    public function subtotal(): float
    {
        return collect($this->items)->sum(function ($item) {
            return ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_cost'] ?? 0));
        });
    }

    private function ensureDistinctProducts(): void
    {
        $productIds = collect($this->items)->pluck('product_id')->filter()->values();

        if ($productIds->count() !== $productIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'استخدم كل منتج مرة واحدة في فاتورة الشراء. زِد الكمية في السطر الموجود بدلًا من تكرار المنتج.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.purchase-invoices.purchase-invoice-create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'subtotal' => $this->subtotal(),
        ])->layout('layouts.app', ['header' => 'إنشاء فاتورة شراء']);
    }
}
