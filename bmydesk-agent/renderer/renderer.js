/* BMyDesk Agent — renderer
 * Registers a session code, listens for viewer join requests over Reverb
 * (Pusher protocol), then streams the screen over P2P WebRTC and forwards
 * incoming input events to the Electron main process for OS injection.
 */

const APP_VERSION = '1.3.21';
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

// ── Settings store (persisted, every key is honoured somewhere below) ──
const CFG_DEFAULTS = { sound: true, flash: true, permClip: true, permFiles: true, permCmd: false, viewOnlyDef: true,
    watermark: true, autoPrivacy: false, quality: 'auto', clipViewer: true };
// ── Session history (local) ──
let histCur = null;
function histStart(dir, who) { histCur = { dir, who: who || 'Unknown', at: Date.now() }; }
function histEnd() {
    if (!histCur) return;
    let h = []; try { h = JSON.parse(localStorage.getItem('bmd_hist') || '[]'); } catch (e) {}
    h.unshift({ ...histCur, dur: Math.round((Date.now() - histCur.at) / 1000) });
    localStorage.setItem('bmd_hist', JSON.stringify(h.slice(0, 50))); histCur = null;
}
function cfg(k) {
    try { const v = JSON.parse(localStorage.getItem('bmd_cfg') || '{}'); return k in v ? v[k] : CFG_DEFAULTS[k]; } catch (e) { return CFG_DEFAULTS[k]; }
}
function cfgSet(k, val) {
    let v = {}; try { v = JSON.parse(localStorage.getItem('bmd_cfg') || '{}'); } catch (e) {}
    v[k] = val; localStorage.setItem('bmd_cfg', JSON.stringify(v));
}

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
        loadSavedDevices();
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
const sigFresh = (m) => m.kind === 'ice' ? (!m.at || (Date.now() - Date.parse(m.at)) < 45000) : true; // only ICE ages out — offers/answers/accepts are state, not noise
const nonce = () => Math.random().toString(36).slice(2) + Date.now().toString(36);
function postSignal(code, token, msg) {
    msg.n = nonce();
    return fetch(`${API}/${code}/signal`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(msg),
    }).then(r => r.json().then(j => (j._st = r.status, j)).catch(() => ({ _st: r.status }))).catch(() => ({}));
}
const failSt = (r) => ' (http ' + (r && r._st || 'net') + ')';
const hostSignal = (m) => session && postSignal(session.session_code, session.agent_token, m);
const viewerSignal = (m) => vJoinedCode && postSignal(vJoinedCode, vChanToken, m);
async function hostAnswerRobust(sdp) {
    const r = await hostSignal({ kind: 'answer', sdp });
    if (r && r.ok) return;
    hdbg('answer POST failed' + failSt(r) + ' — chunked retry');
    const CH = 3000, parts = [];
    for (let i = 0; i < sdp.length; i += CH) parts.push(sdp.slice(i, i + CH));
    for (let i = 0; i < parts.length; i++) {
        const rr = await hostSignal({ kind: 'answer-part', i, total: parts.length, chunk: parts[i] });
        if (!(rr && rr.ok)) { hdbg('chunk ' + i + ' failed' + failSt(rr)); return; }
    }
    hdbg('answer sent in ' + parts.length + ' parts');
}
// chunked fallback — some networks drop large POST bodies; parts are
// reassembled server-side into a normal offer.
async function viewerOfferRobust(sdp) {
    const r = await viewerSignal({ kind: 'offer', sdp });
    if (r && r.ok) return;
    vDbg('offer POST failed' + failSt(r) + ' — chunked retry');
    const CH = 3000, parts = [];
    for (let i = 0; i < sdp.length; i += CH) parts.push(sdp.slice(i, i + CH));
    for (let i = 0; i < parts.length; i++) {
        const rr = await viewerSignal({ kind: 'offer-part', i, total: parts.length, chunk: parts[i] });
        if (!(rr && rr.ok)) { vDbg('chunk ' + i + ' failed' + failSt(rr)); return; }
    }
    vDbg('offer sent in ' + parts.length + ' parts');
}

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
            // Server status is the truth — 'connecting' means a viewer is
            // waiting for our answer regardless of how long ago they joined.
            if (joinMs && joinMs > respondedAt && d.status === 'connecting'
                && $('reqBox').classList.contains('hidden')) {
                showJoinReq((d.viewer || 'Someone') +
                    (Date.now() - joinMs > 120000 ? ' (joined ' + Math.round((Date.now() - joinMs) / 60000) + 'm ago)' : ''),
                    d.viewer_note, d.viewer_device);
            }
            // viewer gave up / server moved past it — hide a stale prompt
            if (d.status !== 'connecting' && $('reqBox') && !$('reqBox').classList.contains('hidden') && !pc) {
                $('reqBox').classList.add('hidden');
            }
            // unattended join (PIN / trusted device): the server already
            // accepted — the offer arrives next; auto-pick the first display
            // instead of blocking on the screen picker.
            if (joinMs && joinMs > respondedAt && d.status === 'active' && !pc && Date.now() - joinMs < 120000) {
                unattendedJoin = true;
                setStatus('Unattended viewer connecting…', 'wait');
            }
            // PIN badge under the code — live status, not just after saving
            if (typeof d.pin_set === 'boolean' && d.pin_set !== pinSet) { pinSet = d.pin_set; refreshPinLink(); }
            // drain queued signals — this is the full ws-free signaling path
            const sr = await fetch(`${API}/${session.session_code}/signals`, {
                headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
            });
            if (sr.status === 403 || sr.status === 404) { stopStatusPoll(); return; } // dead session — stop zombie-polling
            const sd = await sr.json();
            (sd.signals || []).forEach((m) => { if (sigNew(m) && sigFresh(m)) dispatchHostSignal(m); });
        } catch (e) {}
    }, 3000);
}
function stopStatusPoll() { if (statusPoller) { clearInterval(statusPoller); statusPoller = null; } }

// One place that surfaces "someone wants in" — used by the ws whisper, the
// HTTP drain ('join-request' queued server-side), and the /status poll.
let reqViewerDev = null, lastReqName = '';
// auto-accepted join (PIN/trusted) in flight — consumed by pickScreen so an
// unattended viewer never stalls on the display picker.
let unattendedJoin = false;
function showJoinReq(name, note, vdevice) {
    unattendedJoin = false; // a manual knock supersedes any stale auto-accept flag
    // a NEW viewer is knocking — whatever peer we still hold is a dead leftover
    // (viewer vanished without an 'end'); drop it or this device stays stuck
    // until the app is restarted.
    if (pc) teardown('New connection request…');
    offering = false; iceQueue = [];
    lastReqName = name || 'Someone';
    reqViewerDev = vdevice || null;
    $('reqName').textContent = lastReqName;
    const ne = $('reqNote');
    if (ne) { ne.style.display = note ? 'block' : 'none'; ne.textContent = note ? '“' + note + '”' : ''; }
    const vo = $('acceptViewOnly'); if (vo) vo.checked = !!cfg('viewOnlyDef'); // defaults come from Settings
    if ($('permClip')) $('permClip').checked = !!cfg('permClip');
    if ($('permFiles')) $('permFiles').checked = !!cfg('permFiles');
    if ($('permCmd')) $('permCmd').checked = !!cfg('permCmd');
    const tr = $('acceptTrust'); if (tr) { tr.checked = false; tr.parentElement.style.display = vdevice ? '' : 'none'; }
    $('reqBox').classList.remove('hidden');
    if (cfg('flash')) window.agent.attention(name);
    setStatus('Connection request…', 'wait');
    if (cfg('sound')) try { new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=').play().catch(()=>{}); } catch(e){}
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
    setTimeout(() => { if (ch && !ch.subscribed && !pc && /Connecting realtime/.test($('statusText')?.textContent || '')) setStatus('Ready — share your code', 'on'); }, 4000);

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

    ch.bind('client-join-request', (m) => showJoinReq(m.name, m.note, m.vdevice));

    ch.bind('client-signal', (m) => { if (sigNew(m)) dispatchHostSignal(m); });
    ch.bind('client-end', (m) => { if (sigNew(m)) teardown('Viewer disconnected'); });
}

// one dispatcher for host signals — same handling whether they arrive by
// ws broadcast or the HTTP fallback queue.
function dispatchHostSignal(m) {
    if (m.kind === 'join-request') showJoinReq(m.name, m.note, m.vdevice);
    else if (m.kind === 'offer') handleOffer(m);
    else if (m.kind === 'ice' && m.candidate) {
        if (pc && pc.remoteDescription) pc.addIceCandidate(m.candidate).catch(() => {});
        else iceQueue.push(m.candidate);
    } else if (m.kind === 'end') teardown('Viewer disconnected');
}

async function pickScreen() {
    const sources = await window.agent.getScreenSources();
    const box = $('screenPick');
    // unattended (PIN/trusted) join — nobody is at the keyboard to pick a
    // display, so take the primary one automatically.
    if (unattendedJoin) { unattendedJoin = false; return sources[0]?.id; }
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
        // video + system-audio loopback — the legacy desktop path is the only
        // one that can carry Windows audio; try it first, silent fallback after
        () => navigator.mediaDevices.getUserMedia({
            audio: { mandatory: { chromeMediaSource: 'desktop' } },
            video: { mandatory: { chromeMediaSource: 'desktop', chromeMediaSourceId: sourceId, maxFrameRate: 30 } },
        }),
        // modern path — setDisplayMediaRequestHandler in main supplies the source
        () => navigator.mediaDevices.getDisplayMedia({ video: { frameRate: { ideal: 30, max: 30 } }, audio: false }),
        // retry without constraints — some drivers reject frameRate
        () => navigator.mediaDevices.getDisplayMedia({ video: true, audio: false }),
        // legacy desktop-capture path, video only
        () => navigator.mediaDevices.getUserMedia({
            audio: false,
            video: { mandatory: { chromeMediaSource: 'desktop', chromeMediaSourceId: sourceId, maxFrameRate: 30 } },
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

// Some peers emit SDP attributes this libwebrtc build won't parse
// (a=max-message-size outside m=application, LF endings, …). Try raw,
// then retry with CRLF normalization + exotic attribute lines dropped.
// Strip SDP lines that are known to break strict parsers on the OTHER side
// (e.g. a=max-message-size — some libwebrtc builds reject it outright).
function cleanSdp(sdp) {
    const out = String(sdp || '').split(/\r?\n/)
        .filter(l => l && !/^a=max-message-size/.test(l))
        .map(l => { const m = l.match(/^a=sctp-port:(\d+)\s*$/); return m ? 'a=sctpmap:' + m[1] + ' webrtc-datachannel 1024' : l; })
        .join('\r\n');
    return out.endsWith('\r\n') ? out : out + '\r\n'; // strict parsers need the last line terminated
}

async function setRemoteSdp(pc, type, sdp) {
    const raw = String(sdp || '');
    try { await pc.setRemoteDescription({ type, sdp: raw }); return; }
    catch (e1) {
        const cleaned = raw.replace(/\r?\n/g, '\r\n').split('\r\n')
            .filter(l => l && !/^a=max-message-size/.test(l))
            .map(l => { const m = l.match(/^a=sctp-port:(\d+)\s*$/); return m ? 'a=sctpmap:' + m[1] + ' webrtc-datachannel 1024' : l; })
            .join('\r\n') + '\r\n';
        if (cleaned === raw || cleaned === raw + '\r\n') throw e1;
        hdbg('sdp retry — dropped exotic attrs');
        await pc.setRemoteDescription({ type, sdp: cleaned });
    }
}

async function handleOffer(m) {
    if (pc) {
        // ICE restart / renegotiation from a reconnecting viewer — reuse the
        // existing peer (and stream) instead of starting over.
        try {
            await setRemoteSdp(pc, 'offer', m.sdp);
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            hostAnswerRobust(cleanSdp(pc.localDescription.sdp));
            hdbg('re-answer sent');
        } catch (e) { console.error('re-offer failed', e); }
        return;
    }
    if (offering) return; // dup offer (ws + queue) — ignore
    offering = true;
    try {
        const sourceId = await pickScreen();
        if (!sourceId) { hdbg('no screen sources found'); setStatus('No screen to share', 'off'); hostSignal({ kind: 'end' }); offering = false; return; }
        curSourceId = sourceId;
        await captureScreen(sourceId);

        pc = new RTCPeerConnection(pcConfig);
        pc.onicecandidate = (e) => { if (e.candidate) hostSignal({ kind: 'ice', candidate: e.candidate }); };
        pc.onconnectionstatechange = () => {
            if (pc.connectionState === 'connected') {
                setStatus('Viewer connected — streaming', 'on');
                // always-visible system notice — the host should never miss a session
                try { window.agent?.attention(null, 'A remote viewer is connected — your screen is being shared'); } catch (e) {}
                ensureFramesFlowing();
            }
            if (pc.connectionState === 'disconnected') {
                setStatus('Viewer connection unstable…', 'wait');
                // ICE 'disconnected' can sit for a while before 'failed' —
                // a flapping link heals (viewer re-offers) — don't tear down
                // a live session over a short drop, but a truly dead viewer
                // must not hold the host screen hostage either.
                clearTimeout(hostDiscT);
                hostDiscT = setTimeout(() => {
                    if (pc && pc.connectionState !== 'connected') teardown('Viewer disconnected');
                }, 30000);
            }
            if (pc.connectionState === 'connected') clearTimeout(hostDiscT);
            if (['failed', 'closed'].includes(pc.connectionState)) { teardown('Viewer lost — ready for new connection'); }
        };
        // viewer's call media — mic audio plays, camera opens the PIP window
        pc.ontrack = (e) => { if (e.track !== stream?.getVideoTracks()[0]) attachRemoteTrack(e); };
        pc.ondatachannel = (e) => {
            if (e.channel.label === 'ctl') { wireCtl(e.channel); return; }
            dcIn = e.channel;
            dcIn.onmessage = (ev) => {
                try {
                    const msg = JSON.parse(ev.data);
                    if (msg.t === 'ping') { dcIn.send(JSON.stringify({ t: 'pong', ts: msg.ts })); return; }
                    if (msg.t === 'laser') { try { window.agent?.laser(!msg.off, msg.x, msg.y); } catch (x) {} return; }
                    if (viewOnly) return; // host locked remote input
                    window.agent.sendInput(msg); // inject into host OS
                } catch (err) {}
            };
        };

        await setRemoteSdp(pc, 'offer', m.sdp);
        stream.getTracks().forEach(t => pc.addTrack(t, stream));
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        tuneVideoSender();
        hostAnswerRobust(cleanSdp(pc.localDescription.sdp));
        hdbg('answer sent');
        iceQueue.forEach(c => pc.addIceCandidate(c).catch(() => {}));
        iceQueue = [];
        $('endBtn').classList.remove('hidden');
        $('lockEndWrap')?.classList.remove('hidden');
        setStatus('Streaming screen…', 'on');
        startClipSync();
    } catch (e) {
        console.error(e);
        setStatus('Could not capture screen', 'off');
        hdbg('CAPTURE FAIL: ' + (e.name || '?') + ' — ' + (e.message || 'unknown'));
        hostSignal({ kind: 'end' });
    }
    offering = false;
}

// ══ ctl channel — reliable/ordered: chat, clipboard, file transfer ══
let ctl = null, rxFile = null, ctlPeer = 'peer', hostDiscT = null;
let txAbort = false, txActive = false;
const FILE_CHUNK = 16384;

// progress line inside the session bar — shows during any transfer
function fileProg(txt, cancellable) {
    const el = $('fileProg'); if (!el) return;
    if (!txt) { el.classList.add('hidden'); return; }
    el.classList.remove('hidden');
    el.innerHTML = '';
    const span = document.createElement('span');
    span.textContent = txt; span.style.color = '#94a3b8';
    el.appendChild(span);
    if (cancellable) {
        const x = document.createElement('a');
        x.href = '#'; x.textContent = ' ✕ cancel'; x.style.color = '#f43f5e';
        x.onclick = (e) => { e.preventDefault(); cancelTransfer(); };
        el.appendChild(x);
    }
}
function cancelTransfer() {
    if (rxFile) { rxFile = null; ctlSend({ t: 'file-cancel' }); fileProg(null); addChat('System', 'Transfer cancelled', false); return; }
    txAbort = true; // sender loop checks this between chunks
}

function wireCtl(ch) {
    ctl = ch;
    ctl.onmessage = onCtlMessage;
    ctl.onopen = () => {
        $('sessBar')?.classList.remove('hidden');
        // mode-specific buttons: viewers get control buttons, hosts get safety toggles
        const v = isViewing();
        ['monBtn', 'rebootBtn', 'fsBtn', 'lockBtn', 'laserBtn', 'fsOpenBtn'].forEach(id => { const b = $(id); if (b) b.style.display = v ? '' : 'none'; });
        ['viewOnlyBtn', 'hostMonBtn', 'fsToggleBtn'].forEach(id => { const b = $(id); if (b) b.style.display = v ? 'none' : ''; });
        if (!v && viewOnly) ctlSend({ t: 'view-only', on: true }); // host accepted view-only
        // persistent on-screen badge — the host always sees who is watching
        if (!v && cfg('watermark')) { try { window.agent?.watermark(true, lastReqName || 'remote viewer'); } catch (e) {} }
        if (!v && cfg('autoPrivacy') && !privacyOn) { try { privacyOn = true; window.agent.privacyScreen(true); $('privacyBtn') && ($('privacyBtn').style.background = '#f43f5e'); } catch (e) {} }
        if (v && !histCur) histStart('out', vJoinedCode);
        if (v && cfg('quality') !== 'auto') { $('qualitySel').value = cfg('quality'); ctlSend({ t: 'quality', mode: cfg('quality') }); }
    };
    ctl.onclose = () => { $('sessBar')?.classList.add('hidden'); $('chatPanel')?.classList.add('hidden'); };
}
function ctlSend(o) { if (ctl && ctl.readyState === 'open') { try { ctl.send(JSON.stringify(o)); } catch (e) {} } }

async function onCtlMessage(e) {
    try {
        if (typeof e.data === 'string') {
            const m = JSON.parse(e.data);
            if (m.t === 'chat') {
                addChat(m.from || ctlPeer, m.text, false);
                if (m.id) {
                    ctlSend({ t: 'chat-ack', id: m.id }); // delivered — peer's ✓✓
                    if (!$('chatPanel').classList.contains('hidden')) ctlSend({ t: 'chat-seen', id: m.id });
                    else unseenChatIds.push(m.id);
                }
            }
            else if (m.t === 'chat-ack') { chatAcked(m.id, false); }
            else if (m.t === 'chat-seen') { chatAcked(m.id, true); }
            else if (m.t === 'call') { addChat('System', (ctlPeer === 'peer' ? 'Peer' : ctlPeer) + (m.on ? ' turned ' + m.k + ' ON' : ' turned ' + m.k + ' off'), false); }
            else if (m.t === 'clip') { if (!hostGuard(perm.clip, 'clipboard')) return; try { window.agent?.clipboardSet(m.text); } catch (x) {} }
            else if (m.t === 'quality') { applyQuality(m.mode); }
            else if (m.t === 'file-meta') { if (!hostGuard(perm.files, 'file')) { ctlSend({ t: 'file-cancel' }); return; } rxFile = { name: m.name, size: m.size, chunks: [], got: 0 }; hdbg('rx file: ' + m.name); }
            else if (m.t === 'file-cancel') { rxFile = null; fileProg(null); addChat('System', 'Transfer cancelled by peer', false); }
            else if (m.t === 'cmd-out') { addChat('Host ▸', m.out || '(no output)', false); }
            else if (m.t === 'view-only') {
                addChat('System', m.on ? 'Host locked remote input (view-only mode)' : 'Host re-enabled remote input', false);
                const b = $('vOnlyBadge'); if (b) b.style.display = m.on ? 'inline-block' : 'none';
                if (m.on) vStatus('View-only — you can watch but not control');
            }
            else if (m.t === 'fs-list') { renderFsList(m); }
            else if (m.t === 'laser') { try { window.agent?.laser(!m.off, m.x, m.y); } catch (x) {} }
            // host-side actions — ignored while WE are the viewer
            else if (m.t === 'cmd' && !isViewing() && perm.cmd) { handleRemoteCmd(m.cmd); }
            else if (m.t === 'screen' && !isViewing()) { switchMonitor(); }
            else if (m.t === 'reboot' && !isViewing() && perm.cmd) { remoteReboot(); }
            else if (m.t === 'lock' && !isViewing() && perm.cmd) { remoteLock(); }
            else if (m.t === 'fs-list-req' && !isViewing() && perm.files) { handleFsList(m.path); }
            else if (m.t === 'fs-get' && !isViewing() && perm.files) { handleFsGet(m.path); }
            else if (m.t === 'procs' && !isViewing() && perm.cmd) { handleProcs(); }
            else if (m.t === 'kill' && !isViewing()) { handleKill(m.arg); }
        } else if (rxFile) {
            rxFile.chunks.push(e.data); rxFile.got += e.data.byteLength;
            fileProg('Receiving ' + rxFile.name + ' — ' + Math.round(rxFile.got * 100 / rxFile.size) + '%', true);
            if (rxFile.got >= rxFile.size) await finishRxFile();
        }
    } catch (err) { console.warn('ctl msg', err); }
}
async function finishRxFile() {
    const f = rxFile; rxFile = null;
    const b64 = await blobToB64(new Blob(f.chunks));
    if (window.agent?.saveFile) {
        const p = await window.agent.saveFile(f.name, b64);
        fileProg(null);
        if (p) histPush('↓ ' + f.name);
        hdbg(p ? 'saved ' + f.name : 'save cancelled');
        addChat('System', p ? 'File saved: ' + f.name : 'File receive cancelled', false);
    }
}
function blobToB64(blob) {
    return new Promise((res) => {
        const rd = new FileReader();
        rd.onload = () => res(String(rd.result).split(',')[1]);
        rd.readAsDataURL(blob);
    });
}
async function sendFile(buf, name) {
    if (!ctl || ctl.readyState !== 'open') { setStatus('No session — connect first', 'wait'); return; }
    if (txActive) { addChat('System', 'A transfer is already running — wait or cancel it', false); return; }
    txActive = true; txAbort = false;
    ctlSend({ t: 'file-meta', name, size: buf.byteLength });
    for (let off = 0; off < buf.byteLength; off += FILE_CHUNK) {
        if (txAbort) { ctlSend({ t: 'file-cancel' }); fileProg(null); addChat('System', 'Transfer cancelled', false); txActive = false; return; }
        ctl.send(buf.slice(off, off + FILE_CHUNK));
        if (off % (256 * 1024) < FILE_CHUNK) {
            fileProg('Sending ' + name + ' — ' + Math.round(off * 100 / buf.byteLength) + '%', true);
            await new Promise(r => setTimeout(r, 0)); // let bufferedAmount drain
        }
    }
    txActive = false; fileProg(null);
    addChat('System', 'Sent file: ' + name + ' (' + Math.round(buf.byteLength / 1024) + ' KB)', false);
    histPush('↑ ' + name);
}

// transfer history — last few sends/receives, kept in chat
function histPush(t) { addChat('System', t, false); }

// drag-drop a file anywhere on the window → send over ctl
document.addEventListener('dragover', e => e.preventDefault());
document.addEventListener('drop', async (e) => {
    e.preventDefault();
    const f = e.dataTransfer?.files?.[0];
    if (!f || !ctl || ctl.readyState !== 'open') return;
    sendFile(await f.arrayBuffer(), f.name);
});

// Screen content needs a much higher bitrate than WebRTC's camera defaults and
// must keep native resolution (text stays sharp, nothing is cropped). Cap only
// very large displays, via aspect-preserving downscale on the sender.
function tuneVideoSender() {
    const sender = pc?.getSenders().find(s => s.track?.kind === 'video');
    if (!sender) return;
    const w = sender.track.getSettings?.().width || 0;
    const p = sender.getParameters(); p.encodings = p.encodings && p.encodings.length ? p.encodings : [{}];
    const e = p.encodings[0];
    e.maxBitrate = 8000000; e.maxFramerate = 30;
    e.scaleResolutionDownBy = w > 2560 ? w / 2560 : 1;
    p.degradationPreference = 'maintain-resolution';
    sender.setParameters(p).catch(() => {});
}

// Capture watchdog — if WebRTC says "connected" but the capturer is emitting
// dead frames (window was hidden/occluded, WGC hiccup), re-run the capture and
// swap the track in-place (replaceTrack needs no renegotiation). This is the
// fix for "viewer sees black screen while stats say connected".
let flowWatchdog = null;
async function videoBytesSent() {
    const sender = pc?.getSenders().find(s => s.track?.kind === 'video');
    if (!sender) return -1;
    const st = await sender.getStats().catch(() => null);
    let b = -1;
    st?.forEach(r => { if (r.type === 'outbound-rtp' && r.kind === 'video') b = r.bytesSent || 0; });
    return b;
}
function ensureFramesFlowing(tries = 0) {
    clearTimeout(flowWatchdog);
    flowWatchdog = setTimeout(async () => {
        if (!pc || pc.connectionState !== 'connected') return;
        const a = await videoBytesSent();
        setTimeout(async () => {
            if (!pc || pc.connectionState !== 'connected') return;
            const b = await videoBytesSent();
            if (b > a + 50000) return; // ~real video flowing — healthy
            if (tries >= 3) { hdbg('capture still dead after retries'); return; }
            hdbg('no video bytes flowing — recapturing screen');
            try {
                const old = stream;
                await captureScreen(curSourceId);
                const t = stream.getVideoTracks()[0];
                const sender = pc?.getSenders().find(s => s.track?.kind === 'video');
                await sender?.replaceTrack(t);
                old?.getTracks().forEach(tr => tr.stop());
                tuneVideoSender();
            } catch (e) { hdbg('recapture failed: ' + (e.message || e)); }
            ensureFramesFlowing(tries + 1);
        }, 4000);
    }, tries === 0 ? 3000 : 2000);
}

// Viewer picks quality → we retune the outbound video sender live.
function applyQuality(mode) {
    const sender = pc?.getSenders().find(s => s.track?.kind === 'video');
    if (!sender) return;
    const p = sender.getParameters(); p.encodings = p.encodings || [{}];
    const e = p.encodings[0];
    if (mode === 'sd') { e.maxBitrate = 1200000; e.scaleResolutionDownBy = 2; e.maxFramerate = 20; }
    else if (mode === 'hd') { e.maxBitrate = 9000000; e.scaleResolutionDownBy = 1; e.maxFramerate = 30; }
    else if (mode === 'fhd') { e.maxBitrate = 14000000; e.scaleResolutionDownBy = 1; e.maxFramerate = 30; }
    else { e.maxBitrate = 8000000; e.scaleResolutionDownBy = 1; e.maxFramerate = 30; }
    sender.setParameters(p).catch(() => {});
    hdbg('quality: ' + mode);
}

// ══ Remote-control actions (ctl channel, host-side) ══
let curSourceId = null, viewOnly = false;
function isViewing() { return !$('viewerPane')?.classList.contains('hidden'); }

async function handleRemoteCmd(cmd) {
    const c = String(cmd || '').slice(0, 500).trim();
    if (!c) return;
    hdbg('cmd> ' + c); // audit trail in the host log
    if (!window.agent?.execCmd) { ctlSend({ t: 'cmd-out', out: 'Not supported on this host' }); return; }
    const out = await window.agent.execCmd(c).catch(e => 'error: ' + (e.message || e));
    ctlSend({ t: 'cmd-out', out: out || '(no output)' });
}

async function remoteReboot() {
    if (!window.agent?.sysReboot) return;
    if (!confirm('The remote viewer asked to REBOOT this computer.\n\nAllow it?')) {
        ctlSend({ t: 'cmd-out', out: 'Reboot denied by host user' });
        return;
    }
    ctlSend({ t: 'cmd-out', out: 'Rebooting in ~15 seconds — session will end.' });
    try { hostSignal({ kind: 'end' }); } catch (e) {}
    setTimeout(() => window.agent.sysReboot(), 800);
}

async function remoteLock() {
    if (!window.agent?.sysLock) return;
    if (!confirm('The remote viewer asked to LOCK this computer.\n\nAllow it?')) {
        ctlSend({ t: 'cmd-out', out: 'Lock denied by host user' });
        return;
    }
    ctlSend({ t: 'cmd-out', out: 'Locking this workstation…' });
    setTimeout(() => window.agent.sysLock(), 600);
}

// ── Remote file explorer — viewer browses this host over ctl ──
let fsOn = true; // host can cut browse access mid-session via the 📁 toggle
async function handleFsList(p) {
    if (!window.agent?.fsList || !fsOn) {
        ctlSend({ t: 'fs-list', path: String(p || ''), items: [], err: 'File browsing is not available on this host' });
        return;
    }
    try { const r = await window.agent.fsList(p); ctlSend({ t: 'fs-list', path: r.path, items: r.items, err: r.err }); }
    catch (e) { ctlSend({ t: 'fs-list', path: String(p || ''), items: [], err: e.message }); }
}
async function handleFsGet(p) {
    if (!window.agent?.fsGet || !fsOn) { ctlSend({ t: 'cmd-out', out: 'File browsing is not available on this host' }); return; }
    try {
        const f = await window.agent.fsGet(p);
        hdbg('fs-get ' + p); // audit trail in the host log
        addChat('System', 'Sent: ' + f.name + ' (' + Math.round(f.size / 1024) + ' KB)', false);
        sendFile(b64ToBuf(f.data), f.name);
    } catch (e) { ctlSend({ t: 'cmd-out', out: 'Could not read ' + p + ' — ' + e.message }); }
}

// ── Process tools — /ps and /kill <pid|name> ──
async function handleProcs() {
    const win = (window.agent?.platform || '').includes('win');
    const out = await window.agent.execCmd(win ? 'tasklist /fo table' : 'ps aux --sort=-%mem | head -50').catch(e => 'error: ' + e.message);
    ctlSend({ t: 'cmd-out', out: out || '(no processes found)' });
}
async function handleKill(arg) {
    const a = String(arg || '').trim();
    if (!a) { ctlSend({ t: 'cmd-out', out: 'usage: /kill <pid|name>' }); return; }
    const win = (window.agent?.platform || '').includes('win');
    const cmd = win
        ? (/^\d+$/.test(a) ? 'taskkill /PID ' + a + ' /F' : 'taskkill /IM "' + a.replace(/"/g, '') + '" /F')
        : (/^\d+$/.test(a) ? 'kill -9 ' + a : 'pkill -f "' + a.replace(/"/g, '') + '"');
    const out = await window.agent.execCmd(cmd).catch(e => 'error: ' + e.message);
    ctlSend({ t: 'cmd-out', out: out || 'Process ' + a + ' killed' });
}

// Viewer 🖥 / host 🖥 — capture the next display and swap it onto the video sender
async function switchMonitor() {
    if (!window.agent?.getScreenSources || !pc || !stream) return;
    try {
        const sources = await window.agent.getScreenSources();
        if (sources.length < 2) { ctlSend({ t: 'cmd-out', out: 'Only one display detected' }); return; }
        const idx = sources.findIndex(s => s.id === curSourceId);
        const next = sources[(idx + 1 + sources.length) % sources.length];
        const oldStream = stream;
        curSourceId = next.id;
        await captureScreen(next.id);
        const sender = pc.getSenders().find(s => s.track?.kind === 'video');
        if (sender) await sender.replaceTrack(stream.getVideoTracks()[0]);
        oldStream?.getTracks().forEach(t => t.stop());
        ctlSend({ t: 'cmd-out', out: 'Switched display → ' + next.name });
        hdbg('screen → ' + next.name);
    } catch (e) { ctlSend({ t: 'cmd-out', out: 'Screen switch failed: ' + (e.message || e) }); }
}

// ── Chat UI — WhatsApp-style receipts: ✓ sent → ✓✓ delivered → blue ✓✓ seen ──
const chatTicks = new Map(); // id → tick <span>
const unseenChatIds = [];
function addChat(from, text, mine, id) {
    const box = $('chatMsgs');
    if (!box) return;
    const d = document.createElement('div');
    d.style.cssText = 'margin:2px 0;padding:3px 0;';
    d.innerHTML = `<span style="color:${mine ? '#22d3ee' : '#f43f5e'};font-weight:600;">${from}:</span> <span></span>` +
        (mine && id ? ` <span data-mid="${id}" style="color:#64748b;">✓</span>` : '');
    d.children[1].textContent = text;
    box.appendChild(d); box.scrollTop = box.scrollHeight;
    if (mine && id) chatTicks.set(id, d.querySelector('[data-mid]'));
    $('chatPanel').classList.remove('hidden');
}
function chatAcked(id, seen) {
    const t = chatTicks.get(id);
    if (!t) return;
    t.textContent = '✓✓';
    if (seen) t.style.color = '#38bdf8'; // blue double-tick = read
}
function flushChatSeen() {
    while (unseenChatIds.length) ctlSend({ t: 'chat-seen', id: unseenChatIds.shift() });
}
$('chatSend').onclick = sendChat;
$('chatInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') sendChat(); });
let chatSeq = 0;
function sendChat() {
    const t = $('chatInput').value.trim();
    if (!t) return;
    if (t.startsWith('/cmd ')) {
        const c = t.slice(5).trim();
        if (c) { ctlSend({ t: 'cmd', cmd: c }); addChat('Me ▸', c, true); }
    } else if (t === '/ps') {
        ctlSend({ t: 'procs' }); addChat('Me ▸', 'tasklist', true);
    } else if (t.startsWith('/kill ')) {
        const a = t.slice(6).trim();
        if (a) { ctlSend({ t: 'kill', arg: a }); addChat('Me ▸', 'kill ' + a, true); }
    } else {
        const id = 'd' + (++chatSeq) + Math.random().toString(36).slice(2, 8);
        ctlSend({ t: 'chat', id, from: (localStorage.getItem('bmydesk_member_name') || 'Me'), text: t });
        addChat('Me', t, true, id);
    }
    $('chatInput').value = '';
}
$('chatToggle').onclick = () => { $('chatPanel').classList.toggle('hidden'); if (!$('chatPanel').classList.contains('hidden')) flushChatSeen(); };

// ══ Voice/video call — Zoom-style, over the same peer connection ══
// The viewer's offer carries sendrecv audio+video m-lines up front, so a
// toggle is just sender.replaceTrack — no renegotiation, instant on/off.
let micStream = null, camStream = null, micOn = false, camOn = false;
function callPc() { return isViewing() ? vPc : pc; }
function callTrans(kind) {
    const p = callPc(); if (!p) return null;
    if (isViewing()) return kind === 'audio' ? vAudioT : vCamT; // exact refs we created
    // host side — slots mirror the offer's m-lines; pick by receiver kind,
    // excluding the transceiver that carries our screen track.
    const screenTrk = stream?.getVideoTracks()[0];
    return p.getTransceivers().find(t => kind === 'audio'
        ? t.receiver?.track?.kind === 'audio'
        : (t.receiver?.track?.kind === 'video' && t.sender?.track !== screenTrk)) || null;
}
async function callToggle(kind) {
    const p = callPc();
    if (!p) { setStatus('No session — connect first', 'wait'); return; }
    const on = !(kind === 'audio' ? micOn : camOn);
    const tr = callTrans(kind);
    if (!tr) { addChat('System', 'The other app is too old for calls — ask them to update', false); return; }
    try {
        if (kind === 'audio') {
            if (on && !micStream) micStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            if (micStream) {
                const t = micStream.getAudioTracks()[0];
                await tr.sender.replaceTrack(t);
                t.enabled = on;
                if (!on) { micStream.getTracks().forEach(x => x.stop()); micStream = null; }
            }
            micOn = on;
        } else {
            if (on && !camStream) camStream = await navigator.mediaDevices.getUserMedia({ audio: false, video: { width: { ideal: 640 }, height: { ideal: 480 } } });
            if (camStream) {
                await tr.sender.replaceTrack(on ? camStream.getVideoTracks()[0] : null);
                if (!on) { camStream.getTracks().forEach(x => x.stop()); camStream = null; }
            }
            camOn = on;
        }
        $('micBtn').style.background = micOn ? '#15803d' : '#1e293b';
        $('camBtn').style.background = camOn ? '#15803d' : '#1e293b';
        ctlSend({ t: 'call', k: kind === 'audio' ? 'voice' : 'camera', on });
        addChat('System', (kind === 'audio' ? 'Microphone ' : 'Camera ') + (on ? 'on' : 'off'), false);
    } catch (e) { addChat('System', (kind === 'audio' ? 'Mic' : 'Camera') + ' failed: ' + (e.message || e), false); }
}
function stopCallMedia() {
    micOn = false; camOn = false;
    micStream?.getTracks().forEach(t => t.stop()); micStream = null;
    camStream?.getTracks().forEach(t => t.stop()); camStream = null;
    const pb = $('pipVideo'); if (pb) { pb.srcObject = null; pb.style.display = 'none'; }
    const ab = $('callAudio'); if (ab) ab.srcObject = null;
    if ($('micBtn')) $('micBtn').style.background = '#1e293b';
    if ($('camBtn')) $('camBtn').style.background = '#1e293b';
}
$('micBtn').onclick = () => callToggle('audio');
$('camBtn').onclick = () => callToggle('video');
// Remote tracks — viewer mic → audio element, viewer/host camera → PIP window.
// Screen video is excluded: only tracks that aren't our outbound screen feed.
function attachRemoteTrack(e) {
    const t = e.track;
    if (t.kind === 'audio') {
        const a = $('callAudio'); a.srcObject = new MediaStream([t]); a.play().catch(() => {});
    } else if (t.kind === 'video') {
        // empty cam slot stays muted until the peer really enables their
        // camera — show the PIP only when frames flow, not a black box
        const show = () => { const pv = $('pipVideo'); pv.srcObject = new MediaStream([t]); pv.style.display = 'block'; pv.play().catch(() => {}); };
        if (t.muted) t.onunmute = () => { t.onunmute = null; show(); };
        else show();
    }
}
$('fsBtn').onclick = () => {
    const p = $('viewerPane');
    if (!document.fullscreenElement) p?.requestFullscreen?.().catch(() => {});
    else document.exitFullscreen?.();
};
$('monBtn').onclick = () => ctlSend({ t: 'screen' });
$('rebootBtn').onclick = () => { if (confirm('Reboot the REMOTE computer now?\n\nThe host user will be asked to confirm.')) ctlSend({ t: 'reboot' }); };
$('lockBtn').onclick = () => { if (confirm('Lock the REMOTE computer?\n\nThe host user will be asked to confirm.')) ctlSend({ t: 'lock' }); };
$('hostMonBtn').onclick = () => switchMonitor();
$('viewOnlyBtn').onclick = () => {
    viewOnly = !viewOnly;
    $('viewOnlyBtn').style.background = viewOnly ? '#f43f5e' : '#1e293b';
    ctlSend({ t: 'view-only', on: viewOnly });
    addChat('System', viewOnly ? 'Remote input blocked (view-only)' : 'Remote input re-enabled', false);
};
$('fsToggleBtn').onclick = () => {
    fsOn = !fsOn;
    $('fsToggleBtn').style.background = fsOn ? '#22c55e' : '#1e293b';
    addChat('System', fsOn ? 'File browsing enabled' : 'File browsing disabled', false);
};

// ── Remote file explorer — browse the host's drive right from the session ──
let fsCurPath = '';
function joinPath(dir, name) {
    if (!dir) return name;
    return (dir.endsWith('\\') || dir.endsWith('/')) ? dir + name
        : dir + (dir.includes('\\') ? '\\' : '/') + name;
}
function fsUpPath(p) {
    const m = String(p || '').replace(/[\\/]+$/, '');
    const idx = Math.max(m.lastIndexOf('\\'), m.lastIndexOf('/'));
    return idx > 0 ? m.slice(0, idx) : (idx === 0 ? m.slice(0, 1) : '');
}
function fmtSize(s) { return s > 1048576 ? (s / 1048576).toFixed(1) + 'M' : s > 1024 ? (s / 1024).toFixed(0) + 'K' : s + 'B'; }
$('fsOpenBtn').onclick = () => {
    const p = $('fsPanel');
    if (p.classList.contains('hidden')) {
        p.classList.remove('hidden');
        $('fsItems').innerHTML = '<div style="color:#64748b;padding:8px;">Loading…</div>';
        ctlSend({ t: 'fs-list-req', path: fsCurPath });
    } else p.classList.add('hidden');
};
$('fsClose').onclick = () => $('fsPanel').classList.add('hidden');
$('fsUp').onclick = () => ctlSend({ t: 'fs-list-req', path: fsUpPath(fsCurPath) });
function renderFsList(m) {
    const p = $('fsPanel');
    if (p) p.classList.remove('hidden');
    fsCurPath = m.path || '';
    $('fsPath').textContent = m.path || '(drives)';
    $('fsPath').title = m.path || '';
    const box = $('fsItems'); box.innerHTML = '';
    if (m.err) { box.innerHTML = '<div style="color:#f43f5e;padding:8px;"></div>'; box.lastChild.textContent = m.err; return; }
    if (!m.items || !m.items.length) { box.innerHTML = '<div style="color:#64748b;padding:8px;">Empty folder</div>'; return; }
    m.items.forEach(it => {
        const row = document.createElement('div');
        row.style.cssText = 'padding:4px 6px;border-radius:6px;cursor:pointer;color:#cbd5e1;display:flex;gap:6px;align-items:center;';
        row.onmouseenter = () => row.style.background = '#1e293b';
        row.onmouseleave = () => row.style.background = '';
        row.appendChild(document.createTextNode((it.d ? '📁 ' : '📄 ')));
        const lbl = document.createElement('span');
        lbl.textContent = it.n;
        lbl.style.cssText = 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;';
        row.appendChild(lbl);
        if (!it.d) {
            const sz = document.createElement('span');
            sz.textContent = fmtSize(it.s); sz.style.cssText = 'color:#475569;font-size:10px;';
            row.appendChild(sz);
        }
        row.title = it.d ? 'Open folder' : 'Download to this device';
        row.onclick = () => ctlSend({ t: it.d ? 'fs-list-req' : 'fs-get', path: joinPath(fsCurPath, it.n) });
        box.appendChild(row);
    });
}

// 🙈 privacy — blank this machine's screens while being viewed (host-side only)
let privacyOn = false;
$('privacyBtn').onclick = () => {
    privacyOn = !privacyOn;
    window.agent.privacyScreen(privacyOn);
    $('privacyBtn').style.background = privacyOn ? '#f43f5e' : '#1e293b';
    hdbg(privacyOn ? 'privacy: screen blanked' : 'privacy: screen visible');
};
function resetPrivacy() { if (privacyOn) { privacyOn = false; window.agent.privacyScreen(false); $('privacyBtn').style.background = '#1e293b'; } }
$('fileBtn').onclick = async () => {
    if (window.agent?.pickFile) {
        // host in-app send — main reads the file, we stream it
        const f = await window.agent.pickFile().catch(() => null);
        if (f) sendFile(b64ToBuf(f.data), f.name);
    } else {
        $('fileInput').click();
    }
};
$('fileInput').onchange = async (e) => {
    const f = e.target.files[0];
    if (f) sendFile(await f.arrayBuffer(), f.name);
    e.target.value = '';
};
function b64ToBuf(b64) {
    const bin = atob(b64), u8 = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) u8[i] = bin.charCodeAt(i);
    return u8.buffer;
}

// ── Clipboard sync ──
let lastClip = '', clipTimer = null;
// host-granted permissions for the current session (set when accepting a request)
let perm = { clip: true, files: true, cmd: false };
function hostGuard(ok, what) { if (!ok && !isViewing()) { hdbg('blocked: ' + what); return false; } return true; }

function startClipSync() {
    if (clipTimer || !window.agent?.clipboardGet) return;
    clipTimer = setInterval(async () => {
        try {
            const t = await window.agent.clipboardGet();
            if (t && t !== lastClip && (isViewing() ? cfg('clipViewer') : perm.clip)) { lastClip = t; ctlSend({ t: 'clip', text: t }); }
        } catch (e) {}
    }, 2000);
}
function stopClipSync() { if (clipTimer) { clearInterval(clipTimer); clipTimer = null; } }

function teardown(msg) {
    if (histCur && histCur.dir === 'in') histEnd();
    resetPrivacy();
    stopClipSync();
    viewOnly = false; $('viewOnlyBtn') && ($('viewOnlyBtn').style.background = '#1e293b');
    $('fsPanel')?.classList.add('hidden');
    try { window.agent?.laser(false, 0, 0); } catch (e) {}
    try { window.agent?.watermark(false); } catch (e) {}
    // optional hardening — lock the workstation when the session ends
    if (localStorage.getItem('bmd_lock_on_end') === '1' && $('endBtn') && !$('endBtn').classList.contains('hidden')) {
        setTimeout(() => { try { window.agent?.sysLock(); } catch (e) {} }, 1000);
    }
    curSourceId = null;
    clearTimeout(flowWatchdog); flowWatchdog = null;
    stopCallMedia();
    ctl = null; rxFile = null;
    $('sessBar')?.classList.add('hidden'); $('chatPanel')?.classList.add('hidden');
    if (pc) { try { pc.close(); } catch (e) {} pc = null; }
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    $('reqBox').classList.add('hidden');
    $('endBtn').classList.add('hidden');
    $('lockEndWrap')?.classList.add('hidden');
    offering = false; iceQueue = []; perm = { clip: true, files: true, cmd: false };
    if (session) startStatusPoll(); // never leave the host deaf after a session
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
$('acceptBtn').onclick = () => {
    $('reqBox').classList.add('hidden'); respondedAt = Date.now();
    viewOnly = !!$('acceptViewOnly')?.checked;
    perm = { clip: $('permClip')?.checked !== false, files: $('permFiles')?.checked !== false, cmd: !!$('permCmd')?.checked };
    if (viewOnly) $('viewOnlyBtn') && ($('viewOnlyBtn').style.background = '#f43f5e');
    if ($('acceptTrust')?.checked && reqViewerDev && session) {
        // remember this viewer — future joins auto-accept (revocable via /untrust)
        try { fetch(API + '/' + session.session_code + '/trust', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ agent_token: session.agent_token, viewer_device: reqViewerDev, viewer_name: lastReqName }),
        }); } catch (e) {}
        addChat('System', 'Trusted ' + lastReqName + ' — will auto-accept next time', false);
    }
    histEnd(); histStart('in', lastReqName);
    respond('accept'); setStatus(viewOnly ? 'Accepted (view-only) — waiting for stream…' : 'Accepted — waiting for stream…', 'on');
};
$('rejectBtn').onclick = () => { $('reqBox').classList.add('hidden'); respondedAt = Date.now(); respond('reject'); setStatus('Request rejected', 'wait'); };
// persist the "lock PC when session ends" host preference
(() => { const c = $('lockEndChk'); if (c) { c.checked = localStorage.getItem('bmd_lock_on_end') === '1'; c.onchange = () => localStorage.setItem('bmd_lock_on_end', c.checked ? '1' : '0'); } })();
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
let vProgPct = 0, vJoinedAt = 0;

// Connection progress — advances the overlay bar through the join stages so
// the client can see motion instead of a dead "connecting…" screen.
function vStage(pct, text) {
    vProgPct = Math.max(vProgPct, pct);
    const bar = $('vProgBar'); if (bar) bar.style.width = vProgPct + '%';
    const p = $('vProgPct'); if (p) p.textContent = vProgPct + '%';
    if (text) {
        const t = $('vOverlayText'); if (t) t.textContent = text;
        vStatus(text);
    }
}
function vProgReset() {
    vProgPct = 0; vJoinedAt = 0;
    const bar = $('vProgBar'); if (bar) bar.style.width = '0%';
    const p = $('vProgPct'); if (p) p.textContent = '0%';
    const t = $('vOverlayText'); if (t) t.textContent = 'Connecting to remote device…';
}

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
    vProgReset();
    vStatus('Joining ' + code + '…');

    // joining your own code just hangs — the request prompt lands behind the
    // viewer pane; refuse it up front instead of "waiting" forever.
    if (session && code === session.session_code) {
        vStage(0, "That's your own code — enter a partner's");
        $('connectBtn').disabled = false;
        setTimeout(() => exitViewer(0), 2500);
        return;
    }

    try {
        const r = await fetch(API + '/' + code + '/join', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: localStorage.getItem('bmydesk_member_name') || require_os_name(),
                note: ($('remoteNote') && $('remoteNote').value.trim()) || undefined,
                pin: ($('remotePin') && $('remotePin').value.trim()) || undefined,
                viewer_device: deviceId(),
            }),
        });
        const d = await r.json().catch(() => null);
        if (!d || !d.ok) throw new Error((d && (d.error || d.message)) || ('join failed (HTTP ' + r.status + ')'));
        saveRecent(code);
        vStage(15, 'Joined — opening channel…');
        vJoinedAt = Date.now();
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
            vStage(30, 'Relay mode — waiting for host approval…');
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
        // keep the waiting state alive — show elapsed time so the client
        // knows the host still has to press Accept.
        if (vProgPct < 60 && vJoinedAt) {
            const waited = Math.round((Date.now() - vJoinedAt) / 1000);
            vStage(vProgPct < 30 ? 30 : vProgPct,
                'Waiting for host approval… (' + waited + 's)' +
                (waited > 90 ? ' — host may be away; their app must be open' : ''));
        }
        try {
            const r = await fetch(`${API}/${vJoinedCode}/signals`, {
                headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + vChanToken },
            });
            if (r.status === 403 || r.status === 404) { vStatus('Session closed'); exitViewer(1800); return; }
            const d = await r.json();
            (d.signals || []).forEach((m) => { if (sigNew(m) && sigFresh(m)) dispatchViewerSignal(m); });
            // queued accept may be missed mid-flight — status poll is the backup
            const st = await fetch(`${API}/${vJoinedCode}/status`, {
                headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + vChanToken },
            });
            const sd = await st.json();
            if (sd.ok && sd.host_waking === true) { vStatus('Waking the host\'s device…'); }
            else if (sd.ok && sd.host_alive === false) { vStatus('Host went offline — ask them to reopen the app'); exitViewer(3500); return; }
            if (sd.ok && sd.status === 'rejected') { vStatus('Host declined the request'); exitViewer(2500); }
            else if (sd.ok && sd.status === 'ended') { vStatus('Session ended by host'); exitViewer(2000); }
            else if (sd.ok && sd.status === 'active' && vAutoAccepted && !vPc) { vDbg('auto-accepted'); startViewerPeer(); }
        } catch (e) {}
    }, 2000);
}
function stopViewerPoll() { if (vPoller) { clearInterval(vPoller); vPoller = null; } }

function dispatchViewerSignal(m) {
    if (m.kind === 'accept') { vDbg('host accepted'); vStage(60, 'Accepted — starting stream…'); startViewerPeer(); }
    else if (m.kind === 'reject') { vStatus('Host declined the request'); exitViewer(2500); }
    else if (m.kind === 'end') { vStatus('Session ended by host'); exitViewer(2000); }
    else if (m.kind === 'answer' && vPc) { vDbg('answer received'); vStage(84, 'Answer received — connecting…'); setRemoteSdp(vPc, 'answer', m.sdp).catch(() => {}); }
    else if (m.kind === 'ice' && vPc && m.candidate) vPc.addIceCandidate(m.candidate).catch(() => {});
}

function bindViewerChannel(vc, subTimeout) {
    vc.bind('pusher:subscription_succeeded', () => {
        if (subTimeout) clearTimeout(subTimeout);
        vDbg('subscribed');
        vStage(45, 'Waiting for host approval…');
        vc.trigger('client-join-request', { name: (localStorage.getItem('bmydesk_member_name') || require_os_name()) + ' (agent)', note: ($('remoteNote') && $('remoteNote').value.trim()) || undefined, vdevice: deviceId() });
        vDbg('join-request sent');
        // keep the HTTP poll as a safety net even when ws is alive — signals
        // sent by an HTTP-only host still land via the queue.
        startViewerPoll();
    });
    vc.bind('pusher:subscription_error', () => { if (subTimeout) clearTimeout(subTimeout); vDbg('sub err — http fallback'); vStage(30, 'Connecting over relay…'); startViewerPoll(); });
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
    wireCtl(vPc.createDataChannel('ctl')); // reliable — chat/clipboard/file

    // m-line order: [0] screen-recv, [1] call audio, [2] camera — the call
    // slots let either side start mic/camera later WITHOUT renegotiation.
    vScreenT = vPc.addTransceiver('video', { direction: 'recvonly' });
    vAudioT = vPc.addTransceiver('audio', { direction: 'sendrecv' });
    vCamT = vPc.addTransceiver('video', { direction: 'sendrecv' });

    vPc.ontrack = (e) => {
        if (e.transceiver === vScreenT || (e.track.kind === 'video' && !$('remoteVideo').srcObject)) {
            $('remoteVideo').srcObject = new MediaStream([e.track]);
            $('statOverlay').style.display = 'block';
            startStats();
            startSessionTimer();
            $('viewerOverlay').classList.add('hidden');
            vStage(100, 'Connected — move & click to control');
        } else attachRemoteTrack(e);
    };
    vPc.onicecandidate = (e) => { if (e.candidate) viewerSignal({ kind: 'ice', candidate: e.candidate }); };
    vPc.onconnectionstatechange = () => {
        vDbg('rtc:' + vPc.connectionState);
        if (vPc.connectionState === 'connected') { vStage(92, 'Almost there…'); vStatus('Connected'); vReconnectTries = 0; if (vDiscTimer) { clearTimeout(vDiscTimer); vDiscTimer = null; } }
        if (vPc.connectionState === 'failed') viewerReconnect();
        if (vPc.connectionState === 'disconnected') {
            vStatus('Unstable — watching…');
            vDiscTimer = setTimeout(() => { if (vPc && vPc.connectionState === 'disconnected') viewerReconnect(); }, 6000);
        }
        if (vPc.connectionState === 'closed') vStatus('Disconnected');
    };

    // no offerToReceive flags — the explicit transceivers above carry the
    // m-lines (flags would mint extra video slots and scramble the mapping)
    const offer = await vPc.createOffer();
    await vPc.setLocalDescription(offer);
    vStage(72, 'Negotiating secure link…');
    // retransmit until an answer lands — a single lost POST must not kill the session
    let vOfferTries = 0;
    const sendOffer = () => {
        if (!vPc || vPc.remoteDescription || vOfferTries >= 8) return;
        vOfferTries++;
        viewerOfferRobust(cleanSdp(vPc.localDescription.sdp));
        vDbg('offer sent x' + vOfferTries);
        setTimeout(sendOffer, 4000);
    };
    sendOffer();
}

// Auto-reconnect: ICE restart re-offer first; if the peer is truly broken we
// rebuild it wholesale — session stays 'active' server-side so no new host
// approval and no re-join needed.
let vReconnectTries = 0, vDiscTimer = null, sessStart = 0, sessT = null, vAutoDropped = false;
let vScreenT = null, vAudioT = null, vCamT = null; // viewer-side call transceivers
function viewerReconnect() {
    if (!vPc) return;
    vReconnectTries++;
    // 4 tries ≈ ~30s — a flap recovers, a dead host exits fast instead of
    // spinning retries for minutes
    if (vReconnectTries > 4) { vStatus('Connection lost — host app closed or network dropped'); setTimeout(() => exitViewer(), 2500); return; }
    vStatus('Reconnecting… (attempt ' + vReconnectTries + ')');
    vDbg('reconnect x' + vReconnectTries);
    if (vReconnectTries <= 3) viewerReoffer();
    else {
        try { vPc.close(); } catch (e) {}
        vPc = null; vDc = null;
        setTimeout(() => { if (vJoinedCode) startViewerPeer(); }, 1500);
    }
}
function startSessionTimer() {
    if (sessStart) return;
    sessStart = Date.now();
    sessT = setInterval(() => {
        const s = Math.floor((Date.now() - sessStart) / 1000);
        const t = $('sessTimer');
        if (t) t.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
    }, 1000);
}
async function viewerReoffer() {
    if (!vPc) return;
    try {
        const offer = await vPc.createOffer({ iceRestart: true });
        await vPc.setLocalDescription(offer);
        viewerSignal({ kind: 'offer', sdp: cleanSdp(vPc.localDescription.sdp) });
        vDbg('re-offer sent');
    } catch (e) { console.error('reoffer', e); }
}

// Zoom/pan — Ctrl+wheel zooms at the cursor, middle-drag pans when zoomed,
// double-click resets to fit. Input coords stay correct because they are
// computed from getBoundingClientRect (which includes the transform).
let zoom = { s: 1, x: 0, y: 0 }, laserOn = false;
function applyZoom() {
    const v = $('remoteVideo');
    v.style.transformOrigin = '0 0';
    v.style.transform = zoom.s === 1 ? '' : `translate(${zoom.x}px,${zoom.y}px) scale(${zoom.s})`;
}
function zoomAt(v, dy, cx0, cy0) {
    const r = v.getBoundingClientRect(); // already transformed
    const ox = r.left - zoom.x, oy = r.top - zoom.y; // untransformed origin
    const cx = cx0 - ox, cy = cy0 - oy;
    const ns = Math.min(4, Math.max(1, zoom.s * (dy < 0 ? 1.2 : 1 / 1.2)));
    const f = ns / zoom.s;
    zoom.x = cx - (cx - zoom.x) * f; zoom.y = cy - (cy - zoom.y) * f; zoom.s = ns;
    if (zoom.s === 1) { zoom.x = 0; zoom.y = 0; }
    applyZoom();
}
function resetZoom() { zoom = { s: 1, x: 0, y: 0 }; applyZoom(); }
$('laserBtn').onclick = () => {
    laserOn = !laserOn;
    $('laserBtn').style.background = laserOn ? '#f43f5e' : '#1e293b';
    if (!laserOn && vDc?.readyState === 'open') { try { vDc.send(JSON.stringify({ t: 'laser', off: true })); } catch (e) {} }
};

// Input capture on the remote video → host's DataChannel handler
// The <video> element is object-fit:contain — the painted frame is letterboxed
// inside the element rect. Mapping over the element rect lands clicks off by
// the letterbox bars whenever aspect ratios differ — map over the PAINTED frame.
function frameRect(v) {
    const r = v.getBoundingClientRect(); // includes the CSS zoom transform
    const vw = v.videoWidth || 0, vh = v.videoHeight || 0;
    if (!vw || !vh || !r.width || !r.height) return { left: r.left, top: r.top, width: r.width, height: r.height };
    const s = Math.min(r.width / vw, r.height / vh);
    const w = vw * s, h = vh * s;
    return { left: r.left + (r.width - w) / 2, top: r.top + (r.height - h) / 2, width: w, height: h };
}
function vidNorm(v, cx, cy) {
    const r = frameRect(v);
    return { x: Math.min(1, Math.max(0, (cx - r.left) / r.width)), y: Math.min(1, Math.max(0, (cy - r.top) / r.height)) };
}
function bindViewerInput() {
    const v = $('remoteVideo');
    const send = (o) => { if (vDc && vDc.readyState === 'open') { try { vDc.send(JSON.stringify(o)); vLastInput = Date.now(); } catch (e) {} } };
    let last = 0, panning = false, panX = 0, panY = 0;
    v.addEventListener('mousemove', (e) => {
        if (panning) { zoom.x += e.clientX - panX; zoom.y += e.clientY - panY; panX = e.clientX; panY = e.clientY; applyZoom(); return; }
        const n = performance.now(); if (n - last < 33) return; last = n;
        const p = vidNorm(v, e.clientX, e.clientY);
        send(laserOn ? { t: 'laser', x: p.x, y: p.y } : { t: 'move', x: p.x, y: p.y });
    });
    v.addEventListener('mousedown', (e) => {
        e.preventDefault();
        if (e.button === 1 && zoom.s > 1) { panning = true; panX = e.clientX; panY = e.clientY; return; }
        if (!laserOn) send({ t: 'down', b: e.button });
    });
    v.addEventListener('mouseup', (e) => {
        if (e.button === 1 && panning) { panning = false; return; }
        if (!laserOn) send({ t: 'up', b: e.button });
    });
    v.addEventListener('wheel', (e) => {
        e.preventDefault();
        if (e.ctrlKey) { zoomAt(v, e.deltaY, e.clientX, e.clientY); return; }
        send({ t: 'wheel', dx: e.deltaX, dy: e.deltaY });
    }, { passive: false });
    v.addEventListener('dblclick', () => { if (zoom.s !== 1) resetZoom(); });
    v.addEventListener('contextmenu', (e) => e.preventDefault());
    v.tabIndex = 0;
    v.addEventListener('keydown', (e) => { e.preventDefault(); send({ t: 'key', k: e.key, code: e.code, down: true, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });
    v.addEventListener('keyup', (e) => { e.preventDefault(); send({ t: 'key', k: e.key, code: e.code, down: false, ctrl: e.ctrlKey, alt: e.altKey, shift: e.shiftKey, meta: e.metaKey }); });
    v.addEventListener('click', () => v.focus());
}

function exitViewer(delay = 0) {
    const doIt = () => {
        if (histCur && histCur.dir === 'out') histEnd();
        try { viewerSignal({ kind: 'end' }); } catch (e) {}
        stopViewerPoll();
        if (vPc) { try { vPc.close(); } catch (e) {} vPc = null; }
        vDc = null; vCh = null; vJoinedCode = null; vChanName = null; vChanToken = null;
        vScreenT = null; vAudioT = null; vCamT = null;
        stopCallMedia();
        sessStart = 0; if (sessT) { clearInterval(sessT); sessT = null; }
        vReconnectTries = 0; vAutoDropped = false;
        if (vDiscTimer) { clearTimeout(vDiscTimer); vDiscTimer = null; }
        ctl = null; rxFile = null; $('sessBar')?.classList.add('hidden'); $('chatPanel')?.classList.add('hidden');
        $('fsPanel')?.classList.add('hidden');
        laserOn = false; $('laserBtn') && ($('laserBtn').style.background = '#1e293b');
        resetZoom();
        $('viewerPane').classList.add('hidden');
        $('remoteVideo').srcObject = null;
        window.agent.setViewMode(false);
        vProgReset();
    };
    delay ? setTimeout(doIt, delay) : doIt();
}

// copy link — invite URL anyone can open in a browser to view this device
$('copyLink').onclick = async (e) => {
    e.preventDefault();
    const code = ($('code').textContent || '').replace(/\D/g, '');
    if (!code) return;
    const link = 'https://bmydesk.believoo.com/remote/guest?code=' + code;
    try { window.agent.clipboardSet(link); hdbg('invite link copied'); }
    catch (err) { hdbg('invite: ' + link); }
};

// Ctrl+C while viewing → send clipboard text to host (clipboard sync)
document.addEventListener('keydown', async (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c' && ctl && ctl.readyState === 'open'
        && window.agent?.clipboardGet && cfg('clipViewer') && !$('viewerPane').classList.contains('hidden')) {
        try { const t = await window.agent.clipboardGet(); if (t) ctlSend({ t: 'clip', text: t }); } catch (x) {}
    }
});

$('connectBtn').addEventListener('click', connectToPartner);
$('qualitySel').onchange = () => ctlSend({ t: 'quality', mode: $('qualitySel').value });

// 📷 screenshot — grab the current remote frame as PNG
$('shotBtn').onclick = () => {
    const v = $('remoteVideo');
    if (!v || !v.videoWidth) return;
    const c = document.createElement('canvas');
    c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    c.toBlob(b => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(b);
        a.download = 'bmydesk-' + Date.now() + '.png';
        a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 5000);
    });
};

// ⏺ session recording — MediaRecorder on the remote stream, webm download
let rec = null, recChunks = [];
$('recBtn').onclick = () => {
    if (rec) { rec.stop(); return; }
    const st = $('remoteVideo').srcObject;
    if (!st) return;
    recChunks = [];
    try { rec = new MediaRecorder(st, { mimeType: 'video/webm' }); } catch (e) { try { rec = new MediaRecorder(st); } catch (e2) { rec = null; return; } }
    rec.ondataavailable = e => { if (e.data.size) recChunks.push(e.data); };
    rec.onstop = () => {
        const blob = new Blob(recChunks, { type: 'video/webm' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'bmydesk-session-' + Date.now() + '.webm';
        a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 8000);
        rec = null; recChunks = [];
        $('recBtn').style.background = '#1e293b';
    };
    rec.start(1000);
    $('recBtn').style.background = '#f43f5e';
};

let vStatsTimer = null, lastBytes = 0, lastStatAt = 0;
function startStats() {
    stopStats();
    vStatsTimer = setInterval(async () => {
        if (!vPc) return;
        try {
            let fps = 0, rtt = 0, bytesNow = 0;
            (await vPc.getStats()).forEach(r => {
                if (r.type === 'inbound-rtp' && r.kind === 'video') { fps = r.framesPerSecond || 0; bytesNow = r.bytesReceived || 0; }
                if (r.type === 'candidate-pair' && r.nominated) rtt = Math.round((r.currentRoundTripTime || 0) * 1000);
            });
            const now = Date.now();
            const kbps = lastStatAt ? Math.round((bytesNow - lastBytes) * 8 / (now - lastStatAt)) : 0;
            lastBytes = bytesNow; lastStatAt = now;
            const bars = rtt < 80 ? '▂▄▆█' : rtt < 160 ? '▂▄▆' : rtt < 300 ? '▂▄' : '▂';
            $('statOverlay').textContent = bars + '  ' + rtt + 'ms · ' + fps + 'fps · ' + kbps + 'kbps';
            // adaptive quality — sustained bad link drops the host to SD once
            if (!vAutoDropped && rtt > 350 && ctl && ctl.readyState === 'open') {
                vAutoDropped = true;
                ctlSend({ t: 'quality', mode: 'sd' });
                vDbg('slow link — quality auto-lowered');
            }
        } catch (e) {}
    }, 2000);
}
function stopStats() { if (vStatsTimer) clearInterval(vStatsTimer); vStatsTimer = null; lastBytes = 0; lastStatAt = 0; }
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
        connectToPartner(); // one-tap reconnect to a known device
    });
}
renderRecent();

// ── Address book — devices saved server-side under this install's
// device_id; survive reinstall and are shared nowhere else. ──────────
async function loadSavedDevices() {
    if (!session) return;
    try {
        const r = await fetch(`${API}/${session.session_code}/devices`, { headers: { 'Authorization': 'Bearer ' + session.agent_token } });
        const d = await r.json();
        renderSaved(d.devices || []);
    } catch (e) {}
}
function renderSaved(list) {
    const box = $('savedBox');
    if (!box) return;
    if (!list.length) { box.classList.add('hidden'); return; }
    box.classList.remove('hidden');
    box.innerHTML = '<div style="font-size:10px;color:#475569;margin-bottom:4px;">Saved devices — tap to connect:</div>' + list.map(d =>
        `<div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:11px;">
            <span style="width:7px;height:7px;border-radius:50%;background:${d.online ? '#22c55e' : '#334155'};flex:none;"></span>
            <a href="#" data-code="${d.code}" style="color:#22d3ee;text-decoration:none;font-family:monospace;letter-spacing:1px;">${d.code}</a>
            <span style="color:#94a3b8;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${d.label || ''}</span>
            <a href="#" data-del="${d.id}" style="color:#475569;text-decoration:none;">✕</a>
        </div>`).join('');
    box.querySelectorAll('a[data-code]').forEach(a => a.onclick = (e) => {
        e.preventDefault();
        $('remoteCode').value = a.dataset.code;
        connectToPartner();
    });
    box.querySelectorAll('a[data-del]').forEach(a => a.onclick = async (e) => {
        e.preventDefault();
        try { await fetch(`${API}/${session.session_code}/devices/${a.dataset.del}`, { method: 'DELETE', headers: { 'Authorization': 'Bearer ' + session.agent_token } }); } catch (x) {}
        loadSavedDevices();
    });
}
$('saveDeviceLink').onclick = async (e) => {
    e.preventDefault();
    const code = $('remoteCode').value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (code.length < 6 || !session) { setStatus('Enter a code first', 'wait'); return; }
    const label = prompt('Name this device (e.g. "Office PC"):', '');
    if (label === null) return;
    try {
        await fetch(`${API}/${session.session_code}/devices`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
            body: JSON.stringify({ target_code: code, label }),
        });
        loadSavedDevices();
    } catch (x) {}
};

// Unattended access — set a device PIN; viewers with code+PIN connect
// without approval. PIN is stored server-side keyed by device_id.
// NOTE: window.prompt() does NOT exist in Electron — this is a real modal.
let pinSet = false;
function refreshPinLink() {
    const l = $('pinLink'); if (l) l.textContent = pinSet ? '🔒 unattended access — ON ✓' : '🔒 unattended access';
}
$('pinLink').onclick = (e) => {
    e.preventDefault();
    if (!session) return;
    $('pinInput').value = ''; $('pinErr').textContent = '';
    $('pinDlg').classList.remove('hidden');
    $('pinInput').focus();
};
$('pinCancel').onclick = () => $('pinDlg').classList.add('hidden');
$('pinSave').onclick = () => savePin($('pinInput').value.trim());
$('pinRemove').onclick = () => savePin('');
$('pinInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') savePin($('pinInput').value.trim()); });
async function savePin(pin) {
    if (!session) return;
    if (pin !== '' && !/^[0-9]{4,12}$/.test(pin)) { $('pinErr').textContent = 'PIN must be 4–12 digits'; return; }
    try {
        const r = await fetch(`${API}/${session.session_code}/set-pin`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
            body: JSON.stringify({ pin }),
        });
        const d = await r.json();
        if (d.ok) {
            $('pinDlg').classList.add('hidden');
            pinSet = !!d.pin_set; refreshPinLink();
            setStatus(d.pin_set ? 'Unattended access ON' : 'Unattended access OFF', d.pin_set ? 'on' : 'wait');
            // A PIN is useless if the app isn't running after a reboot —
            // offer start-at-login right here, once.
            if (d.pin_set && window.agent?.loginItemGet) {
                const on = await window.agent.loginItemGet().catch(() => true);
                if (!on && confirm('Unattended access only works while BMyDesk is running.\n\nStart BMyDesk automatically when Windows starts? (recommended)')) {
                    await window.agent.loginItemSet(true).catch(() => {});
                    setStatus('Unattended ON — starts with Windows', 'on');
                }
            }
        }
        else $('pinErr').textContent = d.error || 'PIN failed';
    } catch (err) { $('pinErr').textContent = 'PIN update failed'; }
}

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

// bmydesk://connect/<CODE> — shared invite link dropped straight into the
// Remote Desk viewer (guest page hands desktop users here).
window.agent.onConnect?.(({ code }) => {
    if (!code) return;
    $('remoteCode').value = String(code).toUpperCase();
    connectToPartner();
});

register();
checkUpdate();
setInterval(checkUpdate, 3600000);


// ══ Settings centre UI ══
(function () {
    const pane = $('settingsPane'); if (!pane) return;
    const map = { cfgSound: 'sound', cfgFlash: 'flash', cfgPermClip: 'permClip', cfgPermFiles: 'permFiles', cfgPermCmd: 'permCmd',
        cfgViewOnlyDef: 'viewOnlyDef', cfgWatermark: 'watermark', cfgAutoPrivacy: 'autoPrivacy', cfgClipViewer: 'clipViewer' };
    function open() {
        Object.entries(map).forEach(([id, k]) => { $(id).checked = !!cfg(k); });
        $('cfgLockEnd').checked = localStorage.getItem('bmd_lock_on_end') === '1';
        $('cfgQuality').value = cfg('quality');
        $('cfgTheme').value = document.body.classList.contains('light') ? 'light' : 'dark';
        $('cfgCode').textContent = session?.session_code || '—';
        $('cfgDevId').textContent = deviceId();
        $('cfgVer').textContent = 'v' + APP_VERSION;
        window.agent?.loginItemGet?.().then(v => { $('cfgAutostart').checked = !!v; }).catch(() => {});
        renderTrusted(); renderHistory();
        pane.classList.remove('hidden');
    }
    function fmtDur(sec) { return sec >= 3600 ? Math.floor(sec / 3600) + 'h ' + Math.floor(sec % 3600 / 60) + 'm' : sec >= 60 ? Math.floor(sec / 60) + 'm ' + (sec % 60) + 's' : sec + 's'; }
    function renderHistory() {
        let h = []; try { h = JSON.parse(localStorage.getItem('bmd_hist') || '[]'); } catch (e) {}
        $('histList').innerHTML = h.length ? h.map(x => `<div class="srow"><div class="t"><b>${x.dir === 'in' ? '⬇ ' : '⬆ '}${String(x.who).replace(/</g, '&lt;')}</b><span>${x.dir === 'in' ? 'Connected to your PC' : 'You connected to them'} · ${new Date(x.at).toLocaleString()} · ${fmtDur(x.dur)}</span></div></div>`).join('')
            : '<div class="srow"><div class="t"><span>No sessions yet.</span></div></div>';
    }
    async function renderTrusted() {
        const box = $('trustedList'); if (!session) { box.innerHTML = '<div class="srow"><div class="t"><span>Not registered yet.</span></div></div>'; return; }
        try {
            const r = await fetch(`${API}/${session.session_code}/trusted`, { headers: { Accept: 'application/json', Authorization: 'Bearer ' + session.agent_token } });
            const d = await r.json(); const list = d.trusted || [];
            box.innerHTML = list.length ? list.map(t => `<div class="srow"><div class="t"><b>${String(t.viewer_name || 'Unnamed device').replace(/</g, '&lt;')}</b><span style="font-family:monospace">${String(t.viewer_device_id).slice(0, 14)}…</span></div><button class="sbtn danger" data-rm="${String(t.viewer_device_id).replace(/"/g, '')}">Remove</button></div>`).join('')
                : '<div class="srow"><div class="t"><span>No trusted devices — everyone needs your approval.</span></div></div>';
            box.querySelectorAll('[data-rm]').forEach(b => b.onclick = async () => {
                await fetch(`${API}/${session.session_code}/untrust`, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ agent_token: session.agent_token, viewer_device: b.dataset.rm }) });
                renderTrusted();
            });
        } catch (e) { box.innerHTML = '<div class="srow"><div class="t"><span>Could not load.</span></div></div>'; }
    }
    $('cfgClearHist').onclick = () => { localStorage.removeItem('bmd_hist'); renderHistory(); };
    $('cfgUntrustAll').onclick = async () => {
        if (!session || !confirm('Remove ALL trusted devices?')) return;
        await fetch(`${API}/${session.session_code}/untrust`, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ agent_token: session.agent_token }) });
        renderTrusted();
    };
    $('gearBtn').onclick = open;
    $('settingsClose').onclick = () => pane.classList.add('hidden');
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') pane.classList.add('hidden'); });
    $('stabs').querySelectorAll('button').forEach(b => b.onclick = () => {
        $('stabs').querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
        pane.querySelectorAll('.pane').forEach(p => p.classList.toggle('on', p.dataset.pane === b.dataset.p));
    });
    Object.entries(map).forEach(([id, k]) => { $(id).onchange = () => cfgSet(k, $(id).checked); });
    $('cfgLockEnd').onchange = () => { localStorage.setItem('bmd_lock_on_end', $('cfgLockEnd').checked ? '1' : '0'); const c = $('lockEndChk'); if (c) c.checked = $('cfgLockEnd').checked; };
    $('cfgQuality').onchange = () => cfgSet('quality', $('cfgQuality').value);
    $('cfgTheme').onchange = () => { localStorage.setItem('bmydesk_theme', $('cfgTheme').value); applyTheme($('cfgTheme').value); };
    $('cfgAutostart').onchange = () => window.agent?.loginItemSet?.($('cfgAutostart').checked).catch(() => {});
    $('cfgCopyCode').onclick = () => window.agent.clipboardSet(session?.session_code || '');
    $('cfgCopyDev').onclick = () => window.agent.clipboardSet(deviceId());
    $('cfgClearRecent').onclick = () => { localStorage.removeItem('bmydesk_recent'); renderRecent(); };
    $('cfgCheckUpd').onclick = () => { checkUpdate(); $('cfgCheckUpd').textContent = 'Checking…'; setTimeout(() => $('cfgCheckUpd').textContent = 'Check for update', 2000); };
    $('cfgOpenWeb').onclick = () => window.agent.openExternal('https://bmydesk.believoo.com');
    $('cfgOpenHelp').onclick = () => window.agent.openExternal('https://bmydesk.believoo.com/help');
})();

// ═══════════ "How was the app?" — quit-time rating (main.js asks us on
// window close; we answer 'csat-done' immediately if not due, or after the
// user submits/skips the overlay). Once a week max — never naggy. ═══════════
let csatStars = 0;
function csatPaint() {
    $('csatStars').querySelectorAll('span').forEach(s => {
        s.textContent = parseInt(s.dataset.s) <= csatStars ? '★' : '☆';
    });
}
$('csatStars').querySelectorAll('span').forEach(s => {
    s.onclick = () => { csatStars = parseInt(s.dataset.s); csatPaint(); };
});
function csatFinish() {
    $('csatOverlay').classList.add('hidden');
    if (csatStars > 0 && session) {
        fetch(`${API}/${session.session_code}/app-rate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
            body: JSON.stringify({ rating: csatStars, feedback: $('csatText').value.trim() || undefined, platform: window.agent?.platform || 'desktop' }),
        }).catch(() => {});
    }
    csatStars = 0; csatPaint();
    try { window.agent.csatDone(); } catch (e) {}
}
$('csatSubmit').onclick = csatFinish;
$('csatSkip').onclick = csatFinish;
window.agent?.onCsat?.(() => {
    const next = parseInt(localStorage.getItem('bmd_rate_next_at') || '0', 10);
    if (Date.now() < next) { try { window.agent.csatDone(); } catch (e) {} return; }
    // asked → mark the window so it can't re-fire on the next close this week
    localStorage.setItem('bmd_rate_next_at', String(Date.now() + 7 * 864e5));
    $('csatOverlay').classList.remove('hidden');
});
