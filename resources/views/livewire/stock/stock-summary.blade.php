<div>
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <x-stat-card label="عدد المنتجات" value="{{ number_format($summary['products_count']) }}" icon="cube" color="blue" />
        <x-stat-card label="إجمالي الكميات" value="{{ number_format($summary['total_quantity'], 2) }}" icon="archive" color="primary" />
        <x-stat-card label="قيمة المخزون بالتكلفة" value="{{ \App\Support\Money::format($summary['stock_value_at_cost']) }}" icon="chart-bar" color="green" />
        <x-stat-card label="قيمة المخزون بسعر البيع" value="{{ \App\Support\Money::format($summary['stock_value_at_sale']) }}" icon="receipt" color="yellow" />
        <x-stat-card label="منتجات منخفضة" value="{{ number_format($summary['low_stock_count']) }}" icon="exclamation-triangle" color="red" />
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-6">
        <div class="flex flex-col lg:flex-row gap-3 flex-1">
            <div class="flex-1 max-w-md">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="ابحث باسم المنتج أو SKU أو الباركود..."
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2">
                <select wire:model.live="categoryFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل التصنيفات</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="stockFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل حالات المخزون</option>
                    <option value="in_stock">به مخزون</option>
                    <option value="low">منخفض</option>
                    <option value="zero">رصيد صفر</option>
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
    </div>

    <div class="hidden lg:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-sortable-th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">المنتج</x-sortable-th>
                    <x-sortable-th field="category" :sort-field="$sortField" :sort-direction="$sortDirection">التصنيف</x-sortable-th>
                    <x-sortable-th field="stock" :sort-field="$sortField" :sort-direction="$sortDirection">المخزون الحالي</x-sortable-th>
                    <x-sortable-th field="minimum_stock" :sort-field="$sortField" :sort-direction="$sortDirection">حد التنبيه</x-sortable-th>
                    <x-sortable-th field="value_cost" :sort-field="$sortField" :sort-direction="$sortDirection">القيمة بالتكلفة</x-sortable-th>
                    <x-sortable-th field="value_sale" :sort-field="$sortField" :sort-direction="$sortDirection">القيمة بسعر البيع</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                            <div class="text-sm text-gray-500">{{ $product->internal_sku }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $product->category->name }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($product->current_stock_quantity, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ number_format((float) $product->minimum_stock_alert_level, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->stock_value_at_average_cost) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->stock_value_at_sale_price) }}</td>
                        <td class="px-6 py-4">
                            @if($product->isLowStock())
                                <x-badge color="red">منخفض</x-badge>
                            @elseif($product->current_stock_quantity > 0)
                                <x-badge color="green">متوفر</x-badge>
                            @else
                                <x-badge color="gray">رصيد صفر</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            <a href="{{ route('products.show', $product) }}" wire:navigate class="text-gray-600 hover:text-gray-800 text-sm font-medium">المنتج</a>
                            <a href="{{ route('stock.movements.product', $product) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium">الحركات</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="archive" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا توجد منتجات مطابقة.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3">
        @forelse($products as $product)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $product->internal_sku }} - {{ $product->category->name }}</div>
                    </div>
                    <div class="flex-shrink-0">
                        @if($product->isLowStock())
                            <x-badge color="red">منخفض</x-badge>
                        @elseif($product->current_stock_quantity > 0)
                            <x-badge color="green">متوفر</x-badge>
                        @else
                            <x-badge color="gray">رصيد صفر</x-badge>
                        @endif
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <p class="text-gray-500">المخزون الحالي</p>
                        <p class="font-medium text-gray-900">{{ number_format($product->current_stock_quantity, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">حد التنبيه</p>
                        <p class="font-medium text-gray-900">{{ number_format((float) $product->minimum_stock_alert_level, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">القيمة بالتكلفة</p>
                        <p class="font-medium text-gray-900">{{ \App\Support\Money::format($product->stock_value_at_average_cost) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">القيمة بسعر البيع</p>
                        <p class="font-medium text-gray-900">{{ \App\Support\Money::format($product->stock_value_at_sale_price) }}</p>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                    <a href="{{ route('products.show', $product) }}" wire:navigate class="text-gray-600 hover:text-gray-800 text-sm font-medium py-1">المنتج</a>
                    <a href="{{ route('stock.movements.product', $product) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">حركات المخزون</a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="archive" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا توجد منتجات مطابقة.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
