@props(['title' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 overflow-hidden']) }}>
    @if($title)
        <div class="border-b border-gray-200 px-4 py-3 sm:px-6">
            <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
        </div>
    @endif
    <div class="{{ $padding ? 'p-4 sm:p-6' : '' }}">
        {{ $slot }}
    </div>
</div>
