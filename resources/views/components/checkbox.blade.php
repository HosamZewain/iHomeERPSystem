@props(['label' => null, 'description' => null, 'error' => null])

<label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white px-3 py-3">
    <input
        type="checkbox"
        {{ $attributes->merge(['class' => 'mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500']) }}
    >
    <span class="min-w-0">
        @if($label)
            <span class="block text-sm font-medium text-gray-800">{{ $label }}</span>
        @endif
        @if($description)
            <span class="mt-1 block text-xs text-gray-500">{{ $description }}</span>
        @endif
        @if($error)
            <span class="mt-1 block text-xs text-red-600">{{ $error }}</span>
        @endif
    </span>
</label>
