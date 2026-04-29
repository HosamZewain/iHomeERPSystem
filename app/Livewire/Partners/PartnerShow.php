<?php

namespace App\Livewire\Partners;

use App\Enums\SalesInvoiceStatus;
use App\Models\Partner;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PartnerShow extends Component
{
    use WithPagination;

    public Partner $partner;

    public function mount(Partner $partner): void
    {
        $this->partner = $partner;
    }

    public function render()
    {
        $salesInvoices = $this->partner->salesInvoices()
            ->with(['customer'])
            ->withCount('items')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(10);

        $confirmedInvoicesQuery = $this->partner->salesInvoices()->where('status', SalesInvoiceStatus::Confirmed);

        $stats = [
            'sales_invoices_count' => $this->partner->salesInvoices()->count(),
            'confirmed_invoices_count' => (clone $confirmedInvoicesQuery)->count(),
            'confirmed_gross_total' => (float) (clone $confirmedInvoicesQuery)->sum('gross_total'),
            'confirmed_commission_total' => (float) (clone $confirmedInvoicesQuery)->sum('partner_commission_amount'),
            'confirmed_net_revenue_total' => (float) (clone $confirmedInvoicesQuery)->sum('net_revenue_after_partner_commission'),
            'paid_amount' => (float) $this->partner->salesInvoices()->sum('paid_amount'),
            'remaining_amount' => (float) $this->partner->salesInvoices()->sum('remaining_amount'),
        ];

        $commissionByInvoice = $this->partner->salesInvoices()
            ->with(['customer'])
            ->whereIn('status', [SalesInvoiceStatus::Confirmed, SalesInvoiceStatus::Returned])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $customerSummary = DB::table('sales_invoices')
            ->join('customers', 'customers.id', '=', 'sales_invoices.customer_id')
            ->where('sales_invoices.partner_id', $this->partner->id)
            ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->selectRaw('customers.id, customers.name, customers.phone, COUNT(sales_invoices.id) as invoices_count, SUM(sales_invoices.gross_total) as gross_total, SUM(sales_invoices.partner_commission_amount) as commission_total')
            ->orderByDesc('gross_total')
            ->limit(10)
            ->get();

        return view('livewire.partners.partner-show', [
            'salesInvoices' => $salesInvoices,
            'stats' => $stats,
            'commissionByInvoice' => $commissionByInvoice,
            'customerSummary' => $customerSummary,
            'money' => Money::class,
        ])->layout('layouts.app', ['header' => 'الشريك: '.$this->partner->name]);
    }
}
