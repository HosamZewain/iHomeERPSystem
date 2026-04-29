<div>
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="min-w-0">
                <label for="expense-search" class="mb-2 block text-sm font-medium text-gray-700">بحث المصروفات</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-5 w-5" />
                    </div>
                    <input id="expense-search"
                           wire:model.live.debounce.300ms="search"
                           type="search"
                           placeholder="ابحث بالوصف أو الجهة أو الملاحظات..."
                           class="block h-12 w-full rounded-lg border border-gray-300 bg-white py-3 pr-11 pl-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                </div>
            </div>

            <x-button wire:click="create" type="button" class="w-full xl:w-auto xl:min-w-[10rem]">
                <x-icon name="plus" class="ml-1.5 h-4 w-4" />
                إضافة مصروف
            </x-button>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div>
                <select wire:model.live="categoryFilter" class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل الفئات</option>
                    @foreach($allCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="paymentStatusFilter" class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل حالات السداد</option>
                    @foreach($paymentStatuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input wire:model.live="startDate" type="date" class="block h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
            </div>
            <div>
                <input wire:model.live="endDate" type="date" class="block h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
            </div>
            <div>
                <select wire:model.live="sortField" class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    @foreach($sortableFields as $field => $label)
                        <option value="{{ $field }}">ترتيب: {{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="sortDirection" class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
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

    @if($showForm)
        <x-card :title="$editingId ? 'تعديل مصروف' : 'إضافة مصروف'" class="mb-6">
            <form wire:submit="save" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <x-select label="الفئة" wire:model="expense_category_id" required :error="$errors->first('expense_category_id')">
                        <option value="">اختر الفئة</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="الوصف" wire:model="title" type="text" required :error="$errors->first('title')" />
                    <x-input label="القيمة" wire:model="amount" type="number" step="0.01" min="0.01" required :error="$errors->first('amount')" />
                    <x-input label="تاريخ المصروف" wire:model="expense_date" type="date" required :error="$errors->first('expense_date')" />
                    <x-select label="نوع المصروف" wire:model.live="expense_type" required :error="$errors->first('expense_type')">
                        @foreach($expenseTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-select>
                    @if($expense_type === 'recurring')
                        <x-select label="تكرار المصروف" wire:model="recurring_frequency" required :error="$errors->first('recurring_frequency')">
                            @foreach($recurringFrequencies as $frequency)
                                <option value="{{ $frequency->value }}">{{ $frequency->label() }}</option>
                            @endforeach
                        </x-select>
                    @endif
                    <x-input label="المبلغ المدفوع" wire:model="paid_amount" type="number" step="0.01" min="0" required :error="$errors->first('paid_amount')" />
                    <x-input label="الجهة / المستفيد" wire:model="vendor_name" type="text" :error="$errors->first('vendor_name')" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ملاحظات</label>
                    <textarea wire:model="notes" rows="3"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                    <p class="mt-1 text-xs text-gray-500">تُحتسب المصروفات في الربح الصافي حسب تاريخ المصروف وليس حسب حالة السداد.</p>
                    @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:justify-end">
                    <x-button wire:click="cancel" type="button" variant="secondary" class="w-full sm:w-auto">إلغاء</x-button>
                    <x-button type="submit" class="w-full sm:w-auto">حفظ</x-button>
                </div>
            </form>
        </x-card>
    @endif

    <div class="hidden rounded-xl border border-gray-200 bg-white overflow-hidden lg:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">المصروف</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">السداد</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">القيمة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">المدفوع</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">المتبقي</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $expense->title }}</div>
                            <div class="text-xs text-gray-500">{{ $expense->vendor_name ?: '-' }}</div>
                            @if($expense->generatedFrom)
                                <div class="mt-1 text-xs text-blue-600">مولد من {{ $expense->generatedFrom->expense_date->format('Y-m-d') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $expense->category?->name ?: '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $expense->expense_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div>{{ $expense->expense_type->label() }}</div>
                            @if($expense->expense_type->value === 'recurring' && $expense->recurring_frequency)
                                <div class="text-xs text-gray-500">{{ $expense->recurring_frequency->label() }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4"><x-badge :color="$expense->payment_status->color()">{{ $expense->payment_status->label() }}</x-badge></td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ \App\Support\Money::format($expense->amount) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ \App\Support\Money::format($expense->paid_amount) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ \App\Support\Money::format($expense->remaining_amount) }}</td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            <button wire:click="edit({{ $expense->id }})" class="text-sm font-medium text-primary-600 hover:text-primary-800">تعديل</button>
                            @if($expense->canGenerateNextOccurrence())
                                <button wire:click="generateNextOccurrence({{ $expense->id }})" class="text-sm font-medium text-green-600 hover:text-green-800">توليد التالي</button>
                            @endif
                            <button wire:click="delete({{ $expense->id }})" wire:confirm="هل تريد حذف هذا المصروف؟" class="text-sm font-medium text-red-600 hover:text-red-800">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-400">لا توجد مصروفات.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="space-y-3 lg:hidden">
        @forelse($expenses as $expense)
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-900">{{ $expense->title }}</div>
                        <div class="text-xs text-gray-500">{{ $expense->category?->name ?: '-' }} - {{ $expense->expense_date->format('Y-m-d') }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ \App\Support\Money::format($expense->amount) }} / مدفوع {{ \App\Support\Money::format($expense->paid_amount) }}</div>
                    </div>
                    <x-badge :color="$expense->payment_status->color()">{{ $expense->payment_status->label() }}</x-badge>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                    <button wire:click="edit({{ $expense->id }})" class="py-1 text-sm font-medium text-primary-600 hover:text-primary-800">تعديل</button>
                    @if($expense->canGenerateNextOccurrence())
                        <button wire:click="generateNextOccurrence({{ $expense->id }})" class="py-1 text-sm font-medium text-green-600 hover:text-green-800">توليد التالي</button>
                    @endif
                    <button wire:click="delete({{ $expense->id }})" wire:confirm="هل تريد حذف هذا المصروف؟" class="py-1 text-sm font-medium text-red-600 hover:text-red-800">حذف</button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-400">لا توجد مصروفات.</div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
</div>
