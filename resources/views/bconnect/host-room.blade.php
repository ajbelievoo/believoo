@extends('bconnect.layout')
@section('title', 'Host Session ' . $session->session_code)
@section('content')
<div class="max-w-5xl mx-auto">
    <div class="grid lg:grid-cols-3 gap-4">
        <!-- Code card -->
        <div class="bc-card p-8 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-4" style="background:rgba({{ $bconnectBrand['brand_rgb'] }},0.12);">
                <i class="fas fa-share-nodes text-2xl" style="color:var(--bc-cyan)"></i>
            </div>
            <h2 class="text-xl font-black mb-1">Your Session Code</h2>
            <p class="text-xs text-slate-500 mb-5">Share this code with the person who should view your screen</p>
            <div class="text-4xl font-black tracking-[0.25em] py-4 px-3 rounded-2xl bg-[var(--bc-panel)] border border-[var(--bc-border)] select-all" style="color:var(--bc-cyan);">{{ $session->session_code }}</div>
            <button onclick="navigator.clipboard.writeText('{{ $session->session_code }}')" class="bc-btn bc-btn-secondary text-xs mt-4"><i class="fas fa-copy mr-1"></i>Copy Code</button>
            <p class="text-[11px] text-slate-600 mt-4"><i class="fas fa-clock mr-1"></i>Expires {{ $session->expires_at?->diffForHumans() ?? 'in 60 min' }}</p>
        </div>

        <!-- Status / preview -->
        <div class="lg:col-span-2 bc-card p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold"><i class="fas fa-signal mr-2 text-slate-500"></i>Session Status</h3>
                <span id="connStatus" class="bc-badge bc-badge-amber">Waiting for viewer…</span>
            </div>
            <p id="debugLine" class="text-[10px] text-slate-600 font-mono truncate mb-2"></p>

            <div id="joinRequest" class="hidden mb-4 p-4 rounded-xl border border-amber-500/40 bg-amber-500/10">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-400"><i class="fas fa-user"></i></div>
                        <div>
                            <p class="font-bold text-sm"><span id="joinName">Someone</span> wants to connect</p>
                            <p class="text-xs text-slate-400">They will see your screen (view only in browser host mode)</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button id="acceptBtn" class="bc-btn bc-btn-primary text-sm"><i class="fas fa-check mr-1"></i>Accept</button>
                        <button id="rejectBtn" class="bc-btn bc-btn-danger text-sm"><i class="fas fa-times mr-1"></i>Reject</button>
                    </div>
                </div>
            </div>

            <div id="previewBox" class="flex-1 bg-black rounded-xl border border-[var(--bc-border)] relative overflow-hidden min-h-[300px] flex items-center justify-center">
                <video id="localPreview" autoplay muted playsinline class="max-w-full max-h-full w-full h-full object-contain hidden"></video>
                <div id="previewPlaceholder" class="text-center text-slate-500 p-6">
                    <i class="fas fa-desktop text-4xl mb-3 text-slate-700"></i>
                    <p class="text-sm">Your screen preview appears here after you start sharing.</p>
                </div>
            </div>

            <div class="flex gap-2 mt-4 flex-wrap">
                <button id="shareBtn" class="bc-btn bc-btn-primary"><i class="fas fa-share-square mr-1"></i>Choose Screen to Share</button>
                <button id="endBtn" class="bc-btn bc-btn-danger"><i class="fas fa-phone-slash mr-1"></i>End Session</button>
            </div>
            <p class="text-[11px] text-slate-600 mt-3"><i class="fas fa-shield-alt mr-1"></i>Browser host is view-only. For mouse/keyboard control, use the BMyDesk Agent app.</p>
        </div>
    </div>
</div>

<script src="/js/pusher.min.js"></script>
<script>
const CODE = @json($session->session_code);
const channelName = 'remote-agent.' + CODE;
const END_URL = @json(route('bconnect.remote.code.end', $session->session_code));
const backUrl = @json(route('bconnect.remote.connect'));

let pc = null, stream = null, channel = null, iceQueue = [], pendingOffer = null;
const statusEl = document.getElementById('connStatus');
const video = document.getElementById('localPreview');

const pcConfig = { iceServers: @json($iceServers ?? [['urls' => 'stun:stun.l.google.com:19302']]) };

function setStatus(text, badge = 'bc-badge-amber') { statusEl.textContent = text; statusEl.className = 'bc-badge ' + badge; }
function dbg(step) {
    const el = document.getElementById('debugLine');
    el.textContent = (el.textContent + ' › ' + step).slice(-140);
    console.log('[bmydesk-host]', step);
}
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
function whisper(evt, data) { try { channel.trigger('client-' + evt, data); } catch (e) { console.warn(e); } }
function sendSignal(msg) { msg.n = nonce(); whisper('signal', msg); postJson(API + '/signal', msg); }
let respondedAt = 0; // polled join-requests older than this aren't re-shown

const CAN_SHARE = !!(navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia);

function showMobileHostNotice() {
    setStatus('Use the BMyDesk app on this device', 'bc-badge-amber');
    document.getElementById('previewPlaceholder').innerHTML =
        '<i class="fab fa-android text-4xl mb-3 text-green-500"></i>' +
        '<p class="text-sm font-bold text-slate-300">Mobile browser cannot share its screen.</p>' +
        '<p class="text-xs text-slate-500 mt-1 mb-3">Use the BMyDesk app to share this device&apos;s screen — or open this page on a computer.</p>' +
        '<a href="bmydesk://open" class="bc-btn bc-btn-primary text-sm">Open in BMyDesk App</a>' +
        '<p class="text-[11px] text-slate-600 mt-2">App not installed? <a href="/downloads/BMyDesk-Agent-v1.0.7.apk" class="text-cyan-400 underline">Download APK</a></p>';
    const btn = document.getElementById('shareBtn');
    btn.disabled = true;
    btn.classList.add('opacity-40');
}

document.getElementById('shareBtn').addEventListener('click', async () => {
    if (!CAN_SHARE) { showMobileHostNotice(); return; }
    try {
        stream = await navigator.mediaDevices.getDisplayMedia({
            video: { frameRate: { ideal: 30, max: 30 }, width: { ideal: 1920 }, height: { ideal: 1080 } },
            audio: false,
        });
        stream.getVideoTracks()[0].contentHint = 'detail';
        video.srcObject = stream;
        video.classList.remove('hidden');
        document.getElementById('previewPlaceholder').classList.add('hidden');
        document.getElementById('shareBtn').disabled = true;
        setStatus('Screen ready — waiting for viewer', 'bc-badge-cyan');
        stream.getVideoTracks()[0].onended = () => { setStatus('Sharing stopped', 'bc-badge-amber'); };
        if (pendingOffer) { const m = pendingOffer; pendingOffer = null; handleOffer(m); }
    } catch (e) {
        alert('Screen share cancelled or not supported: ' + e.message);
    }
});

async function handleOffer(m) {
    if (!stream) { pendingOffer = m; dbg('offer queued (no screen yet)'); setStatus('Viewer waiting — pick a screen to share', 'bc-badge-amber'); return; }
    dbg('handling offer');
    try {
        pc = new RTCPeerConnection(pcConfig);

        pc.onicecandidate = (e) => { if (e.candidate) sendSignal({ kind: 'ice', candidate: e.candidate }); };
        pc.onconnectionstatechange = () => {
            dbg('peer ' + pc.connectionState);
            if (pc.connectionState === 'connected') setStatus('Viewer connected', 'bc-badge-green');
            if (['failed', 'disconnected', 'closed'].includes(pc.connectionState)) setStatus('Viewer disconnected', 'bc-badge-amber');
        };
        pc.ondatachannel = (e) => {
            const dcIn = e.channel;
            dcIn.onmessage = (ev) => {
                try {
                    const m2 = JSON.parse(ev.data);
                    if (m2.t === 'ping') dcIn.send(JSON.stringify({ t: 'pong', ts: m2.ts }));
                } catch (err) {}
            };
        };

        await pc.setRemoteDescription({ type: 'offer', sdp: m.sdp });
        stream.getTracks().forEach(t => pc.addTrack(t, stream));
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        sendSignal({ kind: 'answer', sdp: pc.localDescription.sdp });
        dbg('answer sent');
        iceQueue.forEach(c => pc.addIceCandidate(c).catch(() => {}));
        iceQueue.length = 0;
        setStatus('Streaming…', 'bc-badge-green');
    } catch (e) { console.error('offer handling failed', e); dbg('ERR ' + e.message); setStatus('Stream error: ' + e.message, 'bc-badge-red'); }
}

async function onSignal(m) {
    if (m.kind === 'offer') return handleOffer(m);
    if (m.kind === 'ice' && m.candidate) {
        if (pc && pc.remoteDescription) await pc.addIceCandidate(m.candidate).catch(() => {});
        else iceQueue.push(m.candidate);
    }
}

// Dedicated raw Pusher connection — bypasses the global Echo manager whose
// 10s polling fallback silently drops whispers (join-request never arrived).
function initChannel() {
    if (typeof Pusher === 'undefined') {
        setStatus('Realtime lib missing — reload', 'bc-badge-red');
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
    channel = p.subscribe('private-' + channelName);

    const subTimeout = setTimeout(() => {
        if (!channel.subscribed) { setStatus('Signaling timeout — reload', 'bc-badge-red'); dbg('subscribe timeout'); }
    }, 20000);

    channel.bind('pusher:subscription_succeeded', () => {
        clearTimeout(subTimeout);
        dbg('channel subscribed');
        if (!CAN_SHARE) showMobileHostNotice(); else setStatus('Waiting for viewer…', 'bc-badge-amber');
    });
    channel.bind('pusher:subscription_error', () => {
        clearTimeout(subTimeout);
        setStatus('Channel auth failed — reload', 'bc-badge-red');
        dbg('subscription_error');
    });

    channel.bind('client-join-request', (m) => {
        dbg('join-request received');
        showJoinRequest(m.name || 'Someone');
    });
    channel.bind('client-signal', (m) => { if (sigNew(m)) { dbg('signal: ' + (m.kind || '?')); onSignal(m); } });
    channel.bind('client-end', (m) => {
        if (!sigNew(m)) return;
        setStatus('Viewer disconnected', 'bc-badge-amber');
        if (pc) { pc.close(); pc = null; }
    });
    startHostPoll();
}

function showJoinRequest(name) {
    if (!CAN_SHARE) { showMobileHostNotice(); postJson(API + '/respond', { action: 'reject' }); whisper('join-reject', { reason: 'device' }); return; }
    document.getElementById('joinName').textContent = name;
    document.getElementById('joinRequest').classList.remove('hidden');
    setStatus('Join request', 'bc-badge-amber');
}

// HTTP fallback — drains queued signals and shows join requests even when
// this tab's ws subscription is dead.
function startHostPoll() {
    setInterval(async () => {
        try {
            const sr = await fetch(API + '/signals', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            const sd = await sr.json();
            (sd.signals || []).forEach((m) => { if (sigNew(m) && sigFresh(m)) { dbg('poll sig: ' + (m.kind || '?')); onSignal(m); } });
            const st = await fetch(API + '/session-status', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            const sd2 = await st.json();
            const jt = sd2.ok && sd2.viewer_joined_at ? Date.parse(sd2.viewer_joined_at) : 0;
            if (jt && jt > respondedAt && (Date.now() - jt < 120000)
                && document.getElementById('joinRequest').classList.contains('hidden') && !pc) {
                dbg('poll join-request');
                showJoinRequest(sd2.viewer || 'Someone');
            }
        } catch (e) {}
    }, 3000);
}

document.getElementById('acceptBtn').addEventListener('click', () => {
    document.getElementById('joinRequest').classList.add('hidden');
    respondedAt = Date.now();
    whisper('join-accept', {});
    postJson(API + '/respond', { action: 'accept' });
    setStatus('Accepted — connecting…', 'bc-badge-cyan');
});
document.getElementById('rejectBtn').addEventListener('click', () => {
    document.getElementById('joinRequest').classList.add('hidden');
    respondedAt = Date.now();
    whisper('join-reject', {});
    postJson(API + '/respond', { action: 'reject' });
    setStatus('Request rejected', 'bc-badge-amber');
});

document.getElementById('endBtn').addEventListener('click', async () => {
    if (!confirm('End this session?')) return;
    whisper('end', {});
    try {
        await fetch(END_URL, { method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF} });
    } catch (e) {}
    window.location.href = backUrl;
});

window.addEventListener('beforeunload', () => {
    whisper('end', {});
    try { fetch(END_URL, { method: 'POST', credentials: 'same-origin', keepalive: true, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } }); } catch (e) {}
    if (pc) pc.close(); if (stream) stream.getTracks().forEach(t => t.stop());
});

initChannel();
</script>
@endsection
