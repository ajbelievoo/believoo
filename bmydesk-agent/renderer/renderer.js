/* BMyDesk Agent — renderer
 * Registers a session code, listens for viewer join requests over Reverb
 * (Pusher protocol), then streams the screen over P2P WebRTC and forwards
 * incoming input events to the Electron main process for OS injection.
 */

const APP_VERSION = '1.0.5';
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
            body: JSON.stringify({ host_name: require_os_name(), version: APP_VERSION, os: window.agent?.platform || 'unknown', member_token: localStorage.getItem('bmydesk_member_token') || undefined }),
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

function require_os_name() {
    return (window.agent?.platform || 'pc') + '-' + (navigator.userAgent.match(/Windows|Mac|Linux/)?.[0] || 'host');
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
            if (d.ok && d.viewer_joined_at && (Date.now() - Date.parse(d.viewer_joined_at) < 120000)
                && $('reqBox').classList.contains('hidden') && !pc) {
                $('reqName').textContent = d.viewer || 'Someone';
                $('reqBox').classList.remove('hidden');
                setStatus('Connection request…', 'wait');
            }
        } catch (e) {}
    }, 4000);
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

    // watchdog — if subscribe doesn't complete, tear the channel down and retry
    if (chRetryTimer) clearTimeout(chRetryTimer);
    chRetryTimer = setTimeout(() => {
        if (!ch.subscribed) {
            subAttempts++;
            hdbg('sub timeout — retry ' + subAttempts);
            try { getPusher().unsubscribe(session.channel); } catch (e) {}
            if (subAttempts >= 2) startStatusPoll();
            connectChannel();
        }
    }, 15000);

    ch.bind('pusher:subscription_succeeded', () => { clearTimeout(chRetryTimer); subAttempts = 0; hdbg('subscribed'); setStatus('Ready — share your code', 'on'); });
    ch.bind('pusher:subscription_error', (e) => {
        clearTimeout(chRetryTimer); subAttempts++;
        hdbg('sub err — retry ' + subAttempts);
        setStatus('Channel auth failed — retrying…', 'off');
        if (subAttempts >= 2) startStatusPoll();
        setTimeout(connectChannel, 5000);
    });

    ch.bind('client-join-request', (m) => {
        $('reqName').textContent = m.name || 'Someone';
        $('reqBox').classList.remove('hidden');
        setStatus('Connection request…', 'wait');
        try { new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=').play().catch(()=>{}); } catch(e){}
    });

    ch.bind('client-signal', async (m) => {
        if (m.kind === 'offer') await handleOffer(m);
        else if (m.kind === 'ice' && m.candidate) {
            if (pc && pc.remoteDescription) await pc.addIceCandidate(m.candidate).catch(() => {});
            else iceQueue.push(m.candidate);
        }
    });

    ch.bind('client-end', () => teardown('Viewer disconnected'));
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
    try {
        // modern path — setDisplayMediaRequestHandler in main supplies the source
        stream = await navigator.mediaDevices.getDisplayMedia({
            video: { frameRate: { ideal: 30, max: 30 } }, audio: false,
        });
    } catch (e) {
        // legacy fallback (older Electron)
        stream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: { mandatory: { chromeMediaSource: 'desktop', chromeMediaSourceId: sourceId, maxWidth: 1920, maxHeight: 1080, maxFrameRate: 30 } },
        });
    }
    stream.getVideoTracks()[0].contentHint = 'detail';
}

async function handleOffer(m) {
    try {
        const sourceId = await pickScreen();
        if (!sourceId) { ch.trigger('client-end', {}); return; }
        await captureScreen(sourceId);

        pc = new RTCPeerConnection(pcConfig);
        pc.onicecandidate = (e) => { if (e.candidate) ch.trigger('client-signal', { kind: 'ice', candidate: e.candidate }); };
        pc.onconnectionstatechange = () => {
            if (pc.connectionState === 'connected') setStatus('Viewer connected — streaming', 'on');
            if (['failed', 'disconnected', 'closed'].includes(pc.connectionState)) setStatus('Viewer lost', 'wait');
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
        ch.trigger('client-signal', { kind: 'answer', sdp: pc.localDescription.sdp });
        iceQueue.forEach(c => pc.addIceCandidate(c).catch(() => {}));
        iceQueue = [];
        $('endBtn').classList.remove('hidden');
        setStatus('Streaming screen…', 'on');
    } catch (e) {
        console.error(e);
        setStatus('Could not capture screen', 'off');
        ch.trigger('client-end', {});
    }
}

function teardown(msg) {
    if (pc) { try { pc.close(); } catch (e) {} pc = null; }
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    $('reqBox').classList.add('hidden');
    $('endBtn').classList.add('hidden');
    setStatus(msg || 'Ready — share your code', 'wait');
}

function respond(action) {
    const event = 'client-join-' + action;
    let sent = false;
    if (ch && ch.subscribed) { try { ch.trigger(event, {}); sent = true; } catch (e) {} }
    if (!sent && session) {
        fetch(`${API}/${session.session_code}/respond`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + session.agent_token },
            body: JSON.stringify({ action }),
        }).catch(() => {});
    }
}
$('acceptBtn').onclick = () => { $('reqBox').classList.add('hidden'); respond('accept'); setStatus('Accepted — waiting for stream…', 'on'); };
$('rejectBtn').onclick = () => { $('reqBox').classList.add('hidden'); respond('reject'); setStatus('Request rejected', 'wait'); };
$('endBtn').onclick = async () => {
    try { ch.trigger('client-end', {}); } catch (e) {}
    try { await fetch(API + '/' + session.session_code + '/end', { method: 'POST', headers: { 'Authorization': 'Bearer ' + session.agent_token } }); } catch (e) {}
    teardown('Session ended');
};

window.addEventListener('beforeunload', () => {
    try { ch?.trigger('client-end', {}); } catch (e) {}
    try { vCh?.trigger('client-end', {}); } catch (e) {}
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
            body: '{}',
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.error || 'join failed');
        vJoinedCode = code;
        const vToken = d.viewer_token;
        if (Array.isArray(d.ice_servers) && d.ice_servers.length) pcConfig = { iceServers: d.ice_servers };

        // Reuse the single ws connection — a second socket can hang on
        // restrictive networks/proxies; multiplexing avoids that entirely.
        channelTokens['private-remote-agent.' + code] = vToken;
        const vp = getPusher();
        vCh = vp.subscribe('private-remote-agent.' + code);

        vStatus('Subscribing…');
        vDbgReset(); vDbg('subscribing');
        vp.connection.bind('state_change', (s) => vDbg('ws:' + s.current));
        vp.connection.bind('error', (e) => vDbg('wserr:' + (e?.error?.data?.code || e?.type || 'net')));
        const subTimeout = setTimeout(() => {
            vStatus('Subscribe timeout — ws:' + (vp.connection?.state || '?') + ' ch:' + vCh.subscribed);
            vDbg('timeout — retry');
            exitViewer(4000);
        }, 20000);
        vCh.bind('pusher:subscription_succeeded', () => {
            clearTimeout(subTimeout);
            vDbg('subscribed');
            vStatus('Waiting for host approval…');
            vCh.trigger('client-join-request', { name: (localStorage.getItem('bmydesk_member_name') || require_os_name()) + ' (agent)' });
            vDbg('join-request sent');
        });
        vCh.bind('pusher:subscription_error', (e) => { clearTimeout(subTimeout); vDbg('sub err'); vStatus('Channel auth failed'); exitViewer(3000); });
        vCh.bind('client-join-accept', () => { vDbg('host accepted'); startViewerPeer(); });
        vCh.bind('client-join-reject', () => { vStatus('Host declined the request'); exitViewer(2500); });
        vCh.bind('client-signal', async (m) => {
            if (!vPc) return;
            try {
                if (m.kind === 'answer') { vDbg('answer received'); await vPc.setRemoteDescription({ type: 'answer', sdp: m.sdp }); }
                else if (m.kind === 'ice' && m.candidate) await vPc.addIceCandidate(m.candidate).catch(() => {});
            } catch (e) { console.error(e); }
        });
        vCh.bind('client-end', () => { vStatus('Session ended by host'); exitViewer(2000); });
    } catch (e) {
        vStatus('Join failed: ' + (e.message || e));
        exitViewer(3000);
    } finally {
        $('connectBtn').disabled = false;
    }
}

async function startViewerPeer() {
    vStatus('Accepted — starting stream…');
    vPc = new RTCPeerConnection(pcConfig);
    vDc = vPc.createDataChannel('input', { ordered: false, maxRetransmits: 0 });

    vPc.ontrack = (e) => {
        $('remoteVideo').srcObject = e.streams[0];
        $('viewerOverlay').classList.add('hidden');
        vStatus('Connected — move & click to control');
    };
    vPc.onicecandidate = (e) => { if (e.candidate) vCh.trigger('client-signal', { kind: 'ice', candidate: e.candidate }); };
    vPc.onconnectionstatechange = () => {
        vDbg('rtc:' + vPc.connectionState);
        if (vPc.connectionState === 'connected') vStatus('Connected');
        if (vPc.connectionState === 'failed') vStatus('Connection failed — retry');
        if (['disconnected', 'closed'].includes(vPc.connectionState)) vStatus('Disconnected');
    };

    const offer = await vPc.createOffer({ offerToReceiveVideo: true, offerToReceiveAudio: true });
    await vPc.setLocalDescription(offer);
    vCh.trigger('client-signal', { kind: 'offer', sdp: vPc.localDescription.sdp });
    vDbg('offer sent');
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
        try { vCh?.trigger('client-end', {}); } catch (e) {}
        if (vPc) { try { vPc.close(); } catch (e) {} vPc = null; }
        vDc = null; vCh = null; vJoinedCode = null;
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
