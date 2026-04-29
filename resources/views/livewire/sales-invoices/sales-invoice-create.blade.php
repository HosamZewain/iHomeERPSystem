<div>
    <div class="mb-4">
        <a href="{{ $isEditing ? route('sales-invoices.show', $invoice) : route('sales-invoices.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            {{ $isEditing ? 'الرجوع إلى الفاتورة' : 'الرجوع إلى فواتير البيع' }}
        </a>
    </div>

    @if($errors->has('items'))
        <x-alert type="error" :message="$errors->first('items')" />
    @endif

    @if(! $hasActiveProducts)
        <x-alert type="warning" message="أضف منتجًا نشطًا واحدًا على الأقل قبل إنشاء فاتورة بيع." />
    @endif

    <form wire:submit="saveDraft" class="space-y-6">
        <x-card title="بيانات الفاتورة" :allow-overflow="true">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                <x-input label="رقم الفاتورة" wire:model="invoice_number" type="text" required :error="$errors->first('invoice_number')" />

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
                           placeholder="عميل نقدي أو اكتب اسم العميل / الهاتف..."
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
                                <div class="px-3 py-2 text-sm text-gray-500">لا توجد نتائج مطابقة. اترك الحقل فارغًا للعميل النقدي أو أضف عميلًا جديدًا من نفس الشاشة.</div>
                            @endforelse
                        </div>
                    @endif

                    @if($showCreateCustomerForm)
                        <div class="mt-3 rounded-lg border border-primary-100 bg-primary-50/40 p-4">
                            <div class="mb-3">
                                <h3 class="text-sm font-semibold text-gray-900">إضافة عميل جديد</h3>
                                <p class="text-xs text-gray-500">بعد الحفظ سيتم اختيار العميل مباشرة في فاتورة البيع.</p>
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

                <x-input label="التاريخ" wire:model="invoice_date" type="date" required :error="$errors->first('invoice_date')" />

                <x-select label="قناة البيع" wire:model.live="sales_channel" required :error="$errors->first('sales_channel')">
                    @foreach($channels as $channel)
                        <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                    @endforeach
                </x-select>

                <x-input label="تاريخ الاستحقاق" wire:model="due_date" type="date" :error="$errors->first('due_date')" />
            </div>

            @if($sales_channel === 'partner')
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-select label="الشريك" wire:model.live="partner_id" required :error="$errors->first('partner_id')">
                        <option value="">اختر الشريك</option>
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select label="نوع عمولة الشريك" wire:model.live="partner_commission_type" required :error="$errors->first('partner_commission_type')">
                        @foreach($commissionTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </x-select>

                    <x-input label="قيمة عمولة الشريك" wire:model.live="partner_commission_value" type="number" step="0.01" min="0" :max="$partner_commission_type === 'percentage' ? '100' : null" required :error="$errors->first('partner_commission_value')" />
                </div>

                <p class="mt-2 text-xs text-gray-500">عمولة الشريك لا تخصم من إجمالي العميل، وتظهر منفصلة لحساب صافي الإيراد والربح.</p>
            @endif

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
            <div class="hidden xl:block">
                <div class="grid grid-cols-12 gap-3 px-1 pb-2 text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="col-span-3">المنتج</div>
                    <div class="col-span-1 text-right">الكمية</div>
                    <div class="col-span-2 text-right">سعر الوحدة</div>
                    <div class="col-span-2 text-right">نوع الخصم</div>
                    <div class="col-span-1 text-right">قيمة الخصم</div>
                    <div class="col-span-2 text-right">الإجمالي</div>
                    <div class="col-span-1"></div>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($items as $index => $item)
                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-3 bg-gray-50 xl:bg-white border border-gray-200 xl:border-0 rounded-lg xl:rounded-none p-3 xl:p-0">
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
                                                <span class="block text-xs text-gray-500">{{ $product->internal_sku }} - مخزون {{ number_format($product->current_stock_quantity, 2) }}</span>
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

                        <div class="xl:col-span-2 xl:pt-6">
                            <p class="text-xs text-gray-500 xl:text-right">إجمالي السطر للعميل</p>
                            <p class="text-sm font-medium text-gray-900 xl:text-right">{{ \App\Support\Money::format($this->lineTotalFor($index)) }}</p>
                            <p class="text-xs text-gray-400 xl:text-right">خصم: {{ \App\Support\Money::format($this->itemDiscountAmountFor($index)) }}</p>
                        </div>

                        <div class="xl:col-span-1 xl:pt-6 xl:text-right">
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
                    إضافة منتج
                </x-button>

                <p class="text-xs text-gray-500">الفاتورة المسودة لا تؤثر على المخزون. يتم خصم المخزون عند التأكيد فقط.</p>
            </div>
        </x-card>

        <x-card title="التركيب">
            <x-checkbox label="إضافة بند تركيب منفصل عن المنتجات" description="التركيب لا يؤثر على المخزون، وخصم الفاتورة يطبق على المنتجات فقط." wire:model.live="installation_enabled" />

            @if($installation_enabled)
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <x-select label="طريقة حساب التركيب" wire:model.live="installation_pricing_mode" required :error="$errors->first('installation_pricing_mode')">
                        @foreach($installationPricingModes as $mode => $label)
                            <option value="{{ $mode }}">{{ $label }}</option>
                        @endforeach
                    </x-select>

                    @if($installation_pricing_mode === \App\Models\SalesInvoice::INSTALLATION_PERCENTAGE)
                        <x-input label="نسبة التركيب من إجمالي المنتجات" wire:model.live="installation_percentage_value" type="number" step="0.01" min="0" max="100" required :error="$errors->first('installation_percentage_value')" />
                    @else
                        <x-input label="مبلغ التركيب الثابت" wire:model.live="installation_fixed_amount" type="number" step="0.01" min="0" required :error="$errors->first('installation_fixed_amount')" />
                    @endif

                    <x-select label="منفذ / مستحق التركيب" wire:model="installation_party_type" required :error="$errors->first('installation_party_type')">
                        @foreach($installationPartyTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </x-select>

                    <x-input label="اسم أو مرجع الطرف" wire:model="installation_party_reference" type="text" :error="$errors->first('installation_party_reference')" />

                    <x-input label="تكلفة / مستحق التركيب" wire:model.live="installation_payout_amount" type="number" step="0.01" min="0" required :error="$errors->first('installation_payout_amount')" />

                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-500">إجمالي التركيب للعميل</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($installationTotal) }}</p>
                    </div>

                    @if($showProfit)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs text-gray-500">ربح التركيب قبل عمولة الشريك</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($installationProfit) }}</p>
                        </div>
                    @endif

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

        <x-card title="الخصومات والإجماليات">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-select label="نوع خصم الفاتورة" wire:model.live="invoice_discount_type" required :error="$errors->first('invoice_discount_type')">
                    @foreach($discountTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </x-select>

                <x-input label="قيمة خصم الفاتورة" wire:model.live="invoice_discount_value" type="number" step="0.01" min="0" :max="$invoice_discount_type === 'percentage' ? '100' : null" required :error="$errors->first('invoice_discount_value')" />
            </div>

            <dl class="mt-6 space-y-3">
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">إجمالي المنتجات بعد خصومات البنود</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($subtotal) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">خصم الفاتورة على المنتجات فقط</dt>
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
                    <dt class="text-sm font-medium text-gray-900">إجمالي فاتورة العميل</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ \App\Support\Money::format($grossTotal) }}</dd>
                </div>
                @if($sales_channel === 'partner')
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500">عمولة الشريك</dt>
                        <dd class="text-sm font-medium text-red-700">-{{ \App\Support\Money::format($partnerCommissionAmount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm font-medium text-gray-900">صافي الإيراد بعد العمولة</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ \App\Support\Money::format($netRevenue) }}</dd>
                    </div>
                @endif
            </dl>

            @if($showProfit)
                <x-alert type="info" message="تكلفة البيع والربح يتم تثبيتهما عند تأكيد الفاتورة باستخدام متوسط تكلفة المنتج وقت التأكيد." class="mt-4" />
            @endif
        </x-card>

        <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('sales-invoices.index') }}" wire:navigate
               class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                إلغاء
            </a>
            <x-button type="submit" variant="secondary" class="w-full sm:w-auto" wire:loading.attr="disabled">
                {{ $isEditing ? 'تحديث المسودة' : 'حفظ كمسودة' }}
            </x-button>
            <x-button wire:click="saveAndConfirm" type="button" class="w-full sm:w-auto" wire:loading.attr="disabled">
                {{ $isEditing ? 'تحديث وتأكيد' : 'حفظ وتأكيد' }}
            </x-button>
        </div>
    </form>
</div>
