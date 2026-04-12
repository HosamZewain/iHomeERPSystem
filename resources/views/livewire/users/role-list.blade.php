<div>
    {{-- Back link --}}
    <div class="mb-4">
        <a href="{{ route('users.index') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى المستخدمين
        </a>
    </div>

    <div class="space-y-4">
        @foreach($roles as $item)
            @php $role = $item['role']; @endphp
            <x-card>
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-base font-semibold text-gray-900">{{ $role->label() }}</h3>
                            <x-badge :color="$role->color()">{{ $item['user_count'] }} مستخدم</x-badge>
                        </div>
                        <p class="text-sm text-gray-500">{{ $role->description() }}</p>
                    </div>
                </div>

                {{-- الصلاحيات --}}
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">الصلاحيات</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($item['permissions'] as $perm)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-600">
                                {{ $perm }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>

    {{-- Info note --}}
    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-start">
            <x-icon name="info" class="h-5 w-5 text-blue-500 ml-2 flex-shrink-0 mt-0.5" />
            <div class="text-sm text-blue-700">
                <p class="font-medium">عن الأدوار</p>
                <p class="mt-1">الأدوار محددة مسبقًا في النظام. لكل دور مجموعة صلاحيات ثابتة تحدد ما يمكن للمستخدم الوصول إليه. لتغيير صلاحيات مستخدم، عدّل الدور من صفحة تعديل المستخدم.</p>
            </div>
        </div>
    </div>
</div>
