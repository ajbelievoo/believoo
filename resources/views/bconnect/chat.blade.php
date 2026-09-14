@extends('bconnect.layout')
@section('title', 'Chat: ' . (isset($project) ? $project->name : $ticket->title))
@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col">
    <div class="bg-slate-900 rounded-t-xl border border-slate-800 p-4 border-b-0">
        <h3 class="font-bold"><i class="fas fa-comments mr-2 text-cyan-400"></i>{{ isset($project) ? $project->name : $ticket->title }}</h3>
        <p class="text-xs text-slate-400">Real-time workspace chat with @mentions, files, voice notes</p>
    </div>
    <div id="chat-box" class="flex-1 bg-slate-900 border-l border-r border-slate-800 overflow-y-auto p-4 space-y-3">
        @foreach($messages as $m)
        <div class="flex gap-3 chat-msg {{ $m->member->id == request()->input('bconnect_member')->id ? 'justify-end' : '' }}" data-id="{{ $m->id }}">
            <div class="max-w-md {{ $m->member->id == request()->input('bconnect_member')->id ? 'bg-cyan-500/20 text-cyan-100' : 'bg-slate-800 text-slate-300' }} rounded-xl p-3">
                <div class="text-xs font-bold mb-1">{{ $m->member->user->name }}</div>
                @if($m->message)<p class="text-sm">{{ $m->message }}</p>@endif
                @if(!empty($m->attachments))
                <div class="mt-2 space-y-1">
                    @foreach($m->attachments as $a)
                    <a href="{{ Storage::url($a) }}" target="_blank" class="block text-xs text-cyan-400 hover:underline"><i class="fas fa-file mr-1"></i>{{ basename($a) }}</a>
                    @endforeach
                </div>
                @endif
                <div class="text-[10px] text-slate-500 mt-1">{{ $m->created_at->format('H:i') }}</div>
            </div>
        </div>
        @endforeach
    </div>
    <form id="chatForm" method="POST" action="{{ route('bconnect.chat.store') }}" enctype="multipart/form-data" class="bg-slate-900 border border-slate-800 rounded-b-xl p-4">
        @csrf
        <input type="hidden" name="channel_type" id="channelType" value="{{ isset($project) ? 'project' : 'ticket' }}">
        <input type="hidden" name="channel_id" id="channelId" value="{{ isset($project) ? $project->id : $ticket->id }}">
        <div class="flex gap-2">
            <input type="text" id="chatInput" name="message" placeholder="Type a message..." class="flex-1 p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
            <input type="file" name="attachments[]" multiple class="hidden" id="chatFiles" onchange="document.getElementById('fileCount').innerText = this.files.length + ' file(s)';">
            <span id="fileCount" class="text-xs text-slate-400 self-center"></span>
            <button type="button" onclick="document.getElementById('chatFiles').click()" class="px-3 bg-slate-800 rounded-lg text-slate-400 hover:text-cyan-400"><i class="fas fa-paperclip"></i></button>
            <button type="submit" class="px-4 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400"><i class="fas fa-paper-plane"></i></button>
        </div>
    </form>
</div>
<script>
const chatBox = document.getElementById('chat-box');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');
const channelType = document.getElementById('channelType').value;
const channelId = document.getElementById('channelId').value;
const currentMemberId = {{ request()->input('bconnect_member')->id }};
chatBox.scrollTop = chatBox.scrollHeight;

function renderAttachments(attachments) {
    if (!attachments || attachments.length === 0) return '';
    return '<div class="mt-2 space-y-1">' + attachments.map(a =>
        `<a href="${a.startsWith('http') ? a : '/storage/' + a.replace(/^public\//, '')}" target="_blank" class="block text-xs text-cyan-400 hover:underline"><i class="fas fa-file mr-1"></i>${a.split('/').pop()}</a>`
    ).join('') + '</div>';
}

function appendMessage(data) {
    const isSelf = data.member_id === currentMemberId;
    const div = document.createElement('div');
    div.className = 'flex gap-3 ' + (isSelf ? 'justify-end' : '');
    const messageHtml = data.message ? `<p class="text-sm">${data.message}</p>` : '';
    div.innerHTML = `<div class="max-w-md ${isSelf ? 'bg-cyan-500/20 text-cyan-100' : 'bg-slate-800 text-slate-300'} rounded-xl p-3"><div class="text-xs font-bold mb-1">${data.member.name}</div>${messageHtml}${renderAttachments(data.attachments)}<div class="text-[10px] text-slate-500 mt-1">${data.created_at}</div></div>`;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

if (window.Echo && window.Echo.connector) {
    const channelName = `company.{{ request()->input('bconnect_company_id') }}.chat.${channelType === 'project' ? 'Project' : 'Ticket'}.${channelId}`;
    window.Echo.channel(channelName)
        .listen('.BconnectMessageSent', (e) => appendMessage(e));
} else {
    console.warn('[B-CONNECT Chat] Reverb not connected, falling back to polling');
    setInterval(() => location.reload(), 8000);
}

chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const hasMessage = chatInput.value.trim().length > 0;
    const hasFiles = document.getElementById('chatFiles').files.length > 0;
    if (!hasMessage && !hasFiles) return;
    const formData = new FormData(chatForm);
    try {
        const r = await fetch('{{ route('bconnect.chat.store') }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'},
            body: formData
        });
        const data = await r.json();
        if (data.success) appendMessage({...data, member: {name: '{{ request()->input('bconnect_member')->user->name }}'}, member_id: currentMemberId});
        chatInput.value = '';
        document.getElementById('chatFiles').value = '';
        document.getElementById('fileCount').innerText = '';
    } catch (err) {
        chatForm.submit();
    }
});
</script>
@endsection
