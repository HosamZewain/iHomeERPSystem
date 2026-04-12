<div>
    {{-- Header actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            {{-- Search --}}
            <div class="flex-1 max-w-md">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="ابحث في المستخدمين..."
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border">
            </div>

            {{-- Filters --}}
            <div class="flex gap-2">
                <select wire:model.live="roleFilter"
                        class="rounded-lg border-gray-300 shadow-sm text-sm py-2.5 px-3 border">
                    <option value="">كل الأدوار</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
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

        <div class="flex gap-2">
            <a href="{{ route('users.roles') }}" wire:navigate
               class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                <x-icon name="shield" class="h-4 w-4 ml-1.5" />
                الأدوار
            </a>
            <a href="{{ route('users.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700">
                <x-icon name="plus" class="h-4 w-4 ml-1.5" />
                إضافة مستخدم
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    {{-- Desktop Table --}}
    <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستخدم</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الدور</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-9 w-9 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-sm font-semibold flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="mr-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <x-badge :color="$user->role->color()">{{ $user->role->label() }}</x-badge>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <x-badge color="green">نشط</x-badge>
                            @else
                                <x-badge color="gray">غير نشط</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 space-x-reverse">
                            <a href="{{ route('users.edit', $user) }}" wire:navigate
                               class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</a>
                            @if($user->id !== auth()->id())
                                <button wire:click="toggleActive({{ $user->id }})"
                                        wire:confirm="هل تريد {{ $user->is_active ? 'إيقاف' : 'تفعيل' }} هذا المستخدم؟"
                                        class="text-sm font-medium {{ $user->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                    {{ $user->is_active ? 'إيقاف' : 'تفعيل' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                            <x-icon name="users" class="h-10 w-10 mx-auto mb-2" />
                            <p class="text-sm">لا يوجد مستخدمون.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden space-y-3">
        @forelse($users as $user)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-center min-w-0">
                        <div class="h-10 w-10 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-sm font-semibold flex-shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="mr-3 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0 mr-2">
                        <x-badge :color="$user->role->color()">{{ $user->role->label() }}</x-badge>
                        @if($user->is_active)
                            <x-badge color="green">نشط</x-badge>
                        @else
                            <x-badge color="gray">متوقف</x-badge>
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-3 border-t border-gray-100 pt-3">
                    <a href="{{ route('users.edit', $user) }}" wire:navigate
                       class="text-primary-600 hover:text-primary-800 text-sm font-medium">تعديل</a>
                    @if($user->id !== auth()->id())
                        <button wire:click="toggleActive({{ $user->id }})"
                                wire:confirm="هل تريد {{ $user->is_active ? 'إيقاف' : 'تفعيل' }} هذا المستخدم؟"
                                class="text-sm font-medium {{ $user->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                            {{ $user->is_active ? 'إيقاف' : 'تفعيل' }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <x-icon name="users" class="h-10 w-10 mx-auto mb-2" />
                <p class="text-sm">لا يوجد مستخدمون.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
