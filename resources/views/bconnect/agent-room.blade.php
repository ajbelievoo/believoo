@extends('bconnect.layout')
@section('title', 'Remote Session ' . $session->session_code)
@section('content')
<div class="h-[calc(100vh-120px)] flex flex-col">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-3">
        <div class="min-w-0">
            <h3 class="font-bold text-lg truncate"><i class="fas fa-desktop mr-2" style="color:var(--bc-cyan)"></i>{{ $session->host_label ?? 'Remote Device' }}</h3>
            <p class="text-xs text-slate-400">
                Code: <code class="text-slate-300">{{ $session->session_code }}</code>
                • <span id="connStatus" class="text-amber-400">Waiting for host approval…</span>
                <span id="latencyBadge" class="hidden text-slate-500">• <span id="latencyVal">—</span> ms</span>
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <span id="inputBadge" class="bc-badge bc-badge-amber hidden"><i class="fas fa-keyboard mr-1"></i>Control enabled</span>
            <button id="fullscreenBtn" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-expand mr-1"></i>Fullscreen</button>
            <button id="endSessionBtn" class="bc-btn bc-btn-danger text-sm"><i class="fas fa-phone-slash mr-1"></i>End</button>
        </div>
    </div>

    <div id="remoteContainer" class="flex-1 bg-black rounded-2xl border border-[var(--bc-border)] relative overflow-hidden flex items-center justify-center select-none">
        <video id="remoteVideo" autoplay playsinline class="max-w-full max-h-full w-full h-full object-contain" style="cursor:crosshair;"></video>
        <div id="waitOverlay" class="absolute inset-0 flex items-center justify-center bg-[var(--bc-bg)]/90 text-center z-10">
            <div>
                <div class="w-14 h-14 mx-auto mb-4 rounded-full border-4 border-[var(--bc-border)] animate-spin" style="border-top-color:var(--bc-cyan);"></div>
                <p class="font-bold mb-1" id="waitTitle">Waiting for host approval…</p>
                <p class="text-xs text-slate-500" id="waitSub">The host will see your request and accept it.</p>
            </div>
        </div>
        <div id="clickHint" class="absolute bottom-3 left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-black/70 text-xs text-slate-300 hidden z-20">
            <i class="fas fa-mouse-pointer mr-1"></i>Click the screen once to enable keyboard & mouse capture. Press <kbd class="px-1.5 py-0.5 bg-slate-700 rounded">Esc</kbd> to release.
        </div>
    </div>
</div>

<script>
const CODE = @json($session->session_code);
const VIEWER = @json($viewerName);
const END_URL = @json(route('bconnect.remote.code.end', $session->session_code));
const channelName = 'remote-agent.' + CODE;

let pc = null, dc = null, channel = null, connected = false;
const video = document.getElementById('remoteVideo');
const statusEl = document.getElementById('connStatus');
const iceQueue = [];

function setStatus(text, cls = 'text-amber-400') { statusEl.textContent = text; statusEl.className = cls; }

const pcConfig = { iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
]};

function whisper(evt, data) {
    try { channel.whisper(evt, data); } catch (e) { console.warn('whisper failed', e); }
}

async function startPeer() {
    pc = new RTCPeerConnection(pcConfig);

    pc.ontrack = (e) => {
        video.srcObject = e.streams[0];
        document.getElementById('waitOverlay').classList.add('hidden');
        document.getElementById('clickHint').classList.remove('hidden');
        setStatus('Connected', 'text-green-400');
        connected = true;
        startPing();
    };

    pc.onicecandidate = (e) => {
        if (e.candidate) whisper('signal', { kind: 'ice', candidate: e.candidate });
    };

    pc.onconnectionstatechange = () => {
        if (pc.connectionState === 'connected') setStatus('Connected', 'text-green-400');
        if (['failed', 'disconnected', 'closed'].includes(pc.connectionState)) {
            setStatus('Connection lost', 'text-red-400');
        }
    };

    dc = pc.createDataChannel('input', { ordered: false, maxRetransmits: 0 });
    dc.onopen = () => document.getElementById('inputBadge').classList.remove('hidden');
    dc.onclose = () => document.getElementById('inputBadge').classList.add('hidden');
    dc.onmessage = (e) => {
        try {
            const m = JSON.parse(e.data);
            if (m.t === 'pong') document.getElementById('latencyVal').textContent = Math.round(performance.now() - m.ts);
        } catch (err) {}
    };

    const offer = await pc.createOffer({ offerToReceiveVideo: true, offerToReceiveAudio: true });
    await pc.setLocalDescription(offer);
    whisper('signal', { kind: 'offer', sdp: pc.localDescription.sdp });
    setStatus('Connecting…', 'text-amber-400');
}

function startPing() {
    document.getElementById('latencyBadge').classList.remove('hidden');
    setInterval(() => {
        if (dc && dc.readyState === 'open') dc.send(JSON.stringify({ t: 'ping', ts: performance.now() }));
    }, 3000);
}

async function onSignal(m) {
    if (!pc) return;
    try {
        if (m.kind === 'answer') {
            await pc.setRemoteDescription({ type: 'answer', sdp: m.sdp });
            iceQueue.forEach(c => pc.addIceCandidate(c).catch(() => {}));
            iceQueue.length = 0;
        } else if (m.kind === 'ice' && m.candidate) {
            if (pc.remoteDescription) {
                await pc.addIceCandidate(m.candidate).catch(() => {});
            } else {
                iceQueue.push(m.candidate);
            }
        }
    } catch (e) { console.error('signal error', e); }
}

// ── Input capture → DataChannel ──────────────────────────────
function sendInput(obj) {
    if (dc && dc.readyState === 'open') {
        try { dc.send(JSON.stringify(obj)); } catch (e) {}
    }
}

let lastMove = 0;
video.addEventListener('mousemove', (e) => {
    const now = performance.now();
    if (now - lastMove < 33) return; // ~30fps
    lastMove = now;
    const r = video.getBoundingClientRect();
    sendInput({ t: 'move', x: (e.clientX - r.left) / r.width, y: (e.clientY - r.top) / r.height });
});
video.addEventListener('mousedown', (e) => { e.preventDefault(); sendInput({ t: 'down', b: e.button }); });
video.addEventListener('mouseup', (e) => sendInput({ t: 'up', b: e.button }));
video.addEventListener('wheel', (e) => { e.preventDefault(); sendInput({ t: 'wheel', dx: e.deltaX, dy: e.deltaY }); }, { passive: false });
video.addEventListener('contextmenu', (e) => e.preventDefault());
video.addEventListener('click', () => { video.focus(); document.getElementById('clickHint').classList.add('hidden'); });
video.tabIndex = 0;
video.addEventListener('keydown', (e) => { e.preventDefault(); sendInput({ t: 'key', k: e.key, code: e.code, down: true, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });
video.addEventListener('keyup', (e) => { e.preventDefault(); sendInput({ t: 'key', k: e.key, code: e.code, down: false, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });

// ── Signaling channel ────────────────────────────────────────
function initChannel() {
    if (!window.Echo || !window.Echo.connector || window.Echo.usePolling) {
        setStatus('Realtime unavailable — Reverb not connected', 'text-red-400');
        document.getElementById('waitSub').textContent = 'Refresh the page and try again.';
        return;
    }
    channel = window.Echo.private(channelName);

    channel.subscribed(() => {
        setStatus('Requesting access…', 'text-amber-400');
        whisper('join-request', { name: VIEWER });
    });

    channel.listenForWhisper('join-accept', () => {
        setStatus('Accepted — starting stream…', 'text-green-400');
        startPeer();
    });
    channel.listenForWhisper('join-reject', () => {
        setStatus('Host rejected the request', 'text-red-400');
        document.getElementById('waitTitle').textContent = 'Connection declined';
        document.getElementById('waitSub').textContent = 'The host declined your request.';
    });
    channel.listenForWhisper('signal', (m) => onSignal(m));
    channel.listenForWhisper('end', () => {
        setStatus('Session ended by host', 'text-red-400');
        cleanup();
        document.getElementById('waitOverlay').classList.remove('hidden');
        document.getElementById('waitTitle').textContent = 'Session ended';
        document.getElementById('waitSub').textContent = 'The host ended this remote session.';
    });
    channel.error((e) => {
        console.error('channel error', e);
        setStatus('Channel auth failed', 'text-red-400');
    });
}

function cleanup() {
    try { if (pc) pc.close(); } catch (e) {}
    pc = null; connected = false;
}

async function endSession() {
    if (!confirm('End this remote session?')) return;
    whisper('end', {});
    try {
        await fetch(END_URL, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        });
    } catch (e) {}
    window.location.href = '{{ route('bconnect.remote.connect') }}';
}

document.getElementById('endSessionBtn').addEventListener('click', endSession);
document.getElementById('fullscreenBtn').addEventListener('click', () => {
    const c = document.getElementById('remoteContainer');
    (c.requestFullscreen || c.webkitRequestFullscreen).call(c);
});

window.addEventListener('beforeunload', () => { whisper('end', {}); cleanup(); });

// Echo loads via @vite app.js — wait for it
let tries = 0;
const waitEcho = setInterval(() => {
    if (window.Echo) { clearInterval(waitEcho); initChannel(); }
    else if (++tries > 40) { clearInterval(waitEcho); setStatus('Realtime init failed — reload', 'text-red-400'); }
}, 250);
</script>
@endsection
