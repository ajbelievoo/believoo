@php($title = 'Believoo Support')
@extends('components.layouts.believoo')

@section('content')
<div class="min-h-screen bg-white text-slate-800 pt-24 pb-20 relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 relative z-10">

        {{-- Hero --}}
        <div class="text-center pt-16 pb-12">
            <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-[#00b7ff] to-[#0066ff] flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-blue-200">
                <i class="fas fa-life-ring text-3xl text-white"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-4">How can we help?</h1>
            <p class="text-slate-500 max-w-xl mx-auto mb-8">Find answers, check system status, create a support ticket, or chat with our team. All support for Believoo, GHC, Bmydesk and Webmail in one place.</p>

            <form action="{{ route('support.kb.index') }}" method="GET" class="max-w-2xl mx-auto relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="q" placeholder="Describe your issue..."
                       class="w-full pl-12 pr-4 py-4 rounded-full border border-slate-200 shadow-lg shadow-slate-100 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#00b7ff] focus:ring-2 focus:ring-[#00b7ff]/20 transition-all">
            </form>
        </div>

        {{-- Product cards --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-16">
            @foreach($products as $product)
                <a href="{{ $product['url'] }}" target="_blank" rel="noopener" class="group p-6 rounded-2xl border border-slate-100 bg-white hover:border-[#00b7ff]/30 hover:shadow-xl hover:shadow-blue-50 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 group-hover:bg-[#00b7ff]/10 flex items-center justify-center mb-4 transition-all">
                        <i class="fas {{ $product['icon'] }} text-[#00b7ff] text-lg"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-1">{{ $product['name'] }}</h3>
                    <p class="text-xs text-slate-500">{{ $product['desc'] }}</p>
                </a>
            @endforeach
        </div>

        {{-- Quick actions --}}
        <div class="grid md:grid-cols-3 gap-6 mb-16">
            <a href="{{ route('support.tickets') }}" class="p-6 rounded-2xl bg-slate-50 hover:bg-slate-100 transition-all flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center text-[#00b7ff]">
                    <i class="fas fa-ticket-alt text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 mb-1">Create a ticket</h3>
                    <p class="text-xs text-slate-500">Open a support ticket and track all your requests.</p>
                </div>
            </a>
            <a href="{{ route('support.kb.index') }}" class="p-6 rounded-2xl bg-slate-50 hover:bg-slate-100 transition-all flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center text-[#00b7ff]">
                    <i class="fas fa-book text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 mb-1">Help Center</h3>
                    <p class="text-xs text-slate-500">Browse articles and guides for all services.</p>
                </div>
            </a>
            <a href="{{ route('support.status') }}" class="p-6 rounded-2xl bg-slate-50 hover:bg-slate-100 transition-all flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center text-[#00b7ff]">
                    <i class="fas fa-chart-line text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 mb-1">System Status</h3>
                    <p class="text-xs text-slate-500">Check real-time status of all platforms.</p>
                </div>
            </a>
        </div>

        {{-- Status summary --}}
        <div class="rounded-2xl border border-slate-100 p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $overall === 'operational' ? 'bg-emerald-400' : 'bg-yellow-400' }}"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 {{ $overall === 'operational' ? 'bg-emerald-500' : 'bg-yellow-500' }}"></span>
                </span>
                <span class="font-bold text-slate-900">{{ $overall === 'operational' ? 'All systems operational' : 'Some services degraded' }}</span>
            </div>
            <a href="{{ route('support.status') }}" class="text-sm font-bold text-[#00b7ff] hover:underline">View status page →</a>
        </div>
    </div>
</div>
@endsection
