<div>
    <div class="mb-4">
        <a href="{{ route('partners.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى الشركاء
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card title="بيانات الشريك">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-xl font-semibold text-gray-900 break-words">{{ $partner->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $partner->type->label() }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ $partner->phone }}</p>
                        @if($partner->email)
                            <p class="mt-1 text-sm text-gray-500">{{ $partner->email }}</p>
                        @endif
                    </div>
                    @if($partner->is_active)
                        <x-badge color="green">نشط</x-badge>
                    @else
                        <x-badge color="gray">غير نشط</x-badge>
                    @endif
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">مسؤول التواصل</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $partner->contact_person ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">العنوان</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $partner->address ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">نوع العمولة الافتراضي</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $partner->default_commission_type->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">قيمة العمولة الافتراضية</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($partner->default_commission_type->value === 'percentage')
                                {{ rtrim(rtrim((string) $partner->default_commission_value, '0'), '.') }}%
                            @else
                                {{ $money::format($partner->default_commission_value) }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @if($partner->notes)
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-900">ملاحظات</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $partner->notes }}</p>
                    </div>
                @endif
            </x-card>

            <x-card title="فواتير الشريك">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفاتورة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">العميل</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">السداد</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">العمولة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
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
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $invoice->customer?->name ?: 'عميل نقدي' }}</td>
                                    <td class="px-4 py-3"><x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge></td>
                                    <td class="px-4 py-3"><x-badge :color="$invoice->payment_status->color()">{{ $invoice->payment_status->label() }}</x-badge></td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $money::format($invoice->partner_commission_amount) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $money::format($invoice->gross_total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">لا توجد فواتير بيع لهذا الشريك.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $salesInvoices->links() }}
                </div>
            </x-card>

            <x-card title="العمولات حسب الفاتورة">
                <div class="space-y-3">
                    @forelse($commissionByInvoice as $invoice)
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('sales-invoices.show', $invoice) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                    <p class="mt-1 text-xs text-gray-500">{{ $invoice->customer?->name ?: 'عميل نقدي' }} - {{ $invoice->invoice_date->format('Y-m-d') }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                                        <x-badge :color="$invoice->payment_status->color()">{{ $invoice->payment_status->label() }}</x-badge>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <p class="text-xs text-gray-500">عمولة الفاتورة</p>
                                    <p class="mt-1 text-lg font-semibold text-amber-700">{{ $money::format($invoice->partner_commission_amount) }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">لا توجد عمولات مسجلة لهذا الشريك حتى الآن.</p>
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="ملخص الشريك">
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-gray-500">عدد فواتير الشريك</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['sales_invoices_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">فواتير مؤكدة</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['confirmed_invoices_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">إجمالي البيع المؤكد</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ $money::format($stats['confirmed_gross_total']) }}</div>
                    </div>
                </div>
            </x-card>

            <x-card title="العمولات والتحصيل">
                <div class="space-y-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-500">إجمالي العمولة المكتسبة</div>
                        <div class="mt-1 text-lg font-semibold text-amber-700">{{ $money::format($stats['confirmed_commission_total']) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">صافي الإيراد بعد العمولة</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ $money::format($stats['confirmed_net_revenue_total']) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">المحصل من فواتير الشريك</div>
                        <div class="mt-1 text-lg font-semibold text-green-700">{{ $money::format($stats['paid_amount']) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">المتبقي للتحصيل</div>
                        <div class="mt-1 text-lg font-semibold text-amber-700">{{ $money::format($stats['remaining_amount']) }}</div>
                    </div>
                </div>
            </x-card>

            <x-card title="أبرز العملاء عبر هذا الشريك">
                <div class="space-y-3">
                    @forelse($customerSummary as $customer)
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                            <p class="mt-1 text-xs text-gray-500">{{ $customer->phone }}</p>
                            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                <span>{{ $customer->invoices_count }} فاتورة</span>
                                <span>{{ $money::format($customer->gross_total) }}</span>
                            </div>
                            <div class="mt-1 text-xs text-amber-700">عمولة: {{ $money::format($customer->commission_total) }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">لا توجد بيانات عملاء مؤكدة لهذا الشريك حتى الآن.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</div>
