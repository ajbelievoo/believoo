@extends('bconnect.layout')
@section('title', 'Remote Session')
@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-lg"><i class="fas fa-desktop mr-2 text-green-400"></i>Remote Session</h3>
            <p class="text-xs text-slate-400">Code: <code>{{ $session->session_code }}</code> • Permission: {{ ucfirst($session->permission) }}</p>
        </div>
        <div class="flex gap-2">
            @if($session->target_id == request()->input('bconnect_member')->id)
            <button id="shareScreenBtn" class="px-4 py-2 bg-green-500/20 text-green-400 rounded-lg font-bold"><i class="fas fa-share-square mr-1"></i>Share Screen</button>
            @else
            <span class="text-xs text-slate-400 px-3 py-2 bg-slate-800 rounded">Waiting for host screen...</span>
            @endif
            <a href="{{ route('bconnect.remote') }}" class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg font-bold">End</a>
        </div>
    </div>
    <div id="remote-grid" class="flex-1 bg-slate-900 rounded-2xl border border-slate-800 relative overflow-hidden">
        <div id="screen-host" class="w-full h-full flex items-center justify-center text-slate-500">Host screen will appear here</div>
    </div>
    <p class="text-xs text-slate-500 mt-2">WebRTC remote view. Full OS control requires host desktop agent.</p>
</div>
<script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.22.0.js"></script>
<script>
const sessionCode = @json($session->session_code);
const isHost = {{ $session->target_id == request()->input('bconnect_member')->id ? 'true' : 'false' }};
const channel = 'bc-remote-' + sessionCode;
const uid = Math.floor(Math.random() * 1000000);
let client = null, screenTrack = null;

async function getToken() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const r = await fetch('/agora/token', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({_token: csrf, channel, uid})
    });
    return await r.json();
}

async function initRemote() {
    const data = await getToken();
    if (data.error) { alert(data.error); return; }
    client = AgoraRTC.createClient({mode:'rtc', codec:'vp8'});
    client.on('user-published', async (user, mediaType) => {
        await client.subscribe(user, mediaType);
        if (mediaType === 'video') {
            const div = document.getElementById('screen-host');
            div.innerHTML = ''; div.className = 'w-full h-full';
            user.videoTrack.play('screen-host');
        }
        if (mediaType === 'audio') user.audioTrack?.play();
    });
    await client.join(data.app_id, channel, data.token, uid);

    if (isHost) {
        document.getElementById('shareScreenBtn').addEventListener('click', async () => {
            try {
                screenTrack = await AgoraRTC.createScreenVideoTrack();
                screenTrack.play('screen-host');
                await client.unpublish();
                await client.publish([screenTrack]);
                document.getElementById('shareScreenBtn').innerHTML = '<i class="fas fa-stop mr-1"></i>Stop Sharing';
            } catch (e) { alert('Screen share failed: ' + e.message); }
        });
    }
}
initRemote();
</script>
@endsection
