/* BMyDesk Agent — renderer
 * Registers a session code, listens for viewer join requests over Reverb
 * (Pusher protocol), then streams the screen over P2P WebRTC and forwards
 * incoming input events to the Electron main process for OS injection.
 */

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
    try {
        const r = await fetch(API + '/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ host_name: require_os_name(), version: '1.0.0', os: window.agent?.platform || 'unknown' }),
        });
        const data = await r.json();
        if (!data.ok) throw new Error(data.error || 'register failed');
        session = data;
        if (Array.isArray(data.ice_servers) && data.ice_servers.length) {
            pcConfig = { iceServers: data.ice_servers };
        }
        $('code').textContent = data.session_code;
        setStatus('Ready — share your code', 'wait');
        try { connectChannel(); }
        catch (e) { console.error('channel setup failed:', e); setStatus('Realtime failed: ' + (e.message || e), 'off'); }
    } catch (e) {
        console.error('register failed:', e);
        setStatus('Register failed: ' + (e.message || 'network'), 'off');
        if (!session) $('code').textContent = 'ERROR';
        setTimeout(register, 5000);
    }
}

function require_os_name() {
    return (window.agent?.platform || 'pc') + '-' + (navigator.userAgent.match(/Windows|Mac|Linux/)?.[0] || 'host');
}

function connectChannel() {
    if (typeof Pusher === 'undefined') {
        setStatus('Realtime lib missing — reinstall the agent', 'off');
        return;
    }
    pusher = new Pusher(REVERB_KEY, {
        wsHost: REVERB_HOST,
        wssPort: REVERB_PORT,
        forceTLS: true,
        enabledTransports: ['wss'],
        // custom authorizer — POSTs socket_id+channel_name to our API with the agent token
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                fetch(API + '/broadcast-auth', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ socket_id: socketId, channel_name: channel.name, agent_token: session.agent_token }),
                })
                    .then(r => r.json())
                    .then(d => d.auth ? callback(null, d) : callback(new Error(d.error || 'auth failed'), null))
                    .catch(e => callback(e, null));
            },
        }),
    });

    ch = pusher.subscribe(session.channel);
    ch.bind('pusher:subscription_succeeded', () => setStatus('Ready — share your code', 'on'));
    ch.bind('pusher:subscription_error', (e) => setStatus('Channel auth failed', 'off'));

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
    stream = await navigator.mediaDevices.getUserMedia({
        audio: false,
        video: {
            mandatory: {
                chromeMediaSource: 'desktop',
                chromeMediaSourceId: sourceId,
                maxWidth: 1920, maxHeight: 1080, maxFrameRate: 30,
            },
        },
    });
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

$('acceptBtn').onclick = () => { $('reqBox').classList.add('hidden'); ch.trigger('client-join-accept', {}); setStatus('Accepted — waiting for stream…', 'on'); };
$('rejectBtn').onclick = () => { $('reqBox').classList.add('hidden'); ch.trigger('client-join-reject', {}); setStatus('Request rejected', 'wait'); };
$('endBtn').onclick = async () => {
    try { ch.trigger('client-end', {}); } catch (e) {}
    try { await fetch(API + '/' + session.session_code + '/end', { method: 'POST', headers: { 'Authorization': 'Bearer ' + session.agent_token } }); } catch (e) {}
    teardown('Session ended');
};

window.addEventListener('beforeunload', () => {
    try { ch?.trigger('client-end', {}); } catch (e) {}
    try { if (session) navigator.sendBeacon(API + '/' + session.session_code + '/end', new Blob([JSON.stringify({agent_token: session.agent_token})], {type:'application/json'})); } catch (e) {}
});

register();
