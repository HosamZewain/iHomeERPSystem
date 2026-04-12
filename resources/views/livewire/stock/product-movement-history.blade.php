<div>
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <a href="{{ route('stock.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى ملخص المخزون
        </a>

        <a href="{{ route('products.show', $product) }}" wire:navigate
           class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
            عرض المنتج
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="المخزون الحالي" value="{{ number_format($product->current_stock_quantity, 2) }}" icon="archive" color="primary" />
        <x-stat-card label="متوسط التكلفة" value="{{ \App\Support\Money::format($product->current_average_cost) }}" icon="chart-bar" color="green" />
        <x-stat-card label="قيمة المخزون" value="{{ \App\Support\Money::format($product->stock_value_at_average_cost) }}" icon="receipt" color="blue" />
        <x-stat-card label="حد التنبيه" value="{{ number_format((float) $product->minimum_stock_alert_level, 2) }}" icon="exclamation-triangle" color="{{ $product->isLowStock() ? 'red' : 'gray' }}" />
    </div>

    <x-card class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-900 break-words">{{ $product->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $product->internal_sku }} - {{ $product->category->name }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full md:w-auto">
                <select wire:model.live="movementTypeFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل أنواع الحركات</option>
                    @foreach($movementTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="sortField"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    @foreach($sortableFields as $field => $label)
                        <option value="{{ $field }}">ترتيب: {{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="sortDirection"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="asc">تصاعدي</option>
                    <option value="desc">تنازلي</option>
                </select>
            </div>
        </div>
    </x-card>

    <div class="hidden lg:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-sortable-th field="movement_date" :sort-field="$sortField" :sort-direction="$sortDirection">التاريخ</x-sortable-th>
                    <x-sortable-th field="movement_type" :sort-field="$sortField" :sort-direction="$sortDirection">نوع الحركة</x-sortable-th>
                    <x-sortable-th field="quantity_in" :sort-field="$sortField" :sort-direction="$sortDirection">وارد</x-sortable-th>
                    <x-sortable-th field="quantity_out" :sort-field="$sortField" :sort-direction="$sortDirection">صادر</x-sortable-th>
                    <x-sortable-th field="balance_after" :sort-field="$sortField" :sort-direction="$sortDirection">الرصيد بعد الحركة</x-sortable-th>
                    <x-sortable-th field="reference" :sort-field="$sortField" :sort-direction="$sortDirection">المرجع</x-sortable-th>
                    <x-sortable-th field="creator" :sort-field="$sortField" :sort-direction="$sortDirection">أنشأها</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الملاحظات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($movements as $movement)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div>{{ $movement->movement_date->format('Y-m-d') }}</div>
                            <div class="text-xs text-gray-400">{{ $movement->created_at->format('Y-m-d H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <x-badge :color="$movement->movementTypeColor()">{{ $movement->movementTypeLabel() }}</x-badge>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-green-700 text-right">
                            {{ $movement->quantity_in > 0 ? number_format($movement->quantity_in, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-red-700 text-right">
                            {{ $movement->quantity_out > 0 ? number_format($movement->quantity_out, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 text-right">
                            {{ $movement->balance_after !== null ? number_format((float) $movement->balance_after, 2) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $movement->referenceTypeLabel() }} #{{ $movement->source_id }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $movement->creator?->name ?: '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $movement->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="archive" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا توجد حركات مخزون لهذا المنتج.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3">
        @forelse($movements as $movement)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-900">{{ $movement->movement_date->format('Y-m-d') }}</div>
                        <div class="text-xs text-gray-500">{{ $movement->referenceTypeLabel() }} #{{ $movement->source_id }}</div>
                    </div>
                    <x-badge :color="$movement->movementTypeColor()">{{ $movement->movementTypeLabel() }}</x-badge>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                    <div>
                        <p class="text-gray-500">وارد</p>
                        <p class="font-medium text-green-700">{{ $movement->quantity_in > 0 ? number_format($movement->quantity_in, 2) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">صادر</p>
                        <p class="font-medium text-red-700">{{ $movement->quantity_out > 0 ? number_format($movement->quantity_out, 2) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">الرصيد</p>
                        <p class="font-medium text-gray-900">{{ $movement->balance_after !== null ? number_format((float) $movement->balance_after, 2) : '-' }}</p>
                    </div>
                </div>

                <div class="mt-3 border-t border-gray-100 pt-3 text-xs text-gray-500">
                    <p>أنشأها: {{ $movement->creator?->name ?: '-' }}</p>
                    <p class="mt-1">وقت التسجيل: {{ $movement->created_at->format('Y-m-d H:i') }}</p>
                    @if($movement->notes)
                        <p class="mt-1 text-gray-700">{{ $movement->notes }}</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="archive" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا توجد حركات مخزون لهذا المنتج.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $movements->links() }}
    </div>
</div>
