@php($title = 'Contact Support')
@extends('components.layouts.believoo')

@section('content')
<div class="min-h-screen bg-white text-slate-800 pt-32 pb-20 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-2">Contact Support</h1>
            <p class="text-slate-500 text-sm">Our team is here to help. Reach out anytime.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mb-12">
            <div class="p-6 rounded-2xl border border-slate-100 hover:shadow-lg hover:shadow-blue-50 transition-all">
                <div class="w-12 h-12 rounded-full bg-[#00b7ff]/10 flex items-center justify-center mb-4">
                    <i class="fas fa-envelope text-[#00b7ff] text-lg"></i>
                </div>
                <h3 class="font-bold text-slate-900 mb-1">Email Support</h3>
                <p class="text-sm text-slate-500 mb-3">Send us an email for non-urgent issues.</p>
                <a href="mailto:{{ $settings['support_email'] ?? 'support@believoo.com' }}" class="text-[#00b7ff] font-bold text-sm hover:underline">{{ $settings['support_email'] ?? 'support@believoo.com' }}</a>
            </div>
            <div class="p-6 rounded-2xl border border-slate-100 hover:shadow-lg hover:shadow-blue-50 transition-all">
                <div class="w-12 h-12 rounded-full bg-[#00b7ff]/10 flex items-center justify-center mb-4">
                    <i class="fas fa-phone text-[#00b7ff] text-lg"></i>
                </div>
                <h3 class="font-bold text-slate-900 mb-1">Phone Support</h3>
                <p class="text-sm text-slate-500 mb-3">Call us for urgent assistance.</p>
                <a href="tel:{{ $settings['contact_phone'] ?? '+91-000-000-0000' }}" class="text-[#00b7ff] font-bold text-sm hover:underline">{{ $settings['contact_phone'] ?? '+91-000-000-0000' }}</a>
            </div>
        </div>

        <div class="p-8 rounded-2xl border border-slate-100 bg-slate-50 text-center">
            <h3 class="font-bold text-slate-900 mb-2">Prefer to chat?</h3>
            <p class="text-sm text-slate-500 mb-4">Use the live chat widget on any page to connect instantly.</p>
            <a href="{{ route('support.tickets') }}" class="inline-flex items-center px-6 py-3 rounded-full bg-[#00b7ff] text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all shadow-lg shadow-blue-200">
                <i class="fas fa-ticket-alt mr-2"></i> Create a Ticket
            </a>
        </div>
    </div>
</div>
@endsection
