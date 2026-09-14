@extends('bconnect.layout')
@section('title', 'Whiteboard: ' . $project->name)
@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col">
    <div class="flex items-center justify-between mb-2">
        <h3 class="font-bold text-xl"><i class="fas fa-chalkboard mr-2 text-cyan-400"></i>{{ $project->name }} Whiteboard</h3>
        <div class="flex gap-2">
            <input type="color" id="color" value="#06b6d4" class="h-9 w-12 rounded cursor-pointer bg-slate-800 border border-slate-600">
            <input type="range" id="size" min="1" max="20" value="3" class="w-24 accent-cyan-500">
            <button id="clearBtn" class="px-3 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
            <button id="saveBtn" class="px-3 py-2 bg-cyan-500/20 text-cyan-400 rounded-lg hover:bg-cyan-500/30"><i class="fas fa-save"></i></button>
        </div>
    </div>
    <div class="flex-1 bg-white rounded-2xl overflow-hidden relative shadow-inner" style="cursor: crosshair;">
        <canvas id="board" class="w-full h-full"></canvas>
    </div>
    <p class="text-xs text-slate-500 mt-2">Draw, annotate, save. Auto-saves every 5 seconds.</p>
</div>
<script>
const canvas = document.getElementById('board');
const ctx = canvas.getContext('2d');
const color = document.getElementById('color');
const size = document.getElementById('size');
const clearBtn = document.getElementById('clearBtn');
const saveBtn = document.getElementById('saveBtn');
let drawing = false;
let strokes = @json($board->data ?? []);

function resize() {
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = canvas.parentElement.clientHeight;
    redraw();
}
window.addEventListener('resize', resize);
resize();

function getPos(e) {
    const r = canvas.getBoundingClientRect();
    return { x: e.clientX - r.left, y: e.clientY - r.top };
}

function startDraw(e) { drawing = true; const p = getPos(e); strokes.push({color: color.value, size: size.value, points:[p]}); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
function draw(e) { if (!drawing) return; const p = getPos(e); strokes[strokes.length-1].points.push(p); ctx.lineTo(p.x, p.y); ctx.strokeStyle = color.value; ctx.lineWidth = size.value; ctx.lineCap='round'; ctx.stroke(); }
function stopDraw() { drawing = false; }

function redraw() {
    ctx.clearRect(0,0,canvas.width,canvas.height);
    strokes.forEach(stroke => {
        ctx.beginPath();
        ctx.strokeStyle = stroke.color; ctx.lineWidth = stroke.size; ctx.lineCap='round';
        stroke.points.forEach((p,i) => { if(i===0) ctx.moveTo(p.x,p.y); else ctx.lineTo(p.x,p.y); });
        ctx.stroke();
    });
}

canvas.addEventListener('mousedown', startDraw);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDraw);
canvas.addEventListener('mouseleave', stopDraw);

function save() {
    fetch('{{ route('bconnect.projects.whiteboard.update', $project->id) }}', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify({ data: JSON.stringify(strokes), _token: document.querySelector('meta[name="csrf-token"]').content })
    }).then(r => r.json()).then(d => { saveBtn.innerHTML = '<i class="fas fa-check"></i>'; setTimeout(()=>saveBtn.innerHTML='<i class="fas fa-save"></i>', 1000); }).catch(console.error);
}

clearBtn.addEventListener('click', () => { strokes=[]; redraw(); save(); });
saveBtn.addEventListener('click', save);
setInterval(save, 5000);
redraw();
</script>
@endsection
