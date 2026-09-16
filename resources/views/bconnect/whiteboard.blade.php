@extends('bconnect.layout')
@section('title', 'Whiteboard: ' . $project->name)
@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col">
    <div class="flex items-center justify-between mb-2">
        <h3 class="font-bold text-xl"><i class="fas fa-chalkboard mr-2 text-cyan-400"></i>{{ $project->name }} Whiteboard</h3>
        <div class="flex items-center gap-2">
            <span id="wbStatus" class="text-xs text-slate-400">Ready</span>
            <input type="color" id="color" value="#06b6d4" class="h-9 w-12 rounded cursor-pointer bg-slate-800 border border-slate-600">
            <input type="range" id="size" min="1" max="20" value="3" class="w-24 accent-cyan-500">
            <button id="clearBtn" class="px-3 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
            <button id="saveBtn" class="px-3 py-2 bg-cyan-500/20 text-cyan-400 rounded-lg hover:bg-cyan-500/30"><i class="fas fa-save"></i></button>
        </div>
    </div>
    <div class="flex-1 bg-white rounded-2xl overflow-hidden relative shadow-inner" style="cursor: crosshair; touch-action: none;">
        <canvas id="board" class="w-full h-full"></canvas>
    </div>
    <p class="text-xs text-slate-500 mt-2">Multi-user whiteboard. Strokes sync in real-time when Echo/Reverb is connected. Auto-saves every 5 seconds.</p>
</div>
<script>
const canvas = document.getElementById('board');
const ctx = canvas.getContext('2d');
const color = document.getElementById('color');
const size = document.getElementById('size');
const clearBtn = document.getElementById('clearBtn');
const saveBtn = document.getElementById('saveBtn');
const statusEl = document.getElementById('wbStatus');
const projectId = {{ $project->id }};
const currentMemberId = {{ request()->input('bconnect_member')->id }};
let drawing = false;
let strokes = @json($board->data ?? []);
let currentStroke = null;
let saveTimeout = null;

function setStatus(text) { statusEl.textContent = text; }

function resize() {
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = canvas.parentElement.clientHeight;
    redraw();
}
window.addEventListener('resize', resize);
resize();

function getPos(e) {
    const r = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return { x: clientX - r.left, y: clientY - r.top };
}

function startDraw(e) {
    e.preventDefault();
    drawing = true;
    const p = getPos(e);
    currentStroke = { color: color.value, size: parseInt(size.value), points: [p], member_id: currentMemberId, created_at: Date.now() };
}

function draw(e) {
    e.preventDefault();
    if (!drawing || !currentStroke) return;
    const p = getPos(e);
    currentStroke.points.push(p);
    ctx.beginPath();
    ctx.moveTo(currentStroke.points[currentStroke.points.length - 2].x, currentStroke.points[currentStroke.points.length - 2].y);
    ctx.lineTo(p.x, p.y);
    ctx.strokeStyle = currentStroke.color;
    ctx.lineWidth = currentStroke.size;
    ctx.lineCap = 'round';
    ctx.stroke();
}

function stopDraw(e) {
    if (e && e.cancelable) e.preventDefault();
    if (!drawing || !currentStroke) return;
    drawing = false;
    strokes.push(currentStroke);
    broadcastStroke(currentStroke);
    currentStroke = null;
    scheduleFullSave();
}

function drawStroke(stroke) {
    if (!stroke || !stroke.points || stroke.points.length < 2) return;
    ctx.beginPath();
    ctx.strokeStyle = stroke.color; ctx.lineWidth = stroke.size; ctx.lineCap = 'round';
    stroke.points.forEach((p, i) => { if (i === 0) ctx.moveTo(p.x, p.y); else ctx.lineTo(p.x, p.y); });
    ctx.stroke();
}

function redraw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    strokes.forEach(drawStroke);
}

canvas.addEventListener('mousedown', startDraw);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDraw);
canvas.addEventListener('mouseleave', stopDraw);
canvas.addEventListener('touchstart', startDraw, {passive: false});
canvas.addEventListener('touchmove', draw, {passive: false});
canvas.addEventListener('touchend', stopDraw, {passive: false});

function scheduleFullSave() {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(saveFull, 5000);
}

function saveFull() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    setStatus('Saving...');
    fetch('{{ route('bconnect.projects.whiteboard.update', $project->id) }}', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({ data: JSON.stringify(strokes), _token: csrf })
    }).then(r => r.json()).then(() => { setStatus('Saved'); saveBtn.innerHTML = '<i class="fas fa-check"></i>'; setTimeout(()=>saveBtn.innerHTML='<i class="fas fa-save"></i>', 1000); }).catch(() => setStatus('Save failed'));
}

function broadcastStroke(stroke) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('{{ route('bconnect.projects.whiteboard.update', $project->id) }}', {
        method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({ data: JSON.stringify(strokes), stroke: stroke, _token: csrf })
    }).catch(() => {});
}

if (window.Echo && window.Echo.connector) {
    window.Echo.channel(`company.{{ request()->input('bconnect_company_id') }}.whiteboard.${projectId}`)
        .listen('.BconnectWhiteboardUpdated', (e) => {
            if (e.member_id === currentMemberId) return;
            strokes.push(e.stroke);
            drawStroke(e.stroke);
            setStatus(`${e.member_name} drew a stroke`);
        });
} else {
    console.warn('[B-CONNECT Whiteboard] Real-time sync unavailable. Echo/Reverb not connected.');
}

clearBtn.addEventListener('click', () => { if(confirm('Clear whiteboard?')) { strokes=[]; redraw(); saveFull(); } });
saveBtn.addEventListener('click', saveFull);
scheduleFullSave();
redraw();
</script>
@endsection
