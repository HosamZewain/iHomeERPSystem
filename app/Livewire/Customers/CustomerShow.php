<?php

namespace App\Livewire\Customers;

use App\Enums\SalesInvoiceStatus;
use App\Models\Customer;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerShow extends Component
{
    use WithPagination;

    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load('creator');
    }

    public function render()
    {
        $invoiceQuery = $this->customer->salesInvoices()
            ->withCount('items')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        $salesInvoices = (clone $invoiceQuery)->paginate(10);

        $stats = [
            'quotations_count' => $this->customer->quotations()->count(),
            'sales_invoices_count' => $this->customer->salesInvoices()->count(),
            'confirmed_invoices_count' => $this->customer->salesInvoices()->where('status', SalesInvoiceStatus::Confirmed)->count(),
            'confirmed_gross_total' => (float) $this->customer->salesInvoices()->where('status', SalesInvoiceStatus::Confirmed)->sum('gross_total'),
            'paid_amount' => (float) $this->customer->salesInvoices()->sum('paid_amount'),
            'remaining_amount' => (float) $this->customer->salesInvoices()->sum('remaining_amount'),
            'confirmed_products_quantity' => (float) DB::table('sales_invoice_items')
                ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
                ->where('sales_invoices.customer_id', $this->customer->id)
                ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
                ->sum('sales_invoice_items.quantity'),
        ];

        $productSummary = DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->where('sales_invoices.customer_id', $this->customer->id)
            ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
            ->groupBy('products.id', 'products.name', 'products.internal_sku')
            ->selectRaw('products.id, products.name, products.internal_sku, SUM(sales_invoice_items.quantity) as total_quantity, SUM(sales_invoice_items.line_total) as total_revenue')
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_revenue')
            ->limit(15)
            ->get();

        return view('livewire.customers.customer-show', [
            'salesInvoices' => $salesInvoices,
            'stats' => $stats,
            'productSummary' => $productSummary,
            'money' => Money::class,
        ])->layout('layouts.app', ['header' => 'العميل: '.$this->customer->name]);
    }
}
