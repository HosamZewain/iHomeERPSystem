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
                    <x-sortable-th field="created_at" :sort-field="$sortField" :sort-direction="$sortDirection">تاريخ الإنشاء</x-sortable-th>
                    <x-sortable-th field="updated_at" :sort-field="$sortField" :sort-direction="$sortDirection">آخر تحديث</x-sortable-th>
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
                        <td colspan="9" class="px-6 py-12 text-center text-gray-400">
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
