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
            <button id="fileBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Send file"><i class="fas fa-paperclip mr-1"></i>File</button>
            <button id="chatBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Chat"><i class="fas fa-comment mr-1"></i>Chat</button>
            <button id="fullscreenBtn" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-expand mr-1"></i>Fullscreen</button>
            <input type="file" id="fileInput" class="hidden">
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
        <div id="chatPanel" class="absolute right-0 top-0 bottom-0 w-72 bg-[var(--bc-card,#0f172a)]/95 border-l border-[var(--bc-border)] z-20 hidden flex-col">
            <div id="chatMsgs" class="flex-1 overflow-y-auto p-3 text-sm"></div>
            <div class="flex gap-2 p-2 border-t border-[var(--bc-border)]">
                <input id="chatInput" placeholder="Message…" class="flex-1 bg-slate-800 border border-[var(--bc-border)] text-slate-200 px-3 py-2 rounded-lg text-sm outline-none">
                <button id="chatSend" class="bc-btn bc-btn-primary text-sm">Send</button>
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

const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const API = '/remote/code/' + CODE;

// ws whisper for speed + HTTP relay (server broadcasts AND queues for a
// dead-ws peer). Shared nonce dedupes the double delivery.
const seenSig = new Set();
function sigNew(m) {
    const n = m && m.n;
    if (!n) return true;
    if (seenSig.has(n)) return false;
    seenSig.add(n); if (seenSig.size > 600) seenSig.delete(seenSig.values().next().value);
    return true;
}
const sigFresh = (m) => !m.at || (Date.now() - Date.parse(m.at)) < 45000; // drop stale queued signals
const nonce = () => Math.random().toString(36).slice(2) + Date.now().toString(36);
function postJson(url, body) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: {
        'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body || {}) }).catch(() => {});
}
function whisper(evt, data) {
    try { channel.trigger('client-' + evt, data); } catch (e) { console.warn('send failed', e); }
}
function sendSignal(msg) { msg.n = nonce(); whisper('signal', msg); postJson(API + '/signal', msg); }

function onAccept() {
    setStatus('Accepted — starting stream…', 'text-green-400');
    dbg('host accepted');
    startPeer();
}
function onReject(reason) {
    setStatus('Host rejected the request', 'text-red-400');
    document.getElementById('waitTitle').textContent = 'Connection declined';
    document.getElementById('waitSub').textContent = (reason === 'device')
        ? 'The host device cannot share its screen from a browser. Ask them to use the BMyDesk Agent app.'
        : 'The host declined your request.';
}
function onEnded() {
    setStatus('Session ended by host', 'text-red-400');
    cleanup();
    document.getElementById('waitOverlay').classList.remove('hidden');
    document.getElementById('waitTitle').textContent = 'Session ended';
    document.getElementById('waitSub').textContent = 'The host ended this remote session.';
}
function dispatchViewerSignal(m) {
    if (m.kind === 'accept') onAccept();
    else if (m.kind === 'reject') onReject(m.reason);
    else if (m.kind === 'end') onEnded();
    else onSignal(m);
}

// HTTP fallback — drains queued signals (accept/answer/ice/end) and polls
// status, so a dead ws still completes the whole session.
function startViewerPoll() {
    setInterval(async () => {
        try {
            const r = await fetch(API + '/signals', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            (d.signals || []).forEach((m) => { if (sigNew(m) && sigFresh(m)) { dbg('poll sig: ' + (m.kind || '?')); dispatchViewerSignal(m); } });
            if (!pc) {
                const s = await fetch(API + '/session-status', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                const sd = await s.json();
                if (sd.ok && sd.status === 'rejected') onReject();
                else if (sd.ok && sd.status === 'ended') onEnded();
            }
        } catch (e) {}
    }, 2000);
}

async function startPeer() {
    if (pc) return; // accept may arrive via ws + queue both
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
        if (e.candidate) sendSignal({ kind: 'ice', candidate: e.candidate });
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
    dc2 = pc.createDataChannel('ctl'); // reliable — chat/clipboard/file
    dc2.onopen = () => { $('fileBtn').classList.remove('hidden'); $('chatBtn').classList.remove('hidden'); };
    dc2.onclose = () => { $('fileBtn').classList.add('hidden'); $('chatBtn').classList.add('hidden'); $('chatPanel').classList.add('hidden'); };
    dc2.onmessage = onCtlMessage;
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
    sendSignal({ kind: 'offer', sdp: pc.localDescription.sdp });
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

// ══ ctl channel — reliable: chat, clipboard sync, file transfer ══
let dc2 = null, rxFile = null;
const FILE_CHUNK = 16384;
function ctlSend(o) { if (dc2 && dc2.readyState === 'open') { try { dc2.send(JSON.stringify(o)); } catch (e) {} } }
async function onCtlMessage(e) {
    try {
        if (typeof e.data === 'string') {
            const m = JSON.parse(e.data);
            if (m.t === 'chat') addChat(m.from || 'Remote', m.text, false);
            else if (m.t === 'clip') { try { await navigator.clipboard.writeText(m.text); } catch (x) {} }
            else if (m.t === 'file-meta') { rxFile = { name: m.name, size: m.size, chunks: [], got: 0 }; addChat('System', 'Receiving ' + m.name + '…', false); }
        } else if (rxFile) {
            rxFile.chunks.push(e.data); rxFile.got += e.data.byteLength;
            if (rxFile.got >= rxFile.size) {
                const f = rxFile; rxFile = null;
                const url = URL.createObjectURL(new Blob(f.chunks));
                const a = document.createElement('a'); a.href = url; a.download = f.name; a.click();
                setTimeout(() => URL.revokeObjectURL(url), 5000);
                addChat('System', 'Saved ' + f.name, false);
            }
        }
    } catch (err) {}
}
function addChat(from, text, mine) {
    const box = $('chatMsgs'); if (!box) return;
    const d = document.createElement('div');
    d.style.cssText = 'margin:4px 0;';
    d.innerHTML = `<span style="color:${mine ? '#22d3ee' : '#f43f5e'};font-weight:600;">${from}:</span> <span style="color:#cbd5e1;"></span>`;
    d.children[1].textContent = text;
    box.appendChild(d); box.scrollTop = box.scrollHeight;
}
const $ = id => document.getElementById(id);
$('chatSend').onclick = () => {
    const t = $('chatInput').value.trim(); if (!t) return;
    ctlSend({ t: 'chat', from: VIEWER, text: t });
    addChat('Me', t, true); $('chatInput').value = '';
};
$('chatInput').addEventListener('keydown', e => { if (e.key === 'Enter') $('chatSend').click(); });
$('chatBtn').onclick = () => { $('chatPanel').classList.toggle('hidden'); $('chatPanel').classList.toggle('flex'); };
$('fileBtn').onclick = () => $('fileInput').click();
$('fileInput').onchange = async e => {
    const f = e.target.files[0]; e.target.value = '';
    if (!f || !dc2 || dc2.readyState !== 'open') return;
    if (f.size > 30 * 1024 * 1024) { addChat('System', 'File too large (30 MB max)', false); return; }
    const buf = await f.arrayBuffer();
    ctlSend({ t: 'file-meta', name: f.name, size: buf.byteLength });
    for (let off = 0; off < buf.byteLength; off += FILE_CHUNK) {
        dc2.send(buf.slice(off, off + FILE_CHUNK));
        if (off % (512 * 1024) < FILE_CHUNK) await new Promise(r => setTimeout(r, 0));
    }
    addChat('System', 'Sent ' + f.name, false);
};
document.addEventListener('keydown', async e => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c' && dc2 && dc2.readyState === 'open') {
        try { const t = await navigator.clipboard.readText(); if (t) ctlSend({ t: 'clip', text: t }); } catch (x) {}
    }
});

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

    channel.bind('client-join-accept', (m) => { if (sigNew(m)) onAccept(); });
    channel.bind('client-join-reject', (m) => { if (sigNew(m)) onReject(m && m.reason); });
    channel.bind('client-signal', (m) => { if (sigNew(m)) onSignal(m); });
    channel.bind('client-end', (m) => { if (sigNew(m)) onEnded(); });
    startViewerPoll();
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

window.addEventListener('beforeunload', () => {
    whisper('end', {});
    try { fetch(END_URL, { method: 'POST', credentials: 'same-origin', keepalive: true, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } }); } catch (e) {}
    cleanup();
});

initChannel();
</script>
@endsection
