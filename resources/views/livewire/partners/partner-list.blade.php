<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="flex-1 max-w-md">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="ابحث في الشركاء..."
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border">
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <select wire:model.live="typeFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل الأنواع</option>
                    @foreach($types as $partnerType)
                        <option value="{{ $partnerType->value }}">{{ $partnerType->label() }}</option>
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

        <x-button wire:click="create" type="button" class="w-full sm:w-auto">
            <x-icon name="plus" class="h-4 w-4 ml-1.5" />
            إضافة شريك
        </x-button>
    </div>

    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    @if($showForm)
        <x-card :title="$editingId ? 'تعديل شريك' : 'إضافة شريك'" class="mb-6">
            <form wire:submit="save" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="اسم الشريك" wire:model="name" type="text" required :error="$errors->first('name')" />

                    <x-select label="النوع" wire:model="type" required :error="$errors->first('type')">
                        @foreach($types as $partnerType)
                            <option value="{{ $partnerType->value }}">{{ $partnerType->label() }}</option>
                        @endforeach
                    </x-select>

                    <x-input label="مسؤول التواصل" wire:model="contact_person" type="text" :error="$errors->first('contact_person')" />
                    <x-input label="الهاتف" wire:model="phone" type="text" required :error="$errors->first('phone')" />
                    <x-input label="البريد الإلكتروني" wire:model="email" type="email" :error="$errors->first('email')" />

                    <x-select label="نوع العمولة الافتراضي" wire:model.live="default_commission_type" required :error="$errors->first('default_commission_type')">
                        @foreach($commissionTypes as $commissionType)
                            <option value="{{ $commissionType->value }}">{{ $commissionType->label() }}</option>
                        @endforeach
                    </x-select>

                    <x-input
                        label="قيمة العمولة الافتراضية"
                        wire:model="default_commission_value"
                        type="number"
                        step="0.01"
                        min="0"
                        :max="$default_commission_type === 'percentage' ? '100' : null"
                        required
                        :error="$errors->first('default_commission_value')"
                    />
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

                <div>
                    <label class="flex items-center cursor-pointer">
                        <input wire:model="is_active" type="checkbox"
                               class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="mr-2 text-sm text-gray-700">شريك نشط</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">الشركاء غير النشطين يظلون في السجل ولا يستخدمون في مبيعات جديدة.</p>
                    @if($errors->has('is_active'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('is_active') }}</p>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200">
                    <x-button wire:click="cancel" type="button" variant="secondary" class="w-full sm:w-auto">إلغاء</x-button>
                    <x-button type="submit" class="w-full sm:w-auto" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'تحديث الشريك' : 'إنشاء الشريك' }}</span>
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
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الشريك</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النوع</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العمولة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($partners as $partner)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('partners.show', $partner) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800">{{ $partner->name }}</a>
                            <div class="text-sm text-gray-500">{{ $partner->phone }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $partner->type->label() }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            @if($partner->default_commission_type->value === 'percentage')
                                {{ rtrim(rtrim($partner->default_commission_value, '0'), '.') }}%
                            @else
                                {{ \App\Support\Money::format($partner->default_commission_value) }}
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($partner->is_active)
                                <x-badge color="green">نشط</x-badge>
                            @else
                                <x-badge color="gray">غير نشط</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            <a href="{{ route('partners.show', $partner) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium">عرض</a>
                            <button wire:click="edit({{ $partner->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</button>
                            <button wire:click="toggleActive({{ $partner->id }})"
                                    wire:confirm="هل تريد {{ $partner->is_active ? 'إيقاف' : 'تفعيل' }} هذا الشريك؟"
                                    class="text-sm font-medium {{ $partner->is_active ? 'text-yellow-700 hover:text-yellow-900' : 'text-green-600 hover:text-green-800' }}">
                                {{ $partner->is_active ? 'إيقاف' : 'تفعيل' }}
                            </button>
                            <button wire:click="delete({{ $partner->id }})"
                                    wire:confirm="هل تريد حذف هذا الشريك؟"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="handshake" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا يوجد شركاء.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="md:hidden space-y-3">
        @forelse($partners as $partner)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('partners.show', $partner) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:text-primary-800 truncate block">{{ $partner->name }}</a>
                        <div class="text-xs text-gray-500 truncate">{{ $partner->type->label() }} - {{ $partner->phone }}</div>
                        <div class="text-xs text-gray-500 truncate">
                            العمولة:
                            @if($partner->default_commission_type->value === 'percentage')
                                {{ rtrim(rtrim($partner->default_commission_value, '0'), '.') }}%
                            @else
                                {{ \App\Support\Money::format($partner->default_commission_value) }}
                            @endif
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        @if($partner->is_active)
                            <x-badge color="green">نشط</x-badge>
                        @else
                            <x-badge color="gray">متوقف</x-badge>
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                    <a href="{{ route('partners.show', $partner) }}" wire:navigate class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">عرض</a>
                    <button wire:click="edit({{ $partner->id }})" class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">تعديل</button>
                    <button wire:click="toggleActive({{ $partner->id }})"
                            wire:confirm="هل تريد {{ $partner->is_active ? 'إيقاف' : 'تفعيل' }} هذا الشريك؟"
                            class="text-sm font-medium py-1 {{ $partner->is_active ? 'text-yellow-700 hover:text-yellow-900' : 'text-green-600 hover:text-green-800' }}">
                        {{ $partner->is_active ? 'إيقاف' : 'تفعيل' }}
                    </button>
                    <button wire:click="delete({{ $partner->id }})"
                            wire:confirm="هل تريد حذف هذا الشريك؟"
                            class="text-red-600 hover:text-red-800 text-sm font-medium py-1">حذف</button>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="handshake" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا يوجد شركاء.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $partners->links() }}
    </div>
</div>
