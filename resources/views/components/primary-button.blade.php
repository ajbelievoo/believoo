<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-8 py-4 bg-electric-blue border border-transparent rounded-xl font-black text-xs text-dark uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-[0_0_20px_rgba(0,183,255,0.2)]']) }}>
    {{ $slot }}
</button>
