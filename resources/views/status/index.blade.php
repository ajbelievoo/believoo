@php($title = 'System Status')
@extends('components.layouts.believoo')

@section('content')
<div class="min-h-screen bg-dark pt-32 pb-20 px-6 relative overflow-hidden">
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full opacity-10"
             style="background: radial-gradient(circle, rgba(0,183,255,0.4) 0%, transparent 70%); filter: blur(80px);"></div>
        <div class="absolute bottom-20 -left-40 w-[400px] h-[400px] rounded-full opacity-10"
             style="background: radial-gradient(circle, rgba(112,0,255,0.4) 0%, transparent 70%); filter: blur(60px);"></div>
        <div class="absolute inset-0 opacity-20"
             style="background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 64px 64px;"></div>
    </div>

    <div class="max-w-4xl mx-auto relative z-10">
        <div class="text-center mb-12">
            <p class="text-[10px] font-black text-electric-blue uppercase tracking-[0.3em] mb-2">Status</p>
            <h1 class="text-5xl font-black text-white uppercase tracking-tighter">System <span class="text-electric-blue">Status</span></h1>
            <p class="text-gray-400 mt-3 text-sm">Real-time status of Believoo services.</p>
        </div>

        <div class="glass rounded-3xl border border-white/5 p-8 text-center mb-10">
            <div class="inline-flex items-center gap-3 px-5 py-3 rounded-full border
                @if($overall === 'operational') bg-emerald-500/10 text-emerald-400 border-emerald-500/20
                @elseif($overall === 'degraded') bg-yellow-500/10 text-yellow-400 border-yellow-500/20
                @else bg-red-500/10 text-red-400 border-red-500/20 @endif">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75
                        @if($overall === 'operational') bg-emerald-400
                        @elseif($overall === 'degraded') bg-yellow-400
                        @else bg-red-400 @endif"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3
                        @if($overall === 'operational') bg-emerald-500
                        @elseif($overall === 'degraded') bg-yellow-500
                        @else bg-red-500 @endif"></span>
                </span>
                <span class="text-sm font-black uppercase tracking-widest">
                    @if($overall === 'operational') All Systems Operational
                    @elseif($overall === 'degraded') Some Systems Degraded
                    @else Major Outage
                    @endif
                </span>
            </div>
            <p class="text-gray-500 text-xs mt-4">Last checked: {{ now()->format('M d, Y h:i A') }} UTC</p>
        </div>

        <div class="glass rounded-3xl border border-white/5 overflow-hidden">
            @foreach($services as $service)
                <div class="flex items-center justify-between p-6 border-b border-white/5 last:border-0 hover:bg-white/[0.02] transition-all">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center">
                            <i class="fas fa-server text-electric-blue text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white">{{ $service['name'] }}</h3>
                            <a href="{{ $service['url'] }}" target="_blank" rel="noopener" class="text-[10px] text-gray-500 hover:text-electric-blue transition-colors">{{ $service['url'] }}</a>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $service['badge'][1] }}">
                        <i class="fas fa-circle text-[6px]"></i> {{ $service['badge'][0] }}
                    </span>
                </div>
            @endforeach
        </div>

        @if(count($incidents))
            <div class="mt-10">
                <h2 class="text-2xl font-black text-white uppercase tracking-tight mb-6">Recent Incidents</h2>
                <div class="space-y-4">
                    @foreach($incidents as $key => $incident)
                        <div class="glass rounded-2xl border border-white/5 p-5">
                            <p class="text-sm text-gray-300">{{ $incident }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
