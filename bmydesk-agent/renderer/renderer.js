/* BMyDesk Agent — renderer
 * Registers a session code, listens for viewer join requests over Reverb
 * (Pusher protocol), then streams the screen over P2P WebRTC and forwards
 * incoming input events to the Electron main process for OS injection.
 */

const APP_VERSION = '1.1.0';
const API = 'https://bmydesk.believoo.com/api/v1/bmydesk/agent';
const REVERB_KEY = 'zenjc9spcwqz8nzdzvtn'; // public app key (safe — auth is server-side)
const REVERB_HOST = 'believoo.com';
const REVERB_PORT = 443;

const $ = (id) => document.getElementById(id);
// ICE servers come from /register (server issues time-limited TURN creds)
let pcConfig = { iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
]};

let session = null;      // {session_code, agent_token, channel}
let pusher = null, ch = null, pc = null, stream = null, dcIn = null;
let iceQueue = [];

function setStatus(text, mode = 'wait') {
    $('statusText').textContent = text;
    $('dot').className = 'dot' + (mode === 'on' ? ' online' : mode === 'off' ? ' off' : '');
}

async function register() {
    try { if (pusher) { pusher.disconnect(); pusher = null; ch = null; } } catch (e) {}
    try {
        const r = await fetch(API + '/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ host_name: require_os_name(), version: APP_VERSION, os: window.agent?.platform || 'unknown', member_token: localStorage.getItem('bmydesk_member_token') || undefined, device_id: deviceId() }),
        });
        const data = await r.json();
        if (!data.ok) throw new Error(data.error || 'register failed');
        session = data;
        if (Array.isArray(data.ice_servers) && data.ice_servers.length) {
            pcConfig = { iceServers: data.ice_servers };
        }
        $('code').textContent = data.session_code;
        setStatus('Connecting realtime…', 'wait');
        try { connectChannel(); }
        catch (e) { console.error('channel setup failed:', e); setStatus('Realtime failed: ' + (e.message || e), 'off'); }
    } catch (e) {
        console.error('register failed:', e);
        setStatus('Register failed: ' + (e.message || 'network'), 'off');
        if (!session) $('code').textContent = 'ERROR';
        setTimeout(register, 5000);
    }
}

function checkUpdate() {
    fetch(API + '/version?platform=windows')
        .then(r => r.json())
        .then(d => {
            if (d.ok && d.latest && d.latest !== APP_VERSION) {
                const b = $('updateBanner');
                $('updateText').textContent = 'Update available — v' + d.latest;
                b.classList.remove('hidden');
                b.onclick = () => window.agent.openExternal(d.url || 'https://bmydesk.believoo.com/remote/agent');
            }
        })
        .catch(() => {});
}

// electron-updater events — silent background download, banner prompts the
// restart when ready. Falls back to the banner link above if the updater
// isn't wired (dev builds).
if (window.agent?.onUpdate) {
    window.agent.onUpdate((d) => {
        const b = $('updateBanner');
        if (d.evt === 'available') {
            $('updateText').textContent = 'Downloading update v' + d.version + '…';
            b.classList.remove('hidden'); b.onclick = null;
        } else if (d.evt === 'progress') {
            $('updateText').textContent = 'Downloading update… ' + d.percent + '%';
            b.classList.remove('hidden'); b.onclick = null;
        } else if (d.evt === 'downloaded') {
            $('updateText').textContent = 'v' + d.version + ' ready — click to restart & update';
            b.classList.remove('hidden');
            b.onclick = () => window.agent.installUpdate();
        }
    });
}

function require_os_name() {
    return (window.agent?.platform || 'pc') + '-' + (navigator.userAgent.match(/Windows|Mac|Linux/)?.[0] || 'host');
}

// Stable device identity — generated once, kept forever. The server maps it
// to ONE session code, so this device's code never changes on its own
// (same model as an AnyDesk ID).
function deviceId() {
    let id = localStorage.getItem('bmydesk_device_id');
    if (!id) {
        id = (crypto.randomUUID ? crypto.randomUUID() : 'dev-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 12));
        localStorage.setItem('bmydesk_device_id', id);
    }
    return id;
}

// One ws connection multiplexes all channel subscriptions (host + viewer).
// channelTokens maps each channel name to the token that authorizes it —
// host channels use agent_token, joined sessions use their viewer_token.
const channelTokens = {};

function hdbg(step) {
    const el = $('hostDebug');
    if (el) el.textContent = (el.textContent + ' › ' + step).slice(-120);
}

function getPusher() {
    if (pusher) return pusher;
    pusher = new Pusher(REVERB_KEY, {
        cluster: 'mt1', // pusher-js requires a cluster even when wsHost overrides it
        wsHost: REVERB_HOST,
        wssPort: REVERB_PORT,
        forceTLS: true,
        enabledTransports: ['wss'],
        // custom authorizer — POSTs socket_id+channel_name to our API
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                hdbg('auth ' + channel.name.split('.').pop());
                const ctl = new AbortController();
                const t = setTimeout(() => ctl.abort(), 10000);
                fetch(API + '/broadcast-auth', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ socket_id: socketId, channel_name: channel.name, agent_token: channelTokens[channel.name] || '' }),
                    signal: ctl.signal,
                })
                    .then(r => r.json())
                    .then(d => { clearTimeout(t); d.auth ? (hdbg('auth ok'), callback(null, d)) : (hdbg('auth fail ' + (d.error || '')), callback(new Error(d.error || 'auth failed'), null)); })
                    .catch(e => { clearTimeout(t); hdbg('auth err ' + (e.name === 'AbortError' ? 'timeout' : e.message || 'net')); callback(e, null); });
            },
        }),
    });
    pusher.connection.bind('state_change', (s) => hdbg('ws:' + s.current));
    pusher.connection.bind('error', (e) => hdbg('wserr:' + (e?.error?.data?.code || e?.type || 'net')));
    return pusher;
}

let chRetryTimer = null, subAttempts = 0, statusPoller = null;
let respondedAt = 0; // ms — polled join-requests older than this aren't re-shown
let vChanName = null, vChanToken = null;        // live viewer channel survives reconnects

// ── Signal transport ────────────────────────────────────────────────────
// Every signal is POSTed to /signal: the server broadcasts it to ws peers
// AND queues it for the other side's HTTP poll. A shared nonce dedupes the
// double delivery, so it works no matter whose socket is alive.
const seenSig = new Set();
function sigNew(m) {
    const n = m && m.n;
    if (!n) return true; // ws whisper without nonce — always process
    if (seenSig.has(n)) return false;
    seenSig.add(n);
    if (seenSig.size > 600) seenSig.delete(seenSig.values().next().value);
    return true;
}
// queued signals get a server timestamp — drop stale ones so a leftover
// "end"/"offer" from a dead pairing can't kill a fresh session.
const sigFresh = (m) => !m.at || (Date.now() - Date.parse(m.at)) < 45000;
const nonce = () => Math.random().toString(36).slice(2) + Date.now().toString(36);
function postSignal(code, token, msg) {
    msg.n = nonce();
    fetch(`${API}/${code}/signal`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(msg),
    }).catch(() => {});
}
const hostSignal = (m) => session && postSignal(session.session_code, session.agent_token, m);
const viewerSignal = (m) => vJoinedCode && postSignal(vJoinedCode, vChanToken, m);

// ws signaling is the primary path; if it keeps failing we still surface join
// requests by polling /status — accept then rides the ws once it recovers.
function startStatusPoll() {
    if (statusPoller || !session) return;
    hdbg('poll fallback on');
    setStatus('Ready — share your code (slow link)', 'wait');
    statusPoller = setInterval(async () => {
        if (!session) return;
        try {
            const r = await fetch(`${API}/${session.session_code}/status`, {
                headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
            });
            const d = await r.json();
            const joinMs = d.ok && d.viewer_joined_at ? Date.parse(d.viewer_joined_at) : 0;
            if (joinMs && joinMs > respondedAt && (Date.now() - joinMs < 120000)
                && $('reqBox').classList.contains('hidden') && !pc) {
                $('reqName').textContent = d.viewer || 'Someone';
                $('reqBox').classList.remove('hidden');
                setStatus('Connection request…', 'wait');
            }
            // drain queued signals — this is the full ws-free signaling path
            const sr = await fetch(`${API}/${session.session_code}/signals`, {
                headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
            });
            const sd = await sr.json();
            (sd.signals || []).forEach((m) => { if (sigNew(m) && sigFresh(m)) dispatchHostSignal(m); });
        } catch (e) {}
    }, 3000);
}

function connectChannel() {
    if (typeof Pusher === 'undefined') {
        setStatus('Realtime lib missing — reinstall the agent', 'off');
        startStatusPoll();
        return;
    }
    channelTokens[session.channel] = session.agent_token;
    ch = getPusher().subscribe(session.channel);
    hdbg('subscribing ' + session.channel.split('.').pop());
    startStatusPoll(); // always on — drains the signal queue + heartbeats the host

    // watchdog — if subscribe doesn't complete, rebuild the whole connection.
    // (pusher-js reinstates a cancelled pending channel WITHOUT resending the
    //  subscribe frame — unsub+resub on the same instance is a no-op, so we
    //  must drop the socket entirely.)
    if (chRetryTimer) clearTimeout(chRetryTimer);
    chRetryTimer = setTimeout(() => {
        if (!ch.subscribed) {
            subAttempts++;
            hdbg('sub timeout — fresh reconnect ' + subAttempts);
            try { pusher.disconnect(); } catch (e) {}
            pusher = null; ch = null;
            connectChannel();
        }
    }, 15000);

    ch.bind('pusher:subscription_succeeded', () => {
        clearTimeout(chRetryTimer); subAttempts = 0;
        hdbg('subscribed'); setStatus('Ready — share your code', 'on');
        // fresh socket — restore a live viewer session if one was open
        if (vChanName && vChanToken && !vCh?.subscribed) {
            channelTokens[vChanName] = vChanToken;
            const nc = pusher.subscribe(vChanName);
            bindViewerChannel(nc);
        }
    });
    ch.bind('pusher:subscription_error', (e) => {
        clearTimeout(chRetryTimer); subAttempts++;
        hdbg('sub err — fresh reconnect ' + subAttempts);
        setStatus('Channel auth failed — retrying…', 'off');
        try { pusher.disconnect(); } catch (err) {}
        pusher = null; ch = null;
        setTimeout(connectChannel, 5000);
    });

    ch.bind('client-join-request', (m) => {
        $('reqName').textContent = m.name || 'Someone';
        $('reqBox').classList.remove('hidden');
        setStatus('Connection request…', 'wait');
        try { new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=').play().catch(()=>{}); } catch(e){}
    });

    ch.bind('client-signal', (m) => { if (sigNew(m)) dispatchHostSignal(m); });
    ch.bind('client-end', (m) => { if (sigNew(m)) teardown('Viewer disconnected'); });
}

// one dispatcher for host signals — same handling whether they arrive by
// ws broadcast or the HTTP fallback queue.
function dispatchHostSignal(m) {
    if (m.kind === 'offer') handleOffer(m);
    else if (m.kind === 'ice' && m.candidate) {
        if (pc && pc.remoteDescription) pc.addIceCandidate(m.candidate).catch(() => {});
        else iceQueue.push(m.candidate);
    } else if (m.kind === 'end') teardown('Viewer disconnected');
}

async function pickScreen() {
    const sources = await window.agent.getScreenSources();
    const box = $('screenPick');
    if (sources.length <= 1) return sources[0]?.id;
    box.innerHTML = '';
    box.classList.remove('hidden');
    return await new Promise((resolve) => {
        sources.forEach((s) => {
            const b = document.createElement('button');
            b.innerHTML = `<img src="${s.thumbnail}"><span>${s.name}</span>`;
            b.onclick = () => { box.classList.add('hidden'); resolve(s.id); };
            box.appendChild(b);
        });
    });
}

async function captureScreen(sourceId) {
    try { await window.agent.selectScreenSource(sourceId); } catch (e) {}
    const attempts = [
        // modern path — setDisplayMediaRequestHandler in main supplies the source
        () => navigator.mediaDevices.getDisplayMedia({ video: { frameRate: { ideal: 30, max: 30 } }, audio: false }),
        // retry without constraints — some drivers reject frameRate
        () => navigator.mediaDevices.getDisplayMedia({ video: true, audio: false }),
        // legacy desktop-capture path (older Electron)
        () => navigator.mediaDevices.getUserMedia({
            audio: false,
            video: { mandatory: { chromeMediaSource: 'desktop', chromeMediaSourceId: sourceId, maxWidth: 1920, maxHeight: 1080, maxFrameRate: 30 } },
        }),
    ];
    let lastErr = null;
    for (const fn of attempts) {
        try { stream = await fn(); lastErr = null; break; }
        catch (e) { lastErr = e; hdbg('capture try: ' + (e.name || '?') + ' ' + (e.message || '').slice(0, 60)); }
    }
    if (lastErr) throw lastErr;
    stream.getVideoTracks()[0].contentHint = 'detail';
}

let offering = false;
async function handleOffer(m) {
    if (pc) {
        // ICE restart / renegotiation from a reconnecting viewer — reuse the
        // existing peer (and stream) instead of starting over.
        try {
            await pc.setRemoteDescription({ type: 'offer', sdp: m.sdp });
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            hostSignal({ kind: 'answer', sdp: pc.localDescription.sdp });
            hdbg('re-answer sent');
        } catch (e) { console.error('re-offer failed', e); }
        return;
    }
    if (offering) return; // dup offer (ws + queue) — ignore
    offering = true;
    try {
        const sourceId = await pickScreen();
        if (!sourceId) { hdbg('no screen sources found'); setStatus('No screen to share', 'off'); hostSignal({ kind: 'end' }); offering = false; return; }
        await captureScreen(sourceId);

        pc = new RTCPeerConnection(pcConfig);
        pc.onicecandidate = (e) => { if (e.candidate) hostSignal({ kind: 'ice', candidate: e.candidate }); };
        pc.onconnectionstatechange = () => {
            if (pc.connectionState === 'connected') setStatus('Viewer connected — streaming', 'on');
            if (pc.connectionState === 'disconnected') setStatus('Viewer connection unstable…', 'wait');
            if (['failed', 'closed'].includes(pc.connectionState)) { teardown('Viewer lost — ready for new connection'); }
        };
        pc.ondatachannel = (e) => {
            dcIn = e.channel;
            dcIn.onmessage = (ev) => {
                try {
                    const msg = JSON.parse(ev.data);
                    if (msg.t === 'ping') { dcIn.send(JSON.stringify({ t: 'pong', ts: msg.ts })); return; }
                    window.agent.sendInput(msg); // inject into host OS
                } catch (err) {}
            };
        };

        await pc.setRemoteDescription({ type: 'offer', sdp: m.sdp });
        stream.getTracks().forEach(t => pc.addTrack(t, stream));
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        hostSignal({ kind: 'answer', sdp: pc.localDescription.sdp });
        hdbg('answer sent');
        iceQueue.forEach(c => pc.addIceCandidate(c).catch(() => {}));
        iceQueue = [];
        $('endBtn').classList.remove('hidden');
        setStatus('Streaming screen…', 'on');
    } catch (e) {
        console.error(e);
        setStatus('Could not capture screen', 'off');
        hdbg('CAPTURE FAIL: ' + (e.name || '?') + ' — ' + (e.message || 'unknown'));
        hostSignal({ kind: 'end' });
    }
    offering = false;
}

function teardown(msg) {
    if (pc) { try { pc.close(); } catch (e) {} pc = null; }
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    $('reqBox').classList.add('hidden');
    $('endBtn').classList.add('hidden');
    setStatus(msg || 'Ready — share your code', 'wait');
}

function respond(action) {
    // Always POST — the server broadcasts to ws peers AND queues it for the
    // viewer's HTTP poll, so it lands no matter whose socket is alive.
    if (!session) return;
    fetch(`${API}/${session.session_code}/respond`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
        body: JSON.stringify({ action }),
    }).catch(() => {});
}
$('acceptBtn').onclick = () => { $('reqBox').classList.add('hidden'); respondedAt = Date.now(); respond('accept'); setStatus('Accepted — waiting for stream…', 'on'); };
$('rejectBtn').onclick = () => { $('reqBox').classList.add('hidden'); respondedAt = Date.now(); respond('reject'); setStatus('Request rejected', 'wait'); };
$('endBtn').onclick = async () => {
    hostSignal({ kind: 'end' });
    try { await fetch(API + '/' + session.session_code + '/end', { method: 'POST', headers: { 'Authorization': 'Bearer ' + session.agent_token } }); } catch (e) {}
    teardown('Session ended');
};

window.addEventListener('beforeunload', () => {
    try { if (session) hostSignal({ kind: 'end' }); } catch (e) {}
    try { if (vJoinedCode) viewerSignal({ kind: 'end' }); } catch (e) {}
    try { if (session) navigator.sendBeacon(API + '/' + session.session_code + '/end', new Blob([JSON.stringify({agent_token: session.agent_token})], {type:'application/json'})); } catch (e) {}
});

// ═══════════ VIEWER MODE — connect to a partner's code (Remote Desk) ═══════════
let vCh = null, vPc = null, vDc = null, vJoinedCode = null;

function vStatus(t) { $('viewerStatus').textContent = t; }
function vDbg(t) { const el = $('viewerDebug'); if (el) { el.textContent = (el.textContent ? el.textContent + ' › ' : '') + t; } }
function vDbgReset() { const el = $('viewerDebug'); if (el) el.textContent = ''; }

async function connectToPartner() {
    const code = $('remoteCode').value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (code.length < 6) { $('remoteCode').focus(); return; }
    $('connectBtn').disabled = true;

    $('viewerPane').classList.remove('hidden');
    $('viewerOverlay').classList.remove('hidden');
    window.agent.setViewMode(true);
    vStatus('Joining ' + code + '…');

    try {
        const r = await fetch(API + '/' + code + '/join', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: localStorage.getItem('bmydesk_member_name') || require_os_name(),
                pin: ($('remotePin') && $('remotePin').value.trim()) || undefined,
            }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.error || 'join failed');
        saveRecent(code);
        if (d.auto_accepted) vAutoAccepted = true;
        vJoinedCode = code;
        const vToken = d.viewer_token;
        if (Array.isArray(d.ice_servers) && d.ice_servers.length) pcConfig = { iceServers: d.ice_servers };

        // Reuse the single ws connection — a second socket can hang on
        // restrictive networks/proxies; multiplexing avoids that entirely.
        vChanName = 'private-remote-agent.' + code;
        vChanToken = vToken;
        channelTokens[vChanName] = vToken;
        const vp = getPusher();
        vCh = vp.subscribe(vChanName);

        vStatus('Subscribing…');
        vDbgReset(); vDbg('subscribing');
        vp.connection.bind('state_change', (s) => vDbg('ws:' + s.current));
        const subTimeout = setTimeout(() => {
            vStatus('Relay mode — waiting for host approval…');
            vDbg('ws timeout — http fallback');
            startViewerPoll();
        }, 20000);
        bindViewerChannel(vCh, subTimeout);
    } catch (e) {
        vStatus('Join failed: ' + (e.message || e));
        exitViewer(3000);
    } finally {
        $('connectBtn').disabled = false;
    }
}

let vAutoAccepted = false;
let vPoller = null;
function startViewerPoll() {
    if (vPoller || !vJoinedCode) return;
    vDbg('poll on');
    vPoller = setInterval(async () => {
        if (!vJoinedCode) return;
        try {
            const r = await fetch(`${API}/${vJoinedCode}/signals`, {
                headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + vChanToken },
            });
            const d = await r.json();
            (d.signals || []).forEach((m) => { if (sigNew(m) && sigFresh(m)) dispatchViewerSignal(m); });
            // queued accept may be missed mid-flight — status poll is the backup
            const st = await fetch(`${API}/${vJoinedCode}/status`, {
                headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + vChanToken },
            });
            const sd = await st.json();
            if (sd.ok && sd.status === 'rejected') { vStatus('Host declined the request'); exitViewer(2500); }
            else if (sd.ok && sd.status === 'ended') { vStatus('Session ended by host'); exitViewer(2000); }
            else if (sd.ok && sd.status === 'active' && vAutoAccepted && !vPc) { vDbg('auto-accepted'); startViewerPeer(); }
        } catch (e) {}
    }, 2000);
}
function stopViewerPoll() { if (vPoller) { clearInterval(vPoller); vPoller = null; } }

function dispatchViewerSignal(m) {
    if (m.kind === 'accept') { vDbg('host accepted'); startViewerPeer(); }
    else if (m.kind === 'reject') { vStatus('Host declined the request'); exitViewer(2500); }
    else if (m.kind === 'end') { vStatus('Session ended by host'); exitViewer(2000); }
    else if (m.kind === 'answer' && vPc) { vDbg('answer received'); vPc.setRemoteDescription({ type: 'answer', sdp: m.sdp }).catch(() => {}); }
    else if (m.kind === 'ice' && vPc && m.candidate) vPc.addIceCandidate(m.candidate).catch(() => {});
}

function bindViewerChannel(vc, subTimeout) {
    vc.bind('pusher:subscription_succeeded', () => {
        if (subTimeout) clearTimeout(subTimeout);
        vDbg('subscribed');
        vStatus('Waiting for host approval…');
        vc.trigger('client-join-request', { name: (localStorage.getItem('bmydesk_member_name') || require_os_name()) + ' (agent)' });
        vDbg('join-request sent');
        // keep the HTTP poll as a safety net even when ws is alive — signals
        // sent by an HTTP-only host still land via the queue.
        startViewerPoll();
    });
    vc.bind('pusher:subscription_error', () => { if (subTimeout) clearTimeout(subTimeout); vDbg('sub err — http fallback'); vStatus('Connecting over relay…'); startViewerPoll(); });
    vc.bind('client-join-accept', (m) => { if (sigNew(m)) dispatchViewerSignal({ kind: 'accept' }); });
    vc.bind('client-join-reject', (m) => { if (sigNew(m)) dispatchViewerSignal({ kind: 'reject' }); });
    vc.bind('client-signal', (m) => { if (sigNew(m)) dispatchViewerSignal(m); });
    vc.bind('client-end', (m) => { if (sigNew(m)) dispatchViewerSignal({ kind: 'end' }); });
}

async function startViewerPeer() {
    if (vPc) return; // dedupe — accept may arrive via ws + queue both
    vStatus('Accepted — starting stream…');
    vPc = new RTCPeerConnection(pcConfig);
    vDc = vPc.createDataChannel('input', { ordered: false, maxRetransmits: 0 });

    vPc.ontrack = (e) => {
        $('remoteVideo').srcObject = e.streams[0];
        $('viewerOverlay').classList.add('hidden');
        vStatus('Connected — move & click to control');
    };
    vPc.onicecandidate = (e) => { if (e.candidate) viewerSignal({ kind: 'ice', candidate: e.candidate }); };
    vPc.onconnectionstatechange = () => {
        vDbg('rtc:' + vPc.connectionState);
        if (vPc.connectionState === 'connected') { vStatus('Connected'); vReconnectTries = 0; }
        if (vPc.connectionState === 'failed') {
            if (vReconnectTries++ < 3) { vStatus('Reconnecting…'); viewerReoffer(); }
            else vStatus('Connection failed — retry');
        }
        if (['disconnected', 'closed'].includes(vPc.connectionState)) vStatus('Disconnected');
    };

    const offer = await vPc.createOffer({ offerToReceiveVideo: true, offerToReceiveAudio: true });
    await vPc.setLocalDescription(offer);
    viewerSignal({ kind: 'offer', sdp: vPc.localDescription.sdp });
    vDbg('offer sent');
}

// Auto-reconnect: on 'failed' re-offer with ICE restart — the session survives
// a network hiccup without re-joining or a new host approval.
let vReconnectTries = 0;
async function viewerReoffer() {
    if (!vPc) return;
    try {
        const offer = await vPc.createOffer({ iceRestart: true });
        await vPc.setLocalDescription(offer);
        viewerSignal({ kind: 'offer', sdp: vPc.localDescription.sdp });
        vDbg('re-offer sent');
    } catch (e) { console.error('reoffer', e); }
}

// Input capture on the remote video → host's DataChannel handler
function bindViewerInput() {
    const v = $('remoteVideo');
    const send = (o) => { if (vDc && vDc.readyState === 'open') { try { vDc.send(JSON.stringify(o)); } catch (e) {} } };
    let last = 0;
    v.addEventListener('mousemove', (e) => {
        const n = performance.now(); if (n - last < 33) return; last = n;
        const r = v.getBoundingClientRect();
        send({ t: 'move', x: (e.clientX - r.left) / r.width, y: (e.clientY - r.top) / r.height });
    });
    v.addEventListener('mousedown', (e) => { e.preventDefault(); send({ t: 'down', b: e.button }); });
    v.addEventListener('mouseup', (e) => send({ t: 'up', b: e.button }));
    v.addEventListener('wheel', (e) => { e.preventDefault(); send({ t: 'wheel', dx: e.deltaX, dy: e.deltaY }); }, { passive: false });
    v.addEventListener('contextmenu', (e) => e.preventDefault());
    v.tabIndex = 0;
    v.addEventListener('keydown', (e) => { e.preventDefault(); send({ t: 'key', k: e.key, code: e.code, down: true, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });
    v.addEventListener('keyup', (e) => { e.preventDefault(); send({ t: 'key', k: e.key, code: e.code, down: false, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });
    v.addEventListener('click', () => v.focus());
}

function exitViewer(delay = 0) {
    const doIt = () => {
        try { viewerSignal({ kind: 'end' }); } catch (e) {}
        stopViewerPoll();
        if (vPc) { try { vPc.close(); } catch (e) {} vPc = null; }
        vDc = null; vCh = null; vJoinedCode = null; vChanName = null; vChanToken = null;
        $('viewerPane').classList.add('hidden');
        $('remoteVideo').srcObject = null;
        window.agent.setViewMode(false);
    };
    delay ? setTimeout(doIt, delay) : doIt();
}

$('connectBtn').addEventListener('click', connectToPartner);
$('remoteCode').addEventListener('keydown', (e) => { if (e.key === 'Enter') connectToPartner(); });
$('viewerEnd').addEventListener('click', () => exitViewer());
bindViewerInput();

// ═══════════ Theme toggle + login ═══════════
const themeBtn = $('themeBtn');
function applyTheme(t) { document.body.classList.toggle('light', t === 'light'); themeBtn.textContent = t === 'light' ? '☀' : '☾'; }
themeBtn.onclick = () => { const t = document.body.classList.contains('light') ? 'dark' : 'light'; localStorage.setItem('bmydesk_theme', t); applyTheme(t); };
applyTheme(localStorage.getItem('bmydesk_theme') || 'dark');

// ── Recent devices — saved locally, one-tap reconnect ──────────
function recentCodes() {
    try { return JSON.parse(localStorage.getItem('bmydesk_recent') || '[]'); } catch (e) { return []; }
}
function saveRecent(code) {
    const list = recentCodes().filter(c => c.code !== code);
    list.unshift({ code, at: Date.now() });
    localStorage.setItem('bmydesk_recent', JSON.stringify(list.slice(0, 6)));
    renderRecent();
}
function renderRecent() {
    const box = $('recentRow');
    const list = recentCodes();
    if (!list.length) { box.classList.add('hidden'); return; }
    box.classList.remove('hidden');
    box.innerHTML = '<span style="font-size:10px;color:#475569;">Recent: </span>' + list.map(c =>
        `<a href="#" data-code="${c.code}" style="font-size:10px;color:#22d3ee;text-decoration:none;margin-right:10px;font-family:monospace;">${c.code}</a>`).join('');
    box.querySelectorAll('a').forEach(a => a.onclick = (e) => {
        e.preventDefault();
        $('remoteCode').value = a.dataset.code;
    });
}
renderRecent();

// Unattended access — set a device PIN; viewers with code+PIN connect
// without approval. PIN is stored server-side keyed by device_id.
$('pinLink').onclick = async (e) => {
    e.preventDefault();
    if (!session) return;
    const pin = prompt('Set unattended-access PIN (4-12 digits).\nLeave empty to REMOVE unattended access:', '');
    if (pin === null) return;
    if (pin !== '' && !/^[0-9]{4,12}$/.test(pin)) { setStatus('PIN must be 4-12 digits', 'off'); return; }
    try {
        const r = await fetch(`${API}/${session.session_code}/set-pin`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
            body: JSON.stringify({ pin }),
        });
        const d = await r.json();
        if (d.ok) setStatus(d.pin_set ? 'Unattended access ON' : 'Unattended access OFF', d.pin_set ? 'on' : 'wait');
        else setStatus(d.error || 'PIN failed', 'off');
    } catch (err) { setStatus('PIN update failed', 'off'); }
};

// Manual "new code" — clears the device identity so the next register mints
// a fresh code. Without this, the code stays permanent (AnyDesk-style).
$('newCodeLink').onclick = (e) => {
    e.preventDefault();
    localStorage.removeItem('bmydesk_device_id');
    session = null;
    setStatus('Registering…', 'wait');
    register();
};

const savedName = localStorage.getItem('bmydesk_member_name');
if (savedName) { $('signedAs').textContent = '✓ Signed in as ' + savedName + ' — click to sign out'; $('signedAs').classList.remove('hidden'); $('loginToggle').classList.add('hidden'); }

$('loginToggle').onclick = () => $('loginCard').classList.toggle('hidden');
$('signedAs').onclick = () => {
    localStorage.removeItem('bmydesk_member_token'); localStorage.removeItem('bmydesk_member_name');
    $('signedAs').classList.add('hidden'); $('loginToggle').classList.remove('hidden');
};
$('loginBtn').onclick = async () => {
    const email = $('loginEmail').value.trim(), pass = $('loginPass').value;
    if (!email || !pass) return;
    $('loginBtn').disabled = true; $('loginBtn').textContent = 'Signing in…'; $('loginErr').classList.add('hidden');
    try {
        const r = await fetch(API + '/login', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email, password: pass }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.error || 'Login failed');
        localStorage.setItem('bmydesk_member_token', d.member_token);
        localStorage.setItem('bmydesk_member_name', d.name);
        $('signedAs').textContent = '✓ Signed in as ' + d.name + ' — click to sign out';
        $('signedAs').classList.remove('hidden');
        $('loginCard').classList.add('hidden'); $('loginToggle').classList.add('hidden');
        $('loginPass').value = '';
    } catch (e) {
        $('loginErr').textContent = e.message || 'Login failed';
        $('loginErr').classList.remove('hidden');
    } finally {
        $('loginBtn').disabled = false; $('loginBtn').textContent = 'Sign In';
    }
};

$('googleBtn').onclick = () => window.agent.openExternal('https://bmydesk.believoo.com/auth/google?agent=1');

// OAuth completed in the system browser → token arrives via bmydesk:// deep link
window.agent.onAuth?.(({ token, name }) => {
    localStorage.setItem('bmydesk_member_token', token);
    localStorage.setItem('bmydesk_member_name', name);
    $('signedAs').textContent = '✓ Signed in as ' + name + ' — click to sign out';
    $('signedAs').classList.remove('hidden');
    $('loginCard').classList.add('hidden'); $('loginToggle').classList.add('hidden');
    register(); // re-register so the active session links to this account
});

register();
checkUpdate();
setInterval(checkUpdate, 3600000);
