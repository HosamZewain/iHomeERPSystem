<div>
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="min-w-0">
                <label for="sales-invoice-search" class="mb-2 block text-sm font-medium text-gray-700">بحث فواتير البيع</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-5 w-5" />
                    </div>
                    <input id="sales-invoice-search"
                           wire:model.live.debounce.300ms="search"
                           type="search"
                           placeholder="ابحث برقم الفاتورة أو العميل أو الشريك..."
                           class="block h-12 w-full rounded-lg border border-gray-300 bg-white py-3 pr-11 pl-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                </div>
            </div>

            <a href="{{ route('sales-invoices.create') }}" wire:navigate
               class="inline-flex w-full items-center justify-center rounded-lg bg-primary-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-primary-700 xl:w-auto xl:min-w-[10rem]">
                <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                فاتورة بيع جديدة
            </a>
        </div>

        <div class="mt-4 grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-5">
            <div>
                <select wire:model.live="channelFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل القنوات</option>
                    @foreach($channels as $channel)
                        <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="partnerFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل الشركاء</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="statusFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل الحالات</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
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

    @if(! empty($selectedInvoices))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-900">تم تحديد {{ count($selectedInvoices) }} فاتورة من الصفحة الحالية</p>
                    <p class="mt-1 text-sm text-amber-800">
                        التسوية الجماعية لحالة السداد تنشئ دفعات فعلية عند الحاجة، وتغيير حالة الفاتورة يستخدم نفس قيود التأكيد أو الإلغاء الحالية.
                    </p>
                </div>
                <button type="button"
                        wire:click="clearSelection"
                        class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-medium text-amber-900 transition hover:bg-amber-100">
                    إلغاء التحديد
                </button>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-amber-900">الإجراء الجماعي</label>
                    <select wire:model.live="bulkAction"
                            class="h-12 w-full rounded-lg border border-amber-200 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        <option value="">اختر إجراءً جماعيًا</option>
                        @foreach($bulkActions as $action => $label)
                            <option value="{{ $action }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if($bulkAction === 'mark_as_paid')
                    <div>
                        <label class="mb-2 block text-sm font-medium text-amber-900">تاريخ التحصيل</label>
                        <input wire:model="bulkPaymentDate"
                               type="date"
                               class="h-12 w-full rounded-lg border border-amber-200 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        @error('bulkPaymentDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-amber-900">طريقة التحصيل</label>
                        <select wire:model="bulkPaymentMethod"
                                class="h-12 w-full rounded-lg border border-amber-200 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                            @foreach($paymentMethods as $method => $label)
                                <option value="{{ $method }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('bulkPaymentMethod') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-amber-900">المرجع / الملاحظات</label>
                        <input wire:model="bulkReferenceNumber"
                               type="text"
                               placeholder="مرجع اختياري"
                               class="mb-2 h-12 w-full rounded-lg border border-amber-200 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        <textarea wire:model="bulkNotes"
                                  rows="2"
                                  placeholder="ملاحظات التسوية الجماعية"
                                  class="w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"></textarea>
                        @error('bulkReferenceNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('bulkNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            @if(in_array($bulkAction, ['mark_as_paid', 'confirm_drafts', 'cancel_drafts'], true))
                <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_14rem_auto] lg:items-end">
                    <div class="rounded-lg border border-amber-200 bg-white p-3 text-sm text-amber-900">
                        @if($bulkAction === 'mark_as_paid')
                            سيتم إنشاء دفعة نهائية لكل فاتورة مؤكدة بالمبلغ المتبقي عليها بدل تعديل حالة السداد يدويًا.
                        @elseif($bulkAction === 'confirm_drafts')
                            سيتم تأكيد المسودات المحددة فقط. هذا قد ينشئ حركات مخزون بعد فحص الكميات على كل فاتورة.
                        @elseif($bulkAction === 'cancel_drafts')
                            سيتم إلغاء المسودات المحددة فقط. الفواتير المؤكدة أو المرتجعة لن تتأثر.
                        @endif
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-amber-900">اكتب "تنفيذ" للتأكيد</label>
                        <input wire:model="bulkConfirmation"
                               type="text"
                               class="h-12 w-full rounded-lg border border-amber-200 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        @error('bulkConfirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="button"
                            wire:click="performBulkAction"
                            class="inline-flex h-12 items-center justify-center rounded-lg bg-amber-600 px-4 text-sm font-medium text-white transition hover:bg-amber-700">
                        تنفيذ الإجراء
                    </button>
                </div>
            @elseif($bulkAction === 'sync_payment_summary')
                <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="rounded-lg border border-amber-200 bg-white p-3 text-sm text-amber-900">
                        سيُعاد احتساب المدفوع والمتبقي وحالة السداد من واقع الدفعات والاستردادات المسجلة، بدون إنشاء أي حركة مالية أو مخزنية جديدة.
                    </div>
                    <button type="button"
                            wire:click="performBulkAction"
                            class="inline-flex h-12 items-center justify-center rounded-lg bg-amber-600 px-4 text-sm font-medium text-white transition hover:bg-amber-700">
                        تنفيذ المزامنة
                    </button>
                </div>
            @endif
        </div>
    @endif

    <div class="hidden lg:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right">
                        <input type="checkbox"
                               wire:model="selectPage"
                               class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    </th>
                    <x-sortable-th field="invoice_date" :sort-field="$sortField" :sort-direction="$sortDirection">الفاتورة</x-sortable-th>
                    <x-sortable-th field="customer" :sort-field="$sortField" :sort-direction="$sortDirection">العميل</x-sortable-th>
                    <x-sortable-th field="channel" :sort-field="$sortField" :sort-direction="$sortDirection">القناة</x-sortable-th>
                    <x-sortable-th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">الحالة</x-sortable-th>
                    <x-sortable-th field="payment_status" :sort-field="$sortField" :sort-direction="$sortDirection">حالة السداد</x-sortable-th>
                    <x-sortable-th field="items_count" :sort-field="$sortField" :sort-direction="$sortDirection">البنود</x-sortable-th>
                    <x-sortable-th field="gross_total" :sort-field="$sortField" :sort-direction="$sortDirection">إجمالي العميل</x-sortable-th>
                    <x-sortable-th field="created_at" :sort-field="$sortField" :sort-direction="$sortDirection">تاريخ الإنشاء</x-sortable-th>
                    <x-sortable-th field="updated_at" :sort-field="$sortField" :sort-direction="$sortDirection">آخر تحديث</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 align-top">
                            <input type="checkbox"
                                   wire:model="selectedInvoices"
                                   value="{{ $invoice->id }}"
                                   class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</div>
                            <div class="text-sm text-gray-500">{{ $invoice->invoice_date->format('Y-m-d') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $invoice->customer?->name ?: 'عميل نقدي' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div>{{ $invoice->sales_channel->label() }}</div>
                            @if($invoice->partner)
                                <div class="text-xs text-gray-500">{{ $invoice->partner->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                        </td>
                        <td class="px-6 py-4">
                            <x-badge :color="$invoice->payment_status->color()">{{ $invoice->payment_status->label() }}</x-badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ $invoice->items_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($invoice->gross_total) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $invoice->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $invoice->updated_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('sales-invoices.show', $invoice) }}" wire:navigate
                               class="text-primary-600 hover:text-primary-800 text-sm font-medium">عرض</a>
                            @if($invoice->status === \App\Enums\SalesInvoiceStatus::Draft)
                                <a href="{{ route('sales-invoices.edit', $invoice) }}" wire:navigate
                                   class="mr-3 text-gray-600 hover:text-gray-800 text-sm font-medium">تعديل</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="receipt" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا توجد فواتير بيع.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3">
        @forelse($invoices as $invoice)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <input type="checkbox"
                               wire:model="selectedInvoices"
                               value="{{ $invoice->id }}"
                               class="mt-1 h-4 w-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $invoice->invoice_number }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $invoice->customer?->name ?: 'عميل نقدي' }}</div>
                            <div class="text-xs text-gray-500">{{ $invoice->invoice_date->format('Y-m-d') }} - {{ $invoice->items_count }} بند</div>
                            <div class="mt-1">
                                <x-badge :color="$invoice->payment_status->color()">{{ $invoice->payment_status->label() }}</x-badge>
                            </div>
                        </div>
                    </div>
                    <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500">{{ $invoice->sales_channel->label() }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $invoice->partner?->name ?: '-' }}</p>
                        <p class="text-xs text-gray-500">الإنشاء: {{ $invoice->created_at->format('Y-m-d H:i') }}</p>
                        <p class="text-xs text-gray-500">آخر تحديث: {{ $invoice->updated_at->format('Y-m-d H:i') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($invoice->gross_total) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('sales-invoices.show', $invoice) }}" wire:navigate
                           class="text-primary-600 hover:text-primary-800 text-sm font-medium py-2 px-2">عرض</a>
                        @if($invoice->status === \App\Enums\SalesInvoiceStatus::Draft)
                            <a href="{{ route('sales-invoices.edit', $invoice) }}" wire:navigate
                               class="text-gray-600 hover:text-gray-800 text-sm font-medium py-2 px-2">تعديل</a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="receipt" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا توجد فواتير بيع.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
</div>
