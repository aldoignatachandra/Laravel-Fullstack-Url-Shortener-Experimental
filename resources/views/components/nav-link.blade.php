@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-brand text-sm font-medium leading-5 text-surface-off-white focus:outline-none focus:border-brand transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-surface-mid-gray hover:text-surface-off-white hover:border-surface-border focus:outline-none focus:text-surface-off-white focus:border-surface-border transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
