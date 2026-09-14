@extends('bconnect.layout')
@section('title', 'Notifications')
@section('content')
<div class="flex justify-between items-center mb-4">
    <button id="enablePush" class="px-4 py-2 bg-cyan-500/20 text-cyan-400 rounded-lg font-bold text-sm"><i class="fas fa-bell mr-1"></i>Enable Push</button>
    <form method="POST" action="{{ route('bconnect.push.test') }}">@csrf<button class="px-4 py-2 bg-pink-500/20 text-pink-400 rounded-lg font-bold text-sm"><i class="fas fa-paper-plane mr-1"></i>Send Test</button></form>
</div>
<div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
    <h3 class="font-bold mb-4">Notifications</h3>
    <div class="space-y-3">
        @forelse($notifications as $n)
        <div class="flex justify-between items-center p-4 rounded-lg {{ $n->is_read ? 'bg-slate-800/30' : 'bg-slate-800' }}">
            <div>
                <div class="font-bold text-sm">{{ $n->title }}</div>
                <div class="text-slate-400 text-sm">{{ $n->message }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ $n->created_at->diffForHumans() }}</div>
            </div>
            @if(!$n->is_read)
            <form method="POST" action="{{ route('bconnect.notifications.read', $n->id) }}">@csrf<button class="px-3 py-1 bg-cyan-500/20 text-cyan-400 rounded text-xs">Mark Read</button></form>
            @endif
        </div>
        @empty<p class="text-slate-500">No notifications.</p>@endforelse
    </div>
    {{ $notifications->links() }}
</div>
@endsection
