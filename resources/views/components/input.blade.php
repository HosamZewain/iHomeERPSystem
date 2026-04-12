@props(['label' => null, 'error' => null])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <input {{ $attributes->merge([
        'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border ' . ($error ? 'border-red-500' : 'border-gray-300')
    ]) }}>
    @if($error)
        <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
