<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-brand border border-transparent rounded-md font-semibold text-xs text-surface-black uppercase tracking-widest hover:bg-brand-dark focus:bg-brand-dark active:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand/50 focus:ring-offset-2 focus:ring-offset-surface-dark transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
