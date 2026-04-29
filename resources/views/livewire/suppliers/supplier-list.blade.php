<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="flex-1 max-w-md">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="ابحث في الموردين..."
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border">
            </div>

            <select wire:model.live="contactFilter"
                    class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                <option value="">كل الموردين</option>
                <option value="with_email">لديه بريد إلكتروني</option>
                <option value="without_email">بدون بريد إلكتروني</option>
                <option value="with_contact_person">لديه مسؤول تواصل</option>
            </select>
        </div>

        <x-button wire:click="create" type="button" class="w-full sm:w-auto">
            <x-icon name="plus" class="h-4 w-4 ml-1.5" />
            إضافة مورد
        </x-button>
    </div>

    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    @if($showForm)
        <x-card :title="$editingId ? 'تعديل مورد' : 'إضافة مورد'" class="mb-6">
            <form wire:submit="save" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="اسم المورد" wire:model="name" type="text" required :error="$errors->first('name')" />
                    <x-input label="مسؤول التواصل" wire:model="contact_person" type="text" :error="$errors->first('contact_person')" />
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
                        <span wire:loading.remove>{{ $editingId ? 'تحديث المورد' : 'إنشاء المورد' }}</span>
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
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المورد</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التواصل</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الهاتف</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($suppliers as $supplier)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('suppliers.show', $supplier) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800">{{ $supplier->name }}</a>
                            <div class="text-sm text-gray-500">{{ $supplier->email ?: 'لا يوجد بريد' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $supplier->contact_person ?: '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $supplier->phone }}</td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            <a href="{{ route('suppliers.show', $supplier) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium">عرض</a>
                            <button wire:click="edit({{ $supplier->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</button>
                            <button wire:click="delete({{ $supplier->id }})"
                                    wire:confirm="هل تريد حذف هذا المورد؟"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="building" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا يوجد موردون.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="md:hidden space-y-3">
        @forelse($suppliers as $supplier)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('suppliers.show', $supplier) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800 truncate block">{{ $supplier->name }}</a>
                        <div class="text-xs text-gray-500 truncate">{{ $supplier->phone }}</div>
                        @if($supplier->contact_person)
                            <div class="text-xs text-gray-500 truncate">{{ $supplier->contact_person }}</div>
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-4 border-t border-gray-100 pt-3">
                    <a href="{{ route('suppliers.show', $supplier) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">عرض</a>
                    <button wire:click="edit({{ $supplier->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">تعديل</button>
                    <button wire:click="delete({{ $supplier->id }})"
                            wire:confirm="هل تريد حذف هذا المورد؟"
                            class="text-red-600 hover:text-red-800 text-sm font-medium py-1">حذف</button>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="building" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا يوجد موردون.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $suppliers->links() }}
    </div>
</div>
