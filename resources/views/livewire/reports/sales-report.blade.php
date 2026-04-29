<div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">نوع الفترة</label>
                    <select wire:model.live="reportMode"
                            class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        <option value="month">تقرير شهري</option>
                        <option value="range">نطاق تاريخ</option>
                    </select>
                </div>

                @if($reportMode === 'month')
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">الشهر</label>
                        <input wire:model.live="selectedMonth"
                               type="month"
                               class="block h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    </div>
                @else
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">من تاريخ</label>
                        <input wire:model.live="startDate"
                               type="date"
                               class="block h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">إلى تاريخ</label>
                        <input wire:model.live="endDate"
                               type="date"
                               class="block h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    </div>
                @endif
            </div>

            <x-button wire:click="applyCurrentMonth" type="button" variant="secondary" class="w-full xl:w-auto xl:min-w-[10rem]">
                الشهر الحالي
            </x-button>
        </div>

        <p class="mt-4 text-sm text-gray-500">
            الفترة المعروضة: {{ $periodStart }} إلى {{ $periodEnd }}. يعتمد التقرير على فواتير البيع المؤكدة فقط، وتُخصم المصروفات حسب تاريخ المصروف المسجل بغض النظر عن حالة السداد.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <x-stat-card label="إجمالي المبيعات" :value="\App\Support\Money::format($totals['grossSales'])" icon="receipt" color="green" />
        <x-stat-card label="عدد الفواتير المؤكدة" :value="number_format($totals['confirmedInvoices'])" icon="check-circle" color="blue" />
        <x-stat-card label="عمولات الشركاء" :value="\App\Support\Money::format($totals['partnerCommissions'])" icon="handshake" color="yellow" />
        <x-stat-card label="صافي الإيراد بعد العمولات" :value="\App\Support\Money::format($totals['netRevenue'])" icon="chart-bar" color="primary" />
        <x-stat-card label="إجمالي ربح المبيعات" :value="\App\Support\Money::format($totals['grossProfit'])" icon="chart-bar" color="green" />
        <x-stat-card label="إجمالي المصروفات" :value="\App\Support\Money::format($totals['expenses'])" icon="receipt" color="yellow" />
        <x-stat-card label="صافي الربح بعد المصروفات" :value="\App\Support\Money::format($totals['netProfit'])" icon="chart-bar" color="primary" />
        <x-stat-card label="متوسط قيمة الفاتورة" :value="\App\Support\Money::format($totals['averageInvoiceValue'])" icon="receipt" color="gray" />
    </div>

    <x-card title="توزيع قنوات البيع">
        <div class="space-y-4">
            @foreach($channelBreakdown as $channel)
                <div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ $channel['label'] }}</p>
                            <p class="text-xs text-gray-500">{{ $channel['count'] }} فاتورة - ربح {{ \App\Support\Money::format($channel['profit']) }}</p>
                        </div>
                        <span class="font-medium text-gray-900">{{ \App\Support\Money::format($channel['value']) }}</span>
                    </div>
                    <div class="mt-2 h-2 rounded bg-gray-100 overflow-hidden">
                        <div class="h-2 rounded bg-primary-500" style="width: {{ $channel['percent'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <x-card title="أعلى المنتجات مبيعًا" :padding="false">
            <div class="hidden md:block overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنتج</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الكمية</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المبيعات</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الربح</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topSellingProducts as $product)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->internal_sku }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $product->quantity_sold, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->sales_total) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->profit_total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">لا توجد مبيعات مؤكدة في هذه الفترة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="md:hidden divide-y divide-gray-200">
                @forelse($topSellingProducts as $product)
                    <div class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500">{{ $product->internal_sku }}</p>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                            <div><p class="text-gray-500">الكمية</p><p class="font-medium text-gray-900">{{ number_format((float) $product->quantity_sold, 2) }}</p></div>
                            <div><p class="text-gray-500">المبيعات</p><p class="font-medium text-gray-900">{{ \App\Support\Money::format($product->sales_total) }}</p></div>
                            <div><p class="text-gray-500">الربح</p><p class="font-medium text-gray-900">{{ \App\Support\Money::format($product->profit_total) }}</p></div>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">لا توجد مبيعات مؤكدة في هذه الفترة.</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="أعلى العملاء" :padding="false">
            <div class="divide-y divide-gray-200">
                @forelse($topCustomers as $customer)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $customer->customer_name }}</p>
                            <p class="text-xs text-gray-500">{{ $customer->customer_phone ?: '-' }} - {{ $customer->invoices_count }} فاتورة</p>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-900">{{ \App\Support\Money::format($customer->sales_total) }}</p>
                            <p class="text-xs text-gray-500">ربح {{ \App\Support\Money::format($customer->profit_total) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">لا توجد مبيعات مؤكدة للعملاء في هذه الفترة.</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="أعلى الشركاء" :padding="false">
            <div class="divide-y divide-gray-200">
                @forelse($topPartners as $partner)
                    <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $partner->name }}</p>
                            <p class="text-xs text-gray-500">{{ $partner->invoices_count }} فاتورة - مبيعات {{ \App\Support\Money::format($partner->sales_total) }}</p>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-900">{{ \App\Support\Money::format($partner->commission_total) }}</p>
                            <p class="text-xs text-gray-500">صافي {{ \App\Support\Money::format($partner->net_revenue_total) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">لا توجد مبيعات شركاء مؤكدة في هذه الفترة.</p>
                @endforelse
            </div>
        </x-card>
    </div>
</div>
