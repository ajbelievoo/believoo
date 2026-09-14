@php($title = 'System Status')
@extends('components.layouts.believoo')

@section('content')
<div class="min-h-screen bg-white text-slate-800 pt-32 pb-20 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-2">System Status</h1>
            <p class="text-slate-500 text-sm">Real-time status across all Believoo platforms.</p>
        </div>

        <div class="rounded-2xl border border-slate-100 p-8 text-center mb-10 bg-slate-50">
            <div class="inline-flex items-center gap-3 px-5 py-3 rounded-full border
                @if($overall === 'operational') bg-emerald-50 text-emerald-600 border-emerald-200
                @elseif($overall === 'degraded') bg-yellow-50 text-yellow-600 border-yellow-200
                @else bg-red-50 text-red-600 border-red-200 @endif">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $overall === 'operational' ? 'bg-emerald-400' : ($overall === 'degraded' ? 'bg-yellow-400' : 'bg-red-400') }}"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 {{ $overall === 'operational' ? 'bg-emerald-500' : ($overall === 'degraded' ? 'bg-yellow-500' : 'bg-red-500') }}"></span>
                </span>
                <span class="text-sm font-black uppercase tracking-widest">
                    @if($overall === 'operational') All Systems Operational
                    @elseif($overall === 'degraded') Some Systems Degraded
                    @else Major Outage
                    @endif
                </span>
            </div>
            <p class="text-slate-400 text-xs mt-4">Last checked: {{ now()->format('M d, Y h:i A') }} UTC</p>
        </div>

        <div class="rounded-2xl border border-slate-100 overflow-hidden bg-white">
            @foreach($services as $service)
                <div class="flex items-center justify-between p-6 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                            <i class="fas fa-server text-[#00b7ff] text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900">{{ $service['name'] }}</h3>
                            <a href="{{ $service['url'] }}" target="_blank" rel="noopener" class="text-[10px] text-slate-400 hover:text-[#00b7ff] transition-colors">{{ $service['url'] }}</a>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $service['badge'][1] }}">
                        <i class="fas fa-circle text-[6px]"></i> {{ $service['badge'][0] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
