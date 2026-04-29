<?php

namespace App\Livewire;

use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Expense;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $todaySales = $this->salesTotals($today, $today);
        $monthSales = $this->salesTotals($monthStart, $monthEnd);
        $productsWithStock = Product::query()->withStockQuantity()->get();
        $lowStockProducts = $productsWithStock
            ->filter(fn (Product $product) => $product->isLowStock())
            ->sortBy('current_stock_quantity')
            ->take(6)
            ->values();

        return view('livewire.dashboard', [
            'cards' => [
                'salesToday' => $todaySales['sales'],
                'confirmedInvoicesToday' => $todaySales['count'],
                'grossProfitToday' => $todaySales['gross_profit'],
                'expensesToday' => $todaySales['expenses'],
                'netProfitToday' => $todaySales['net_profit'],
                'quotationsToday' => Quotation::query()->whereDate('quotation_date', $today)->count(),
                'salesThisMonth' => $monthSales['sales'],
                'confirmedInvoicesThisMonth' => $monthSales['count'],
                'grossProfitThisMonth' => $monthSales['gross_profit'],
                'expensesThisMonth' => $monthSales['expenses'],
                'netProfitThisMonth' => $monthSales['net_profit'],
                'quotationsThisMonth' => Quotation::query()->whereBetween('quotation_date', [$monthStart, $monthEnd])->count(),
                'partnerCommissionsThisMonth' => $monthSales['partner_commissions'],
                'netRevenueThisMonth' => $monthSales['net_revenue'],
                'lowStockProductsCount' => $lowStockProducts->count(),
                'stockValuationAverageCost' => $productsWithStock->sum(fn (Product $product) => $product->stock_value_at_average_cost),
                'stockValuationSalePrice' => $productsWithStock->sum(fn (Product $product) => $product->stock_value_at_sale_price),
            ],
            'recentSalesInvoices' => SalesInvoice::query()
                ->with(['customer', 'partner'])
                ->latest('invoice_date')
                ->latest('id')
                ->limit(6)
                ->get(),
            'recentQuotations' => Quotation::query()
                ->with('customer')
                ->latest('quotation_date')
                ->latest('id')
                ->limit(6)
                ->get(),
            'topSellingProducts' => $this->topSellingProducts($monthStart, $monthEnd),
            'topCustomers' => $this->topCustomers($monthStart, $monthEnd),
            'topPartners' => $this->topPartners($monthStart, $monthEnd),
            'lowStockProducts' => $lowStockProducts,
            'salesTrend' => $this->salesTrend(),
            'monthlySalesProfit' => $this->monthlySalesProfit(),
            'channelBreakdown' => $this->channelBreakdown($monthStart, $monthEnd),
        ])->layout('layouts.app', [
            'header' => 'لوحة التحكم',
        ]);
    }

    private function salesTotals(string $startDate, string $endDate): array
    {
        $totals = SalesInvoice::query()
            ->where('status', SalesInvoiceStatus::Confirmed->value)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw('COALESCE(SUM(gross_total), 0) as sales_total')
            ->selectRaw('COALESCE(SUM(total_profit), 0) as gross_profit_total')
            ->selectRaw('COALESCE(SUM(partner_commission_amount), 0) as partner_commissions_total')
            ->selectRaw('COALESCE(SUM(net_revenue_after_partner_commission), 0) as net_revenue_total')
            ->first();

        $expenses = Expense::totalForPeriod($startDate, $endDate);
        $grossProfit = (float) $totals->gross_profit_total;

        return [
            'count' => (int) $totals->invoices_count,
            'sales' => (float) $totals->sales_total,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => round($grossProfit - $expenses, 2),
            'partner_commissions' => (float) $totals->partner_commissions_total,
            'net_revenue' => (float) $totals->net_revenue_total,
        ];
    }

    private function topSellingProducts(string $monthStart, string $monthEnd)
    {
        return Product::query()
            ->join('sales_invoice_items', 'sales_invoice_items.product_id', '=', 'products.id')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
            ->whereBetween('sales_invoices.invoice_date', [$monthStart, $monthEnd])
            ->select('products.id', 'products.name', 'products.internal_sku')
            ->selectRaw('SUM(sales_invoice_items.quantity) as quantity_sold')
            ->selectRaw('SUM(sales_invoice_items.line_total) as sales_total')
            ->groupBy('products.id', 'products.name', 'products.internal_sku')
            ->orderByDesc('quantity_sold')
            ->limit(6)
            ->get();
    }

    private function topCustomers(string $monthStart, string $monthEnd)
    {
        return SalesInvoice::query()
            ->join('customers', 'customers.id', '=', 'sales_invoices.customer_id')
            ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
            ->whereBetween('sales_invoices.invoice_date', [$monthStart, $monthEnd])
            ->select('customers.id', 'customers.name', 'customers.phone')
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw('SUM(sales_invoices.gross_total) as sales_total')
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderByDesc('sales_total')
            ->limit(6)
            ->get();
    }

    private function topPartners(string $monthStart, string $monthEnd)
    {
        return Partner::query()
            ->join('sales_invoices', 'sales_invoices.partner_id', '=', 'partners.id')
            ->where('sales_invoices.status', SalesInvoiceStatus::Confirmed->value)
            ->where('sales_invoices.sales_channel', SalesChannel::Partner->value)
            ->whereBetween('sales_invoices.invoice_date', [$monthStart, $monthEnd])
            ->select('partners.id', 'partners.name')
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw('SUM(sales_invoices.gross_total) as sales_total')
            ->selectRaw('SUM(sales_invoices.partner_commission_amount) as commission_total')
            ->groupBy('partners.id', 'partners.name')
            ->orderByDesc('commission_total')
            ->limit(6)
            ->get();
    }

    private function salesTrend(): array
    {
        $start = now()->subDays(6)->toDateString();
        $end = now()->toDateString();
        $invoices = $this->confirmedSalesBetween($start, $end)
            ->get(['invoice_date', 'gross_total'])
            ->groupBy(fn (SalesInvoice $invoice) => $invoice->invoice_date->format('Y-m-d'));

        $rows = collect(range(6, 0))->map(function (int $daysAgo) use ($invoices) {
            $date = now()->subDays($daysAgo);
            $dateKey = $date->format('Y-m-d');

            return [
                'label' => $date->format('d/m'),
                'value' => (float) ($invoices->get($dateKey)?->sum(fn (SalesInvoice $invoice) => (float) $invoice->gross_total) ?? 0),
            ];
        });

        return $this->withPercentages($rows->all(), 'value');
    }

    private function monthlySalesProfit(): array
    {
        $start = now()->startOfMonth()->subMonths(5)->toDateString();
        $end = now()->endOfMonth()->toDateString();
        $invoices = $this->confirmedSalesBetween($start, $end)
            ->get(['invoice_date', 'gross_total', 'total_profit'])
            ->groupBy(fn (SalesInvoice $invoice) => $invoice->invoice_date->format('Y-m'));

        $rows = collect(range(5, 0))->map(function (int $monthsAgo) use ($invoices) {
            $month = now()->startOfMonth()->subMonths($monthsAgo);
            $key = $month->format('Y-m');
            $monthInvoices = $invoices->get($key, collect());

            return [
                'label' => $month->format('m/Y'),
                'sales' => (float) $monthInvoices->sum(fn (SalesInvoice $invoice) => (float) $invoice->gross_total),
                'profit' => (float) $monthInvoices->sum(fn (SalesInvoice $invoice) => (float) $invoice->total_profit),
            ];
        });

        $max = max($rows->max('sales') ?? 0, $rows->max('profit') ?? 0, 1);

        return $rows->map(fn (array $row) => $row + [
            'sales_percent' => round($row['sales'] / $max * 100, 1),
            'profit_percent' => round(max($row['profit'], 0) / $max * 100, 1),
        ])->all();
    }

    private function channelBreakdown(string $monthStart, string $monthEnd): array
    {
        $channels = $this->confirmedSalesBetween($monthStart, $monthEnd)
            ->get(['sales_channel', 'gross_total'])
            ->groupBy(fn (SalesInvoice $invoice) => $invoice->sales_channel->value);

        $rows = collect(SalesChannel::cases())->map(function (SalesChannel $channel) use ($channels) {
            $channelInvoices = $channels->get($channel->value, collect());

            return [
                'label' => $channel->label(),
                'value' => (float) $channelInvoices->sum(fn (SalesInvoice $invoice) => (float) $invoice->gross_total),
                'count' => $channelInvoices->count(),
            ];
        });

        return $this->withPercentages($rows->all(), 'value');
    }

    private function confirmedSalesBetween(string $startDate, string $endDate)
    {
        return SalesInvoice::query()
            ->where('status', SalesInvoiceStatus::Confirmed->value)
            ->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    private function withPercentages(array $rows, string $valueKey): array
    {
        $max = max(collect($rows)->max($valueKey) ?? 0, 1);

        return collect($rows)
            ->map(fn (array $row) => $row + ['percent' => round(((float) $row[$valueKey]) / $max * 100, 1)])
            ->all();
    }
}
