@props([
    'field',
    'sortField' => null,
    'sortDirection' => 'asc',
])

<th {{ $attributes->merge(['class' => 'px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider']) }}>
    <button type="button"
            wire:click="sortBy('{{ $field }}')"
            class="inline-flex items-center gap-1 hover:text-gray-700">
        <span>{{ $slot }}</span>
        @if($sortField === $field)
            <span class="text-primary-600">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
        @else
            <span class="text-gray-300">↕</span>
        @endif
    </button>
</th>
