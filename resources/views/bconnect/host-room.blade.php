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

<script>
const CODE = @json($session->session_code);
const channelName = 'remote-agent.' + CODE;
const END_URL = @json(route('bconnect.remote.code.end', $session->session_code));
const backUrl = @json(route('bconnect.remote.connect'));

let pc = null, stream = null, channel = null, iceQueue = [];
const statusEl = document.getElementById('connStatus');
const video = document.getElementById('localPreview');

const pcConfig = { iceServers: @json($iceServers ?? [['urls' => 'stun:stun.l.google.com:19302']]) };

function setStatus(text, badge = 'bc-badge-amber') { statusEl.textContent = text; statusEl.className = 'bc-badge ' + badge; }
function whisper(evt, data) { try { channel.whisper(evt, data); } catch (e) { console.warn(e); } }

document.getElementById('shareBtn').addEventListener('click', async () => {
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
    } catch (e) {
        alert('Screen share cancelled or not supported: ' + e.message);
    }
});

async function handleOffer(m) {
    if (!stream) { console.warn('No screen shared yet'); return; }
    try {
        pc = new RTCPeerConnection(pcConfig);

        pc.onicecandidate = (e) => { if (e.candidate) whisper('signal', { kind: 'ice', candidate: e.candidate }); };
        pc.onconnectionstatechange = () => {
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
        whisper('signal', { kind: 'answer', sdp: pc.localDescription.sdp });
        iceQueue.forEach(c => pc.addIceCandidate(c).catch(() => {}));
        iceQueue.length = 0;
        setStatus('Streaming…', 'bc-badge-green');
    } catch (e) { console.error('offer handling failed', e); }
}

async function onSignal(m) {
    if (m.kind === 'offer') return handleOffer(m);
    if (m.kind === 'ice' && m.candidate) {
        if (pc && pc.remoteDescription) await pc.addIceCandidate(m.candidate).catch(() => {});
        else iceQueue.push(m.candidate);
    }
}

function initChannel() {
    if (!window.Echo || !window.Echo.connector || window.Echo.usePolling) {
        setStatus('Realtime unavailable', 'bc-badge-red');
        return;
    }
    channel = window.Echo.private(channelName);

    channel.subscribed(() => setStatus('Waiting for viewer…', 'bc-badge-amber'));

    channel.listenForWhisper('join-request', (m) => {
        document.getElementById('joinName').textContent = m.name || 'Someone';
        document.getElementById('joinRequest').classList.remove('hidden');
        setStatus('Join request', 'bc-badge-amber');
    });
    channel.listenForWhisper('signal', (m) => onSignal(m));
    channel.listenForWhisper('end', () => {
        setStatus('Viewer disconnected', 'bc-badge-amber');
        if (pc) { pc.close(); pc = null; }
    });
    channel.error((e) => { console.error(e); setStatus('Channel auth failed', 'bc-badge-red'); });
}

document.getElementById('acceptBtn').addEventListener('click', () => {
    document.getElementById('joinRequest').classList.add('hidden');
    whisper('join-accept', {});
    setStatus('Accepted — connecting…', 'bc-badge-cyan');
});
document.getElementById('rejectBtn').addEventListener('click', () => {
    document.getElementById('joinRequest').classList.add('hidden');
    whisper('join-reject', {});
    setStatus('Request rejected', 'bc-badge-amber');
});

document.getElementById('endBtn').addEventListener('click', async () => {
    if (!confirm('End this session?')) return;
    whisper('end', {});
    try {
        await fetch(END_URL, { method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content} });
    } catch (e) {}
    window.location.href = backUrl;
});

window.addEventListener('beforeunload', () => { whisper('end', {}); if (pc) pc.close(); if (stream) stream.getTracks().forEach(t => t.stop()); });

let tries = 0;
const waitEcho = setInterval(() => {
    if (window.Echo) { clearInterval(waitEcho); initChannel(); }
    else if (++tries > 40) { clearInterval(waitEcho); setStatus('Realtime init failed — reload', 'bc-badge-red'); }
}, 250);
</script>
@endsection
