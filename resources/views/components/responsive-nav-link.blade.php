@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand text-start text-base font-medium text-brand bg-brand/10 focus:outline-none focus:text-surface-off-white focus:bg-brand/10 focus:border-brand transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-surface-mid-gray hover:text-surface-off-white hover:bg-surface-border hover:border-surface-border focus:outline-none focus:text-surface-off-white focus:bg-surface-border focus:border-surface-border transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
