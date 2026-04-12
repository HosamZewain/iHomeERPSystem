<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="flex flex-col lg:flex-row gap-3 flex-1">
            <div class="flex-1 max-w-md">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="ابحث برقم العرض أو العميل..."
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2">
                <select wire:model.live="customerFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل العملاء</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="statusFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل الحالات</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
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

        <a href="{{ route('quotations.create') }}" wire:navigate
           class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 w-full sm:w-auto">
            <x-icon name="plus" class="h-4 w-4 ml-1.5" />
            عرض سعر جديد
        </a>
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
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
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
