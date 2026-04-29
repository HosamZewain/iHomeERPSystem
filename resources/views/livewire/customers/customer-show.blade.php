<div>
    <div class="mb-4">
        <a href="{{ route('customers.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى العملاء
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card title="بيانات العميل">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-xl font-semibold text-gray-900 break-words">{{ $customer->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $customer->phone }}</p>
                        @if($customer->email)
                            <p class="mt-1 text-sm text-gray-500">{{ $customer->email }}</p>
                        @endif
                    </div>
                    <x-badge color="blue">عميل</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">العنوان</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->address ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">أضيف بواسطة</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->creator?->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">تاريخ الإنشاء</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">آخر تحديث</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->updated_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>

                @if($customer->notes)
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-900">ملاحظات</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $customer->notes }}</p>
                    </div>
                @endif
            </x-card>

            <x-card title="فواتير البيع">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفاتورة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">السداد</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">البنود</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المدفوع</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المتبقي</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($salesInvoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('sales-invoices.show', $invoice) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                        <div class="mt-1 text-xs text-gray-500">{{ $invoice->invoice_date->format('Y-m-d') }}</div>
                                    </td>
                                    <td class="px-4 py-3"><x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge></td>
                                    <td class="px-4 py-3"><x-badge :color="$invoice->payment_status->color()">{{ $invoice->payment_status->label() }}</x-badge></td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $invoice->items_count }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $money::format($invoice->gross_total) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $money::format($invoice->paid_amount) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $money::format($invoice->remaining_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">لا توجد فواتير بيع لهذا العميل.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $salesInvoices->links() }}
                </div>
            </x-card>

            <x-card title="المنتجات المستلمة">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المنتج</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">SKU</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الكمية المؤكدة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">القيمة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($productSummary as $product)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $product->internal_sku }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $product->total_quantity, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $money::format($product->total_revenue) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">لا توجد منتجات مؤكدة لهذا العميل حتى الآن.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="ملخص العميل">
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-gray-500">عدد عروض الأسعار</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['quotations_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">عدد فواتير البيع</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['sales_invoices_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">فواتير مؤكدة</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['confirmed_invoices_count'] }}</div>
                    </div>
                </div>
            </x-card>

            <x-card title="الحركة المالية">
                <div class="space-y-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-500">إجمالي الفواتير المؤكدة</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ $money::format($stats['confirmed_gross_total']) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">إجمالي ما دفعه العميل</div>
                        <div class="mt-1 text-lg font-semibold text-green-700">{{ $money::format($stats['paid_amount']) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">المتبقي للتحصيل</div>
                        <div class="mt-1 text-lg font-semibold text-amber-700">{{ $money::format($stats['remaining_amount']) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">إجمالي الكميات المؤكدة</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($stats['confirmed_products_quantity'], 2) }}</div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
