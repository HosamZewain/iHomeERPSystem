<div>
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="min-w-0">
                <label for="product-search" class="mb-2 block text-sm font-medium text-gray-700">بحث المنتجات</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-5 w-5" />
                    </div>
                    <input id="product-search"
                           wire:model.live.debounce.300ms="search"
                           type="search"
                           placeholder="ابحث باسم المنتج أو SKU أو الباركود..."
                           class="block h-12 w-full rounded-lg border border-gray-300 bg-white py-3 pr-11 pl-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                </div>
            </div>

            <x-button wire:click="create" type="button" class="w-full xl:w-auto xl:min-w-[10rem] xl:self-end">
                <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                إضافة منتج
            </x-button>
        </div>

        <div class="mt-4 grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
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
                <select wire:model.live="supplierFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل الموردين</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="statusFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل الحالات</option>
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
    </div>

    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    @if($categories->isEmpty())
        <x-alert type="warning" message="أضف تصنيفًا واحدًا على الأقل قبل إنشاء المنتجات." />
    @endif

    @if($showForm)
        <x-card :title="$editingId ? 'تعديل منتج' : 'إضافة منتج'" class="mb-6">
            <form wire:submit="save" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <x-input label="اسم المنتج" wire:model="name" type="text" required :error="$errors->first('name')" />
                    <x-input label="SKU الداخلي" wire:model="internal_sku" type="text" required :error="$errors->first('internal_sku')" />
                    <x-input label="الباركود" wire:model="barcode" type="text" :error="$errors->first('barcode')" />
                    <x-input label="مسار صورة المنتج أو رابطها" wire:model="image_path" type="text" placeholder="/storage/products/product.jpg" :error="$errors->first('image_path')" />

                    <x-select label="التصنيف" wire:model="category_id" required :error="$errors->first('category_id')">
                        <option value="">اختر التصنيف</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select label="المورد الرئيسي" wire:model="supplier_id" :error="$errors->first('supplier_id')">
                        <option value="">بدون مورد</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </x-select>

                    <x-input label="سعر البيع" wire:model="sale_price" type="number" step="0.01" min="0" required :error="$errors->first('sale_price')" />
                    <x-input label="متوسط التكلفة الحالي" wire:model="current_average_cost" type="number" step="0.01" min="0" required :error="$errors->first('current_average_cost')" />
                    <x-input label="حد تنبيه المخزون" wire:model="minimum_stock_alert_level" type="number" step="0.01" min="0" required :error="$errors->first('minimum_stock_alert_level')" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رفع صورة المنتج</label>
                    <input wire:model="imageUpload" type="file" accept="image/*"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 file:ml-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700">
                    @if($errors->has('imageUpload'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('imageUpload') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">PNG أو JPG حتى 4MB. تظهر الصورة في شاشة المنتج وملفات عروض الأسعار وفواتير البيع المطبوعة.</p>
                    @if(filled($image_path))
                        <img src="{{ $image_path }}" alt="صورة المنتج الحالية" class="mt-3 h-24 w-24 rounded-lg border border-gray-200 object-cover">
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea wire:model="notes" rows="3"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                    @if($errors->has('notes'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('notes') }}</p>
                    @endif
                </div>

                <div>
                    <label class="flex items-center cursor-pointer">
                        <input wire:model="is_active" type="checkbox"
                               class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="mr-2 text-sm text-gray-700">منتج نشط</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">المنتجات غير النشطة تظل في السجل ولا يفضل استخدامها في مبيعات جديدة.</p>
                    @if($errors->has('is_active'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('is_active') }}</p>
                    @endif
                </div>

                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                    <p class="text-sm font-medium text-gray-800">المخزون لا يتم تعديله من شاشة المنتج.</p>
                    <p class="text-xs text-gray-500 mt-1">الرصيد الحالي يتم حسابه من حركات المخزون المسجلة.</p>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200">
                    <x-button wire:click="cancel" type="button" variant="secondary" class="w-full sm:w-auto">إلغاء</x-button>
                    <x-button type="submit" class="w-full sm:w-auto" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'تحديث المنتج' : 'إنشاء المنتج' }}</span>
                        <span wire:loading>جار الحفظ...</span>
                    </x-button>
                </div>
            </form>
        </x-card>
    @endif

    <div class="hidden lg:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-sortable-th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">المنتج</x-sortable-th>
                    <x-sortable-th field="category" :sort-field="$sortField" :sort-direction="$sortDirection">التصنيف</x-sortable-th>
                    <x-sortable-th field="supplier" :sort-field="$sortField" :sort-direction="$sortDirection">المورد</x-sortable-th>
                    <x-sortable-th field="sale_price" :sort-field="$sortField" :sort-direction="$sortDirection">السعر</x-sortable-th>
                    <x-sortable-th field="average_cost" :sort-field="$sortField" :sort-direction="$sortDirection">متوسط التكلفة</x-sortable-th>
                    <x-sortable-th field="stock" :sort-field="$sortField" :sort-direction="$sortDirection">المخزون</x-sortable-th>
                    <x-sortable-th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">الحالة</x-sortable-th>
                    <x-sortable-th field="created_at" :sort-field="$sortField" :sort-direction="$sortDirection">تاريخ الإنشاء</x-sortable-th>
                    <x-sortable-th field="updated_at" :sort-field="$sortField" :sort-direction="$sortDirection">آخر تحديث</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($product->image_path)
                                    <img src="{{ $product->image_path }}" alt="{{ $product->name }}" class="h-12 w-12 rounded-lg border border-gray-200 object-cover">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-400">
                                        <x-icon name="cube" class="h-5 w-5" />
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $product->internal_sku }}{{ $product->barcode ? ' / ' . $product->barcode : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $product->category->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $product->supplier?->name ?: '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->sale_price) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($product->current_average_cost) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">
                            {{ number_format($product->current_stock_quantity, 2) }}
                            <div class="text-xs text-gray-400">من الحركات</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($product->is_active)
                                <x-badge color="green">نشط</x-badge>
                            @else
                                <x-badge color="gray">غير نشط</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $product->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $product->updated_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            <a href="{{ route('products.show', $product) }}" wire:navigate class="text-gray-600 hover:text-gray-800 text-sm font-medium">عرض</a>
                            <button wire:click="edit({{ $product->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</button>
                            <button wire:click="toggleActive({{ $product->id }})"
                                    wire:confirm="هل تريد {{ $product->is_active ? 'إيقاف' : 'تفعيل' }} هذا المنتج؟"
                                    class="text-sm font-medium {{ $product->is_active ? 'text-yellow-700 hover:text-yellow-900' : 'text-green-600 hover:text-green-800' }}">
                                {{ $product->is_active ? 'إيقاف' : 'تفعيل' }}
                            </button>
                            <button wire:click="delete({{ $product->id }})"
                                    wire:confirm="هل تريد حذف هذا المنتج؟"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="cube" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا توجد منتجات.</p>
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
                    <div class="flex min-w-0 items-start gap-3">
                        @if($product->image_path)
                            <img src="{{ $product->image_path }}" alt="{{ $product->name }}" class="h-14 w-14 flex-shrink-0 rounded-lg border border-gray-200 object-cover">
                        @else
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-400">
                                <x-icon name="cube" class="h-5 w-5" />
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</div>
                            <div class="text-xs text-gray-500 truncate">SKU: {{ $product->internal_sku }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $product->category->name }}{{ $product->supplier ? ' - ' . $product->supplier->name : '' }}</div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        @if($product->is_active)
                            <x-badge color="green">نشط</x-badge>
                        @else
                            <x-badge color="gray">متوقف</x-badge>
                        @endif
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                    <div>
                        <p class="text-gray-500">السعر</p>
                        <p class="font-medium text-gray-900">{{ \App\Support\Money::format($product->sale_price) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">متوسط التكلفة</p>
                        <p class="font-medium text-gray-900">{{ \App\Support\Money::format($product->current_average_cost) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">المخزون</p>
                        <p class="font-medium text-gray-900">{{ number_format($product->current_stock_quantity, 2) }}</p>
                    </div>
                </div>

                <div class="mt-3 space-y-1 border-t border-gray-100 pt-3 text-xs text-gray-500">
                    <p>الإنشاء: {{ $product->created_at->format('Y-m-d H:i') }}</p>
                    <p>آخر تحديث: {{ $product->updated_at->format('Y-m-d H:i') }}</p>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                    <a href="{{ route('products.show', $product) }}" wire:navigate class="text-gray-600 hover:text-gray-800 text-sm font-medium py-1">عرض</a>
                    <button wire:click="edit({{ $product->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">تعديل</button>
                    <button wire:click="toggleActive({{ $product->id }})"
                            wire:confirm="هل تريد {{ $product->is_active ? 'إيقاف' : 'تفعيل' }} هذا المنتج؟"
                            class="text-sm font-medium py-1 {{ $product->is_active ? 'text-yellow-700 hover:text-yellow-900' : 'text-green-600 hover:text-green-800' }}">
                        {{ $product->is_active ? 'إيقاف' : 'تفعيل' }}
                    </button>
                    <button wire:click="delete({{ $product->id }})"
                            wire:confirm="هل تريد حذف هذا المنتج؟"
                            class="text-red-600 hover:text-red-800 text-sm font-medium py-1">حذف</button>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="cube" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا توجد منتجات.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
