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
            <p id="debugLine" class="text-[10px] text-slate-600 font-mono truncate"></p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <span id="inputBadge" class="bc-badge bc-badge-amber hidden"><i class="fas fa-keyboard mr-1"></i>Control enabled</span>
            @if(!$canControl)<span class="bc-badge bc-badge-cyan" title="Upgrade to Pro/Enterprise for mouse & keyboard control"><i class="fas fa-eye mr-1"></i>View only · upgrade for control</span>@endif
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
            <i class="fas fa-mouse-pointer mr-1"></i><span id="clickHintText">Click the screen once to enable keyboard &amp; mouse capture. Press <kbd class="px-1.5 py-0.5 bg-slate-700 rounded">Esc</kbd> to release.</span>
        </div>
    </div>
</div>

<script src="/js/pusher.min.js"></script>
<script>
const CODE = @json($session->session_code);
const VIEWER = @json($viewerName);
const CAN_CONTROL = @json($canControl);
const END_URL = @json(route('bconnect.remote.code.end', $session->session_code));
const channelName = 'remote-agent.' + CODE;

let pc = null, dc = null, channel = null, connected = false, streamTimeout = null;
const video = document.getElementById('remoteVideo');
const statusEl = document.getElementById('connStatus');
const iceQueue = [];

function setStatus(text, cls = 'text-amber-400') { statusEl.textContent = text; statusEl.className = cls; }
function dbg(step) {
    const el = document.getElementById('debugLine');
    el.textContent = (el.textContent + ' › ' + step).slice(-140);
    console.log('[bmydesk]', step);
}

const pcConfig = { iceServers: @json($iceServers ?? [['urls' => 'stun:stun.l.google.com:19302']]) };

function whisper(evt, data) {
    try { channel.trigger('client-' + evt, data); } catch (e) { console.warn('send failed', e); }
}

async function startPeer() {
    pc = new RTCPeerConnection(pcConfig);

    pc.ontrack = (e) => {
        dbg('video track arrived');
        video.srcObject = e.streams[0];
        document.getElementById('waitOverlay').classList.add('hidden');
        if (CAN_CONTROL) document.getElementById('clickHint').classList.remove('hidden');
        setStatus('Connected', 'text-green-400');
        connected = true;
        if (streamTimeout) clearTimeout(streamTimeout);
        startPing();
    };

    pc.onicecandidate = (e) => {
        if (e.candidate) whisper('signal', { kind: 'ice', candidate: e.candidate });
    };

    pc.onconnectionstatechange = () => {
        dbg('peer ' + pc.connectionState);
        if (pc.connectionState === 'connected') setStatus('Connected', 'text-green-400');
        if (pc.connectionState === 'failed' && pc) {
            setStatus('Reconnecting…', 'text-amber-400');
            try { pc.restartIce(); } catch (e) {}
        }
        if (['disconnected', 'closed'].includes(pc.connectionState)) {
            setStatus('Connection lost', 'text-red-400');
        }
    };

    dc = pc.createDataChannel('input', { ordered: false, maxRetransmits: 0 });
    dc.onopen = () => { if (CAN_CONTROL) document.getElementById('inputBadge').classList.remove('hidden'); };
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
    dbg('offer sent');
    setStatus('Connecting…', 'text-amber-400');
    if (streamTimeout) clearTimeout(streamTimeout);
    streamTimeout = setTimeout(() => {
        if (!connected) {
            setStatus('Host did not respond', 'text-red-400');
            document.getElementById('waitTitle').textContent = 'No response from host';
            document.getElementById('waitSub').textContent = 'The host accepted but the screen stream never arrived. It may have closed — ask them to reopen the agent and try again.';
            document.getElementById('waitOverlay').classList.remove('hidden');
        }
    }, 25000);
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
            dbg('answer received');
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

// ── Input capture → DataChannel (Pro/Enterprise only) ─────────
function sendInput(obj) {
    if (CAN_CONTROL && dc && dc.readyState === 'open') {
        try { dc.send(JSON.stringify(obj)); } catch (e) {}
    }
}

let lastMove = 0;
if (CAN_CONTROL) {
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
    video.addEventListener('keydown', (e) => { e.preventDefault(); sendInput({ t: 'key', k: e.key, code: e.code, down: true, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });
    video.addEventListener('keyup', (e) => { e.preventDefault(); sendInput({ t: 'key', k: e.key, code: e.code, down: false, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });

    // Touch → mouse mapping (mobile viewers)
    let touchMoved = false;
    const pos = (t) => { const r = video.getBoundingClientRect(); return { x: (t.clientX - r.left) / r.width, y: (t.clientY - r.top) / r.height }; };
    video.addEventListener('touchstart', (e) => { touchMoved = false; }, { passive: true });
    video.addEventListener('touchmove', (e) => {
        touchMoved = true;
        const p = pos(e.touches[0]);
        const now = performance.now();
        if (now - lastMove < 33) return; lastMove = now;
        sendInput({ t: 'move', x: p.x, y: p.y });
    }, { passive: true });
    video.addEventListener('touchend', (e) => {
        if (!touchMoved && e.changedTouches.length) { // tap = left click
            const p = pos(e.changedTouches[0]);
            sendInput({ t: 'move', x: p.x, y: p.y });
            sendInput({ t: 'down', b: 0 });
            setTimeout(() => sendInput({ t: 'up', b: 0 }), 60);
        }
    }, { passive: true });
    video.addEventListener('touchstart', (e) => e.preventDefault(), { passive: false });
} else {
    video.addEventListener('mousemove', () => { video.style.cursor = 'default'; });
}
video.addEventListener('contextmenu', (e) => e.preventDefault());
video.addEventListener('click', () => { video.focus(); document.getElementById('clickHint').classList.add('hidden'); });
video.tabIndex = 0;

// ── Signaling channel — dedicated raw Pusher connection ─────
// (bypasses the global Echo manager: its 10s polling fallback silently
//  swallows whispers, which broke join-request delivery)
function initChannel() {
    if (typeof Pusher === 'undefined') {
        setStatus('Realtime lib missing — reload', 'text-red-400');
        document.getElementById('waitSub').textContent = 'Refresh the page and try again.';
        return;
    }
    const p = new Pusher(@json(config('broadcasting.connections.reverb.key')), {
        cluster: 'mt1',
        wsHost: 'believoo.com',
        wssPort: 443,
        forceTLS: true,
        enabledTransports: ['wss'],
        authEndpoint: '/broadcasting/auth',
        auth: { headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }},
    });
    p.connection.bind('state_change', s => dbg('ws:' + s.current));
    p.connection.bind('error', () => dbg('ws error'));
    channel = p.subscribe('private-' + channelName);

    const subTimeout = setTimeout(() => {
        if (!channel.subscribed) {
            setStatus('Signaling timeout — reload and retry', 'text-red-400');
            dbg('subscribe timeout, ws:' + p.connection.state);
        }
    }, 20000);

    channel.bind('pusher:subscription_succeeded', () => {
        clearTimeout(subTimeout);
        setStatus('Requesting access…', 'text-amber-400');
        dbg('channel subscribed');
        whisper('join-request', { name: VIEWER });
        dbg('join-request sent');
    });
    channel.bind('pusher:subscription_error', () => {
        clearTimeout(subTimeout);
        setStatus('Channel auth failed — reload', 'text-red-400');
        dbg('subscription_error');
    });

    channel.bind('client-join-accept', () => {
        setStatus('Accepted — starting stream…', 'text-green-400');
        dbg('host accepted');
        startPeer();
    });
    channel.bind('client-join-reject', (m) => {
        setStatus('Host rejected the request', 'text-red-400');
        document.getElementById('waitTitle').textContent = 'Connection declined';
        document.getElementById('waitSub').textContent = (m && m.reason === 'device')
            ? 'The host device cannot share its screen from a browser. Ask them to use the BMyDesk Agent app.'
            : 'The host declined your request.';
    });
    channel.bind('client-signal', (m) => onSignal(m));
    channel.bind('client-end', () => {
        setStatus('Session ended by host', 'text-red-400');
        cleanup();
        document.getElementById('waitOverlay').classList.remove('hidden');
        document.getElementById('waitTitle').textContent = 'Session ended';
        document.getElementById('waitSub').textContent = 'The host ended this remote session.';
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

initChannel();
</script>
@endsection
