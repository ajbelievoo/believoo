@extends('bconnect.layout')
@section('title', 'Meeting: ' . $meeting->title)
@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
        <div>
            <h3 class="font-bold text-lg">{{ $meeting->title }}</h3>
            <code class="text-xs text-slate-400">Room: {{ $room }}</code>
        </div>
        <div class="flex flex-wrap gap-2">
            <button id="micBtn" class="px-3 py-2 bg-slate-800 rounded-lg text-slate-300 hover:text-white"><i class="fas fa-microphone"></i></button>
            <button id="camBtn" class="px-3 py-2 bg-slate-800 rounded-lg text-slate-300 hover:text-white"><i class="fas fa-video"></i></button>
            <button id="screenBtn" class="px-3 py-2 bg-slate-800 rounded-lg text-slate-300 hover:text-white"><i class="fas fa-desktop"></i></button>
            @if($canRecord)
            <button id="recordBtn" class="px-4 py-2 bg-pink-500/20 text-pink-400 rounded-lg font-bold hover:bg-pink-500/30 text-sm"><i class="fas fa-circle mr-1"></i>Record</button>
            @endif
            @if($canAi)
            <button id="transcriptBtn" class="px-3 py-2 bg-cyan-500/20 text-cyan-400 rounded-lg font-bold hover:bg-cyan-500/30 text-sm"><i class="fas fa-closed-captioning mr-1"></i>Captions</button>
            <label id="audioUploadBtn" class="px-3 py-2 bg-cyan-500/20 text-cyan-400 rounded-lg font-bold hover:bg-cyan-500/30 cursor-pointer hidden text-sm">
                <i class="fas fa-upload mr-1"></i>Audio
                <input type="file" id="audioUploadInput" accept="audio/*" class="hidden">
            </label>
            @endif
            <button id="endMeetingBtn" class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg font-bold hover:bg-red-500/30 text-sm"><i class="fas fa-phone-slash mr-1"></i>End{{ $canAi ? ' & Summarize' : '' }}</button>
        </div>
    </div>
    <div class="flex-1 bg-slate-900 rounded-2xl border border-slate-800 relative overflow-hidden grid grid-cols-1 md:grid-cols-2 gap-2 p-2" id="video-grid">
        <div id="local-player" class="bg-slate-800 rounded-xl flex items-center justify-center relative min-h-[180px]"><span class="text-slate-500 text-sm">Loading camera...</span></div>
    </div>
    @if($canAi)
    <div id="transcriptBox" class="hidden mt-4 p-4 bg-slate-900 border border-cyan-500/30 rounded-xl max-h-48 overflow-y-auto">
        <h4 class="font-bold text-cyan-400 mb-2"><i class="fas fa-closed-captioning mr-1"></i>Live Transcript</h4>
        <div id="transcriptFeed" class="text-sm text-slate-300 space-y-1"></div>
    </div>
    <div id="summaryBox" class="hidden mt-4 p-4 bg-slate-900 border border-cyan-500/30 rounded-xl">
        <h4 class="font-bold text-cyan-400 mb-2"><i class="fas fa-robot mr-1"></i>AI Meeting Summary</h4>
        <div id="summaryText" class="text-sm text-slate-300 whitespace-pre-line"></div>
        <a id="notesLink" href="{{ route('bconnect.meeting.notes', $room) }}" class="hidden mt-3 inline-block bc-btn bc-btn-primary py-1 px-3 text-xs">View full notes</a>
    </div>
    @endif
</div>
<script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.22.0.js"></script>
<script>
const room = @json($room);
const uid = Math.floor(Math.random() * 1000000);
const channel = 'believoo-' + room;
let appId = '';
let token = '';
let client = null;
let localAudioTrack = null;
let localVideoTrack = null;
let localScreenTrack = null;
let isMicOn = true;
let isCamOn = true;

async function initAgora() {
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const resp = await fetch('/agora/token', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ _token: csrf, channel: channel, uid: uid })
        });
        const text = await resp.text();
        let data = {};
        try { data = JSON.parse(text); } catch (e) { throw new Error(text); }
        if (data.error) {
            document.getElementById('video-grid').innerHTML = '<div class="p-4 text-red-400">' + data.error + '</div>';
            return;
        }
        appId = data.app_id;
        token = data.token;
        client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
        client.on('user-published', async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === 'video') showRemoteVideo(user);
            if (mediaType === 'audio') user.audioTrack?.play();
        });
        client.on('user-unpublished', (user) => removeRemoteVideo(user));

        [localAudioTrack, localVideoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();
        localVideoTrack.play('local-player');
        document.getElementById('local-player').querySelector('span')?.remove();

        await client.join(appId, channel, token, uid);
        await client.publish([localAudioTrack, localVideoTrack]);
    } catch (e) {
        console.error(e);
        document.getElementById('video-grid').innerHTML += '<div class="p-4 text-red-400">Camera/mic access required: ' + e.message + '</div>';
    }
}

function showRemoteVideo(user) {
    const id = 'remote-' + user.uid;
    if (!document.getElementById(id)) {
        const div = document.createElement('div');
        div.id = id;
        div.className = 'bg-slate-800 rounded-xl relative flex items-center justify-center min-h-[180px]';
        document.getElementById('video-grid').appendChild(div);
    }
    user.videoTrack.play(id);
}

function removeRemoteVideo(user) {
    const el = document.getElementById('remote-' + user.uid);
    if (el) el.remove();
}

document.getElementById('micBtn').addEventListener('click', async () => {
    if (!localAudioTrack) return;
    isMicOn = !isMicOn;
    await localAudioTrack.setMuted(!isMicOn);
    document.getElementById('micBtn').className = 'px-3 py-2 rounded-lg ' + (isMicOn ? 'bg-slate-800 text-slate-300' : 'bg-red-500/20 text-red-400');
});

document.getElementById('camBtn').addEventListener('click', async () => {
    if (!localVideoTrack) return;
    isCamOn = !isCamOn;
    await localVideoTrack.setMuted(!isCamOn);
    document.getElementById('camBtn').className = 'px-3 py-2 rounded-lg ' + (isCamOn ? 'bg-slate-800 text-slate-300' : 'bg-red-500/20 text-red-400');
});

document.getElementById('screenBtn').addEventListener('click', async () => {
    if (localScreenTrack) { localScreenTrack.stop(); localScreenTrack.close(); localScreenTrack = null; return; }
    try {
        localScreenTrack = await AgoraRTC.createScreenVideoTrack();
        await client.unpublish(localVideoTrack);
        await client.publish(localScreenTrack);
        document.getElementById('screenBtn').className = 'px-3 py-2 rounded-lg bg-cyan-500/20 text-cyan-400';
    } catch (e) { alert('Screen share cancelled or not supported'); }
});

let recording = false;
const recordBtn = document.getElementById('recordBtn');
if (recordBtn) {
recordBtn.addEventListener('click', async () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const mode = recording ? 'stop' : 'start';
    try {
        const r = await fetch(`/meetings/${room}/record/${mode}`, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({_token: csrf})
        });
        const data = await r.json();
        if (data.success) {
            recording = !recording;
            recordBtn.innerHTML = recording ? '<i class="fas fa-stop mr-1"></i>Stop' : '<i class="fas fa-circle mr-1"></i>Record';
            recordBtn.className = recording ? 'px-4 py-2 bg-pink-500 text-white rounded-lg font-bold' : 'px-4 py-2 bg-pink-500/20 text-pink-400 rounded-lg font-bold hover:bg-pink-500/30';
        }
    } catch (e) { console.error(e); }
});
}

let transcript = '';
let recognition = null;
let captionsOn = false;

function appendTranscript(text) {
    const feed = document.getElementById('transcriptFeed');
    if (!feed) return;
    const p = document.createElement('p');
    p.textContent = text;
    p.className = 'border-l-2 border-cyan-500 pl-2';
    feed.appendChild(p);
    feed.parentElement.scrollTop = feed.parentElement.scrollHeight;
}

function saveTranscriptSegment(text, startsAt = 0) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch(`/meetings/${room}/transcript`, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ _token: csrf, text: text, starts_at: startsAt })
    }).catch(e => console.warn('Transcript save failed', e));
}

function initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) return null;
    const r = new SpeechRecognition();
    r.continuous = true;
    r.interimResults = true;
    r.lang = 'en-IN';
    r.onresult = (event) => {
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                const text = event.results[i][0].transcript + ' ';
                transcript += text;
                appendTranscript(text);
                saveTranscriptSegment(text.trim(), Math.floor(Date.now() / 1000 - startTs));
            }
        }
    };
    r.onerror = (e) => { console.warn('Speech recognition error', e); };
    r.onend = () => { if (captionsOn) r.start(); };
    return r;
}

let startTs = Math.floor(Date.now() / 1000);
const transcriptBtn = document.getElementById('transcriptBtn');
if (transcriptBtn) {
    transcriptBtn.addEventListener('click', () => {
        if (!recognition) recognition = initSpeechRecognition();
        if (!recognition) { alert('Speech-to-text not supported in this browser.'); return; }
        captionsOn = !captionsOn;
        const box = document.getElementById('transcriptBox');
        if (captionsOn) {
            try { recognition.start(); } catch(e){}
            transcriptBtn.className = 'px-3 py-2 bg-cyan-500 text-white rounded-lg font-bold';
            transcriptBtn.innerHTML = '<i class="fas fa-closed-captioning mr-1"></i>Stop';
            if (box) box.classList.remove('hidden');
            document.getElementById('audioUploadBtn')?.classList.remove('hidden');
        } else {
            try { recognition.stop(); } catch(e){}
            transcriptBtn.className = 'px-3 py-2 bg-cyan-500/20 text-cyan-400 rounded-lg font-bold hover:bg-cyan-500/30';
            transcriptBtn.innerHTML = '<i class="fas fa-closed-captioning mr-1"></i>Captions';
        }
    });
}

const audioInput = document.getElementById('audioUploadInput');
if (audioInput) {
    audioInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const form = new FormData();
        form.append('audio', file);
        form.append('_token', csrf);
        try {
            transcriptBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Transcribing...';
            const r = await fetch(`/meetings/${room}/audio`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf },
                body: form
            });
            const data = await r.json();
            if (data.success) {
                alert('Audio transcribed. Refresh this page or open Notes to see the transcript.');
            } else {
                alert(data.error || 'Transcription failed.');
            }
        } catch (err) {
            console.error(err);
            alert('Audio upload failed.');
        } finally {
            transcriptBtn.innerHTML = '<i class="fas fa-closed-captioning mr-1"></i>Captions';
        }
    });
}

document.getElementById('endMeetingBtn').addEventListener('click', async () => {
    if (recognition && captionsOn) { try { recognition.stop(); } catch(e){} captionsOn = false; }
    const finalTranscript = transcript.trim() || 'Meeting ended by user';
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const resp = await fetch(`/meetings/${room}/end`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ _token: csrf, transcript: finalTranscript })
        });
        const data = await resp.json();
        if (client) { client.leave(); localAudioTrack?.close(); localVideoTrack?.close(); }
        document.getElementById('video-grid').innerHTML = '<div class="p-4 text-slate-400 w-full text-center">Meeting ended</div>';
        const summaryBox = document.getElementById('summaryBox');
        if (summaryBox) {
            summaryBox.classList.remove('hidden');
            let html = '';
            if (data.summary) html += '<p class="mb-2">' + escapeHtml(data.summary) + '</p>';
            if (data.key_points?.length) html += '<p class="font-bold mt-2">Key points</p><ul>' + data.key_points.map(p => '<li>' + escapeHtml(p) + '</li>').join('') + '</ul>';
            if (data.action_items?.length) html += '<p class="font-bold mt-2">Action items</p><ul>' + data.action_items.map(p => '<li>' + escapeHtml(p) + '</li>').join('') + '</ul>';
            if (data.decisions?.length) html += '<p class="font-bold mt-2">Decisions</p><ul>' + data.decisions.map(p => '<li>' + escapeHtml(p) + '</li>').join('') + '</ul>';
            document.getElementById('summaryText').innerHTML = html || 'No summary generated.';
            document.getElementById('notesLink')?.classList.remove('hidden');
        }
    } catch (e) { alert('Failed to end meeting.'); }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

initAgora();
</script>
@endsection
