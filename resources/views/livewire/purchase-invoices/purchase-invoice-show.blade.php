<div>
    <div class="mb-4">
        <a href="{{ route('purchase-invoices.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى فواتير الشراء
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
                        <p class="mt-1 text-sm text-gray-500">{{ $invoice->supplier->name }}</p>
                    </div>
                    <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">الإجمالي</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($invoice->total) }}</dd>
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

            <x-card title="البنود" :padding="false">
                <div class="hidden md:block overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنتج</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الكمية</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تكلفة الوحدة</th>
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
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($item->unit_cost) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-700">الإجمالي</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ \App\Support\Money::format($invoice->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="md:hidden p-4 space-y-3">
                    @foreach($invoice->items as $item)
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->product->internal_sku }}</div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <p class="text-gray-500">الكمية</p>
                                    <p class="font-medium text-gray-900">{{ number_format((float) $item->quantity, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">التكلفة</p>
                                    <p class="font-medium text-gray-900">{{ \App\Support\Money::format($item->unit_cost) }}</p>
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
                                    <p class="text-sm font-semibold text-green-700">+{{ number_format((float) $movement->quantity, 2) }}</p>
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
            <x-card title="الملخص">
                <dl class="space-y-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">الإجمالي الفرعي</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($invoice->subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-3">
                        <dt class="text-sm font-medium text-gray-900">الإجمالي</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($invoice->total) }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="الإجراءات">
                <div class="space-y-3">
                    @if($invoice->canConfirm())
                        <x-button wire:click="confirm"
                                  type="button"
                                  class="w-full"
                                  wire:confirm="هل تريد تأكيد هذه الفاتورة؟ سيزيد المخزون وسيتم تحديث متوسط تكلفة المنتجات.">
                            تأكيد الفاتورة
                        </x-button>
                    @endif

                    @if($invoice->canCancel())
                        <x-button wire:click="cancelDraft"
                                  type="button"
                                  variant="danger"
                                  class="w-full"
                                  wire:confirm="هل تريد إلغاء مسودة الفاتورة؟">
                            إلغاء المسودة
                        </x-button>
                    @endif

                    @if($invoice->status->value === 'confirmed')
                        <x-alert type="info" message="فواتير الشراء المؤكدة مقفلة. استخدم تسوية مخزون أو مرتجع مورد إذا احتاج المخزون إلى تصحيح." />
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</div>
