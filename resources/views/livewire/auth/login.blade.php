<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary-800">iHome</h1>
            <p class="text-sm text-gray-500 mt-1">نظام إدارة المعرض</p>
        </div>

        @if (session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif

        <form wire:submit="login" class="space-y-5">
            <x-input
                label="البريد الإلكتروني"
                type="email"
                wire:model="email"
                placeholder="admin@ihome.com"
                required
                autofocus
                :error="$errors->first('email')"
            />

            <x-input
                label="كلمة المرور"
                type="password"
                wire:model="password"
                placeholder="أدخل كلمة المرور"
                required
                :error="$errors->first('password')"
            />

            <div class="flex items-center">
                <input wire:model="remember" type="checkbox" id="remember"
                       class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <label for="remember" class="mr-2 text-sm text-gray-600">تذكرني</label>
            </div>

            <x-button type="submit" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>تسجيل الدخول</span>
                <span wire:loading>جار تسجيل الدخول...</span>
            </x-button>
        </form>
    </div>
</div>
