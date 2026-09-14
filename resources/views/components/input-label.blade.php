@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-black text-[10px] text-gray-500 uppercase tracking-[0.2em] mb-2 ml-1']) }}>
    {{ $value ?? $slot }}
</label>
