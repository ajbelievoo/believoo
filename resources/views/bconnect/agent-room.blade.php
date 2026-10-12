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
                <span id="encBadge" class="hidden text-slate-500" title="End-to-end encrypted (DTLS)">• 🔒</span>
                <span id="latencyBadge" class="hidden text-slate-500">• <span id="latencyVal">—</span> ms</span>
                <span id="qualBadge" class="hidden text-slate-500" title="Connection quality"></span>
                <span id="timeBadge" class="hidden text-slate-500 tabular-nums">• 00:00</span>
            </p>
            <p id="debugLine" class="text-[10px] text-slate-600 font-mono truncate"></p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <span id="inputBadge" class="bc-badge bc-badge-amber hidden"><i class="fas fa-keyboard mr-1"></i>Control enabled</span>
            @if(!$canControl)<span class="bc-badge bc-badge-cyan" title="Upgrade to Pro/Enterprise for mouse & keyboard control"><i class="fas fa-eye mr-1"></i>View only · upgrade for control</span>@endif
            <button id="fileBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Send file"><i class="fas fa-paperclip mr-1"></i>File</button>
            <button id="chatBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Chat"><i class="fas fa-comment mr-1"></i>Chat</button>
            <button id="shotBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Screenshot"><i class="fas fa-camera mr-1"></i></button>
            <button id="recBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Record session"><i class="fas fa-circle mr-1"></i></button>
            <button id="monBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Switch remote display"><i class="fas fa-desktop mr-1"></i></button>
            <button id="rebootBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Reboot remote computer"><i class="fas fa-power-off mr-1"></i></button>
            <button id="lockBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Lock remote computer"><i class="fas fa-lock mr-1"></i></button>
            <button id="laserBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Laser pointer"><i class="fas fa-location-arrow mr-1"></i></button>
            <button id="fsOpenBtn" class="bc-btn bc-btn-secondary text-sm hidden" title="Browse remote files"><i class="fas fa-folder-open mr-1"></i></button>
            <select id="qualitySel" title="Quality" class="bc-btn bc-btn-secondary text-sm hidden" style="padding:4px 8px;">
                <option value="auto">Auto</option><option value="sd">SD</option><option value="hd">HD</option>
            </select>
            <button id="fullscreenBtn" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-expand mr-1"></i>Fullscreen</button>
            <input type="file" id="fileInput" class="hidden">
            <button id="endSessionBtn" class="bc-btn bc-btn-danger text-sm"><i class="fas fa-phone-slash mr-1"></i>End</button>
        </div>
    </div>

    <div id="remoteContainer" class="flex-1 bg-black rounded-2xl border border-[var(--bc-border)] relative overflow-hidden flex items-center justify-center select-none">
        <video id="remoteVideo" autoplay playsinline class="max-w-full max-h-full w-full h-full object-contain" style="cursor:crosshair;"></video>
        <div id="statOverlay" class="absolute top-2 left-2 text-[10px] text-slate-200 bg-slate-900/60 px-2 py-0.5 rounded hidden pointer-events-none"></div>
        <div id="waitOverlay" class="absolute inset-0 flex items-center justify-center bg-[var(--bc-bg)]/90 text-center z-10">
            <div>
                <div class="w-14 h-14 mx-auto mb-4 rounded-full border-4 border-[var(--bc-border)] animate-spin" style="border-top-color:var(--bc-cyan);"></div>
                <p class="font-bold mb-1" id="waitTitle">Waiting for host approval…</p>
                <p class="text-xs text-slate-500" id="waitSub">The host will see your request and accept it.</p>
                <div id="waitProgWrap" class="mt-4">
                    <div class="w-64 max-w-[80vw] h-1.5 bg-slate-700/40 rounded-full mx-auto mb-1.5 overflow-hidden">
                        <div id="waitProg" class="h-full w-0 rounded-full transition-all duration-500" style="background:linear-gradient(90deg,var(--bc-cyan,#22d3ee),#f43f5e);"></div>
                    </div>
                    <div id="waitPct" class="text-[11px] text-slate-500 tabular-nums">0%</div>
                </div>
            </div>
        </div>
        <div id="fsPanel" class="absolute right-0 top-0 bottom-0 w-72 bg-[var(--bc-card,#0f172a)]/95 border-l border-[var(--bc-border)] z-20 hidden flex-col">
            <div class="flex gap-2 p-2 border-b border-[var(--bc-border)] items-center">
                <button id="fsUp" title="Up one level" class="bc-btn bc-btn-secondary text-xs px-2">↑</button>
                <div id="fsPath" class="flex-1 text-[11px] text-slate-500 truncate">…</div>
            </div>
            <div id="fsItems" class="flex-1 overflow-y-auto p-2 text-sm"></div>
        </div>
        <div id="chatPanel" class="absolute right-0 top-0 bottom-0 w-72 bg-[var(--bc-card,#0f172a)]/95 border-l border-[var(--bc-border)] z-20 hidden flex-col">
            <div id="chatMsgs" class="flex-1 overflow-y-auto p-3 text-sm"></div>
            <div class="flex gap-2 p-2 border-t border-[var(--bc-border)]">
                <input id="chatInput" placeholder="Message… or /cmd hostname" class="flex-1 bg-slate-800 border border-[var(--bc-border)] text-slate-200 px-3 py-2 rounded-lg text-sm outline-none">
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

// Connection progress — moves through the join stages so the client can
// tell waiting-for-approval apart from a dead screen.
let wPct = 0;
const wJoinedAt = Date.now();
function wStage(pct, title, sub) {
    wPct = Math.max(wPct, pct);
    const b = document.getElementById('waitProg'); if (b) b.style.width = wPct + '%';
    const p = document.getElementById('waitPct'); if (p) p.textContent = wPct + '%';
    if (title) document.getElementById('waitTitle').textContent = title;
    if (sub !== undefined) document.getElementById('waitSub').textContent = sub;
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
const sigFresh = (m) => m.kind === 'ice' ? (!m.at || (Date.now() - Date.parse(m.at)) < 45000) : true; // only ICE ages out
const nonce = () => Math.random().toString(36).slice(2) + Date.now().toString(36);
function postJson(url, body) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: {
        'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body || {}) }).catch(() => {});
}
function whisper(evt, data) {
    try { channel.trigger('client-' + evt, data); } catch (e) { console.warn('send failed', e); }
}
    // strip SDP lines some libwebrtc/Chrome builds reject outright
    function cleanSdp(sdp) {
        const out = String(sdp || '').split(/\r?\n/)
            .filter(l => l && !/^a=max-message-size/.test(l))
            .map(l => { const m = l.match(/^a=sctp-port:(\d+)\s*$/); return m ? 'a=sctpmap:' + m[1] + ' webrtc-datachannel 1024' : l; })
            .join('\r\n');
        return out.endsWith('\r\n') ? out : out + '\r\n';
    }

function sendSignal(msg) { msg.n = nonce(); whisper('signal', msg); return postJson(API + '/signal', msg); }
async function sendOfferRobust(sdp) {
    const r = await sendSignal({ kind: 'offer', sdp });
    if (r && r.ok) return;
    dbg('offer POST failed — chunked retry');
    const CH = 3000, parts = [];
    for (let i = 0; i < sdp.length; i += CH) parts.push(sdp.slice(i, i + CH));
    for (let i = 0; i < parts.length; i++) {
        const rr = await sendSignal({ kind: 'offer-part', i, total: parts.length, chunk: parts[i] });
        if (!(rr && rr.ok)) { dbg('chunk ' + i + ' failed'); return; }
    }
    dbg('offer sent in ' + parts.length + ' parts');
}

function onAccept() {
    setStatus('Accepted — starting stream…', 'text-green-400');
    dbg('host accepted');
    wStage(60, 'Accepted — starting stream…');
    startPeer();
}
function onReject(reason) {
    setStatus('Host rejected the request', 'text-red-400');
    document.getElementById('waitTitle').textContent = 'Connection declined';
    document.getElementById('waitSub').textContent = (reason === 'device')
        ? 'The host device cannot share its screen from a browser. Ask them to use the BMyDesk Agent app.'
        : 'The host declined your request.';
    document.getElementById('waitProgWrap').style.display = 'none';
}
function onEnded() {
    setStatus('Session ended by host', 'text-red-400');
    cleanup();
    document.getElementById('waitOverlay').classList.remove('hidden');
    document.getElementById('waitTitle').textContent = 'Session ended';
    document.getElementById('waitSub').textContent = 'The host ended this remote session.';
    document.getElementById('waitProgWrap').style.display = 'none';
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
            // keep waiting state alive — elapsed seconds tell the client the
            // host still has to press Accept.
            if (wPct && wPct < 60) {
                const waited = Math.round((Date.now() - wJoinedAt) / 1000);
                wStage(wPct, 'Waiting for host approval… (' + waited + 's)' +
                    (waited > 90 ? ' — host may be away; their app must be open' : ''));
            }
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
        wStage(100, 'Connected');
        video.srcObject = e.streams[0];
        $('statOverlay')?.classList.remove('hidden');
        startStats();
        startSessionTimer();
        document.getElementById('waitOverlay').classList.add('hidden');
        if (CAN_CONTROL) document.getElementById('clickHint').classList.remove('hidden');
        setStatus('Connected', 'text-green-400');
        connected = true;
        document.getElementById('encBadge').classList.remove('hidden');
        if (streamTimeout) clearTimeout(streamTimeout);
        startPing();
    };

    pc.onicecandidate = (e) => {
        if (e.candidate) sendSignal({ kind: 'ice', candidate: e.candidate });
    };

    pc.onconnectionstatechange = () => {
        dbg('peer ' + pc.connectionState);
        if (pc.connectionState === 'connected') { wStage(92, 'Almost there…'); setStatus('Connected', 'text-green-400'); connTries = 0; if (discTimer) { clearTimeout(discTimer); discTimer = null; } }
        if (pc.connectionState === 'failed') attemptReconnect();
        if (pc.connectionState === 'disconnected') {
            setStatus('Unstable — watching…', 'text-amber-400');
            discTimer = setTimeout(() => { if (pc && pc.connectionState === 'disconnected') attemptReconnect(); }, 6000);
        }
        if (pc.connectionState === 'closed') setStatus('Connection lost', 'text-red-400');
    };

    dc = pc.createDataChannel('input', { ordered: false, maxRetransmits: 0 });
    dc2 = pc.createDataChannel('ctl'); // reliable — chat/clipboard/file
    dc2.onopen = () => { ['fileBtn','chatBtn','shotBtn','recBtn','qualitySel','monBtn','rebootBtn','lockBtn','laserBtn','fsOpenBtn'].forEach(i => $(i).classList.remove('hidden')); };
    dc2.onclose = () => { ['fileBtn','chatBtn','shotBtn','recBtn','qualitySel','monBtn','rebootBtn','lockBtn','laserBtn','fsOpenBtn','chatPanel','fsPanel'].forEach(i => $(i).classList.add('hidden')); stopStats(); stopRec(); };
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
    // retransmit until an answer lands — a single lost POST must not kill the session
    let offerTries = 0;
    const sendOffer = () => {
        if (!pc || pc.remoteDescription || offerTries >= 8) return;
        offerTries++;
        sendOfferRobust(cleanSdp(pc.localDescription.sdp));
        dbg('offer sent x' + offerTries);
        setTimeout(sendOffer, 4000);
    };
    sendOffer();
    setStatus('Connecting…', 'text-amber-400');
    wStage(72, 'Negotiating secure link…');
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

// Auto-reconnect — session stays 'active' server-side, so no new host
// approval: ICE restart first, then a full peer rebuild.
let connTries = 0, discTimer = null;
function attemptReconnect() {
    if (!pc) return;
    connTries++;
    if (connTries > 10) {
        setStatus('Connection lost — reload to retry', 'text-red-400');
        document.getElementById('waitTitle').textContent = 'Connection lost';
        document.getElementById('waitSub').textContent = 'The session could not be re-established. Reload this page to try again.';
        document.getElementById('waitProgWrap').style.display = 'none';
        document.getElementById('waitOverlay').classList.remove('hidden');
        return;
    }
    setStatus('Reconnecting… (attempt ' + connTries + ')', 'text-amber-400');
    if (connTries <= 3) {
        try { pc.restartIce(); } catch (e) {}
    } else {
        try { pc.close(); } catch (e) {}
        pc = null;
        setTimeout(() => startPeer(), 1500);
    }
}

async function onSignal(m) {
    if (!pc) return;
    try {
        if (m.kind === 'answer') {
            dbg('answer received');
            wStage(84, 'Answer received — connecting…');
            try { await pc.setRemoteDescription({ type: 'answer', sdp: m.sdp }); }
            catch (e) { await pc.setRemoteDescription({ type: 'answer', sdp: cleanSdp(m.sdp) }); }
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
            else if (m.t === 'cmd-out') addChat('Host ▸', m.out || '(no output)', false);
            else if (m.t === 'view-only') addChat('System', m.on ? 'Host locked remote input (view-only mode)' : 'Host re-enabled remote input', false);
            else if (m.t === 'fs-list') renderFsList(m);
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
    if (t.startsWith('/cmd ')) {
        const c = t.slice(5).trim();
        if (c) { ctlSend({ t: 'cmd', cmd: c }); addChat('Me ▸', c, true); }
    } else if (t === '/ps') {
        ctlSend({ t: 'procs' }); addChat('Me ▸', 'tasklist', true);
    } else if (t.startsWith('/kill ')) {
        const a = t.slice(6).trim();
        if (a) { ctlSend({ t: 'kill', arg: a }); addChat('Me ▸', 'kill ' + a, true); }
    } else {
        ctlSend({ t: 'chat', from: VIEWER, text: t });
        addChat('Me', t, true);
    }
    $('chatInput').value = '';
};
$('chatInput').addEventListener('keydown', e => { if (e.key === 'Enter') $('chatSend').click(); });
$('qualitySel').onchange = () => ctlSend({ t: 'quality', mode: $('qualitySel').value });
$('monBtn').onclick = () => ctlSend({ t: 'screen' });
$('rebootBtn').onclick = () => { if (confirm('Reboot the REMOTE computer now?\n\nThe host user will be asked to confirm.')) ctlSend({ t: 'reboot' }); };
$('lockBtn').onclick = () => { if (confirm('Lock the REMOTE computer?\n\nThe host user will be asked to confirm.')) ctlSend({ t: 'lock' }); };
$('laserBtn').onclick = () => {
    laserOn = !laserOn;
    $('laserBtn').classList.toggle('bc-btn-danger', laserOn);
    if (!laserOn) sendInput({ t: 'laser', off: true });
};

// ── Remote file explorer ──
let fsCurPath = '';
function fsJoin(dir, name) {
    if (!dir) return name;
    return (dir.endsWith('\\') || dir.endsWith('/')) ? dir + name
        : dir + (dir.includes('\\') ? '\\' : '/') + name;
}
function fsUpPath(p) {
    const m = String(p || '').replace(/[\\/]+$/, '');
    const idx = Math.max(m.lastIndexOf('\\'), m.lastIndexOf('/'));
    return idx > 0 ? m.slice(0, idx) : (idx === 0 ? m.slice(0, 1) : '');
}
function fsFmt(s) { return s > 1048576 ? (s / 1048576).toFixed(1) + 'M' : s > 1024 ? (s / 1024).toFixed(0) + 'K' : s + 'B'; }
$('fsOpenBtn').onclick = () => {
    const p = $('fsPanel');
    if (p.classList.contains('hidden')) {
        p.classList.remove('hidden'); p.classList.add('flex');
        $('fsItems').innerHTML = '<div class="text-slate-500 p-3">Loading…</div>';
        ctlSend({ t: 'fs-list-req', path: fsCurPath });
    } else { p.classList.add('hidden'); p.classList.remove('flex'); }
};
$('fsUp').onclick = () => ctlSend({ t: 'fs-list-req', path: fsUpPath(fsCurPath) });
function renderFsList(m) {
    const p = $('fsPanel');
    p.classList.remove('hidden'); p.classList.add('flex');
    fsCurPath = m.path || '';
    $('fsPath').textContent = m.path || '(drives)';
    $('fsPath').title = m.path || '';
    const box = $('fsItems'); box.innerHTML = '';
    if (m.err) { const d = document.createElement('div'); d.className = 'text-red-400 p-3'; d.textContent = m.err; box.appendChild(d); return; }
    if (!m.items || !m.items.length) { box.innerHTML = '<div class="text-slate-500 p-3">Empty folder</div>'; return; }
    m.items.forEach(it => {
        const row = document.createElement('div');
        row.className = 'px-2 py-1 rounded hover:bg-slate-700 cursor-pointer text-slate-300 flex items-center gap-2 truncate';
        row.appendChild(document.createTextNode(it.d ? '📁 ' : '📄 '));
        const lbl = document.createElement('span');
        lbl.textContent = it.n; lbl.className = 'truncate flex-1';
        row.appendChild(lbl);
        if (!it.d) { const sz = document.createElement('span'); sz.textContent = fsFmt(it.s); sz.className = 'text-slate-500 text-[10px]'; row.appendChild(sz); }
        row.title = it.d ? 'Open folder' : 'Download to this device';
        row.onclick = () => ctlSend({ t: it.d ? 'fs-list-req' : 'fs-get', path: fsJoin(fsCurPath, it.n) });
        box.appendChild(row);
    });
}

let stTimer = null, stBytes = 0, stAt = 0, sessStart = 0, sessT = null, autoDropped = false;
function startSessionTimer() {
    if (sessStart) return;
    sessStart = Date.now();
    sessT = setInterval(() => {
        const s = Math.floor((Date.now() - sessStart) / 1000);
        const t = $('timeBadge');
        if (t) { t.classList.remove('hidden'); t.textContent = '• ' + String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }
    }, 1000);
}
function qualBars(rtt) {
    const b = $('qualBadge'); if (!b) return;
    b.classList.remove('hidden');
    const bars = rtt < 80 ? '▂▄▆█' : rtt < 160 ? '▂▄▆ ' : rtt < 300 ? '▂▄  ' : '▂   ';
    b.textContent = '• ' + bars;
    b.style.color = rtt < 160 ? '#34d399' : rtt < 300 ? '#fbbf24' : '#f87171';
    b.title = 'Link quality (' + rtt + 'ms)';
}
function startStats() {
    stopStats();
    stTimer = setInterval(async () => {
        if (!pc) return;
        try {
            let fps = 0, rtt = 0, bytesNow = 0;
            (await pc.getStats()).forEach(r => {
                if (r.type === 'inbound-rtp' && r.kind === 'video') { fps = r.framesPerSecond || 0; bytesNow = r.bytesReceived || 0; }
                if (r.type === 'candidate-pair' && r.nominated) rtt = Math.round((r.currentRoundTripTime || 0) * 1000);
            });
            const now = Date.now();
            const kbps = stAt ? Math.round((bytesNow - stBytes) * 8 / (now - stAt)) : 0;
            stBytes = bytesNow; stAt = now;
            const el = $('statOverlay'); if (el) el.textContent = (progTxt ? progTxt + '  ·  ' : '') + rtt + 'ms · ' + fps + 'fps · ' + kbps + 'kbps' + (progTxt ? '  (tap = cancel)' : '');
            qualBars(rtt);
            if (!autoDropped && $('qualitySel').value === 'auto' && rtt > 350 && connected) {
                autoDropped = true;
                ctlSend({ t: 'quality', mode: 'sd' });
                addChat('System', 'Network is slow — quality lowered to SD automatically.', false);
            }
        } catch (e) {}
    }, 2000);
}
function stopStats() { if (stTimer) clearInterval(stTimer); stTimer = null; stBytes = 0; stAt = 0; }

$('shotBtn').onclick = () => {
    const v = $('remoteVideo');
    if (!v.videoWidth) return;
    const c = document.createElement('canvas');
    c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    const a = document.createElement('a');
    a.href = c.toDataURL('image/png'); a.download = 'bmydesk-' + Date.now() + '.png'; a.click();
};

let rec = null, recChunks = [];
$('recBtn').onclick = () => {
    if (rec && rec.state !== 'inactive') { stopRec(); return; }
    const stream = $('remoteVideo').srcObject;
    if (!stream) return;
    try { rec = new MediaRecorder(stream, { mimeType: 'video/webm' }); }
    catch (e) { try { rec = new MediaRecorder(stream); } catch (x) { return; } }
    recChunks = [];
    rec.ondataavailable = e => { if (e.data.size) recChunks.push(e.data); };
    rec.onstop = () => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob(recChunks, { type: 'video/webm' }));
        a.download = 'bmydesk-session-' + Date.now() + '.webm'; a.click();
        rec = null;
    };
    rec.start(1000);
    $('recBtn').innerHTML = '<i class="fas fa-stop mr-1"></i>';
    $('recBtn').style.color = '#f43f5e';
};
function stopRec() {
    if (rec && rec.state !== 'inactive') rec.stop();
    const b = $('recBtn'); if (b) { b.innerHTML = '<i class="fas fa-circle mr-1"></i>'; b.style.color = ''; }
}

$('chatBtn').onclick = () => { $('chatPanel').classList.toggle('hidden'); $('chatPanel').classList.toggle('flex'); };
$('fileBtn').onclick = () => $('fileInput').click();
let txAbort = false, txActive = false, progTxt = null;
$('fileInput').onchange = async e => {
    const f = e.target.files[0]; e.target.value = '';
    if (!f || !dc2 || dc2.readyState !== 'open') return;
    if (f.size > 200 * 1024 * 1024) { addChat('System', 'File too large (200 MB max)', false); return; }
    await sendBuf(await f.arrayBuffer(), f.name);
};
async function sendBuf(buf, name) {
    if (txActive) { addChat('System', 'A transfer is already running', false); return; }
    txActive = true; txAbort = false;
    ctlSend({ t: 'file-meta', name, size: buf.byteLength });
    for (let off = 0; off < buf.byteLength; off += FILE_CHUNK) {
        if (txAbort) { ctlSend({ t: 'file-cancel' }); progTxt = null; addChat('System', 'Transfer cancelled', false); txActive = false; return; }
        dc2.send(buf.slice(off, off + FILE_CHUNK));
        if (off % (256 * 1024) < FILE_CHUNK) { progTxt = 'Sending ' + name + ' — ' + Math.round(off * 100 / buf.byteLength) + '%'; await new Promise(r => setTimeout(r, 0)); }
    }
    txActive = false; progTxt = null;
    addChat('System', '↑ Sent ' + name + ' (' + Math.round(buf.byteLength / 1024) + ' KB)', false);
}
document.addEventListener('dragover', e => e.preventDefault());
document.addEventListener('drop', async e => {
    e.preventDefault();
    const f = e.dataTransfer?.files?.[0];
    if (!f || !dc2 || dc2.readyState !== 'open') return;
    await sendBuf(await f.arrayBuffer(), f.name);
});
$('statOverlay').onclick = () => { if (rxFile) { rxFile = null; ctlSend({ t: 'file-cancel' }); progTxt = null; addChat('System', 'Transfer cancelled', false); } else if (txActive) txAbort = true; };
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

// Stable viewer identity — hosts can mark it "always trust this device"
function vdev() {
    let d = localStorage.getItem('bmd_vdev');
    if (!d) { d = 'web-' + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2); localStorage.setItem('bmd_vdev', d); }
    return d;
}
let lastMove = 0;
// Zoom/pan — Ctrl+wheel zooms at cursor, middle-drag pans when zoomed,
// double-click resets to fit. Works in view-only sessions too (coords come
// from the transformed rect, so remote input stays accurate while zoomed).
let zoom = { s: 1, x: 0, y: 0 }, laserOn = false, panning = false, panX = 0, panY = 0;
function applyZoom() {
    video.style.transformOrigin = '0 0';
    video.style.transform = zoom.s === 1 ? '' : `translate(${zoom.x}px,${zoom.y}px) scale(${zoom.s})`;
}
video.addEventListener('wheel', (e) => {
    if (!e.ctrlKey) return;
    e.preventDefault();
    const r = video.getBoundingClientRect();
    const ox = r.left - zoom.x, oy = r.top - zoom.y;
    const cx = e.clientX - ox, cy = e.clientY - oy;
    const ns = Math.min(4, Math.max(1, zoom.s * (e.deltaY < 0 ? 1.2 : 1 / 1.2)));
    const f = ns / zoom.s;
    zoom.x = cx - (cx - zoom.x) * f; zoom.y = cy - (cy - zoom.y) * f; zoom.s = ns;
    if (zoom.s === 1) { zoom.x = 0; zoom.y = 0; }
    applyZoom();
}, { passive: false });
video.addEventListener('dblclick', () => { if (zoom.s !== 1) { zoom = { s: 1, x: 0, y: 0 }; applyZoom(); } });
if (CAN_CONTROL) {
    video.addEventListener('mousemove', (e) => {
        if (panning) { zoom.x += e.clientX - panX; zoom.y += e.clientY - panY; panX = e.clientX; panY = e.clientY; applyZoom(); return; }
        const now = performance.now();
        if (now - lastMove < 33) return; // ~30fps
        lastMove = now;
        const r = video.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
        sendInput(laserOn ? { t: 'laser', x, y } : { t: 'move', x, y });
    });
    video.addEventListener('mousedown', (e) => {
        e.preventDefault();
        if (e.button === 1 && zoom.s > 1) { panning = true; panX = e.clientX; panY = e.clientY; return; }
        if (!laserOn) sendInput({ t: 'down', b: e.button });
    });
    video.addEventListener('mouseup', (e) => {
        if (e.button === 1 && panning) { panning = false; return; }
        if (!laserOn) sendInput({ t: 'up', b: e.button });
    });
    video.addEventListener('wheel', (e) => { e.preventDefault(); if (!e.ctrlKey) sendInput({ t: 'wheel', dx: e.deltaX, dy: e.deltaY }); }, { passive: false });
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
    wStage(15, 'Waiting for host approval…');
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
        wStage(45, 'Waiting for host approval…');
        whisper('join-request', { name: VIEWER, vdevice: vdev() });
        // queue the request too — a dead host ws must not hide the prompt
        sendSignal({ kind: 'join-request', name: VIEWER, vdevice: vdev() });
        dbg('join-request sent');
    });
    channel.bind('pusher:subscription_error', () => {
        clearTimeout(subTimeout);
        setStatus('Channel auth failed — reload', 'text-red-400');
        dbg('subscription_error');
        wStage(Math.max(wPct, 30), 'Connecting over relay…');
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
