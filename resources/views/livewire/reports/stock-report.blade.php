<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-stat-card label="عدد المنتجات" :value="number_format($summary['products_count'])" icon="cube" color="blue" />
        <x-stat-card label="إجمالي الكميات" :value="number_format($summary['total_quantity'], 2)" icon="archive" color="primary" />
        <x-stat-card label="قيمة المخزون بالتكلفة" :value="\App\Support\Money::format($summary['value_at_average_cost'])" icon="chart-bar" color="green" />
        <x-stat-card label="قيمة المخزون بسعر البيع" :value="\App\Support\Money::format($summary['value_at_sale_price'])" icon="receipt" color="yellow" />
        <x-stat-card label="منتجات منخفضة المخزون" :value="number_format($summary['low_stock_count'])" icon="exclamation-triangle" color="red" />
        <x-stat-card label="منتجات برصيد صفر" :value="number_format($summary['zero_stock_count'])" icon="archive" color="gray" />
        <x-stat-card label="منتجات برصيد سالب" :value="number_format($summary['negative_stock_count'])" icon="exclamation-triangle" color="red" />
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="min-w-0">
                <label for="stock-report-search" class="mb-2 block text-sm font-medium text-gray-700">بحث تقرير المخزون</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-5 w-5" />
                    </div>
                    <input id="stock-report-search"
                           wire:model.live.debounce.300ms="search"
                           type="search"
                           placeholder="ابحث باسم المنتج أو SKU أو الباركود..."
                           class="block h-12 w-full rounded-lg border border-gray-300 bg-white py-3 pr-11 pl-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                </div>
            </div>

            <x-button wire:click="resetFilters" type="button" variant="secondary" class="w-full xl:w-auto xl:min-w-[10rem]">
                إعادة ضبط الفلاتر
            </x-button>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <select wire:model.live="categoryFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل التصنيفات</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="stockFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل حالات المخزون</option>
                    <option value="in_stock">به مخزون</option>
                    <option value="low">منخفض</option>
                    <option value="zero">رصيد صفر</option>
                    <option value="negative">رصيد سالب</option>
                </select>
            </div>

            <div>
                <select wire:model.live="activeFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل حالات المنتج</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>

            <div>
                <select wire:model.live="sortField"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    @foreach($sortableFields as $field => $label)
                        <option value="{{ $field }}">ترتيب: {{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="sortDirection"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="asc">تصاعدي</option>
                    <option value="desc">تنازلي</option>
                </select>
            </div>
        </div>

        <p class="mt-4 text-sm text-gray-500">القيم محسوبة من حركات المخزون الحالية ومتوسط تكلفة/سعر بيع المنتج الحالي.</p>
    </div>

    <x-card title="منتجات منخفضة المخزون" :padding="false">
        <div class="divide-y divide-gray-200">
            @forelse($lowStockProducts as $product)
                <div class="px-4 py-3 sm:px-6 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500">{{ $product->internal_sku }} - {{ $product->category->name }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-semibold text-red-700">{{ number_format($product->current_stock_quantity, 2) }}</p>
                        <p class="text-xs text-gray-500">حد التنبيه {{ number_format((float) $product->minimum_stock_alert_level, 2) }}</p>
                    </div>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">لا توجد منتجات منخفضة ضمن الفلاتر الحالية.</p>
            @endforelse
        </div>
    </x-card>

    <x-card title="رصيد المنتجات" :padding="false">
        <div class="hidden xl:block overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <x-sortable-th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">المنتج</x-sortable-th>
                        <x-sortable-th field="category" :sort-field="$sortField" :sort-direction="$sortDirection">التصنيف</x-sortable-th>
                        <x-sortable-th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">الحالة</x-sortable-th>
                        <x-sortable-th field="stock" :sort-field="$sortField" :sort-direction="$sortDirection">المخزون الحالي</x-sortable-th>
                        <x-sortable-th field="minimum_stock" :sort-field="$sortField" :sort-direction="$sortDirection">حد التنبيه</x-sortable-th>
                        <x-sortable-th field="average_cost" :sort-field="$sortField" :sort-direction="$sortDirection">متوسط التكلفة</x-sortable-th>
                        <x-sortable-th field="sale_price" :sort-field="$sortField" :sort-direction="$sortDirection">سعر البيع</x-sortable-th>
                        <x-sortable-th field="value_cost" :sort-field="$sortField" :sort-direction="$sortDirection">القيمة بالتكلفة</x-sortable-th>
                        <x-sortable-th field="value_sale" :sort-field="$sortField" :sort-direction="$sortDirection">القيمة بسعر البيع</x-sortable-th>
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
                            <td class="px-6 py-4">
                                @if(! $product->is_active)
                                    <x-badge color="gray">غير نشط</x-badge>
                                @elseif($product->isLowStock())
                                    <x-badge color="red">منخفض</x-badge>
                                @elseif($product->current_stock_quantity > 0)
                                    <x-badge color="green">متوفر</x-badge>
                                @else
                                    <x-badge color="gray">رصيد صفر</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($product->current_stock_quantity, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ number_format((float) $product->minimum_stock_alert_level, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->current_average_cost) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->sale_price) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->stock_value_at_average_cost) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->stock_value_at_sale_price) }}</td>
                            <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                                <a href="{{ route('products.show', $product) }}" wire:navigate class="text-gray-600 hover:text-gray-800 text-sm font-medium">المنتج</a>
                                <a href="{{ route('stock.movements.product', $product) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium">الحركات</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-gray-400">
                                <x-icon name="archive" class="h-10 w-10 mx-auto mb-2" />
                                <p class="text-sm">لا توجد منتجات مطابقة.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="xl:hidden space-y-3 p-4">
            @forelse($products as $product)
                <div class="rounded-lg border border-gray-200 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $product->internal_sku }} - {{ $product->category->name }}</div>
                        </div>
                        <div class="flex-shrink-0">
                            @if(! $product->is_active)
                                <x-badge color="gray">غير نشط</x-badge>
                            @elseif($product->isLowStock())
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
                            <p class="text-gray-500">متوسط التكلفة</p>
                            <p class="font-medium text-gray-900">{{ \App\Support\Money::format($product->current_average_cost) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">سعر البيع</p>
                            <p class="font-medium text-gray-900">{{ \App\Support\Money::format($product->sale_price) }}</p>
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
                <div class="p-8 text-center text-gray-400">
                    <x-icon name="archive" class="h-10 w-10 mx-auto mb-2" />
                    <p class="text-sm">لا توجد منتجات مطابقة.</p>
                </div>
            @endforelse
        </div>
    </x-card>

    <div>
        {{ $products->links() }}
    </div>
</div>
