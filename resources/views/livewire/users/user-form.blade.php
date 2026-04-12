<div>
    <div class="max-w-2xl mx-auto">
        {{-- Back link --}}
        <div class="mb-4">
            <a href="{{ route('users.index') }}" wire:navigate
               class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
                الرجوع إلى المستخدمين
            </a>
        </div>

        <x-card :title="$pageTitle">
            <form wire:submit="save" class="space-y-5">
                {{-- Name --}}
                <x-input
                    label="الاسم الكامل"
                    wire:model="name"
                    type="text"
                    placeholder="أدخل الاسم الكامل"
                    required
                    :error="$errors->first('name')"
                />

                {{-- Email --}}
                <x-input
                    label="البريد الإلكتروني"
                    wire:model="email"
                    type="email"
                    placeholder="user@ihome.com"
                    required
                    :error="$errors->first('email')"
                />

                {{-- Role --}}
                <x-select
                    label="الدور"
                    wire:model="role"
                    required
                    :error="$errors->first('role')"
                >
                    @foreach($roles as $r)
                        <option value="{{ $r->value }}">{{ $r->label() }} — {{ $r->description() }}</option>
                    @endforeach
                </x-select>

                {{-- كلمة المرور --}}
                <x-input
                    label="{{ $isEditing ? 'كلمة مرور جديدة (اتركها فارغة للإبقاء على الحالية)' : 'كلمة المرور' }}"
                    wire:model="password"
                    type="password"
                    placeholder="{{ $isEditing ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية' : '8 أحرف على الأقل' }}"
                    :required="!$isEditing"
                    :error="$errors->first('password')"
                />

                {{-- تأكيد كلمة المرور --}}
                <x-input
                    label="تأكيد كلمة المرور"
                    wire:model="password_confirmation"
                    type="password"
                    placeholder="أعد إدخال كلمة المرور"
                    :required="!$isEditing"
                    :error="$errors->first('password_confirmation')"
                />

                {{-- Active toggle --}}
                <div>
                    <label class="flex items-center cursor-pointer">
                        <input wire:model="is_active" type="checkbox"
                               class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="mr-2 text-sm text-gray-700">حساب نشط</span>
                    </label>
                    @if($errors->has('is_active'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('is_active') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">المستخدمون غير النشطين لا يمكنهم تسجيل الدخول للنظام.</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('users.index') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        إلغاء
                    </a>
                    <x-button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $isEditing ? 'تحديث المستخدم' : 'إنشاء المستخدم' }}</span>
                        <span wire:loading>جار الحفظ...</span>
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</div>
