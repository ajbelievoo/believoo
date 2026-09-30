@extends('bconnect.layout')
@section('title', 'Notifications')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-white">Notifications</h2>
    <div class="flex gap-2">
        <button id="enablePush" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-bell mr-1"></i>Enable Push</button>
        <form method="POST" action="{{ route('bconnect.push.test') }}">@csrf<button class="bc-btn bc-btn-primary text-sm"><i class="fas fa-paper-plane mr-1"></i>Send Test</button></form>
    </div>
</div>
<div class="bc-card p-6">
    <div class="space-y-3">
        @forelse($notifications as $n)
        <div class="flex justify-between items-start gap-4 p-4 rounded-xl {{ $n->is_read ? 'bg-slate-800/30' : 'bg-slate-800 border border-cyan-500/20' }}">
            <div class="flex-1 min-w-0">
                <div class="font-bold text-sm text-white flex items-center gap-2">
                    {{ $n->title }}
                    @if(!$n->is_read)<span class="w-2 h-2 rounded-full bg-cyan-400"></span>@endif
                </div>
                <div class="text-slate-400 text-sm mt-1">{{ $n->message }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ $n->created_at->diffForHumans() }}</div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                @if($n->url)<a href="{{ $n->url }}" class="text-cyan-400 hover:underline text-xs">View</a>@endif
                @if(!$n->is_read)
                <form method="POST" action="{{ route('bconnect.notifications.read', $n->id) }}">@csrf<button class="bc-btn bc-btn-secondary text-xs py-1 px-2">Mark Read</button></form>
                @endif
            </div>
        </div>
        @empty
        <div class="bc-empty"><i class="fas fa-bell-slash"></i><div>No notifications yet.</div></div>
        @endforelse
    </div>
    @if($notifications->hasPages())
    <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
