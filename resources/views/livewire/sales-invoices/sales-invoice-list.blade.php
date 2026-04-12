<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="flex flex-col xl:flex-row gap-3 flex-1">
            <div class="flex-1 max-w-md">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="ابحث برقم الفاتورة أو العميل أو الشريك..."
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 2xl:grid-cols-5 gap-2">
                <select wire:model.live="channelFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل القنوات</option>
                    @foreach($channels as $channel)
                        <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                    @endforeach
                </select>

                <select wire:model.live="partnerFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل الشركاء</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}">{{ $partner->name }}</option>
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

        <a href="{{ route('sales-invoices.create') }}" wire:navigate
           class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 w-full sm:w-auto">
            <x-icon name="plus" class="h-4 w-4 ml-1.5" />
            فاتورة بيع جديدة
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
                    <x-sortable-th field="invoice_date" :sort-field="$sortField" :sort-direction="$sortDirection">الفاتورة</x-sortable-th>
                    <x-sortable-th field="customer" :sort-field="$sortField" :sort-direction="$sortDirection">العميل</x-sortable-th>
                    <x-sortable-th field="channel" :sort-field="$sortField" :sort-direction="$sortDirection">القناة</x-sortable-th>
                    <x-sortable-th field="status" :sort-field="$sortField" :sort-direction="$sortDirection">الحالة</x-sortable-th>
                    <x-sortable-th field="items_count" :sort-field="$sortField" :sort-direction="$sortDirection">البنود</x-sortable-th>
                    <x-sortable-th field="gross_total" :sort-field="$sortField" :sort-direction="$sortDirection">إجمالي العميل</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
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
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ $invoice->items_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ \App\Support\Money::format($invoice->gross_total) }}</td>
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
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
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
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ $invoice->invoice_number }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $invoice->customer?->name ?: 'عميل نقدي' }}</div>
                        <div class="text-xs text-gray-500">{{ $invoice->invoice_date->format('Y-m-d') }} - {{ $invoice->items_count }} بند</div>
                    </div>
                    <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500">{{ $invoice->sales_channel->label() }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $invoice->partner?->name ?: '-' }}</p>
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
