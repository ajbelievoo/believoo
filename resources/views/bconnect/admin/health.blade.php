@extends('bconnect.layout')
@section('title', 'Server Health')
@section('content')
<div class="bc-card p-6 max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h3 class="font-bold">Server health</h3>
        <button onclick="location.reload()" class="bc-btn bc-btn-secondary text-xs"><i class="fas fa-rotate mr-1"></i>Recheck</button>
    </div>
    <div class="space-y-3">
        @foreach([
            'db' => 'Database', 'cache' => 'Cache / signal queue', 'disk' => 'Disk space',
            'ws' => 'WebSocket (Reverb)', 'queue' => 'Queue worker', 'agents' => 'Registered agents'
        ] as $k => $label)
        <div class="flex items-center justify-between bg-slate-800/40 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full {{ $h[$k]['ok'] ? 'bg-green-400' : 'bg-red-500' }}"></span>
                <span class="font-medium text-sm">{{ $label }}</span>
            </div>
            <span class="text-xs text-slate-400">{{ $h[$k]['detail'] }}</span>
        </div>
        @endforeach
    </div>
    <a href="{{ route('bconnect.admin.audit.csv') }}" class="bc-btn bc-btn-secondary text-xs mt-6 inline-block"><i class="fas fa-file-csv mr-1"></i>Export audit log (CSV)</a>
</div>
@endsection
