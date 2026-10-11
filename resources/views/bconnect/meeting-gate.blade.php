@extends('bconnect.layout')
@section('title', $meeting->title)
@section('content')
<div class="max-w-md mx-auto mt-16 bc-card p-8 text-center">
    @if($kind === 'password')
        <i class="fas fa-lock text-3xl text-cyan-400 mb-4"></i>
        <h3 class="font-bold text-xl mb-2">This meeting is protected</h3>
        <p class="text-slate-400 text-sm mb-6">Enter the meeting password to join <b>{{ $meeting->title }}</b>.</p>
        <div class="flex gap-2">
            <input id="mpw" type="password" placeholder="Meeting password" class="bc-input flex-1" autofocus>
            <button onclick="checkPw()" class="bc-btn bc-btn-primary">Join</button>
        </div>
        <p id="pwErr" class="text-rose-400 text-xs mt-3 hidden">Wrong password</p>
        <script>
        async function checkPw(){
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch('{{ route("bconnect.meeting.password", $room) }}', {method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({password: document.getElementById('mpw').value})});
            const d = await r.json();
            if (d.ok) location.reload(); else document.getElementById('pwErr').classList.remove('hidden');
        }
        document.getElementById('mpw').addEventListener('keydown', e => { if(e.key==='Enter') checkPw(); });
        </script>
    @elseif($kind === 'waiting')
        <i class="fas fa-hourglass-half text-3xl text-amber-400 mb-4"></i>
        <h3 class="font-bold text-xl mb-2">Waiting for the host</h3>
        <p class="text-slate-400 text-sm mb-4">You're in the lobby for <b>{{ $meeting->title }}</b>. The host will let you in.</p>
        <div class="bc-skel-line bc-skel w-3/4 mx-auto"></div>
        <p class="text-xs text-slate-500 mt-4">This page refreshes automatically when you're admitted.</p>
        <script>
        setInterval(async () => {
            const r = await fetch('{{ route("bconnect.meeting.wait", $room) }}', {headers:{'Accept':'application/json'}});
            const d = await r.json();
            if (d.status === 'joined') location.reload();
            if (d.status === 'denied') location.reload();
        }, 4000);
        </script>
    @else
        <i class="fas fa-ban text-3xl text-rose-400 mb-4"></i>
        <h3 class="font-bold text-xl mb-2">Not admitted</h3>
        <p class="text-slate-400 text-sm">The host declined your request to join this meeting.</p>
        <a href="{{ route('bconnect.meetings') }}" class="bc-btn bc-btn-secondary mt-6 inline-block">Back to meetings</a>
    @endif
</div>
@endsection
