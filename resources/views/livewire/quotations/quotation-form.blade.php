<div>
    <div class="mb-4">
        <a href="{{ route('quotations.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى عروض الأسعار
        </a>
    </div>

    @if($errors->has('items'))
        <x-alert type="error" :message="$errors->first('items')" />
    @endif

    @php
        $firstRowErrorKey = collect($errors->keys())->first(fn ($key) => str_starts_with($key, 'items.'));
    @endphp
    @if($firstRowErrorKey)
        <x-alert type="error" :message="$errors->first($firstRowErrorKey)" />
    @endif

    @if(! $hasActiveProducts)
        <x-alert type="warning" message="أضف منتجًا نشطًا واحدًا على الأقل قبل إنشاء عرض سعر." />
    @endif

    <form wire:submit="save" class="space-y-6">
        <x-card title="بيانات عرض السعر" :allow-overflow="true">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <x-input label="رقم عرض السعر" wire:model="quotation_number" type="text" required :error="$errors->first('quotation_number')" />

                <div class="relative md:col-span-2 xl:col-span-2">
                    <div class="mb-1 flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium text-gray-700">العميل</label>
                        <button type="button"
                                wire:click="{{ $showCreateCustomerForm ? 'cancelCreateCustomer' : 'showCreateCustomer' }}"
                                class="text-sm font-medium text-primary-600 hover:text-primary-800">
                            {{ $showCreateCustomerForm ? 'إغلاق نموذج العميل' : 'إضافة عميل جديد' }}
                        </button>
                    </div>
                    <input type="hidden" wire:model="customer_id">
                    <input wire:model.live.debounce.300ms="customerSearch"
                           type="search"
                           placeholder="اكتب اسم العميل أو الهاتف ثم اختر..."
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border {{ $errors->has('customer_id') ? 'border-red-500' : 'border-gray-300' }}">
                    @if($errors->has('customer_id'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('customer_id') }}</p>
                    @endif
                    @if($customerSearch !== '' && ! $customer_id)
                        <div class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            @forelse($this->customerOptions() as $customer)
                                <button type="button"
                                        wire:click="selectCustomer({{ $customer->id }})"
                                        class="block w-full px-3 py-2 text-right text-sm text-gray-700 hover:bg-primary-50">
                                    <span class="font-medium text-gray-900">{{ $customer->name }}</span>
                                    <span class="block text-xs text-gray-500">{{ $customer->phone ?: '-' }}</span>
                                </button>
                            @empty
                                <div class="px-3 py-2 text-sm text-gray-500">لا توجد نتائج مطابقة. استخدم "إضافة عميل جديد" من نفس الشاشة.</div>
                            @endforelse
                        </div>
                    @endif

                    @if($showCreateCustomerForm)
                        <div class="mt-3 rounded-lg border border-primary-100 bg-primary-50/40 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">إضافة عميل جديد</h3>
                                    <p class="text-xs text-gray-500">بعد الحفظ سيتم اختيار العميل مباشرة في عرض السعر.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <x-input label="اسم العميل" wire:model="new_customer_name" type="text" required :error="$errors->first('new_customer_name')" />
                                <x-input label="رقم الهاتف" wire:model="new_customer_phone" type="text" required :error="$errors->first('new_customer_phone')" />
                                <x-input label="البريد الإلكتروني" wire:model="new_customer_email" type="email" :error="$errors->first('new_customer_email')" />
                                <x-input label="العنوان" wire:model="new_customer_address" type="text" :error="$errors->first('new_customer_address')" />
                            </div>

                            <div class="mt-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات العميل</label>
                                <textarea wire:model="new_customer_notes" rows="3"
                                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                                @if($errors->has('new_customer_notes'))
                                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('new_customer_notes') }}</p>
                                @endif
                            </div>

                            <div class="mt-4 flex flex-col sm:flex-row sm:justify-end gap-3">
                                <x-button wire:click="cancelCreateCustomer" type="button" variant="secondary" class="w-full sm:w-auto">إلغاء</x-button>
                                <x-button wire:click="createCustomer" type="button" class="w-full sm:w-auto">حفظ العميل واختياره</x-button>
                            </div>
                        </div>
                    @endif
                </div>

                <x-input label="التاريخ" wire:model="quotation_date" type="date" required :error="$errors->first('quotation_date')" />

                <x-select label="الحالة" wire:model="status" required :error="$errors->first('status')">
                    @foreach($statuses as $quotationStatus)
                        <option value="{{ $quotationStatus->value }}">{{ $quotationStatus->label() }}</option>
                    @endforeach
                </x-select>
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

        <x-card title="البنود والخصومات" :allow-overflow="true">
            <div class="space-y-3">
                @foreach($items as $index => $item)
                    @if(($item['row_type'] ?? \App\Models\QuotationItem::TYPE_PRODUCT) === \App\Models\QuotationItem::TYPE_SECTION)
                        <div wire:key="{{ $item['row_key'] ?? 'quotation-row-'.$index }}" class="rounded-lg border border-primary-200 bg-primary-50/50 p-4">
                            <input type="hidden" wire:model="items.{{ $index }}.row_key">
                            <input type="hidden" wire:model="items.{{ $index }}.row_type">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="flex-1">
                                    <x-input label="اسم القسم" wire:model.live="items.{{ $index }}.section_title" type="text" placeholder="مثال: غرفة النوم" :error="$errors->first('items.' . $index . '.section_title')" />
                                </div>

                                <div class="grid grid-cols-2 gap-2 md:w-auto md:grid-cols-3">
                                    <button type="button" wire:click="moveItemUp({{ $index }})" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">أعلى</button>
                                    <button type="button" wire:click="moveItemDown({{ $index }})" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">أسفل</button>
                                    <button type="button" wire:click="removeItem({{ $index }})" class="rounded-lg border border-red-300 bg-white px-3 py-2 text-sm text-red-700 hover:bg-red-50 md:col-span-1 col-span-2">حذف</button>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-primary-900/80">القسم ينظم عرض السعر فقط ولا يؤثر على الكمية أو السعر أو الإجمالي.</p>
                        </div>
                    @else
                        <div wire:key="{{ $item['row_key'] ?? 'quotation-row-'.$index }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <input type="hidden" wire:model="items.{{ $index }}.row_key">
                            <input type="hidden" wire:model="items.{{ $index }}.row_type">

                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <div class="text-xs font-medium text-gray-500">بند منتج #{{ $index + 1 }}</div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="moveItemUp({{ $index }})" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">أعلى</button>
                                    <button type="button" wire:click="moveItemDown({{ $index }})" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">أسفل</button>
                                    <button wire:click="removeItem({{ $index }})" type="button" class="rounded-lg border border-red-300 bg-white px-3 py-1.5 text-sm text-red-700 hover:bg-red-50">حذف</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-12 gap-3">
                                <div class="xl:col-span-3">
                                    <div class="relative">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">المنتج</label>
                                        <input type="hidden" wire:model="items.{{ $index }}.product_id">
                                        <input wire:model.live.debounce.300ms="productSearch.{{ $index }}"
                                               type="search"
                                               placeholder="اكتب اسم المنتج أو SKU..."
                                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border {{ $errors->has('items.' . $index . '.product_id') ? 'border-red-500' : 'border-gray-300' }}">
                                        @if($errors->has('items.' . $index . '.product_id'))
                                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('items.' . $index . '.product_id') }}</p>
                                        @endif
                                        @if(($productSearch[$index] ?? '') !== '' && ! ($items[$index]['product_id'] ?? ''))
                                            <div class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                                                @forelse($this->productOptionsFor($index) as $product)
                                                    <button type="button"
                                                            wire:click="selectProduct({{ $index }}, {{ $product->id }})"
                                                            class="block w-full px-3 py-2 text-right text-sm text-gray-700 hover:bg-primary-50">
                                                        <span class="font-medium text-gray-900">{{ $product->name }}</span>
                                                        <span class="block text-xs text-gray-500">{{ $product->internal_sku }}</span>
                                                    </button>
                                                @empty
                                                    <div class="px-3 py-2 text-sm text-gray-500">لا توجد نتائج مطابقة.</div>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="xl:col-span-1">
                                    <x-input label="الكمية" wire:model.live="items.{{ $index }}.quantity" type="number" step="0.01" min="0.01" required :error="$errors->first('items.' . $index . '.quantity')" />
                                </div>

                                <div class="xl:col-span-2">
                                    <x-input label="سعر الوحدة" wire:model.live="items.{{ $index }}.unit_sale_price" type="number" step="0.01" min="0" required :error="$errors->first('items.' . $index . '.unit_sale_price')" />
                                </div>

                                <div class="xl:col-span-2">
                                    <x-select label="نوع خصم البند" wire:model.live="items.{{ $index }}.item_discount_type" required :error="$errors->first('items.' . $index . '.item_discount_type')">
                                        @foreach($discountTypes as $type => $label)
                                            <option value="{{ $type }}">{{ $label }}</option>
                                        @endforeach
                                    </x-select>
                                </div>

                                <div class="xl:col-span-1">
                                    <x-input label="قيمة الخصم" wire:model.live="items.{{ $index }}.item_discount_value" type="number" step="0.01" min="0" :max="($item['item_discount_type'] ?? '') === 'percentage' ? '100' : null" required :error="$errors->first('items.' . $index . '.item_discount_value')" />
                                </div>

                                <div class="xl:col-span-3 xl:pt-6">
                                    <p class="text-xs text-gray-500 xl:text-right">الإجمالي بعد خصم البند</p>
                                    <p class="text-sm font-medium text-gray-900 xl:text-right">{{ \App\Support\Money::format($this->lineTotalFor($index)) }}</p>
                                    <p class="text-xs text-gray-400 xl:text-right">خصم: {{ \App\Support\Money::format($this->itemDiscountAmountFor($index)) }}</p>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="mb-1 block text-sm font-medium text-gray-700">وصف البند داخل عرض السعر</label>
                                <textarea wire:model.live="items.{{ $index }}.description"
                                          rows="3"
                                          placeholder="وصف اختياري يظهر تحت المنتج في عرض السعر المطبوع فقط."
                                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                                @if($errors->has('items.' . $index . '.description'))
                                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('items.' . $index . '.description') }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-200 pt-4">
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                    <x-button wire:click="addItem" type="button" variant="secondary" class="w-full sm:w-auto">
                        <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                        إضافة منتج
                    </x-button>
                    <x-button wire:click="addSection" type="button" variant="secondary" class="w-full sm:w-auto">
                        <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                        إضافة قسم
                    </x-button>
                </div>

                <p class="text-xs text-gray-500">يمكن تنظيم البنود داخل أقسام. الأقسام لا تؤثر على الإجمالي، والوصف يظهر في طباعة عرض السعر فقط عند تعبئته.</p>
            </div>
        </x-card>

        <x-card title="التركيب">
            <x-checkbox label="إضافة بند تركيب منفصل عن المنتجات" description="التركيب لا يؤثر على المخزون ولا يدخل ضمن خصم العرض." wire:model.live="installation_enabled" />

            @if($installation_enabled)
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <x-select label="طريقة حساب التركيب" wire:model.live="installation_pricing_mode" required :error="$errors->first('installation_pricing_mode')">
                        @foreach($installationPricingModes as $mode => $label)
                            <option value="{{ $mode }}">{{ $label }}</option>
                        @endforeach
                    </x-select>

                    @if($installation_pricing_mode === \App\Models\Quotation::INSTALLATION_PERCENTAGE)
                        <x-input label="نسبة التركيب من إجمالي المنتجات" wire:model.live="installation_percentage_value" type="number" step="0.01" min="0" max="100" required :error="$errors->first('installation_percentage_value')" />
                    @else
                        <x-input label="مبلغ التركيب الثابت" wire:model.live="installation_fixed_amount" type="number" step="0.01" min="0" required :error="$errors->first('installation_fixed_amount')" />
                    @endif

                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">إجمالي التركيب</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($installationTotal) }}</p>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">أساس النسبة</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">إجمالي المنتجات فقط: {{ \App\Support\Money::format($subtotal) }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات التركيب</label>
                    <textarea wire:model="installation_notes" rows="3"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                    @if($errors->has('installation_notes'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('installation_notes') }}</p>
                    @endif
                </div>
            @endif
        </x-card>

        <x-card title="خصم وإجمالي العرض">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-select label="نوع خصم العرض" wire:model.live="invoice_discount_type" required :error="$errors->first('invoice_discount_type')">
                    @foreach($discountTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </x-select>

                <x-input label="قيمة خصم العرض" wire:model.live="invoice_discount_value" type="number" step="0.01" min="0" :max="$invoice_discount_type === 'percentage' ? '100' : null" required :error="$errors->first('invoice_discount_value')" />
            </div>

            <dl class="mt-6 space-y-3">
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">إجمالي المنتجات بعد خصومات البنود</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($subtotal) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">خصم العرض على المنتجات فقط</dt>
                    <dd class="text-sm font-medium text-red-700">-{{ \App\Support\Money::format($invoiceDiscountAmount) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">صافي المنتجات</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($netProductsTotal) }}</dd>
                </div>
                @if($installation_enabled)
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">التركيب</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($installationTotal) }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-gray-200 pt-3">
                    <dt class="text-sm font-medium text-gray-900">الإجمالي النهائي</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($total) }}</dd>
                </div>
            </dl>
        </x-card>

        <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('quotations.index') }}" wire:navigate
               class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                إلغاء
            </a>
            <x-button type="submit" class="w-full sm:w-auto" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $isEditing ? 'تحديث عرض السعر' : 'إنشاء عرض السعر' }}</span>
                <span wire:loading>جار الحفظ...</span>
            </x-button>
        </div>
    </form>
</div>
