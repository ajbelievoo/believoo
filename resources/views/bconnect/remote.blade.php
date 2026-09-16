@extends('bconnect.layout')
@section('title', 'Remote Desktop')
@section('content')
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4"><i class="fas fa-desktop mr-2 text-green-400"></i>Request Control</h3>
        <form method="POST" action="{{ route('bconnect.remote.request') }}" class="space-y-4">@csrf
            <select name="target_id" required class="bc-input"><option value="">Select user</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->user->name }} ({{ ucfirst(str_replace('_',' ',$m->role)) }})</option>@endforeach</select>
            <select name="permission" class="bc-input">
                <option value="view">View Only</option>
                <option value="control">Request Full Control (requires host approval + agent)</option>
                <option value="clipboard">Clipboard Access</option>
            </select>
            <p class="text-xs text-slate-500">Full OS mouse/keyboard control needs the host to approve and run the desktop agent. Browser-based view works without agent.</p>
            <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-share-square mr-1"></i>Send Request</button>
        </form>
    </div>
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4"><i class="fas fa-bell mr-2 text-amber-400"></i>Incoming Requests</h3>
        <div class="space-y-3">
            @forelse($sessions->where('status', 'pending') as $s)
            <div class="bg-slate-900 rounded-lg p-4 flex justify-between items-center border border-slate-800">
                <div>
                    <div class="font-bold">{{ $s->requester->user->name }}</div>
                    <div class="text-xs text-slate-400">Code: <code>{{ $s->session_code }}</code> • {{ ucfirst($s->permission) }}</div>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="active"><button class="bc-btn bc-btn-primary text-xs py-1 px-2">Accept</button></form>
                    <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="rejected"><button class="bc-btn bc-btn-danger text-xs py-1 px-2">Reject</button></form>
                </div>
            </div>
            @empty<p class="text-slate-500 text-sm">No pending requests.</p>@endforelse
        </div>
    </div>
</div>

<div class="bc-card p-6">
    <h3 class="font-bold mb-4">Remote Session Log</h3>
    <div class="overflow-x-auto">
        <table class="bc-table">
            <thead><tr><th>Code</th><th>Requester</th><th>Target</th><th>Permission</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($sessions as $s)
                <tr>
                    <td class="font-mono">{{ $s->session_code }}</td>
                    <td>{{ $s->requester->user->name }}</td>
                    <td>{{ $s->target->user->name }}</td>
                    <td>{{ ucfirst($s->permission) }}</td>
                    <td><span class="bc-badge {{ $s->status == 'active' ? 'bc-badge-green' : ($s->status == 'rejected' ? 'bc-badge-red' : 'bc-badge-amber') }}">{{ ucfirst($s->status) }}</span></td>
                    <td>
                        @if($s->status == 'active')
                        <a href="{{ route('bconnect.remote.room', $s->id) }}" class="bc-btn bc-btn-primary text-xs py-1 px-2">Join</a>
                        <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="ended"><button class="bc-btn bc-btn-danger text-xs py-1 px-2">End</button></form>
                        @elseif($s->status == 'pending' && $s->target_id == request()->input('bconnect_member')->id)
                        <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="active"><button class="bc-btn bc-btn-primary text-xs py-1 px-2">Accept</button></form>
                        @else
                        <span class="text-slate-500 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $sessions->links() }}
</div>
@endsection
