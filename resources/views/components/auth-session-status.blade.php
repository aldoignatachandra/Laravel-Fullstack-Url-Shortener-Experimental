@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-brand', 'aria-live' => 'polite']) }}>
        {{ $status }}
    </div>
@endif
