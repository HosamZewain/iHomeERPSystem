<?php

namespace App\Livewire\Reports;

use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SalesInvoice;
use Illuminate\Support\Carbon;
use Livewire\Component;

class SalesReport extends Component
{
    public string $reportMode = 'month';

    public string $selectedMonth = '';

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function updatedReportMode(): void
    {
        if ($this->reportMode === 'month') {
            $this->selectedMonth = $this->selectedMonth ?: now()->format('Y-m');

            return;
        }

        $dates = $this->periodDates();
        $this->startDate = $dates['start'];
        $this->endDate = $dates['end'];
    }

    public function applyCurrentMonth(): void
    {
        $this->reportMode = 'month';
        $this->selectedMonth = now()->format('Y-m');
    }

    public function render()
    {
        $dates = $this->periodDates();
        $totals = $this->totals($dates['start'], $dates['end']);

        return view('livewire.reports.sales-report', [
            'periodStart' => $dates['start'],
            'periodEnd' => $dates['end'],
            'totals' => $totals,
            'topSellingProducts' => $this->topSellingProducts($dates['start'], $dates['end']),
            'topCustomers' => $this->topCustomers($dates['start'], $dates['end']),
            'topPartners' => $this->topPartners($dates['start'], $dates['end']),
            'channelBreakdown' => $this->channelBreakdown($dates['start'], $dates['end']),
        ])->layout('layouts.app', ['header' => 'تقرير المبيعات']);
    }

    private function periodDates(): array
    {
        if ($this->reportMode === 'range') {
            $start = $this->parseDate($this->startDate, now()->startOfMonth())->toDateString();
            $end = $this->parseDate($this->endDate, now())->toDateString();

            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }

            return ['start' => $start, 'end' => $end];
        }

        $month = $this->parseMonth($this->selectedMonth);

        return [
            'start' => $month->startOfMonth()->toDateString(),
            'end' => $month->endOfMonth()->toDateString(),
        ];
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function parseMonth(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m', $value)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    private function totals(string $startDate, string $endDate): array
    {
        $totals = $this->confirmedSalesBetween($startDate, $endDate)
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw('COALESCE(SUM(gross_total), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(partner_commission_amount), 0) as partner_commissions')
            ->selectRaw('COALESCE(SUM(net_revenue_after_partner_commission), 0) as net_revenue')
            ->selectRaw('COALESCE(SUM(total_profit), 0) as profit')
            ->first();

        $count = (int) $totals->invoices_count;
        $grossSales = (float) $totals->gross_sales;

        return [
            'grossSales' => $grossSales,
            'confirmedInvoices' => $count,
            'partnerCommissions' => (float) $totals->partner_commissions,
            'netRevenue' => (float) $totals->net_revenue,
            'profit' => (float) $totals->profit,
            'averageInvoiceValue' => $count > 0 ? round($grossSales / $count, 2) : 0.0,
        ];
    }

    private function topSellingProducts(string $startDate, string $endDate)
    {
        return Product::query()
            ->join('sales_invoice_items', 'sales_invoice_items.product_id', '=', 'products.id')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
            ->whereDate('sales_invoices.invoice_date', '>=', $startDate)
            ->whereDate('sales_invoices.invoice_date', '<=', $endDate)
            ->select('products.id', 'products.name', 'products.internal_sku')
            ->selectRaw('SUM(sales_invoice_items.quantity) as quantity_sold')
            ->selectRaw('SUM(sales_invoice_items.line_total) as sales_total')
            ->selectRaw('SUM(sales_invoice_items.line_profit) as profit_total')
            ->groupBy('products.id', 'products.name', 'products.internal_sku')
            ->orderByDesc('sales_total')
            ->limit(10)
            ->get();
    }

    private function topCustomers(string $startDate, string $endDate)
    {
        return SalesInvoice::query()
            ->leftJoin('customers', 'customers.id', '=', 'sales_invoices.customer_id')
            ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
            ->whereDate('sales_invoices.invoice_date', '>=', $startDate)
            ->whereDate('sales_invoices.invoice_date', '<=', $endDate)
            ->selectRaw('sales_invoices.customer_id')
            ->selectRaw("COALESCE(customers.name, 'عميل نقدي') as customer_name")
            ->selectRaw('customers.phone as customer_phone')
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw('SUM(sales_invoices.gross_total) as sales_total')
            ->selectRaw('SUM(sales_invoices.total_profit) as profit_total')
            ->groupBy('sales_invoices.customer_id', 'customers.name', 'customers.phone')
            ->orderByDesc('sales_total')
            ->limit(10)
            ->get();
    }

    private function topPartners(string $startDate, string $endDate)
    {
        return Partner::query()
            ->join('sales_invoices', 'sales_invoices.partner_id', '=', 'partners.id')
            ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
            ->where('sales_invoices.sales_channel', SalesChannel::Partner->value)
            ->whereDate('sales_invoices.invoice_date', '>=', $startDate)
            ->whereDate('sales_invoices.invoice_date', '<=', $endDate)
            ->select('partners.id', 'partners.name')
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw('SUM(sales_invoices.gross_total) as sales_total')
            ->selectRaw('SUM(sales_invoices.partner_commission_amount) as commission_total')
            ->selectRaw('SUM(sales_invoices.net_revenue_after_partner_commission) as net_revenue_total')
            ->groupBy('partners.id', 'partners.name')
            ->orderByDesc('commission_total')
            ->limit(10)
            ->get();
    }

    private function channelBreakdown(string $startDate, string $endDate): array
    {
        $channels = $this->confirmedSalesBetween($startDate, $endDate)
            ->get(['sales_channel', 'gross_total', 'total_profit'])
            ->groupBy(fn (SalesInvoice $invoice) => $invoice->sales_channel->value);

        $rows = collect(SalesChannel::cases())->map(function (SalesChannel $channel) use ($channels) {
            $channelInvoices = $channels->get($channel->value, collect());

            return [
                'label' => $channel->label(),
                'value' => (float) $channelInvoices->sum(fn (SalesInvoice $invoice) => (float) $invoice->gross_total),
                'profit' => (float) $channelInvoices->sum(fn (SalesInvoice $invoice) => (float) $invoice->total_profit),
                'count' => $channelInvoices->count(),
            ];
        });

        $max = max($rows->max('value') ?? 0, 1);

        return $rows
            ->map(fn (array $row) => $row + ['percent' => round($row['value'] / $max * 100, 1)])
            ->all();
    }

    private function confirmedSalesBetween(string $startDate, string $endDate)
    {
        return SalesInvoice::query()
            ->where('status', SalesInvoiceStatus::Confirmed->value)
            ->whereDate('invoice_date', '>=', $startDate)
            ->whereDate('invoice_date', '<=', $endDate);
    }
}
