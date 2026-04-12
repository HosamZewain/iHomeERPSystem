@props(['type' => 'info', 'message'])

@php
    $styles = match($type) {
        'success' => 'bg-green-50 border-green-400 text-green-800',
        'error' => 'bg-red-50 border-red-400 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-400 text-yellow-800',
        default => 'bg-blue-50 border-blue-400 text-blue-800',
    };
    $icon = match($type) {
        'success' => 'check-circle',
        'error', 'warning' => 'exclamation-triangle',
        default => 'check-circle',
    };
@endphp

<div class="rounded-lg border-r-4 p-4 mb-4 {{ $styles }}" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
    <div class="flex items-center">
        <x-icon :name="$icon" class="h-5 w-5 ml-2 flex-shrink-0" />
        <p class="text-sm font-medium">{{ $message }}</p>
        <button @click="show = false" class="mr-auto">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>
</div>
