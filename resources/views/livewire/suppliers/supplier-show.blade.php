<div>
    <div class="mb-4">
        <a href="{{ route('suppliers.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى الموردين
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card title="بيانات المورد">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-xl font-semibold text-gray-900 break-words">{{ $supplier->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $supplier->phone }}</p>
                        @if($supplier->email)
                            <p class="mt-1 text-sm text-gray-500">{{ $supplier->email }}</p>
                        @endif
                    </div>
                    <x-badge color="purple">مورد</x-badge>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">مسؤول التواصل</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->contact_person ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">العنوان</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->address ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">تاريخ الإنشاء</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">آخر تحديث</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->updated_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>

                @if($supplier->notes)
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-900">ملاحظات</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $supplier->notes }}</p>
                    </div>
                @endif
            </x-card>

            <x-card title="فواتير الشراء">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الفاتورة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">البنود</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($purchaseInvoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('purchase-invoices.show', $invoice) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                        <div class="mt-1 text-xs text-gray-500">{{ $invoice->invoice_date->format('Y-m-d') }}</div>
                                    </td>
                                    <td class="px-4 py-3"><x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge></td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $invoice->items_count }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $money::format($invoice->total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">لا توجد فواتير شراء لهذا المورد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $purchaseInvoices->links() }}
                </div>
            </x-card>

            <x-card title="أبرز المنتجات من هذا المورد">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المنتج</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">SKU</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الكمية المشتراة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تكلفة الشراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($purchaseProductSummary as $product)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $product->internal_sku }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $product->total_quantity, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $money::format($product->total_cost) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">لا توجد مشتريات مؤكدة لهذا المورد حتى الآن.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="ملخص المورد">
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-gray-500">عدد المنتجات</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['products_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">منتجات نشطة</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['active_products_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">عدد فواتير الشراء</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['purchase_invoices_count'] }}</div>
                    </div>
                </div>
            </x-card>

            <x-card title="إحصاءات الشراء">
                <div class="space-y-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-500">فواتير شراء مؤكدة</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ $stats['confirmed_purchase_invoices_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">إجمالي الشراء المؤكد</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ $money::format($stats['confirmed_purchase_total']) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">إجمالي الكميات المشتراة</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($stats['confirmed_purchase_quantity'], 2) }}</div>
                    </div>
                </div>
            </x-card>

            <x-card title="منتجات المورد">
                <div class="space-y-3">
                    @forelse($productSummary as $product)
                        <div class="rounded-lg border border-gray-200 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', $product) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800">
                                        {{ $product->name }}
                                    </a>
                                    <p class="mt-1 text-xs text-gray-500">{{ $product->internal_sku }}</p>
                                </div>
                                @if($product->is_active)
                                    <x-badge color="green">نشط</x-badge>
                                @else
                                    <x-badge color="gray">غير نشط</x-badge>
                                @endif
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                <span>المخزون: {{ number_format($product->current_stock_quantity, 2) }}</span>
                                <span>{{ $money::format($product->sale_price) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">لا توجد منتجات مرتبطة بهذا المورد.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</div>
