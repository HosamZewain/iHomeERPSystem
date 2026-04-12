<div>
    <div class="mb-4">
        <a href="{{ route('purchase-invoices.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى فواتير الشراء
        </a>
    </div>

    @if($errors->has('items'))
        <x-alert type="error" :message="$errors->first('items')" />
    @endif

    @if($suppliers->isEmpty() || $products->isEmpty())
        <x-alert type="warning" message="أضف موردًا واحدًا ومنتجًا نشطًا واحدًا على الأقل قبل إنشاء فاتورة شراء." />
    @endif

    <form wire:submit="saveDraft" class="space-y-6">
        <x-card title="بيانات الفاتورة">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input label="رقم الفاتورة" wire:model="invoice_number" type="text" required :error="$errors->first('invoice_number')" />

                <x-select label="المورد" wire:model="supplier_id" required :error="$errors->first('supplier_id')">
                    <option value="">اختر المورد</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </x-select>

                <x-input label="التاريخ" wire:model="invoice_date" type="date" required :error="$errors->first('invoice_date')" />
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                <textarea wire:model="notes" rows="3"
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                @if($errors->has('notes'))
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('notes') }}</p>
                @endif
            </div>
        </x-card>

        <x-card title="البنود">
            <div class="hidden md:block">
                <div class="grid grid-cols-12 gap-3 px-1 pb-2 text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="col-span-5">المنتج</div>
                    <div class="col-span-2 text-right">الكمية</div>
                    <div class="col-span-2 text-right">تكلفة الوحدة</div>
                    <div class="col-span-2 text-right">إجمالي السطر</div>
                    <div class="col-span-1"></div>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($items as $index => $item)
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 bg-gray-50 md:bg-white border border-gray-200 md:border-0 rounded-lg md:rounded-none p-3 md:p-0">
                        <div class="md:col-span-5">
                            <x-select label="المنتج" wire:model.live="items.{{ $index }}.product_id" required :error="$errors->first('items.' . $index . '.product_id')">
                                <option value="">اختر المنتج</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->internal_sku }})</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="md:col-span-2">
                            <x-input label="الكمية" wire:model.live="items.{{ $index }}.quantity" type="number" step="0.01" min="0.01" required :error="$errors->first('items.' . $index . '.quantity')" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input label="تكلفة الوحدة" wire:model.live="items.{{ $index }}.unit_cost" type="number" step="0.01" min="0" required :error="$errors->first('items.' . $index . '.unit_cost')" />
                        </div>

                        <div class="md:col-span-2 md:pt-6">
                            <p class="text-xs text-gray-500 md:text-right">إجمالي السطر</p>
                            <p class="text-sm font-medium text-gray-900 md:text-right">
                                {{ \App\Support\Money::format(((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_cost'] ?? 0))) }}
                            </p>
                        </div>

                        <div class="md:col-span-1 md:pt-6 md:text-right">
                            <button wire:click="removeItem({{ $index }})"
                                    type="button"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium py-2">
                                حذف
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-200 pt-4">
                <x-button wire:click="addItem" type="button" variant="secondary" class="w-full sm:w-auto">
                    <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                    إضافة بند
                </x-button>

                <div class="text-right">
                    <p class="text-xs text-gray-500">الإجمالي الفرعي</p>
                    <p class="text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($subtotal) }}</p>
                </div>
            </div>
        </x-card>

        <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('purchase-invoices.index') }}" wire:navigate
               class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                إلغاء
            </a>
            <x-button type="submit" variant="secondary" class="w-full sm:w-auto" wire:loading.attr="disabled">
                حفظ كمسودة
            </x-button>
            <x-button wire:click="saveAndConfirm" type="button" class="w-full sm:w-auto" wire:loading.attr="disabled">
                حفظ وتأكيد
            </x-button>
        </div>
    </form>
</div>
