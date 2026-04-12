<div>
    <div class="mb-4">
        <a href="{{ route('quotations.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى عروض الأسعار
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
            <x-card title="بيانات عرض السعر">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $quotation->quotation_number }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $quotation->customer->name }} - {{ $quotation->customer->phone }}</p>
                    </div>
                    <x-badge :color="$quotation->status->color()">{{ $quotation->status->label() }}</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quotation->quotation_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">الإجمالي</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($quotation->total) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">أنشأها</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quotation->creator?->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">آخر تحديث</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $quotation->updated_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>

                @if($quotation->notes)
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-900">ملاحظات</h3>
                        <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $quotation->notes }}</p>
                    </div>
                @endif

                <x-alert type="info" message="عرض السعر لا يؤثر على المخزون. سيتم تخفيض المخزون لاحقًا عند تأكيد فاتورة البيع فقط." class="mt-6" />
            </x-card>

            @if($quotation->installation_enabled)
                <x-card title="التركيب">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">طريقة الحساب</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \App\Models\Quotation::installationPricingModes()[$quotation->installation_pricing_mode] ?? $quotation->installation_pricing_mode }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">إجمالي التركيب</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($quotation->installation_total) }}</dd>
                        </div>
                        @if($quotation->installation_pricing_mode === \App\Models\Quotation::INSTALLATION_PERCENTAGE)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">النسبة</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ number_format((float) $quotation->installation_percentage_value, 2) }}%</dd>
                            </div>
                        @else
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">المبلغ الثابت</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($quotation->installation_fixed_amount) }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if($quotation->installation_notes)
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-medium text-gray-900">ملاحظات التركيب</h3>
                            <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $quotation->installation_notes }}</p>
                        </div>
                    @endif
                </x-card>
            @endif

            <x-card title="البنود" :padding="false">
                <div class="hidden lg:block overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنتج</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الكمية</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">سعر الوحدة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">خصم البند</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجمالي السطر</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($quotation->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $item->product->internal_sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($item->unit_sale_price) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">
                                        {{ $discountTypes[$item->item_discount_type] ?? $item->item_discount_type }}
                                        <div class="text-xs text-gray-400">{{ \App\Support\Money::format($item->item_discount_amount) }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden p-4 space-y-3">
                    @foreach($quotation->items as $item)
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
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="الملخص">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">إجمالي المنتجات</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($quotation->subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">خصم العرض على المنتجات</dt>
                        <dd class="text-sm font-medium text-red-700">-{{ \App\Support\Money::format($quotation->invoice_discount_amount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">صافي المنتجات</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format(max((float) $quotation->subtotal - (float) $quotation->invoice_discount_amount, 0)) }}</dd>
                    </div>
                    @if($quotation->installation_enabled)
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-500">التركيب</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($quotation->installation_total) }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-gray-200 pt-3">
                        <dt class="text-sm font-medium text-gray-900">الإجمالي النهائي</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($quotation->total) }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="الإجراءات">
                <div class="space-y-3">
                    <a href="{{ route('quotations.print', $quotation) }}" target="_blank"
                       class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-900 bg-gray-900 text-white hover:bg-gray-800">
                        طباعة / حفظ PDF
                    </a>

                    @if($quotation->canConvert())
                        <x-button wire:click="convertToInvoice"
                                  type="button"
                                  variant="success"
                                  class="w-full"
                                  wire:confirm="هل تريد تحويل عرض السعر إلى فاتورة بيع مسودة؟ لن يتم خصم المخزون قبل تأكيد فاتورة البيع.">
                            تحويل إلى فاتورة بيع
                        </x-button>
                    @elseif($quotation->salesInvoice)
                        <a href="{{ route('sales-invoices.show', $quotation->salesInvoice) }}" wire:navigate
                           class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium rounded-lg border border-green-300 bg-green-50 text-green-700 hover:bg-green-100">
                            عرض فاتورة البيع المرتبطة
                        </a>
                    @endif

                    @if($quotation->canEdit())
                        <a href="{{ route('quotations.edit', $quotation) }}" wire:navigate
                           class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                            تعديل عرض السعر
                        </a>
                    @endif

                    <form wire:submit="updateStatus" class="space-y-3">
                        <x-select label="تحديث الحالة" wire:model="status" required :error="$errors->first('status')">
                            @foreach($statuses as $quotationStatus)
                                <option value="{{ $quotationStatus->value }}">{{ $quotationStatus->label() }}</option>
                            @endforeach
                        </x-select>
                        <x-button type="submit" class="w-full" wire:loading.attr="disabled">
                            حفظ الحالة
                        </x-button>
                    </form>
                </div>
            </x-card>
        </div>
    </div>
</div>
