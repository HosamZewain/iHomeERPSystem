<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">قوالب الطباعة</h2>
            <p class="mt-1 text-sm text-gray-500">
                أنشئ أكثر من قالب لعروض الأسعار وفواتير البيع، وحدد القالب الافتراضي لكل نوع.
            </p>
        </div>

        <a href="{{ route('settings.print.create') }}" wire:navigate
           class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700">
            <x-icon name="plus" class="h-4 w-4 ml-1.5" />
            قالب جديد
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="ابحث بالاسم أو الكود..."
                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border">

            <select wire:model.live="documentTypeFilter"
                    class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                <option value="">كل أنواع المستندات</option>
                @foreach($documentTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter"
                    class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                <option value="">كل الحالات</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
            </select>
        </div>
    </div>

    <div class="hidden lg:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">القالب</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نوع المستند</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الترتيب</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($templates as $template)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $template->name }}</p>
                            <p class="text-xs text-gray-500">{{ $template->code }} - {{ $template->title }}</p>
                            @if($template->notes)
                                <p class="mt-1 text-xs text-gray-400">{{ $template->notes }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $template->documentTypeLabel() }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                <x-badge :color="$template->is_active ? 'green' : 'gray'">{{ $template->is_active ? 'نشط' : 'غير نشط' }}</x-badge>
                                @if($template->is_default)
                                    <x-badge color="blue">افتراضي</x-badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $template->sort_order }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="{{ route('settings.print.edit', $template) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</a>
                                <button wire:click="toggleActive({{ $template->id }})" class="text-sm font-medium {{ $template->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                    {{ $template->is_active ? 'إيقاف' : 'تفعيل' }}
                                </button>
                                @if(!$template->is_default)
                                    <button wire:click="setDefault({{ $template->id }})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعيين كافتراضي</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">لا توجد قوالب طباعة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3">
        @forelse($templates as $template)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ $template->name }}</p>
                        <p class="text-xs text-gray-500">{{ $template->documentTypeLabel() }} - {{ $template->code }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $template->title }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        <x-badge :color="$template->is_active ? 'green' : 'gray'">{{ $template->is_active ? 'نشط' : 'غير نشط' }}</x-badge>
                        @if($template->is_default)
                            <x-badge color="blue">افتراضي</x-badge>
                        @endif
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-3">
                    <a href="{{ route('settings.print.edit', $template) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</a>
                    <button wire:click="toggleActive({{ $template->id }})" class="text-sm font-medium {{ $template->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                        {{ $template->is_active ? 'إيقاف' : 'تفعيل' }}
                    </button>
                    @if(!$template->is_default)
                        <button wire:click="setDefault({{ $template->id }})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعيين كافتراضي</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-sm text-gray-400">لا توجد قوالب طباعة.</div>
        @endforelse
    </div>

    <div>
        {{ $templates->links() }}
    </div>
</div>
