<div>
    <div class="mb-4">
        <a href="{{ route('sales-invoices.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى فواتير البيع
        </a>
    </div>

    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <x-card title="بيانات الفاتورة">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $invoice->invoice_number }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $invoice->customer?->name ?: 'عميل نقدي' }}</p>
                    </div>
                    <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">إجمالي فاتورة العميل</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($invoice->gross_total) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">قناة البيع</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->sales_channel->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">الشريك</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->partner?->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">عرض السعر المصدر</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($invoice->quotation)
                                <a href="{{ route('quotations.show', $invoice->quotation) }}" wire:navigate class="text-primary-600 hover:text-primary-800">
                                    {{ $invoice->quotation->quotation_number }}
                                </a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">أنشأها</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->creator?->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">تم التأكيد</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $invoice->confirmed_at ? $invoice->confirmed_at->format('Y-m-d H:i') : '-' }}
                        </dd>
                    </div>
                </dl>

                @if($invoice->notes)
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-900">ملاحظات</h3>
                        <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $invoice->notes }}</p>
                    </div>
                @endif
            </x-card>

            @if($invoice->installation_enabled)
                <x-card title="التركيب">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">طريقة الحساب</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \App\Models\SalesInvoice::installationPricingModes()[$invoice->installation_pricing_mode] ?? $invoice->installation_pricing_mode }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">إجمالي التركيب للعميل</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($invoice->installation_total) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">منفذ / مستحق التركيب</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \App\Models\SalesInvoice::installationPartyTypes()[$invoice->installation_party_type] ?? $invoice->installation_party_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">مرجع الطرف</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->installation_party_reference ?: '-' }}</dd>
                        </div>
                        @if($showProfit)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">تكلفة / مستحق التركيب</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($invoice->installation_payout_amount) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">ربح التركيب قبل عمولة الشريك</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($invoice->installation_profit) }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if($invoice->installation_notes)
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-medium text-gray-900">ملاحظات التركيب</h3>
                            <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $invoice->installation_notes }}</p>
                        </div>
                    @endif
                </x-card>
            @endif

            <x-card title="البنود" :padding="false">
                <div class="hidden xl:block overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنتج</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الكمية</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">سعر الوحدة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">خصم البند</th>
                                @if($showProfit)
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التكلفة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ربح السطر</th>
                                @endif
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجمالي السطر</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $item->product->internal_sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($item->unit_sale_price) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">
                                        {{ \App\Support\Money::format($item->item_discount_amount) }}
                                    </td>
                                    @if($showProfit)
                                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($item->cost_at_sale_time) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($item->line_profit) }}</td>
                                    @endif
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="xl:hidden p-4 space-y-3">
                    @foreach($invoice->items as $item)
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->product->internal_sku }}</div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <p class="text-gray-500">الكمية</p>
                                    <p class="font-medium text-gray-900">{{ number_format((float) $item->quantity, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">سعر الوحدة</p>
                                    <p class="font-medium text-gray-900">{{ \App\Support\Money::format($item->unit_sale_price) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">خصم البند</p>
                                    <p class="font-medium text-red-700">{{ \App\Support\Money::format($item->item_discount_amount) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">الإجمالي</p>
                                    <p class="font-medium text-gray-900">{{ \App\Support\Money::format($item->line_total) }}</p>
                                </div>
                                @if($showProfit)
                                    <div>
                                        <p class="text-gray-500">تكلفة البيع</p>
                                        <p class="font-medium text-gray-900">{{ \App\Support\Money::format($item->cost_at_sale_time) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">ربح السطر</p>
                                        <p class="font-medium text-gray-900">{{ \App\Support\Money::format($item->line_profit) }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            @if($stockMovements->isNotEmpty())
                <x-card title="حركات المخزون" :padding="false">
                    <div class="divide-y divide-gray-200">
                        @foreach($stockMovements as $movement)
                            <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $movement->product->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $movement->movement_date->format('Y-m-d') }} - {{ $movement->movementTypeLabel() }}</p>
                                    <p class="text-xs text-gray-400">أنشأها: {{ $movement->creator?->name ?: '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-red-700">{{ number_format((float) $movement->quantity, 2) }}</p>
                                    <p class="text-xs text-gray-500">{{ \App\Support\Money::format($movement->unit_cost) }}</p>
                                    <p class="text-xs text-gray-400">الرصيد: {{ $movement->balance_after !== null ? number_format((float) $movement->balance_after, 2) : '-' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card title="ملخص العميل">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">إجمالي المنتجات</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($invoice->subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">خصم الفاتورة على المنتجات</dt>
                        <dd class="text-sm font-medium text-red-700">-{{ \App\Support\Money::format($invoice->invoice_discount_amount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">صافي المنتجات</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format(max((float) $invoice->subtotal - (float) $invoice->invoice_discount_amount, 0)) }}</dd>
                    </div>
                    @if($invoice->installation_enabled)
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500">التركيب</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($invoice->installation_total) }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-gray-200 pt-3">
                        <dt class="text-sm font-medium text-gray-900">إجمالي فاتورة العميل</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($invoice->gross_total) }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="الشريك والربحية">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">عمولة الشريك</dt>
                        <dd class="text-sm font-medium text-red-700">-{{ \App\Support\Money::format($invoice->partner_commission_amount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-3">
                        <dt class="text-sm font-medium text-gray-900">صافي الإيراد</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ \App\Support\Money::format($invoice->net_revenue_after_partner_commission) }}</dd>
                    </div>
                    @if($showProfit)
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500">تكلفة المنتجات</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($invoice->total_cost) }}</dd>
                        </div>
                        @if($invoice->installation_enabled)
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500">ربح المنتجات</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($invoice->product_profit) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500">ربح التركيب قبل العمولة</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($invoice->installation_profit) }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <dt class="text-sm font-medium text-gray-900">إجمالي الربح</dt>
                            <dd class="text-sm font-semibold text-gray-900">{{ \App\Support\Money::format($invoice->total_profit) }}</dd>
                        </div>
                    @else
                        <p class="text-xs text-gray-500">بيانات التكلفة والربح تظهر فقط للأدوار المصرح لها.</p>
                    @endif
                </dl>
            </x-card>

            <x-card title="الإجراءات">
                <div class="space-y-3">
                    <a href="{{ route('sales-invoices.print', $invoice) }}" target="_blank"
                       class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-900 bg-gray-900 text-white hover:bg-gray-800">
                        طباعة / حفظ PDF
                    </a>

                    @if($invoice->sales_channel === \App\Enums\SalesChannel::Partner && $invoice->partner)
                        <a href="{{ route('sales-invoices.partner-settlement.print', $invoice) }}" target="_blank"
                           class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium rounded-lg border border-green-300 bg-green-50 text-green-700 hover:bg-green-100">
                            طباعة مستند عمولة الشريك
                        </a>
                    @endif

                    @if($invoice->canConfirm())
                        <a href="{{ route('sales-invoices.edit', $invoice) }}" wire:navigate
                           class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                            تعديل المسودة
                        </a>

                        <x-button wire:click="confirm"
                                  type="button"
                                  class="w-full"
                                  wire:confirm="هل تريد تأكيد هذه الفاتورة؟ سيتم خصم المخزون وتثبيت تكلفة البيع والربح.">
                            تأكيد الفاتورة
                        </x-button>
                    @endif

                    @if($invoice->canCancel())
                        <x-button wire:click="cancelDraft"
                                  type="button"
                                  variant="danger"
                                  class="w-full"
                                  wire:confirm="هل تريد إلغاء مسودة فاتورة البيع؟">
                            إلغاء المسودة
                        </x-button>
                    @endif

                    @if($invoice->status->value === 'confirmed')
                        <x-alert type="info" message="فاتورة البيع المؤكدة مقفلة. استخدم مرتجع بيع أو تسوية مخزون إذا احتاج المخزون إلى تصحيح." />
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</div>
