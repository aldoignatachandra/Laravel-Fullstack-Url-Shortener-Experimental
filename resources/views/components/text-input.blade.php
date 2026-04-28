@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-surface-black border-surface-border focus:border-brand focus:ring-brand/50 rounded-md shadow-sm text-surface-off-white placeholder-surface-mid-gray']) }}>
