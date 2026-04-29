<div>
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="min-w-0">
                <label for="expense-category-search" class="mb-2 block text-sm font-medium text-gray-700">بحث فئات المصروفات</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-5 w-5" />
                    </div>
                    <input id="expense-category-search"
                           wire:model.live.debounce.300ms="search"
                           type="search"
                           placeholder="ابحث باسم الفئة..."
                           class="block h-12 w-full rounded-lg border border-gray-300 bg-white py-3 pr-11 pl-3 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                </div>
            </div>

            <x-button wire:click="create" type="button" class="w-full xl:w-auto xl:min-w-[10rem]">
                <x-icon name="plus" class="ml-1.5 h-4 w-4" />
                إضافة فئة
            </x-button>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    @if($showForm)
        <x-card :title="$editingId ? 'تعديل فئة مصروف' : 'إضافة فئة مصروف'" class="mb-6">
            <form wire:submit="save" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-input label="اسم الفئة" wire:model="name" type="text" required :error="$errors->first('name')" />
                    <div class="flex items-center pt-7">
                        <label class="flex cursor-pointer items-center">
                            <input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="mr-2 text-sm text-gray-700">فئة نشطة</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ملاحظات</label>
                    <textarea wire:model="notes" rows="3"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:justify-end">
                    <x-button wire:click="cancel" type="button" variant="secondary" class="w-full sm:w-auto">إلغاء</x-button>
                    <x-button type="submit" class="w-full sm:w-auto">حفظ</x-button>
                </div>
            </form>
        </x-card>
    @endif

    <div class="hidden rounded-xl border border-gray-200 bg-white overflow-hidden md:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الفئة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">عدد المصروفات</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($categories as $category)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                            <div class="text-xs text-gray-500">{{ $category->notes ?: '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <x-badge :color="$category->is_active ? 'green' : 'gray'">{{ $category->is_active ? 'نشط' : 'غير نشط' }}</x-badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $category->expenses_count }}</td>
                        <td class="px-6 py-4 space-x-2 space-x-reverse text-right">
                            <button wire:click="edit({{ $category->id }})" class="text-sm font-medium text-primary-600 hover:text-primary-800">تعديل</button>
                            <button wire:click="toggleActive({{ $category->id }})" class="text-sm font-medium {{ $category->is_active ? 'text-yellow-700 hover:text-yellow-900' : 'text-green-600 hover:text-green-800' }}">
                                {{ $category->is_active ? 'إيقاف' : 'تفعيل' }}
                            </button>
                            <button wire:click="delete({{ $category->id }})" wire:confirm="هل تريد حذف هذه الفئة؟" class="text-sm font-medium text-red-600 hover:text-red-800">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400">لا توجد فئات مصروفات.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="space-y-3 md:hidden">
        @forelse($categories as $category)
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                        <div class="text-xs text-gray-500">{{ $category->expenses_count }} مصروف</div>
                    </div>
                    <x-badge :color="$category->is_active ? 'green' : 'gray'">{{ $category->is_active ? 'نشط' : 'غير نشط' }}</x-badge>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                    <button wire:click="edit({{ $category->id }})" class="py-1 text-sm font-medium text-primary-600 hover:text-primary-800">تعديل</button>
                    <button wire:click="toggleActive({{ $category->id }})" class="py-1 text-sm font-medium {{ $category->is_active ? 'text-yellow-700 hover:text-yellow-900' : 'text-green-600 hover:text-green-800' }}">
                        {{ $category->is_active ? 'إيقاف' : 'تفعيل' }}
                    </button>
                    <button wire:click="delete({{ $category->id }})" wire:confirm="هل تريد حذف هذه الفئة؟" class="py-1 text-sm font-medium text-red-600 hover:text-red-800">حذف</button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-400">لا توجد فئات مصروفات.</div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
