<div>
    <div class="mb-4">
        <a href="{{ route('products.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى المنتجات
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
            <x-card title="بيانات المنتج">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start">
                        @if($product->image_path)
                            <img src="{{ $product->image_path }}" alt="{{ $product->name }}" class="h-28 w-28 rounded-lg border border-gray-200 object-cover">
                        @else
                            <div class="flex h-28 w-28 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-400">
                                <x-icon name="cube" class="h-10 w-10" />
                            </div>
                        @endif

                        <div class="min-w-0">
                            <h2 class="text-xl font-semibold text-gray-900 break-words">{{ $product->name }}</h2>
                            <p class="mt-1 text-sm text-gray-500">SKU: {{ $product->internal_sku }}</p>
                            @if($product->barcode)
                                <p class="mt-1 text-sm text-gray-500">الباركود: {{ $product->barcode }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        @if($product->is_active)
                            <x-badge color="green">نشط</x-badge>
                        @else
                            <x-badge color="gray">غير نشط</x-badge>
                        @endif
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">التصنيف</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->category->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">المورد الرئيسي</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->supplier?->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">سعر البيع</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($product->sale_price) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">متوسط التكلفة الحالي</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\Money::format($product->current_average_cost) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">حد تنبيه المخزون</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format((float) $product->minimum_stock_alert_level, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">المخزون الحالي</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($product->current_stock_quantity, 2) }}</dd>
                    </div>
                </dl>

                @if($product->notes)
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-900">ملاحظات</h3>
                        <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $product->notes }}</p>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="المخزون">
                <div class="text-3xl font-semibold text-gray-900">{{ number_format($product->current_stock_quantity, 2) }}</div>
                <p class="mt-2 text-sm text-gray-500">الرصيد الحالي يتم حسابه من حركات المخزون المسجلة ولا يتم تعديله مباشرة من المنتج.</p>
            </x-card>

            <x-card title="الإجراءات">
                <div class="space-y-3">
                    <a href="{{ route('stock.movements.product', $product) }}" wire:navigate
                       class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        عرض حركات المخزون
                    </a>

                    <x-button wire:click="toggleActive"
                              type="button"
                              variant="{{ $product->is_active ? 'secondary' : 'success' }}"
                              class="w-full"
                              wire:confirm="هل تريد {{ $product->is_active ? 'إيقاف' : 'تفعيل' }} هذا المنتج؟">
                        {{ $product->is_active ? 'إيقاف المنتج' : 'تفعيل المنتج' }}
                    </x-button>

                    <x-button wire:click="delete"
                              type="button"
                              variant="danger"
                              class="w-full"
                              wire:confirm="هل تريد حذف هذا المنتج؟">
                        حذف المنتج
                    </x-button>
                </div>
            </x-card>
        </div>
    </div>
</div>
