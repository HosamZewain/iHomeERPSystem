<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="flex-1 max-w-md">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="ابحث في التصنيفات..."
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border">
            </div>

            <select wire:model.live="sort"
                    class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                <option value="name">الاسم أ-ي</option>
                <option value="latest">الأحدث أولًا</option>
            </select>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <x-card title="إضافة تصنيف" class="mb-6">
        <form wire:submit="create" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input
                    label="اسم التصنيف"
                    wire:model="name"
                    type="text"
                    placeholder="إضاءة، أمان، حساسات..."
                    required
                    :error="$errors->first('name')"
                />
            </div>
            <div class="flex items-end">
                <x-button type="submit" class="w-full sm:w-auto" wire:loading.attr="disabled">
                    <span wire:loading.remove>إضافة التصنيف</span>
                    <span wire:loading>جار الحفظ...</span>
                </x-button>
            </div>
        </form>
    </x-card>

    <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التصنيف</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنتجات</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($categories as $category)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            @if($editingId === $category->id)
                                <div class="max-w-sm">
                                    <x-input
                                        wire:model="editingName"
                                        type="text"
                                        :error="$errors->first('editingName')"
                                    />
                                </div>
                            @else
                                <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $category->products_count ?? 0 }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            @if($editingId === $category->id)
                                <button wire:click="update" class="text-primary-600 hover:text-primary-800 text-sm font-medium">حفظ</button>
                                <button wire:click="cancelEdit" class="text-gray-600 hover:text-gray-800 text-sm font-medium">إلغاء</button>
                            @else
                                <button wire:click="startEdit({{ $category->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</button>
                                <button wire:click="delete({{ $category->id }})"
                                        wire:confirm="هل تريد حذف هذا التصنيف؟"
                                        class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="tag" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا توجد تصنيفات.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="md:hidden space-y-3">
        @forelse($categories as $category)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                @if($editingId === $category->id)
                    <div class="space-y-3">
                        <x-input
                            label="اسم التصنيف"
                            wire:model="editingName"
                            type="text"
                            :error="$errors->first('editingName')"
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <x-button wire:click="update" type="button">حفظ</x-button>
                            <x-button wire:click="cancelEdit" type="button" variant="secondary">إلغاء</x-button>
                        </div>
                    </div>
                @else
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $category->name }}</div>
                            <div class="text-xs text-gray-500">{{ $category->products_count ?? 0 }} منتج</div>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-4 border-t border-gray-100 pt-3">
                        <button wire:click="startEdit({{ $category->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">تعديل</button>
                        <button wire:click="delete({{ $category->id }})"
                                wire:confirm="هل تريد حذف هذا التصنيف؟"
                                class="text-red-600 hover:text-red-800 text-sm font-medium py-1">حذف</button>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="tag" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا توجد تصنيفات.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
