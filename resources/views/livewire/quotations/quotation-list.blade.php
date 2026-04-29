<div>
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="min-w-0">
                <label for="quotation-search" class="mb-2 block text-sm font-medium text-gray-700">بحث عروض الأسعار</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-5 w-5" />
                    </div>
                    <input id="quotation-search"
                           wire:model.live.debounce.300ms="search"
                           type="search"
                           placeholder="ابحث برقم العرض أو العميل..."
                           class="block h-12 w-full rounded-lg border border-gray-300 bg-white py-3 pr-11 pl-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                </div>
            </div>

            <a href="{{ route('quotations.create') }}" wire:navigate
               class="inline-flex w-full items-center justify-center rounded-lg bg-primary-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-primary-700 xl:w-auto xl:min-w-[10rem]">
                <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                عرض سعر جديد
            </a>
        </div>

        <div class="mt-4 grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <select wire:model.live="customerFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل العملاء</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
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

    <div class="hidden lg:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-sortable-th field="quotation_date" :sort-field="$sortField" :sort-direction="$sortDirection">عرض السعر</x-sortable-th>
                    <x-sortable-th field="customer" :sort-field="$sortField" :sort-direction="$sortDirection">العميل</x-sortable-th>
                    <x-sortable-th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">الحالة</x-sortable-th>
                    <x-sortable-th field="items_count" :sort-field="$sortField" :sort-direction="$sortDirection">البنود</x-sortable-th>
                    <x-sortable-th field="total" :sort-field="$sortField" :sort-direction="$sortDirection">الإجمالي</x-sortable-th>
                    <x-sortable-th field="created_at" :sort-field="$sortField" :sort-direction="$sortDirection">تاريخ الإنشاء</x-sortable-th>
                    <x-sortable-th field="updated_at" :sort-field="$sortField" :sort-direction="$sortDirection">آخر تحديث</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($quotations as $quotation)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $quotation->quotation_number }}</div>
                            <div class="text-sm text-gray-500">{{ $quotation->quotation_date->format('Y-m-d') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $quotation->customer->name }}</td>
                        <td class="px-6 py-4">
                            <x-badge :color="$quotation->status->color()">{{ $quotation->status->label() }}</x-badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ $quotation->items_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($quotation->total) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $quotation->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $quotation->updated_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            <a href="{{ route('quotations.show', $quotation) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium">عرض</a>
                            @if($quotation->canConvert())
                                <button type="button"
                                        wire:click="convert({{ $quotation->id }})"
                                        wire:confirm="هل تريد تحويل عرض السعر إلى فاتورة بيع مسودة؟"
                                        class="text-green-600 hover:text-green-800 text-sm font-medium">
                                    تحويل
                                </button>
                            @elseif($quotation->salesInvoice)
                                <a href="{{ route('sales-invoices.show', $quotation->salesInvoice) }}" wire:navigate class="text-green-600 hover:text-green-800 text-sm font-medium">الفاتورة</a>
                            @endif
                            @if($quotation->canEdit())
                                <a href="{{ route('quotations.edit', $quotation) }}" wire:navigate class="text-gray-600 hover:text-gray-800 text-sm font-medium">تعديل</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="document-text" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا توجد عروض أسعار.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3">
        @forelse($quotations as $quotation)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ $quotation->quotation_number }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $quotation->customer->name }}</div>
                        <div class="text-xs text-gray-500">{{ $quotation->quotation_date->format('Y-m-d') }} - {{ $quotation->items_count }} بند</div>
                    </div>
                    <x-badge :color="$quotation->status->color()">{{ $quotation->status->label() }}</x-badge>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                    <div>
                        <p class="text-xs text-gray-500">الإجمالي</p>
                        <p class="text-xs text-gray-500">الإنشاء: {{ $quotation->created_at->format('Y-m-d H:i') }}</p>
                        <p class="text-xs text-gray-500">آخر تحديث: {{ $quotation->updated_at->format('Y-m-d H:i') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ \App\Support\Money::format($quotation->total) }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('quotations.show', $quotation) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">عرض</a>
                        @if($quotation->canConvert())
                            <button type="button"
                                    wire:click="convert({{ $quotation->id }})"
                                    wire:confirm="هل تريد تحويل عرض السعر إلى فاتورة بيع مسودة؟"
                                    class="text-green-600 hover:text-green-800 text-sm font-medium py-1">
                                تحويل
                            </button>
                        @elseif($quotation->salesInvoice)
                            <a href="{{ route('sales-invoices.show', $quotation->salesInvoice) }}" wire:navigate class="text-green-600 hover:text-green-800 text-sm font-medium py-1">الفاتورة</a>
                        @endif
                        @if($quotation->canEdit())
                            <a href="{{ route('quotations.edit', $quotation) }}" wire:navigate class="text-gray-600 hover:text-gray-800 text-sm font-medium py-1">تعديل</a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="document-text" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا توجد عروض أسعار.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $quotations->links() }}
    </div>
</div>
