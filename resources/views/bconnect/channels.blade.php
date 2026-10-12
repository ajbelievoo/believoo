@extends('bconnect.layout')
@section('title', 'Channels')
@section('content')
<div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <div class="bc-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-lg"><i class="fas fa-hashtag mr-2 text-cyan-400"></i>Channels</h3>
            </div>
            <p class="text-slate-400 text-sm mb-5">Team-wide rooms and private groups — chat with mentions, replies, voice notes & files.</p>
            @if($channels->count())
                <div class="divide-y divide-slate-800">
                    @foreach($channels as $c)
                    <a href="{{ route('bconnect.chat.channel', $c->id) }}" class="flex items-center justify-between py-3 group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $c->is_private ? 'bg-amber-500/15 text-amber-400' : 'bg-cyan-500/15 text-cyan-400' }} flex items-center justify-center">
                                <i class="fas {{ $c->is_private ? 'fa-lock' : 'fa-hashtag' }}"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-white group-hover:text-cyan-300 transition">{{ $c->name }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $c->is_private ? 'Private · ' . count($c->member_ids ?: []) . ' member(s)' : 'Everyone' }}
                                    · by {{ $c->creator?->user?->name ?? '—' }} · {{ $c->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-slate-600 group-hover:text-cyan-400 transition"></i>
                    </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-slate-500">
                    <i class="fas fa-hashtag text-3xl mb-3 opacity-50"></i>
                    <p>No channels yet — create one to get your team talking.</p>
                </div>
            @endif
        </div>
    </div>
    <div>
        <div class="bc-card p-6">
            <h4 class="font-bold mb-4">New channel</h4>
            <form method="POST" action="{{ route('bconnect.chat.channels.store') }}" class="space-y-4">@csrf
                <input name="name" required maxlength="80" placeholder="e.g. design-team, general" class="bc-input">
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="is_private" value="1" id="isPrivate" class="accent-cyan-500"> Private group (invite only)
                </label>
                <div id="memberPicker" class="hidden max-h-44 overflow-y-auto border border-slate-700 rounded-lg p-2 space-y-1">
                    @foreach($members as $m)
                    <label class="flex items-center gap-2 text-xs text-slate-300 px-1 py-0.5 rounded hover:bg-slate-800">
                        <input type="checkbox" name="member_ids[]" value="{{ $m->id }}" class="accent-cyan-500"> {{ $m->user->name }}
                    </label>
                    @endforeach
                </div>
                <button class="bc-btn bc-btn-primary w-full">Create channel</button>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('isPrivate').addEventListener('change', e => {
    document.getElementById('memberPicker').classList.toggle('hidden', !e.target.checked);
});
</script>
@endsection
