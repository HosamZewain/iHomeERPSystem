<div>
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="min-w-0">
                <label for="customer-search" class="mb-2 block text-sm font-medium text-gray-700">بحث العملاء</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-5 w-5" />
                    </div>
                    <input id="customer-search"
                           wire:model.live.debounce.300ms="search"
                           type="search"
                           placeholder="ابحث بالاسم أو الهاتف أو البريد..."
                           class="block h-12 w-full rounded-lg border border-gray-300 bg-white py-3 pr-11 pl-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                </div>
            </div>

            <x-button wire:click="create" type="button" class="w-full xl:w-auto xl:min-w-[10rem] xl:self-end">
                <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                إضافة عميل
            </x-button>
        </div>

        <div class="mt-4 grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <select wire:model.live="contactFilter"
                        class="h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                    <option value="">كل العملاء</option>
                    <option value="with_email">لديه بريد إلكتروني</option>
                    <option value="without_email">بدون بريد إلكتروني</option>
                    <option value="with_address">لديه عنوان</option>
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

    @if($showForm)
        <x-card :title="$editingId ? 'تعديل عميل' : 'إضافة عميل'" class="mb-6">
            <form wire:submit="save" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="اسم العميل" wire:model="name" type="text" required :error="$errors->first('name')" />
                    <x-input label="الهاتف" wire:model="phone" type="text" required :error="$errors->first('phone')" />
                    <x-input label="البريد الإلكتروني" wire:model="email" type="email" :error="$errors->first('email')" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <textarea wire:model="address" rows="3"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                    @if($errors->has('address'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('address') }}</p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea wire:model="notes" rows="3"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                    @if($errors->has('notes'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('notes') }}</p>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200">
                    <x-button wire:click="cancel" type="button" variant="secondary" class="w-full sm:w-auto">إلغاء</x-button>
                    <x-button type="submit" class="w-full sm:w-auto" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'تحديث العميل' : 'إنشاء العميل' }}</span>
                        <span wire:loading>جار الحفظ...</span>
                    </x-button>
                </div>
            </form>
        </x-card>
    @endif

    <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-sortable-th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">العميل</x-sortable-th>
                    <x-sortable-th field="phone" :sort-field="$sortField" :sort-direction="$sortDirection">الهاتف</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العنوان</th>
                    <x-sortable-th field="created_at" :sort-field="$sortField" :sort-direction="$sortDirection">تاريخ الإنشاء</x-sortable-th>
                    <x-sortable-th field="updated_at" :sort-field="$sortField" :sort-direction="$sortDirection">آخر تحديث</x-sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('customers.show', $customer) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800">{{ $customer->name }}</a>
                            <div class="text-sm text-gray-500">{{ $customer->email ?: 'لا يوجد بريد' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $customer->phone }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ $customer->address ?: '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $customer->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $customer->updated_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            <a href="{{ route('customers.show', $customer) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium">عرض</a>
                            <button wire:click="edit({{ $customer->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</button>
                            <button wire:click="delete({{ $customer->id }})"
                                    wire:confirm="هل تريد حذف هذا العميل؟"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="users" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا يوجد عملاء.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="md:hidden space-y-3">
        @forelse($customers as $customer)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('customers.show', $customer) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800 truncate block">{{ $customer->name }}</a>
                        <div class="text-xs text-gray-500 truncate">{{ $customer->phone }}</div>
                        @if($customer->email)
                            <div class="text-xs text-gray-500 truncate">{{ $customer->email }}</div>
                        @endif
                        <div class="text-xs text-gray-500">الإنشاء: {{ $customer->created_at->format('Y-m-d H:i') }}</div>
                        <div class="text-xs text-gray-500">آخر تحديث: {{ $customer->updated_at->format('Y-m-d H:i') }}</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-4 border-t border-gray-100 pt-3">
                    <a href="{{ route('customers.show', $customer) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">عرض</a>
                    <button wire:click="edit({{ $customer->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">تعديل</button>
                    <button wire:click="delete({{ $customer->id }})"
                            wire:confirm="هل تريد حذف هذا العميل؟"
                            class="text-red-600 hover:text-red-800 text-sm font-medium py-1">حذف</button>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="users" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا يوجد عملاء.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>
</div>
