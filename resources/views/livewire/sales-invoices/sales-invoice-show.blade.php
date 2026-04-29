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
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                        <x-badge :color="$invoice->payment_status->color()">{{ $invoice->payment_status->label() }}</x-badge>
                    </div>
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
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">حالة السداد</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->payment_status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">قناة البيع</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->sales_channel->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الاستحقاق</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</dd>
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
                    @if($invoice->status->value === 'returned')
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ المرتجع</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $invoice->returned_at ? $invoice->returned_at->format('Y-m-d H:i') : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">نفذ المرتجع</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->returner?->name ?: '-' }}</dd>
                        </div>
                    @endif
                </dl>

                @if($invoice->notes)
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-900">ملاحظات</h3>
                        <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $invoice->notes }}</p>
                    </div>
                @endif

                @if($invoice->status->value === 'returned' && $invoice->return_reason)
                    <div class="mt-6 border-t border-amber-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-900">سبب المرتجع</h3>
                        <p class="mt-2 text-sm text-amber-800 whitespace-pre-line">{{ $invoice->return_reason }}</p>
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
                                    <p class="text-sm font-semibold {{ $movement->isIncrease() ? 'text-green-700' : 'text-red-700' }}">{{ number_format((float) $movement->quantity, 2) }}</p>
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
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">المدفوع</dt>
                        <dd class="text-sm font-medium text-green-700">{{ \App\Support\Money::format($invoice->paid_amount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">المتبقي</dt>
                        <dd class="text-sm font-medium {{ (float) $invoice->remaining_amount > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ \App\Support\Money::format($invoice->remaining_amount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">حالة السداد</dt>
                        <dd><x-badge :color="$invoice->payment_status->color()">{{ $invoice->payment_status->label() }}</x-badge></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">تاريخ الاستحقاق</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="الدفعات والتحصيل">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">إجمالي الفاتورة</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($invoice->gross_total) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">إجمالي المدفوع</p>
                        <p class="mt-1 text-lg font-semibold text-green-700">{{ \App\Support\Money::format($invoice->paid_amount) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">إجمالي المسترد</p>
                        <p class="mt-1 text-lg font-semibold text-amber-700">{{ \App\Support\Money::format($invoice->refunds->sum('amount')) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">المتبقي</p>
                        <p class="mt-1 text-lg font-semibold {{ (float) $invoice->remaining_amount > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ \App\Support\Money::format($invoice->remaining_amount) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">حالة السداد</p>
                        <div class="mt-2"><x-badge :color="$invoice->payment_status->color()">{{ $invoice->payment_status->label() }}</x-badge></div>
                    </div>
                </dl>

                @if($invoice->payments->isNotEmpty())
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-semibold text-gray-900">سجل الدفعات</h3>
                        <div class="mt-3 space-y-3">
                            @foreach($invoice->payments as $payment)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">{{ $payment->receipt_number }}</p>
                                            <p class="text-xs text-gray-500">{{ $payment->payment_date->format('Y-m-d') }} - {{ $payment->payment_method->label() }}</p>
                                            <p class="text-xs text-gray-500">سجلها: {{ $payment->creator?->name ?: '-' }}</p>
                                            @if($payment->reference_number)
                                                <p class="text-xs text-gray-500">المرجع: {{ $payment->reference_number }}</p>
                                            @endif
                                            @if($payment->notes)
                                                <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $payment->notes }}</p>
                                            @endif
                                        </div>
                                        <div class="sm:text-left">
                                            <p class="text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($payment->amount) }}</p>
                                            <p class="text-xs text-gray-500">المتبقي بعد الدفعة: {{ \App\Support\Money::format($payment->remaining_amount_after) }}</p>
                                            <a href="{{ route('sales-invoices.payments.print', [$invoice, $payment]) }}" target="_blank"
                                               class="mt-3 inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                طباعة إيصال الاستلام
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center text-sm text-gray-500">
                        لم يتم تسجيل أي دفعات على هذه الفاتورة حتى الآن.
                    </div>
                @endif

                @if($invoice->refunds->isNotEmpty())
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-semibold text-gray-900">سجل الاسترداد</h3>
                        <div class="mt-3 space-y-3">
                            @foreach($invoice->refunds as $refund)
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">{{ $refund->refund_number }}</p>
                                            <p class="text-xs text-gray-500">{{ $refund->refund_date->format('Y-m-d') }} - {{ $refund->payment_method->label() }}</p>
                                            <p class="text-xs text-gray-500">سجله: {{ $refund->creator?->name ?: '-' }}</p>
                                            @if($refund->reference_number)
                                                <p class="text-xs text-gray-500">المرجع: {{ $refund->reference_number }}</p>
                                            @endif
                                            @if($refund->notes)
                                                <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $refund->notes }}</p>
                                            @endif
                                        </div>
                                        <div class="sm:text-left">
                                            <p class="text-lg font-semibold text-amber-800">{{ \App\Support\Money::format($refund->amount) }}</p>
                                            <p class="text-xs text-gray-500">استرداد من مبالغ العميل</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($invoice->canReceivePayments())
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">إضافة دفعة</h3>
                                <p class="text-xs text-gray-500">الفاتورة تبقى واحدة، ويمكن تسجيل أكثر من دفعة حتى السداد الكامل.</p>
                            </div>
                            <x-button wire:click="fillRemainingAmount" type="button" variant="secondary" class="w-full sm:w-auto">
                                تعبئة المتبقي بالكامل
                            </x-button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input label="تاريخ الدفعة" wire:model="payment_date" type="date" required :error="$errors->first('payment_date')" />
                            <x-input label="قيمة الدفعة" wire:model="payment_amount" type="number" step="0.01" min="0.01" required :error="$errors->first('payment_amount')" />

                            <x-select label="طريقة الدفع" wire:model="payment_method" required :error="$errors->first('payment_method')">
                                @foreach($paymentMethods as $method => $label)
                                    <option value="{{ $method }}">{{ $label }}</option>
                                @endforeach
                            </x-select>

                            <x-input label="رقم المرجع" wire:model="reference_number" type="text" :error="$errors->first('reference_number')" />
                        </div>

                        <div class="mt-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">ملاحظات الدفعة</label>
                            <textarea wire:model="payment_notes" rows="3"
                                      class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                            @if($errors->has('payment_notes'))
                                <p class="mt-1 text-xs text-red-600">{{ $errors->first('payment_notes') }}</p>
                            @endif
                        </div>

                        <div class="mt-4 flex flex-col sm:flex-row sm:justify-end gap-3">
                            <x-button wire:click="savePayment" type="button" class="w-full sm:w-auto">
                                حفظ الدفعة
                            </x-button>
                        </div>
                    </div>
                @elseif($invoice->status->value === 'confirmed')
                    <div class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-4 text-sm text-green-800">
                        هذه الفاتورة مسددة بالكامل، ولا توجد مبالغ متبقية للتحصيل.
                    </div>
                @elseif($invoice->status->value === 'returned')
                    <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                        تم تنفيذ مرتجع كامل لهذه الفاتورة. لا يمكن تسجيل دفعات عليها بعد تنفيذ المرتجع.
                    </div>
                @else
                    <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-4 text-sm text-yellow-800">
                        يمكن تسجيل الدفعات بعد تأكيد فاتورة البيع فقط. تسجيل الدفعات لا يؤثر على المخزون.
                    </div>
                @endif
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

                    @if($invoice->canReverseConfirmed())
                        @if($showReturnForm)
                            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
                                <h3 class="font-semibold text-amber-900">تنفيذ مرتجع كامل لفاتورة مؤكدة</h3>
                                <p class="mt-2 text-amber-800">
                                    هذا الإجراء سيُرجع كل الكميات إلى المخزون ويغيّر حالة الفاتورة إلى مرتجع. لا يمكن التراجع عنه من هذه الشاشة.
                                </p>

                                @if((float) $invoice->paid_amount > 0)
                                    <div class="mt-3 rounded-lg border border-amber-300 bg-white px-4 py-3 text-amber-900">
                                        سيتم تسجيل استرداد كامل بقيمة {{ \App\Support\Money::format($invoice->paid_amount) }} قبل إغلاق الفاتورة كمرتجع.
                                    </div>
                                @endif

                                <div class="mt-4">
                                    <label class="mb-1 block text-sm font-medium text-gray-700">سبب المرتجع</label>
                                    <textarea wire:model="return_reason" rows="3"
                                              class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                    @if($errors->has('return_reason'))
                                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('return_reason') }}</p>
                                    @endif
                                </div>

                                <div class="mt-4">
                                    <x-input label='اكتب "مرتجع" للتأكيد' wire:model="return_confirmation" type="text" :error="$errors->first('return_confirmation')" />
                                </div>

                                @if((float) $invoice->paid_amount > 0)
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <x-input label="تاريخ الاسترداد" wire:model="refund_date" type="date" required :error="$errors->first('refund_date')" />

                                        <x-select label="طريقة الاسترداد" wire:model="refund_method" required :error="$errors->first('refund_method')">
                                            @foreach($refundMethods as $method => $label)
                                                <option value="{{ $method }}">{{ $label }}</option>
                                            @endforeach
                                        </x-select>

                                        <x-input label="مرجع الاسترداد" wire:model="refund_reference_number" type="text" :error="$errors->first('refund_reference_number')" />
                                    </div>

                                    <div class="mt-4">
                                        <label class="mb-1 block text-sm font-medium text-gray-700">ملاحظات الاسترداد</label>
                                        <textarea wire:model="refund_notes" rows="3"
                                                  class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                        @if($errors->has('refund_notes'))
                                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('refund_notes') }}</p>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-4 flex flex-col sm:flex-row gap-3">
                                    <x-button wire:click="reverseConfirmed" type="button" variant="danger" class="w-full sm:w-auto">
                                        تنفيذ المرتجع الكامل
                                    </x-button>
                                    <x-button wire:click="cancelReturn" type="button" variant="secondary" class="w-full sm:w-auto">
                                        إلغاء
                                    </x-button>
                                </div>
                            </div>
                        @else
                            <x-button wire:click="startReturn"
                                      type="button"
                                      variant="danger"
                                      class="w-full">
                                تنفيذ مرتجع كامل
                            </x-button>
                        @endif
                    @elseif($invoice->status->value === 'confirmed')
                        <x-alert type="info" message="فاتورة البيع المؤكدة مقفلة. لا يمكن إلغاؤها مباشرة. استخدم تنفيذ المرتجع الكامل، وسيتم أيضًا تسجيل استرداد الدفعات إذا كانت الفاتورة مدفوعة جزئيًا أو كليًا." />
                    @elseif($invoice->status->value === 'returned')
                        <x-alert type="info" message="تم تنفيذ مرتجع كامل لهذه الفاتورة، وتمت إعادة الكميات إلى المخزون." />
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</div>
