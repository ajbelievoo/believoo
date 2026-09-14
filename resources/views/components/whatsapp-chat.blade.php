{{-- WhatsApp Chat Floating Button --}}
@php
$phone = \App\Models\Setting::getValue('whatsapp', config('services.whatsapp.business_number', ''));
$message = urlencode($message ?? 'Hi Believoo! I need help with my project.');
$whatsappUrl = $phone ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "?text={$message}" : '#';
@endphp

@if($phone)
<div class="fixed bottom-6 right-6 z-50">
    {{-- Main WhatsApp Button --}}
    <a href="{{ $whatsappUrl }}"
       target="_blank"
       class="group flex items-center gap-3 bg-green-500 hover:bg-green-600 text-white px-5 py-4 rounded-full shadow-2xl transition-all hover:scale-105">
        <div class="relative">
            <i class="fab fa-whatsapp text-2xl"></i>
            {{-- Online indicator --}}
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-300 rounded-full border-2 border-green-500 animate-pulse"></span>
        </div>
        <span class="font-bold text-sm hidden group-hover:inline-block whitespace-nowrap">Chat with us</span>
    </a>
</div>
@endif
