@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full px-5 py-4 rounded-xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-sm text-gray-200 placeholder-gray-500']) }}>
