@props(['route', 'icon', 'label', 'soon' => false])

@php
    $isActive = !$soon && request()->routeIs($route) || request()->routeIs($route . '.*');
    $classes = $isActive
        ? 'bg-primary-700 text-white'
        : 'text-primary-100 hover:bg-primary-700 hover:text-white';
@endphp

<a href="{{ $soon ? '#' : route($route) }}"
   @if(!$soon) wire:navigate @endif
   class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $classes }} {{ $soon ? 'opacity-50 cursor-default' : '' }}"
   @if($soon) onclick="event.preventDefault()" @endif>
    <x-icon :name="$icon" class="h-5 w-5 ml-3 flex-shrink-0" />
    <span>{{ $label }}</span>
    @if($soon)
        <span class="mr-auto text-[10px] bg-primary-600 text-primary-200 px-1.5 py-0.5 rounded">{{ __('ui.nav.soon') }}</span>
    @endif
</a>
