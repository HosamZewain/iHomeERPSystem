<?php

namespace App\Livewire\Suppliers;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\Product;
use App\Models\Supplier;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierShow extends Component
{
    use WithPagination;

    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function render()
    {
        $purchaseInvoices = $this->supplier->purchaseInvoices()
            ->withCount('items')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(10);

        $stats = [
            'products_count' => $this->supplier->products()->count(),
            'active_products_count' => $this->supplier->products()->where('is_active', true)->count(),
            'purchase_invoices_count' => $this->supplier->purchaseInvoices()->count(),
            'confirmed_purchase_invoices_count' => $this->supplier->purchaseInvoices()->where('status', PurchaseInvoiceStatus::Confirmed)->count(),
            'confirmed_purchase_total' => (float) $this->supplier->purchaseInvoices()->where('status', PurchaseInvoiceStatus::Confirmed)->sum('total'),
            'confirmed_purchase_quantity' => (float) DB::table('purchase_invoice_items')
                ->join('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_invoice_items.purchase_invoice_id')
                ->where('purchase_invoices.supplier_id', $this->supplier->id)
                ->where('purchase_invoices.status', PurchaseInvoiceStatus::Confirmed->value)
                ->sum('purchase_invoice_items.quantity'),
        ];

        $productSummary = Product::query()
            ->withStockQuantity()
            ->where('supplier_id', $this->supplier->id)
            ->orderBy('name')
            ->limit(20)
            ->get();

        $purchaseProductSummary = DB::table('purchase_invoice_items')
            ->join('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_invoice_items.purchase_invoice_id')
            ->join('products', 'products.id', '=', 'purchase_invoice_items.product_id')
            ->where('purchase_invoices.supplier_id', $this->supplier->id)
            ->where('purchase_invoices.status', PurchaseInvoiceStatus::Confirmed->value)
            ->groupBy('products.id', 'products.name', 'products.internal_sku')
            ->selectRaw('products.id, products.name, products.internal_sku, SUM(purchase_invoice_items.quantity) as total_quantity, SUM(purchase_invoice_items.line_total) as total_cost')
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_cost')
            ->limit(15)
            ->get();

        return view('livewire.suppliers.supplier-show', [
            'purchaseInvoices' => $purchaseInvoices,
            'stats' => $stats,
            'productSummary' => $productSummary,
            'purchaseProductSummary' => $purchaseProductSummary,
            'money' => Money::class,
        ])->layout('layouts.app', ['header' => 'المورد: '.$this->supplier->name]);
    }
}
