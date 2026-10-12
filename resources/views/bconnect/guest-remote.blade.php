<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Remote Desktop Access — BMyDesk</title>
<link rel="icon" type="image/png" href="{{ $bconnectBrand['favicon'] ?? '/favicon.png' }}">
{{-- PWA — guest remote page installs like an app --}}
<link rel="manifest" href="/remote-manifest.json">
<meta name="theme-color" content="#0b1220">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="BMyDesk Remote">
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/remote-sw.js').catch(() => {});
}
</script>
<style>
:root { --bg:#0b1220; --card:#101a2e; --border:#1e293b; --cyan:#22d3ee; --rose:#f43f5e; --txt:#e2e8f0; --dim:#64748b; }
* { box-sizing:border-box; margin:0; }
body { background:var(--bg); color:var(--txt); font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; min-height:100vh; }
.top { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid var(--border); }
.top img { height:30px; }
.top b { font-size:15px; }
.top .sub { color:var(--dim); font-size:12px; }
.wrap { max-width:520px; margin:0 auto; padding:40px 16px; }
.card { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:28px; text-align:center; }
.card h1 { font-size:22px; margin-bottom:6px; }
.card p.hint { color:var(--dim); font-size:13px; margin-bottom:20px; }
.code-input { width:100%; max-width:260px; text-align:center; font-size:30px; letter-spacing:6px; font-family:ui-monospace,monospace; text-transform:uppercase; background:#0b1424; border:1px solid var(--border); border-radius:12px; color:var(--cyan); padding:12px; outline:none; }
.code-input:focus { border-color:var(--cyan); }
.name-input { width:100%; max-width:260px; margin:0 auto 14px; display:block; text-align:center; font-size:15px; background:#0b1424; border:1px solid var(--border); border-radius:12px; color:var(--txt); padding:10px; outline:none; }
.name-input:focus { border-color:var(--cyan); }
.btn { margin-top:18px; background:linear-gradient(135deg,var(--rose),#e11d48); color:#fff; border:0; font-weight:700; font-size:15px; padding:12px 34px; border-radius:12px; cursor:pointer; }
.btn:disabled { opacity:.5; cursor:default; }
.err { color:#f87171; font-size:13px; margin-top:12px; min-height:16px; }
.dbg { color:#3b4a63; font-size:10px; font-family:monospace; margin-top:12px; min-height:12px; word-break:break-all; }
.login-link { display:block; margin-top:18px; color:var(--dim); font-size:12px; text-decoration:none; }
.login-link:hover { color:var(--cyan); }
.hidden { display:none !important; }

.stage { position:fixed; inset:0; top:52px; background:#000; display:flex; flex-direction:column; }
.stage-bar { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 14px; background:var(--card); border-bottom:1px solid var(--border); flex-wrap:wrap; }
.stage-bar .lbl { font-size:13px; font-weight:600; }
.badge { font-size:11px; padding:3px 10px; border-radius:999px; background:#1e293b; color:var(--dim); }
.badge.on { background:#064e3b; color:#34d399; }
.stage-video { flex:1; position:relative; display:flex; align-items:center; justify-content:center; overflow:hidden; }
#remoteVideo { max-width:100%; max-height:100%; width:100%; height:100%; object-fit:contain; cursor:crosshair; outline:none; }
.overlay { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(11,18,32,.92); text-align:center; z-index:5; padding:16px; }
.spin { width:46px; height:46px; margin:0 auto 14px; border:4px solid var(--border); border-top-color:var(--cyan); border-radius:50%; animation:sp 1s linear infinite; }
@keyframes sp { to { transform:rotate(360deg); } }
.endbtn { background:#7f1d1d; color:#fecaca; border:0; font-weight:600; font-size:12px; padding:8px 16px; border-radius:10px; cursor:pointer; }
#clickHint { position:absolute; bottom:14px; left:50%; transform:translateX(-50%); background:rgba(0,0,0,.72); color:#cbd5e1; font-size:12px; padding:8px 16px; border-radius:999px; z-index:6; }
</style>
</head>
<body>
<div class="top">
    <img src="{{ $bconnectBrand['logo'] ?? '/logo.png' }}" alt="BMyDesk">
    <div><b>Remote Desktop Access</b><div class="sub">View &amp; control a device — no account needed</div></div>
</div>

<div class="wrap" id="joinCard">
    <div class="card">
        <h1>Connect to a device</h1>
        <p class="hint">Enter the code shown on the remote computer or phone running the BMyDesk Agent.</p>
        <input class="name-input" id="nameInput" maxlength="40" placeholder="Your name (optional)" autocomplete="name">
        <input class="code-input" id="codeInput" maxlength="8" placeholder="CODE" autocomplete="off" spellcheck="false" value="{{ $prefillCode ?? '' }}">
        <input class="name-input" id="pinInput" maxlength="12" inputmode="numeric" placeholder="PIN — only for unattended devices" style="margin-top:10px;font-family:monospace;letter-spacing:3px">
        <br>
        <button class="btn" id="joinBtn">Connect</button>
        <div id="recentRow" style="margin-top:12px;display:none;text-align:center;"></div>
        <div class="err" id="joinErr"></div>
        <div class="dbg" id="joinDbg"></div>
        @auth
            <a class="login-link" href="{{ route('bconnect.remote.connect') }}" style="color:var(--cyan)">You're signed in — open the workspace Remote Desk →</a>
        @else
            <a class="login-link" href="{{ route('bconnect.login') }}">Have a BMyDesk account? Sign in for the full workspace →</a>
        @endauth
    </div>
</div>

<div class="stage hidden" id="stage">
    <div class="stage-bar">
        <div class="lbl"><span id="hostLabel">Remote device</span> <code style="color:var(--cyan)" id="codeEcho"></code></div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="badge" id="connBadge">connecting</span>
            <span class="badge" id="latBadge" style="display:none"><span id="latVal">—</span> ms</span>
            <button class="endbtn" id="fileBtn" title="Send file" style="background:#1e293b;color:#e2e8f0;display:none;">📎</button>
            <button class="endbtn" id="chatBtn" title="Chat" style="background:#1e293b;color:#e2e8f0;display:none;">💬</button>
            <button class="endbtn" id="shotBtn" title="Screenshot" style="background:#1e293b;color:#e2e8f0;display:none;">📸</button>
            <button class="endbtn" id="recBtn" title="Record session" style="background:#1e293b;color:#e2e8f0;display:none;">⏺</button>
            <button class="endbtn" id="kbdBtn" title="Keyboard" style="background:#1e293b;color:#e2e8f0;display:none;">⌨</button>
            <select id="qualitySel" title="Quality" style="background:#1e293b;border:0;color:#94a3b8;font-size:11px;padding:5px;border-radius:6px;display:none;">
                <option value="auto">Auto</option><option value="sd">SD</option><option value="hd">HD</option>
            </select>
            <button class="endbtn" id="endBtn">End session</button>
            <input type="file" id="fileInput" style="display:none">
            <input type="text" id="mobKbd" autocomplete="off" autocorrect="off" autocapitalize="none" style="position:fixed;bottom:-60px;left:0;width:2px;height:2px;opacity:.01;border:0;z-index:-1">
        </div>
    </div>
    <div class="stage-video">
        <video id="remoteVideo" autoplay playsinline tabindex="0"></video>
        <div id="statOverlay" style="position:absolute;top:52px;left:10px;font-size:10px;color:rgba(226,232,240,.85);background:rgba(15,23,42,.65);padding:2px 8px;border-radius:6px;display:none;pointer-events:none;"></div>
        <div class="overlay" id="waitOverlay">
            <div>
                <div class="spin"></div>
                <b id="waitTitle">Waiting for host approval…</b>
                <div style="color:var(--dim);font-size:12px;margin-top:6px" id="waitSub">The host will see your request.</div>
                <div id="waitProgWrap" style="margin-top:16px;">
                    <div style="width:260px;max-width:80vw;height:6px;background:rgba(148,163,184,.16);border-radius:99px;margin:0 auto 6px;overflow:hidden;">
                        <div id="waitProg" style="height:100%;width:0%;background:linear-gradient(90deg,var(--cyan),var(--rose));border-radius:99px;transition:width .45s ease;"></div>
                    </div>
                    <div id="waitPct" style="color:var(--dim);font-size:11px;font-variant-numeric:tabular-nums;">0%</div>
                </div>
            </div>
        </div>
        <div id="clickHint" style="display:none">Click the screen once to enable keyboard &amp; mouse — Esc to release.</div>
        <div id="chatPanel" style="display:none;position:absolute;right:0;top:0;bottom:0;width:280px;background:rgba(15,23,42,.96);border-left:1px solid var(--border);z-index:8;flex-direction:column;">
            <div id="chatMsgs" style="flex:1;overflow-y:auto;padding:12px;font-size:13px;"></div>
            <div style="display:flex;gap:6px;padding:10px;border-top:1px solid var(--border);">
                <input id="chatInput" placeholder="Message…" style="flex:1;background:#0b1424;border:1px solid var(--border);color:var(--txt);padding:8px 10px;border-radius:8px;font-size:13px;outline:none;">
                <button id="chatSend" style="background:var(--rose);border:0;color:#fff;padding:8px 14px;border-radius:8px;cursor:pointer;font-weight:600;">Send</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/pusher.min.js"></script>
<script>
const API = '/api/v1/bmydesk/agent';
const REVERB_KEY = @json($reverbKey);
let CODE = null, VTOKEN = null, channelName = null;
let pc = null, dc = null, channel = null, pusher = null, connected = false, streamTimeout = null;
const iceQueue = [];
const $ = id => document.getElementById(id);
const dbg = s => { const el = $('joinDbg'); el.textContent = (el.textContent + ' › ' + s).slice(-160); console.log('[guest]', s); };
const setBadge = (t, on) => { $('connBadge').textContent = t; $('connBadge').className = 'badge' + (on ? ' on' : ''); };

// Connection progress — the bar moves through the join stages so the client
// isn't staring at a dead "waiting…" screen.
let gPct = 0, gJoinedAt = 0;
function gStage(pct, title, sub) {
    gPct = Math.max(gPct, pct);
    const b = $('waitProg'); if (b) b.style.width = gPct + '%';
    const p = $('waitPct'); if (p) p.textContent = gPct + '%';
    if (title) $('waitTitle').textContent = title;
    if (sub !== undefined) $('waitSub').textContent = sub;
}
function gProgReset() {
    gPct = 0; gJoinedAt = 0;
    const b = $('waitProg'); if (b) b.style.width = '0%';
    const p = $('waitPct'); if (p) p.textContent = '0%';
    const w = $('waitProgWrap'); if (w) w.style.display = '';
}

const seenSig = new Set();
function sigNew(m) {
    const n = m && m.n; if (!n) return true;
    if (seenSig.has(n)) return false;
    seenSig.add(n); if (seenSig.size > 600) seenSig.delete(seenSig.values().next().value);
    return true;
}
const sigFresh = m => m.kind === 'ice' ? (!m.at || (Date.now() - Date.parse(m.at)) < 45000) : true; // only ICE ages out — state signals never drop
const nonce = () => Math.random().toString(36).slice(2) + Date.now().toString(36);
function postJson(url, body) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body || {}) }).then(r => r.json()).catch(() => ({}));
}
function whisper(evt, data) { try { channel.trigger('client-' + evt, data); } catch (e) {} }
    // strip SDP lines some libwebrtc/Chrome builds reject outright
    function cleanSdp(sdp) {
        const out = String(sdp || '').split(/\r?\n/)
            .filter(l => l && !/^a=max-message-size/.test(l))
            .map(l => { const m = l.match(/^a=sctp-port:(\d+)\s*$/); return m ? 'a=sctpmap:' + m[1] + ' webrtc-datachannel 1024' : l; })
            .join('\r\n');
        return out.endsWith('\r\n') ? out : out + '\r\n';
    }

function sendSignal(msg) { msg.n = nonce(); whisper('signal', msg); return postJson(API + '/' + CODE + '/signal', Object.assign({ agent_token: VTOKEN }, msg)); }
// chunked SDP fallback — some networks/proxies drop large POST bodies; small
// parts get reassembled server-side transparently.
async function sendOfferRobust(sdp) {
    const r = await sendSignal({ kind: 'offer', sdp });
    if (r && r.ok) { dbg('offer delivered'); return; }
    dbg('offer POST failed — chunked retry');
    const CH = 3000, parts = [];
    for (let i = 0; i < sdp.length; i += CH) parts.push(sdp.slice(i, i + CH));
    for (let i = 0; i < parts.length; i++) {
        const rr = await sendSignal({ kind: 'offer-part', i, total: parts.length, chunk: parts[i] });
        if (!(rr && rr.ok)) { dbg('chunk ' + i + ' failed'); return; }
    }
    dbg('offer sent in ' + parts.length + ' parts');
}

async function join() {
    const code = $('codeInput').value.trim().toUpperCase();
    if (!/^[A-Z0-9]{6,10}$/.test(code)) { $('joinErr').textContent = 'Enter the code shown on the remote device.'; return; }
    $('joinBtn').disabled = true; $('joinErr').textContent = '';
    dbg('joining ' + code);
    const r = await fetch(API + '/' + code + '/join', { method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name: $('nameInput').value.trim() || 'Guest', pin: $('pinInput').value.trim() || undefined }) }).catch(() => null);
    const d = r ? await r.json().catch(() => ({})) : {};
    if (!d.ok) {
        $('joinErr').textContent = d.error || 'Could not reach the device.';
        $('joinBtn').disabled = false;
        return;
    }
    saveRecent(code);
    if (d.auto_accepted) { setBadge('accepted'); setTimeout(() => { gStage(60, 'Accepted — starting stream…'); startPeer(); }, 400); }
    CODE = code; VTOKEN = d.viewer_token; channelName = d.channel;
    window.__ice = d.ice_servers || [{ urls: 'stun:stun.l.google.com:19302' }];
    $('hostLabel').textContent = d.host_label || 'Remote device';
    $('codeEcho').textContent = code;
    $('joinCard').classList.add('hidden'); $('stage').classList.remove('hidden');
    dbg('joined'); setBadge('requesting');
    gProgReset(); gJoinedAt = Date.now();
    gStage(15, 'Waiting for host approval…', 'The host will see your request.');
    initChannel();
    startViewerPoll();
}

function initChannel() {
    if (typeof Pusher === 'undefined') { dbg('ws lib missing'); return; }
    pusher = new Pusher(REVERB_KEY, {
        cluster: 'mt1', wsHost: 'believoo.com', wssPort: 443, forceTLS: true,
        enabledTransports: ['wss'],
        authorizer: (ch) => ({
            authorize: (socketId, cb) => {
                postJson(API + '/broadcast-auth', { socket_id: socketId, channel_name: ch.name, agent_token: VTOKEN })
                    .then(d => d.auth ? cb(null, d) : cb(new Error('auth failed'), null))
                    .catch(e => cb(e, null));
            }
        }),
    });
    pusher.connection.bind('state_change', s => dbg('ws:' + s.current));
    channel = pusher.subscribe(channelName);
    channel.bind('pusher:subscription_succeeded', () => {
        dbg('subscribed');
        gStage(45, 'Waiting for host approval…');
        whisper('join-request', { name: $('nameInput').value.trim() || 'Guest' });
        dbg('join-request sent');
    });
    channel.bind('pusher:subscription_error', () => { dbg('sub error (poll fallback active)'); gStage(Math.max(gPct, 30), 'Connecting over relay…'); });
    channel.bind('client-join-accept', m => { if (sigNew(m)) onAccept(); });
    channel.bind('client-join-reject', m => { if (sigNew(m)) onReject(m && m.reason); });
    channel.bind('client-signal', m => { if (sigNew(m)) onSignal(m); });
    channel.bind('client-end', m => { if (sigNew(m)) onEnded(); });
}

// HTTP fallback — drains queued signals so a dead ws still completes the session.
function startViewerPoll() {
    setInterval(async () => {
        try {
            // keep the waiting state alive — elapsed seconds tell the client
            // the host still has to press Accept.
            if (gPct && gPct < 60 && gJoinedAt)
                gStage(gPct, 'Waiting for host approval… (' + Math.round((Date.now() - gJoinedAt) / 1000) + 's)');
            const r = await fetch(API + '/' + CODE + '/signals?agent_token=' + encodeURIComponent(VTOKEN), { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            (d.signals || []).forEach(m => { if (sigNew(m) && sigFresh(m)) { dbg('poll:' + (m.kind || '?')); dispatchSignal(m); } });
            if (!pc) {
                const s = await fetch(API + '/' + CODE + '/status?agent_token=' + encodeURIComponent(VTOKEN), { headers: { 'Accept': 'application/json' } });
                const sd = await s.json();
                if (sd.ok && sd.status === 'rejected') onReject();
                else if (sd.ok && (sd.status === 'ended' || sd.status === 'waiting')) onEnded(sd.status === 'waiting');
            }
        } catch (e) {}
    }, 2000);
}
function dispatchSignal(m) {
    if (m.kind === 'accept') onAccept();
    else if (m.kind === 'reject') onReject(m.reason);
    else if (m.kind === 'end') onEnded();
    else onSignal(m);
}

function onAccept() { setBadge('accepted'); dbg('accepted'); gStage(60, 'Accepted — starting stream…'); startPeer(); }
function onReject(reason) {
    setBadge('rejected');
    $('waitTitle').textContent = 'Connection declined';
    $('waitSub').textContent = 'The host declined your request.';
    $('waitProgWrap').style.display = 'none';
    $('waitOverlay').classList.remove('hidden');
}
function onEnded(backToJoin) {
    setBadge('ended'); cleanup();
    if (backToJoin) {
        $('stage').classList.add('hidden'); $('joinCard').classList.remove('hidden');
        $('joinBtn').disabled = false; $('joinErr').textContent = 'Session ended or unavailable.';
    } else {
        $('waitTitle').textContent = 'Session ended'; $('waitSub').textContent = 'The host ended this session.';
        $('waitProgWrap').style.display = 'none';
        $('waitOverlay').classList.remove('hidden');
    }
}

async function startPeer() {
    if (pc) return;
    pc = new RTCPeerConnection({ iceServers: window.__ice });
    pc.ontrack = e => {
        dbg('video');
        gStage(100, 'Connected');
        $('remoteVideo').srcObject = e.streams[0];
        $('statOverlay').style.display = 'block';
        startStats();
        $('waitOverlay').classList.add('hidden');
        $('clickHint').style.display = 'block';
        setBadge('connected', true); connected = true;
        if (streamTimeout) clearTimeout(streamTimeout);
        startPing();
    };
    pc.onicecandidate = e => { if (e.candidate) sendSignal({ kind: 'ice', candidate: e.candidate }); };
    pc.onconnectionstatechange = () => {
        dbg('peer ' + pc.connectionState);
        if (pc.connectionState === 'connected') { gStage(92, 'Almost there…'); setBadge('connected', true); }
        if (pc.connectionState === 'failed' && pc) { setBadge('reconnecting'); try { pc.restartIce(); } catch (e) {} }
        if (['disconnected', 'closed'].includes(pc.connectionState)) setBadge('lost');
    };
    dc = pc.createDataChannel('input', { ordered: false, maxRetransmits: 0 });
    dc2 = pc.createDataChannel('ctl'); // reliable — chat/clipboard/file
    dc2.onopen = () => { ['fileBtn','chatBtn','shotBtn','recBtn','qualitySel','kbdBtn'].forEach(i => $(i).style.display = 'inline-block'); };
    dc2.onclose = () => { ['fileBtn','chatBtn','shotBtn','recBtn','qualitySel','kbdBtn','chatPanel'].forEach(i => $(i).style.display = 'none'); stopStats(); stopRec(); };
    dc2.onmessage = onCtlMessage;
    dc.onmessage = e => {
        try { const m = JSON.parse(e.data); if (m.t === 'pong') { $('latBadge').style.display = 'inline-block'; $('latVal').textContent = Math.round(performance.now() - m.ts); } } catch (x) {}
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
    setBadge('negotiating');
    gStage(72, 'Negotiating secure link…');
    streamTimeout = setTimeout(() => {
        if (!connected) {
            setBadge('no response');
            $('waitTitle').textContent = 'No stream from host';
            $('waitSub').textContent = 'The host accepted but no video arrived — ask them to reopen the agent.';
            $('waitOverlay').classList.remove('hidden');
        }
    }, 25000);
}

async function onSignal(m) {
    if (!pc) return;
    try {
        if (m.kind === 'answer') {
            dbg('answer');
            gStage(84, 'Answer received — connecting…');
            try { await pc.setRemoteDescription({ type: 'answer', sdp: m.sdp }); }
            catch (e) { await pc.setRemoteDescription({ type: 'answer', sdp: cleanSdp(m.sdp) }); }
            iceQueue.forEach(c => pc.addIceCandidate(c).catch(() => {})); iceQueue.length = 0;
        } else if (m.kind === 'ice' && m.candidate) {
            if (pc.remoteDescription) await pc.addIceCandidate(m.candidate).catch(() => {});
            else iceQueue.push(m.candidate);
        }
    } catch (e) { console.error(e); }
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
            else if (m.t === 'file-cancel') { rxFile = null; setProg(null); addChat('System', 'Transfer cancelled by peer', false); }
        } else if (rxFile) {
            rxFile.chunks.push(e.data); rxFile.got += e.data.byteLength;
            setProg('Receiving ' + rxFile.name + ' — ' + Math.round(rxFile.got * 100 / rxFile.size) + '%');
            if (rxFile.got >= rxFile.size) {
                const f = rxFile; rxFile = null;
                const url = URL.createObjectURL(new Blob(f.chunks));
                const a = document.createElement('a'); a.href = url; a.download = f.name; a.click();
                setTimeout(() => URL.revokeObjectURL(url), 5000);
                setProg(null);
                addChat('System', '↓ Saved ' + f.name + ' (' + Math.round(f.size / 1024) + ' KB)', false);
            }
        }
    } catch (err) {}
}
function addChat(from, text, mine) {
    const box = $('chatMsgs');
    const d = document.createElement('div');
    d.style.cssText = 'margin:4px 0;';
    d.innerHTML = `<span style="color:${mine ? '#22d3ee' : '#f43f5e'};font-weight:600;">${from}:</span> <span style="color:#cbd5e1;"></span>`;
    d.children[1].textContent = text;
    box.appendChild(d); box.scrollTop = box.scrollHeight;
}
$('chatSend').onclick = () => {
    const t = $('chatInput').value.trim(); if (!t) return;
    ctlSend({ t: 'chat', from: $('nameInput').value.trim() || 'Guest', text: t });
    addChat('Me', t, true); $('chatInput').value = '';
};
$('chatInput').addEventListener('keydown', e => { if (e.key === 'Enter') $('chatSend').click(); });
$('qualitySel').onchange = () => ctlSend({ t: 'quality', mode: $('qualitySel').value });

// ── Stats overlay (rtt/fps/bitrate) ──
let stTimer = null, stBytes = 0, stAt = 0;
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
            $('statOverlay').textContent = (progTxt ? progTxt + '  ·  ' : '') + rtt + 'ms · ' + fps + 'fps · ' + kbps + 'kbps' + (progTxt ? '  (tap = cancel)' : '');
        } catch (e) {}
    }, 2000);
}
function stopStats() { if (stTimer) clearInterval(stTimer); stTimer = null; stBytes = 0; stAt = 0; }

// ── Screenshot + session recording ──
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
    $('recBtn').style.color = '#f43f5e';
    $('recBtn').textContent = '⏹';
};
function stopRec() {
    if (rec && rec.state !== 'inactive') rec.stop();
    $('recBtn').style.color = ''; $('recBtn').textContent = '⏺';
}

$('chatBtn').onclick = () => { const p = $('chatPanel'); p.style.display = p.style.display === 'none' ? 'flex' : 'none'; };
$('fileBtn').onclick = () => $('fileInput').click();
let txAbort = false, txActive = false, progTxt = null;
function setProg(txt) { progTxt = txt; }
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
        if (txAbort) { ctlSend({ t: 'file-cancel' }); setProg(null); addChat('System', 'Transfer cancelled', false); txActive = false; return; }
        dc2.send(buf.slice(off, off + FILE_CHUNK));
        if (off % (256 * 1024) < FILE_CHUNK) { setProg('Sending ' + name + ' — ' + Math.round(off * 100 / buf.byteLength) + '%'); await new Promise(r => setTimeout(r, 0)); }
    }
    txActive = false; setProg(null);
    addChat('System', '↑ Sent ' + name + ' (' + Math.round(buf.byteLength / 1024) + ' KB)', false);
}
document.addEventListener('dragover', e => e.preventDefault());
document.addEventListener('drop', async e => {
    e.preventDefault();
    const f = e.dataTransfer?.files?.[0];
    if (!f || !dc2 || dc2.readyState !== 'open') return;
    await sendBuf(await f.arrayBuffer(), f.name);
});
// tapping the stats overlay while transferring cancels it
$('statOverlay').onclick = () => { if (rxFile) { rxFile = null; ctlSend({ t: 'file-cancel' }); setProg(null); addChat('System', 'Transfer cancelled', false); } else if (txActive) txAbort = true; };
// Ctrl+C on this page → clipboard text goes to the host
document.addEventListener('keydown', async e => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c' && dc2 && dc2.readyState === 'open') {
        try { const t = await navigator.clipboard.readText(); if (t) ctlSend({ t: 'clip', text: t }); } catch (x) {}
    }
});

function sendInput(o) { if (dc && dc.readyState === 'open') { try { dc.send(JSON.stringify(o)); } catch (e) {} } }
const video = $('remoteVideo');
let lastMove = 0;
video.addEventListener('mousemove', e => {
    const now = performance.now(); if (now - lastMove < 33) return; lastMove = now;
    const r = video.getBoundingClientRect();
    sendInput({ t: 'move', x: (e.clientX - r.left) / r.width, y: (e.clientY - r.top) / r.height });
});
video.addEventListener('mousedown', e => { e.preventDefault(); sendInput({ t: 'down', b: e.button }); });
video.addEventListener('mouseup', e => sendInput({ t: 'up', b: e.button }));
video.addEventListener('wheel', e => { e.preventDefault(); sendInput({ t: 'wheel', dx: e.deltaX, dy: e.deltaY }); }, { passive: false });
video.addEventListener('keydown', e => { e.preventDefault(); sendInput({ t: 'key', k: e.key, code: e.code, down: true, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });
video.addEventListener('keyup', e => { e.preventDefault(); sendInput({ t: 'key', k: e.key, code: e.code, down: false, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });
let touchMoved = false, touchStartMs = 0, twoFinger = false, lastPinchY = 0, lpTimer = null;
const tpos = t => { const r = video.getBoundingClientRect(); return { x: (t.clientX - r.left) / r.width, y: (t.clientY - r.top) / r.height }; };
video.addEventListener('touchstart', e => {
    e.preventDefault();
    if (e.touches.length === 2) {
        twoFinger = true; touchMoved = true; lastPinchY = e.touches[0].clientY;
        if (lpTimer) { clearTimeout(lpTimer); lpTimer = null; }
        return; // two-finger → scroll; tap later → right-click
    }
    twoFinger = false; touchMoved = false; touchStartMs = performance.now();
    // long-press (600ms, no move) → right click
    const p0 = tpos(e.touches[0]);
    lpTimer = setTimeout(() => {
        if (!touchMoved) {
            sendInput({ t: 'move', x: p0.x, y: p0.y }); sendInput({ t: 'down', b: 2 });
            setTimeout(() => sendInput({ t: 'up', b: 2 }), 60);
            lpTimer = 'fired'; if (navigator.vibrate) navigator.vibrate(25);
        }
    }, 600);
}, { passive: false });
video.addEventListener('touchmove', e => {
    if (e.touches.length === 2) { // two-finger drag → scroll
        const y = e.touches[0].clientY, dy = lastPinchY - y; lastPinchY = y;
        sendInput({ t: 'wheel', dx: 0, dy: dy * 3 }); return;
    }
    if (lpTimer && lpTimer !== 'fired' && !touchMoved) { clearTimeout(lpTimer); lpTimer = null; }
    if (lpTimer === 'fired') return;
    touchMoved = true; const p = tpos(e.touches[0]);
    const now = performance.now(); if (now - lastMove < 33) return; lastMove = now;
    sendInput({ t: 'move', x: p.x, y: p.y });
}, { passive: true });
video.addEventListener('touchend', e => {
    if (lpTimer && lpTimer !== 'fired') { clearTimeout(lpTimer); lpTimer = null; }
    if (twoFinger) {
        twoFinger = false;
        if (!touchMoved || performance.now() - touchStartMs < 250) { /* was scroll */ }
        return;
    }
    if (lpTimer === 'fired') { lpTimer = null; return; }
    if (!touchMoved && e.changedTouches.length) {
        const p = tpos(e.changedTouches[0]);
        sendInput({ t: 'move', x: p.x, y: p.y }); sendInput({ t: 'down', b: 0 });
        setTimeout(() => sendInput({ t: 'up', b: 0 }), 60);
    }
}, { passive: true });
// soft-keyboard button → hidden input focuses → mobile keyboard opens → keys forwarded
document.getElementById('kbdBtn')?.addEventListener('click', () => {
    const i = document.getElementById('mobKbd');
    i.style.display = 'block'; i.focus();
    i.addEventListener('blur', () => { i.style.display = 'none'; }, { once: true });
});
document.getElementById('mobKbd')?.addEventListener('keydown', e => {
    e.preventDefault();
    sendInput({ t: 'key', k: e.key, code: e.code, down: true, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey });
    if (e.key === 'Backspace') sendInput({ t: 'key', k: e.key, code: e.code, down: false });
});
document.getElementById('mobKbd')?.addEventListener('keyup', e => {
    if (e.key !== 'Backspace') sendInput({ t: 'key', k: e.key, code: e.code, down: false, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey });
});
video.addEventListener('contextmenu', e => e.preventDefault());
video.addEventListener('click', () => { video.focus(); $('clickHint').style.display = 'none'; });

function startPing() {
    setInterval(() => { if (dc && dc.readyState === 'open') dc.send(JSON.stringify({ t: 'ping', ts: performance.now() })); }, 3000);
}
function cleanup() { try { if (pc) pc.close(); } catch (e) {} pc = null; connected = false; }
$('joinBtn').addEventListener('click', join);
$('codeInput').addEventListener('keydown', e => { if (e.key === 'Enter') join(); });
$('endBtn').addEventListener('click', async () => {
    sendSignal({ kind: 'end' }); cleanup();
    $('stage').classList.add('hidden'); $('joinCard').classList.remove('hidden'); $('joinBtn').disabled = false;
});
window.addEventListener('beforeunload', () => {
    try { fetch(API + '/' + CODE + '/signal', { method: 'POST', keepalive: true, headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ kind: 'end', agent_token: VTOKEN, n: nonce() }) }); } catch (e) {}
});
// Saved devices (guest address book) — labels + one-tap connect, browser-local.
function recentCodes() { try { return JSON.parse(localStorage.getItem('bmydesk_recent') || '[]'); } catch (e) { return []; } }
function saveRecent(code, label) {
    const list = recentCodes().filter(c => c.code !== code);
    list.unshift({ code, label: label || (recentCodes().find(c => c.code === code) || {}).label || '', at: Date.now() });
    localStorage.setItem('bmydesk_recent', JSON.stringify(list.slice(0, 10)));
}
function renderRecent() {
    const list = recentCodes(), box = $('recentRow');
    if (!list.length) { box.style.display = 'none'; return; }
    box.style.display = 'block';
    box.innerHTML = '<span style="color:#475569;font-size:11px;">Devices: </span>' + list.map(c =>
        `<a href="#" data-c="${c.code}" style="color:#22d3ee;font-size:11px;font-family:monospace;margin:0 5px;text-decoration:none;">${c.label ? c.label + ' · ' : ''}${c.code}</a>`).join('')
        + ` <a href="#" id="saveDevLink" style="color:#475569;font-size:11px;text-decoration:none;margin-left:4px;">☆ name &amp; save</a>`;
    box.querySelectorAll('a[data-c]').forEach(a => a.onclick = e => { e.preventDefault(); $('codeInput').value = a.dataset.c; });
    const sl = $('saveDevLink');
    if (sl) sl.onclick = e => {
        e.preventDefault();
        const code = $('codeInput').value.trim().toUpperCase();
        if (code.length < 6) { $('codeInput').focus(); return; }
        const label = prompt('Name this device:', '') || '';
        saveRecent(code, label);
        renderRecent();
    };
}
renderRecent();
if ($('codeInput').value) dbg('code prefilled — press Connect');
</script>
</body>
</html>
