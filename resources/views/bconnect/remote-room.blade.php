@extends('bconnect.layout')
@section('title', 'Remote Session')
@section('content')
@php
$isHost = $session->target_id == request()->input('bconnect_member')->id;
$isViewer = !$isHost;
@endphp
<div class="h-[calc(100vh-140px)] flex flex-col">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
        <div class="min-w-0">
            <h3 class="font-bold text-lg truncate"><i class="fas fa-desktop mr-2 text-green-400"></i>Remote Session</h3>
            <p class="text-xs text-slate-400">
                Code: <code>{{ $session->session_code }}</code> • Permission: <span class="font-bold text-cyan-400">{{ ucfirst($session->permission) }}</span>
                • <span id="connStatus" class="text-amber-400">Connecting...</span>
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if($isHost)
            <button id="shareScreenBtn" class="bc-btn bc-btn-primary text-sm"><i class="fas fa-share-square mr-1"></i>Share Screen</button>
            <button id="stopShareBtn" class="bc-btn bc-btn-danger text-sm hidden"><i class="fas fa-stop mr-1"></i>Stop Sharing</button>
            @else
            <span id="waitingBadge" class="bc-badge bc-badge-amber">Waiting for host screen...</span>
            @if($session->permission === 'control')
            <button id="requestControlBtn" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-mouse-pointer mr-1"></i>Request Control</button>
            @endif
            @endif
            <button id="endSessionBtn" class="bc-btn bc-btn-danger text-sm"><i class="fas fa-phone-slash mr-1"></i>End Session</button>
            <button id="fullscreenBtn" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-expand mr-1"></i>Full Screen</button>
        </div>
    </div>

    <div id="remoteContainer" class="flex-1 bg-slate-900 rounded-2xl border border-slate-800 relative overflow-hidden flex items-center justify-center">
        <div id="screen-host" class="w-full h-full flex items-center justify-center text-slate-500">
            <div class="text-center">
                <i class="fas fa-desktop text-4xl mb-3 text-slate-700"></i>
                <p>Host screen will appear here once sharing starts.</p>
                @if($isHost)
                <p class="text-xs text-slate-600 mt-1">Click "Share Screen" to begin.</p>
                @endif
            </div>
        </div>
    </div>

    <div id="controlNotice" class="hidden mt-3 p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl text-amber-300 text-xs">
        <i class="fas fa-info-circle mr-1"></i> Browser-based remote desktop is <strong>view-only</strong>. Full OS mouse/keyboard control requires the host to install and run the B-CONNECT desktop agent and explicitly grant control.
    </div>
</div>

<script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.22.0.js"></script>
<script>
const sessionCode = @json($session->session_code);
const isHost = {{ $isHost ? 'true' : 'false' }};
const canControl = {{ $session->permission === 'control' ? 'true' : 'false' }};
const channel = 'bc-remote-' + sessionCode;
const uid = Math.floor(Math.random() * 1000000);
let client = null, screenTrack = null, remoteContainer = document.getElementById('remoteContainer');

function setStatus(text, color='text-slate-400') {
    const el = document.getElementById('connStatus');
    el.textContent = text;
    el.className = color;
}

async function getToken() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const r = await fetch('/agora/token', {
        method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({_token: csrf, channel, uid})
    });
    return await r.json();
}

async function endSession() {
    if (!confirm('End this remote session?')) return;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
        await fetch(`/remote/{{ $session->id }}/end`, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({_token: csrf})
        });
    } catch (e) {}
    window.location.href = '{{ route('bconnect.remote') }}';
}

async function initRemote() {
    try {
        const data = await getToken();
        if (data.error) { setStatus('Token error: ' + data.error, 'text-red-400'); return; }

        client = AgoraRTC.createClient({mode: 'rtc', codec: 'vp8'});
        client.on('connection-state-change', (cur, prev, reason) => {
            if (cur === 'CONNECTED') setStatus('Connected', 'text-green-400');
            if (cur === 'DISCONNECTED') setStatus('Disconnected', 'text-red-400');
            if (cur === 'CONNECTING') setStatus('Connecting...', 'text-amber-400');
        });

        client.on('user-published', async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === 'video') {
                const div = document.getElementById('screen-host');
                div.innerHTML = ''; div.className = 'w-full h-full bg-black';
                user.videoTrack.play('screen-host');
                document.getElementById('waitingBadge')?.classList.add('hidden');
                setStatus('Receiving screen', 'text-green-400');
            }
            if (mediaType === 'audio') user.audioTrack?.play();
        });

        client.on('user-unpublished', () => {
            setStatus('Host stopped sharing', 'text-amber-400');
        });

        await client.join(data.app_id, channel, data.token, uid);
        setStatus('Connected', 'text-green-400');

        if (isHost) {
            document.getElementById('shareScreenBtn').addEventListener('click', async () => {
                try {
                    screenTrack = await AgoraRTC.createScreenVideoTrack({encoderConfig: '1080p'});
                    screenTrack.play('screen-host');
                    await client.unpublish();
                    await client.publish([screenTrack]);
                    document.getElementById('shareScreenBtn').classList.add('hidden');
                    document.getElementById('stopShareBtn').classList.remove('hidden');
                    setStatus('Sharing screen', 'text-cyan-400');

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    await fetch(`/remote/{{ $session->id }}/start`, {
                        method: 'POST', credentials: 'same-origin',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                        body: JSON.stringify({_token: csrf})
                    });
                } catch (e) { alert('Screen share cancelled or not supported: ' + e.message); }
            });

            document.getElementById('stopShareBtn').addEventListener('click', () => {
                if (screenTrack) { screenTrack.stop(); screenTrack.close(); screenTrack = null; }
                client.unpublish();
                document.getElementById('shareScreenBtn').classList.remove('hidden');
                document.getElementById('stopShareBtn').classList.add('hidden');
                setStatus('Connected (not sharing)', 'text-slate-400');
            });
        }
    } catch (e) {
        console.error(e);
        setStatus('Connection failed', 'text-red-400');
    }
}

@if(!$isHost && $session->permission === 'control')
document.getElementById('requestControlBtn')?.addEventListener('click', () => {
    document.getElementById('controlNotice').classList.remove('hidden');
});
@endif

document.getElementById('endSessionBtn').addEventListener('click', endSession);

document.getElementById('fullscreenBtn').addEventListener('click', () => {
    if (remoteContainer.requestFullscreen) remoteContainer.requestFullscreen();
    else if (remoteContainer.webkitRequestFullscreen) remoteContainer.webkitRequestFullscreen();
});

window.addEventListener('beforeunload', () => {
    if (client) client.leave();
    if (screenTrack) { screenTrack.stop(); screenTrack.close(); }
});

initRemote();
</script>
@endsection
