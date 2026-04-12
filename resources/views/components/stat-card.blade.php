@props(['label', 'value', 'icon' => null, 'color' => 'primary'])

@php
    $bgColor = match($color) {
        'green' => 'bg-green-50 text-green-600',
        'red' => 'bg-red-50 text-red-600',
        'yellow' => 'bg-yellow-50 text-yellow-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'gray' => 'bg-gray-50 text-gray-600',
        default => 'bg-primary-50 text-primary-600',
    };
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
    <div class="flex items-center">
        @if($icon)
            <div class="flex-shrink-0 rounded-lg p-3 {{ $bgColor }}">
                <x-icon :name="$icon" class="h-6 w-6" />
            </div>
        @endif
        <div class="{{ $icon ? 'mr-4' : '' }} min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-500 truncate">{{ $label }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $value }}</p>
        </div>
    </div>
    @if($slot->isNotEmpty())
        <div class="mt-3 text-sm text-gray-500">
            {{ $slot }}
        </div>
    @endif
</div>
