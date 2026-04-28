@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-surface-light-gray']) }}>
    {{ $value ?? $slot }}
</label>
