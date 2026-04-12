<div class="space-y-6">
    @php
        $canViewSales = auth()->user()->hasPermission('sales.create');
        $canViewQuotations = auth()->user()->hasPermission('quotations.create');
        $canViewProducts = auth()->user()->hasPermission('products.manage') || auth()->user()->hasPermission('stock.view');
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-stat-card label="مبيعات اليوم" :value="\App\Support\Money::format($cards['salesToday'])" icon="receipt" color="green" />
        <x-stat-card label="فواتير مؤكدة اليوم" :value="number_format($cards['confirmedInvoicesToday'])" icon="check-circle" color="blue" />
        <x-stat-card label="ربح اليوم" :value="\App\Support\Money::format($cards['profitToday'])" icon="chart-bar" color="primary" />
        <x-stat-card label="عروض اليوم" :value="number_format($cards['quotationsToday'])" icon="document-text" color="yellow" />

        <x-stat-card label="مبيعات الشهر" :value="\App\Support\Money::format($cards['salesThisMonth'])" icon="receipt" color="green" />
        <x-stat-card label="فواتير مؤكدة هذا الشهر" :value="number_format($cards['confirmedInvoicesThisMonth'])" icon="check-circle" color="blue" />
        <x-stat-card label="ربح الشهر" :value="\App\Support\Money::format($cards['profitThisMonth'])" icon="chart-bar" color="primary" />
        <x-stat-card label="عروض هذا الشهر" :value="number_format($cards['quotationsThisMonth'])" icon="document-text" color="yellow" />

        <x-stat-card label="عمولات الشركاء هذا الشهر" :value="\App\Support\Money::format($cards['partnerCommissionsThisMonth'])" icon="handshake" color="blue" />
        <x-stat-card label="صافي الإيراد بعد العمولات" :value="\App\Support\Money::format($cards['netRevenueThisMonth'])" icon="chart-bar" color="green" />
        <x-stat-card label="منتجات منخفضة المخزون" :value="number_format($cards['lowStockProductsCount'])" icon="exclamation-triangle" color="red" />
        <x-stat-card label="قيمة المخزون بالتكلفة" :value="\App\Support\Money::format($cards['stockValuationAverageCost'])" icon="archive" color="gray" />
        <x-stat-card label="قيمة المخزون بسعر البيع" :value="\App\Support\Money::format($cards['stockValuationSalePrice'])" icon="archive" color="primary" />
    </div>

    @if(auth()->user()->hasPermission('quotations.create') || auth()->user()->hasPermission('sales.create') || auth()->user()->hasPermission('purchases.manage'))
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">إجراءات سريعة</h2>
            <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3">
                @if(auth()->user()->hasPermission('quotations.create'))
                    <a href="{{ route('quotations.create') }}" wire:navigate
                       class="inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 px-4 py-2.5 text-sm">
                        <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                        عرض سعر جديد
                    </a>
                @endif
                @if(auth()->user()->hasPermission('sales.create'))
                    <a href="{{ route('sales-invoices.create') }}" wire:navigate
                       class="inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 px-4 py-2.5 text-sm">
                        <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                        فاتورة بيع جديدة
                    </a>
                @endif
                @if(auth()->user()->hasPermission('purchases.manage'))
                    <a href="{{ route('purchase-invoices.create') }}" wire:navigate
                       class="inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-primary-500 px-4 py-2.5 text-sm">
                        <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                        فاتورة شراء جديدة
                    </a>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <x-card title="اتجاه المبيعات آخر 7 أيام">
            <div class="space-y-3">
                @foreach($salesTrend as $day)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-gray-500">{{ $day['label'] }}</span>
                            <span class="font-medium text-gray-900">{{ \App\Support\Money::format($day['value']) }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded bg-gray-100 overflow-hidden">
                            <div class="h-2 rounded bg-green-500" style="width: {{ $day['percent'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="المبيعات والربح خلال 6 أشهر">
            <div class="space-y-4">
                @foreach($monthlySalesProfit as $month)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-gray-500">{{ $month['label'] }}</span>
                            <span class="font-medium text-gray-900">{{ \App\Support\Money::format($month['sales']) }} / {{ \App\Support\Money::format($month['profit']) }}</span>
                        </div>
                        <div class="mt-2 space-y-1">
                            <div class="h-2 rounded bg-gray-100 overflow-hidden">
                                <div class="h-2 rounded bg-blue-500" style="width: {{ $month['sales_percent'] }}%"></div>
                            </div>
                            <div class="h-2 rounded bg-gray-100 overflow-hidden">
                                <div class="h-2 rounded bg-green-500" style="width: {{ $month['profit_percent'] }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded bg-blue-500"></span>المبيعات</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded bg-green-500"></span>الربح</span>
                </div>
            </div>
        </x-card>

        <x-card title="قنوات البيع هذا الشهر">
            <div class="space-y-4">
                @foreach($channelBreakdown as $channel)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-gray-900">{{ $channel['label'] }}</span>
                            <span class="text-gray-500">{{ $channel['count'] }} فاتورة</span>
                        </div>
                        <div class="mt-1 flex items-center gap-3">
                            <div class="h-2 flex-1 rounded bg-gray-100 overflow-hidden">
                                <div class="h-2 rounded bg-primary-500" style="width: {{ $channel['percent'] }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-900">{{ \App\Support\Money::format($channel['value']) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <x-card title="آخر فواتير البيع" :padding="false">
            <div class="divide-y divide-gray-200">
                @forelse($recentSalesInvoices as $invoice)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            @if($canViewSales)
                                <a href="{{ route('sales-invoices.show', $invoice) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800 truncate block">
                                    {{ $invoice->invoice_number }}
                                </a>
                            @else
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $invoice->invoice_number }}</p>
                            @endif
                            <p class="text-xs text-gray-500 truncate">{{ $invoice->customer?->name ?: 'عميل نقدي' }} - {{ $invoice->invoice_date->format('Y-m-d') }}</p>
                        </div>
                        <div class="text-left">
                            <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                            <p class="mt-1 text-sm font-medium text-gray-900">{{ \App\Support\Money::format($invoice->gross_total) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400">
                        <x-icon name="receipt" class="h-10 w-10 mx-auto mb-2" />
                        <p class="text-sm">لا توجد فواتير بيع بعد.</p>
                    </div>
                @endforelse
            </div>
        </x-card>

        <x-card title="آخر عروض الأسعار" :padding="false">
            <div class="divide-y divide-gray-200">
                @forelse($recentQuotations as $quotation)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            @if($canViewQuotations)
                                <a href="{{ route('quotations.show', $quotation) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800 truncate block">
                                    {{ $quotation->quotation_number }}
                                </a>
                            @else
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $quotation->quotation_number }}</p>
                            @endif
                            <p class="text-xs text-gray-500 truncate">{{ $quotation->customer->name }} - {{ $quotation->quotation_date->format('Y-m-d') }}</p>
                        </div>
                        <div class="text-left">
                            <x-badge :color="$quotation->status->color()">{{ $quotation->status->label() }}</x-badge>
                            <p class="mt-1 text-sm font-medium text-gray-900">{{ \App\Support\Money::format($quotation->total) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400">
                        <x-icon name="document-text" class="h-10 w-10 mx-auto mb-2" />
                        <p class="text-sm">لا توجد عروض أسعار بعد.</p>
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <x-card title="أعلى المنتجات مبيعًا هذا الشهر" :padding="false">
            <div class="divide-y divide-gray-200">
                @forelse($topSellingProducts as $product)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</p>
                            <p class="text-xs text-gray-500">{{ $product->internal_sku }}</p>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-900">{{ number_format((float) $product->quantity_sold, 2) }}</p>
                            <p class="text-xs text-gray-500">{{ \App\Support\Money::format($product->sales_total) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">لا توجد مبيعات مؤكدة هذا الشهر.</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="أعلى العملاء هذا الشهر" :padding="false">
            <div class="divide-y divide-gray-200">
                @forelse($topCustomers as $customer)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $customer->name }}</p>
                            <p class="text-xs text-gray-500">{{ $customer->phone }}</p>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-900">{{ \App\Support\Money::format($customer->sales_total) }}</p>
                            <p class="text-xs text-gray-500">{{ $customer->invoices_count }} فاتورة</p>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">لا توجد مبيعات عملاء مؤكدة هذا الشهر.</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="أعلى الشركاء هذا الشهر" :padding="false">
            <div class="divide-y divide-gray-200">
                @forelse($topPartners as $partner)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $partner->name }}</p>
                            <p class="text-xs text-gray-500">{{ $partner->invoices_count }} فاتورة</p>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-900">{{ \App\Support\Money::format($partner->commission_total) }}</p>
                            <p class="text-xs text-gray-500">مبيعات {{ \App\Support\Money::format($partner->sales_total) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">لا توجد مبيعات شركاء مؤكدة هذا الشهر.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <x-card title="منتجات منخفضة المخزون" :padding="false">
        <div class="hidden md:block overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنتج</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المخزون الحالي</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">حد التنبيه</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">قيمة التكلفة</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($lowStockProducts as $product)
                        <tr>
                            <td class="px-6 py-4">
                                @if($canViewProducts)
                                    <a href="{{ route('products.show', $product) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800">{{ $product->name }}</a>
                                @else
                                    <span class="text-sm font-medium text-gray-900">{{ $product->name }}</span>
                                @endif
                                <div class="text-xs text-gray-500">{{ $product->internal_sku }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-red-700 text-right">{{ number_format($product->current_stock_quantity, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ number_format((float) $product->minimum_stock_alert_level, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->stock_value_at_average_cost) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-400">لا توجد منتجات منخفضة المخزون.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden divide-y divide-gray-200">
            @forelse($lowStockProducts as $product)
                <div class="px-4 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500">{{ $product->internal_sku }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-semibold text-red-700">{{ number_format($product->current_stock_quantity, 2) }}</p>
                        <p class="text-xs text-gray-500">حد {{ number_format((float) $product->minimum_stock_alert_level, 2) }}</p>
                    </div>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">لا توجد منتجات منخفضة المخزون.</p>
            @endforelse
        </div>
    </x-card>
</div>
