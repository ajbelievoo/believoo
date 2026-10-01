@extends('bconnect.layout')
@section('title', 'Chat: ' . (isset($project) ? $project->name : $ticket->title))
@section('content')
@php
$channel = $project ?? $ticket;
$channelType = isset($project) ? 'project' : 'ticket';
$channelId = $channel->id;
@endphp
<div class="h-[calc(100vh-140px)] flex flex-col">
    <div class="bg-slate-900 rounded-t-xl border border-slate-800 p-4 border-b-0 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h3 class="font-bold"><i class="fas fa-comments mr-2 text-cyan-400"></i>{{ isset($project) ? $project->name : $ticket->title }}</h3>
            <p class="text-xs text-slate-400 flex items-center gap-2">
                <span id="typingIndicator" class="hidden text-cyan-400"><i class="fas fa-pencil-alt"></i> <span id="typingName"></span> is typing...</span>
                <span>Real-time workspace chat with @mentions, replies & files</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
            <span class="bg-red-500/20 text-red-400 text-xs px-2 py-1 rounded-full">{{ $unreadCount }} unread</span>
            @endif
            <form id="chatSearchForm" class="relative">
                <input type="text" id="chatSearch" name="q" placeholder="Search messages..." value="{{ $search ?? '' }}" class="bc-input text-sm py-1.5 pr-8">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <div id="searchResults" class="hidden bg-slate-900 border-l border-r border-slate-800 p-3 max-h-40 overflow-y-auto"></div>

    <div id="chat-box" class="flex-1 bg-slate-900 border-l border-r border-slate-800 overflow-y-auto p-4 space-y-3">
        @foreach($messages as $m)
        <div class="flex gap-3 chat-msg {{ $m->member->id == $memberId ? 'justify-end' : '' }}" data-id="{{ $m->id }}">
            <div class="max-w-md {{ $m->member->id == $memberId ? 'bg-cyan-500/20 text-cyan-100' : 'bg-slate-800 text-slate-300' }} rounded-xl p-3 {{ !$m->is_read && $m->member->id != $memberId ? 'border border-amber-500/50' : '' }}">
                <div class="flex justify-between items-start gap-2">
                    <div class="text-xs font-bold mb-1">{{ $m->member->user->name }}</div>
                    @if(!$m->is_read && $m->member->id != $memberId)
                    <span class="text-[9px] text-amber-400">NEW</span>
                    @endif
                </div>
                @if($m->parent_id)
                <div class="text-[10px] text-slate-500 mb-1">Replied to #{{ $m->parent_id }}</div>
                @endif
                @if($m->message)
                <div class="text-sm message-text">{!! nl2br(e($m->message)) !!}</div>
                @endif
                @if(!empty($m->attachments))
                <div class="mt-2 space-y-1">
                    @foreach($m->attachments as $a)
                    <a href="{{ Storage::url($a) }}" target="_blank" class="block text-xs text-cyan-400 hover:underline"><i class="fas fa-file mr-1"></i>{{ basename($a) }}</a>
                    @endforeach
                </div>
                @endif
                <div class="flex items-center justify-between gap-2 mt-1">
                    <div class="text-[10px] text-slate-500">{{ $m->created_at->format('H:i') }}</div>
                    <button type="button" class="text-[10px] text-cyan-400 hover:underline reply-btn" data-id="{{ $m->id }}" data-name="{{ $m->member->user->name }}">Reply</button>
                </div>
                @if($m->replies->isNotEmpty())
                <div class="mt-2 pl-3 border-l-2 border-slate-700 space-y-2">
                    @foreach($m->replies as $reply)
                    <div class="text-xs">
                        <span class="font-bold text-slate-400">{{ $reply->member->user->name }}</span>
                        <span class="text-slate-300">{{ $reply->message }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div id="replyBar" class="hidden bg-slate-900 border-l border-r border-slate-800 px-4 py-2 text-xs text-slate-400 flex justify-between items-center">
        <span>Replying to <span id="replyToName" class="text-cyan-400"></span></span>
        <button type="button" id="cancelReply" class="text-slate-500 hover:text-slate-300"><i class="fas fa-times"></i></button>
    </div>

    <form id="chatForm" method="POST" action="{{ route('bconnect.chat.store') }}" enctype="multipart/form-data" class="bg-slate-900 border border-slate-800 rounded-b-xl p-4 relative">
        @csrf
        <input type="hidden" name="channel_type" id="channelType" value="{{ $channelType }}">
        <input type="hidden" name="channel_id" id="channelId" value="{{ $channelId }}">
        <input type="hidden" name="parent_id" id="parentId" value="">
        <div class="flex gap-2 relative">
            <div class="flex-1 relative">
                <input type="text" id="chatInput" name="message" autocomplete="off" placeholder="Type a message... Use @name or @id to mention" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
                <div id="mentionSuggestions" class="hidden absolute bottom-full left-0 w-full bg-slate-800 border border-slate-700 rounded-lg shadow-xl max-h-40 overflow-y-auto z-10"></div>
            </div>
            <input type="file" name="attachments[]" multiple class="hidden" id="chatFiles" onchange="updateFileCount()">
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
const currentMemberId = {{ $memberId }};
const members = @json($members->map(fn($m) => ['id' => $m->id, 'name' => $m->user->name]));
let replyToId = null;
let typingTimeout = null;

chatBox.scrollTop = chatBox.scrollHeight;

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function linkifyMentions(text) {
    return escapeHtml(text).replace(/@([a-zA-Z0-9_\-\.\s]+?)@|@([a-zA-Z0-9_\-]+)/g, (match, p1, p2) => {
        const name = (p1 || p2).trim();
        return `<span class="text-cyan-400 font-medium">@${name}</span>`;
    });
}

function renderAttachments(attachments) {
    if (!attachments || attachments.length === 0) return '';
    return '<div class="mt-2 space-y-1">' + attachments.map(a => {
        const url = a.startsWith('http') ? a : '/storage/' + a.replace(/^public\//, '');
        const name = a.split('/').pop();
        return `<a href="${escapeHtml(url)}" target="_blank" class="block text-xs text-cyan-400 hover:underline"><i class="fas fa-file mr-1"></i>${escapeHtml(name)}</a>`;
    }).join('') + '</div>';
}

function appendMessage(data, prepend = false) {
    const isSelf = data.member_id === currentMemberId;
    const existing = document.querySelector(`.chat-msg[data-id="${data.id}"]`);
    if (existing) return;
    const div = document.createElement('div');
    div.className = 'flex gap-3 chat-msg ' + (isSelf ? 'justify-end' : '');
    div.dataset.id = data.id;
    const replyLine = data.parent_id ? `<div class="text-[10px] text-slate-500 mb-1">Replied to #${data.parent_id}</div>` : '';
    const messageText = data.message ? `<div class="text-sm message-text">${linkifyMentions(data.message)}</div>` : '';
    div.innerHTML = `<div class="max-w-md ${isSelf ? 'bg-cyan-500/20 text-cyan-100' : 'bg-slate-800 text-slate-300'} rounded-xl p-3"><div class="text-xs font-bold mb-1">${escapeHtml(data.member.name)}</div>${replyLine}${messageText}${renderAttachments(data.attachments)}<div class="flex items-center justify-between gap-2 mt-1"><div class="text-[10px] text-slate-500">${data.created_at}</div><button type="button" class="text-[10px] text-cyan-400 hover:underline reply-btn" data-id="${data.id}" data-name="${escapeHtml(data.member.name)}">Reply</button></div></div>`;
    if (prepend) chatBox.prepend(div);
    else chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
    bindReplyButtons();
    if (!isSelf) markRead(data.id);
}

function bindReplyButtons() {
    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.onclick = () => {
            replyToId = btn.dataset.id;
            document.getElementById('parentId').value = replyToId;
            document.getElementById('replyToName').textContent = btn.dataset.name;
            document.getElementById('replyBar').classList.remove('hidden');
            chatInput.focus();
        };
    });
}
bindReplyButtons();

document.getElementById('cancelReply').addEventListener('click', () => {
    replyToId = null;
    document.getElementById('parentId').value = '';
    document.getElementById('replyBar').classList.add('hidden');
});

function updateFileCount() {
    const files = document.getElementById('chatFiles').files;
    document.getElementById('fileCount').textContent = files.length ? files.length + ' file(s)' : '';
}

async function markRead(messageId) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch(`/chat/messages/${messageId}/read`, {
        method: 'POST', credentials: 'same-origin',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
    }).catch(() => {});
}

// Mark visible messages as read
const readObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const id = entry.target.closest('.chat-msg')?.dataset.id;
            if (id) markRead(id);
        }
    });
}, { root: chatBox, threshold: 0.5 });

document.querySelectorAll('.chat-msg').forEach(el => {
    const content = el.querySelector('.max-w-md');
    if (content) readObserver.observe(content);
});

// Mentions
chatInput.addEventListener('input', () => {
    const val = chatInput.value;
    const lastAt = val.lastIndexOf('@');
    const box = document.getElementById('mentionSuggestions');
    if (lastAt >= 0 && (lastAt === val.length - 1 || !val.slice(lastAt).includes(' '))) {
        const query = val.slice(lastAt + 1).toLowerCase();
        const matches = members.filter(m => m.name.toLowerCase().includes(query)).slice(0, 5);
        if (matches.length) {
            box.innerHTML = matches.map(m => `<div class="px-3 py-2 hover:bg-slate-700 cursor-pointer text-sm text-slate-300" data-id="${m.id}" data-name="${escapeHtml(m.name)}">@${escapeHtml(m.name)}</div>`).join('');
            box.classList.remove('hidden');
            box.querySelectorAll('div').forEach(item => {
                item.addEventListener('click', () => {
                    const before = val.slice(0, lastAt);
                    chatInput.value = before + '@' + item.dataset.name + '@ ';
                    box.classList.add('hidden');
                    chatInput.focus();
                });
            });
            return;
        }
    }
    box.classList.add('hidden');

    // Typing indicator
    if (typingTimeout) clearTimeout(typingTimeout);
    sendTyping();
    typingTimeout = setTimeout(() => {}, 2000);
});

function sendTyping() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('/chat/typing', {
        method: 'POST', credentials: 'same-origin',
        headers: {'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({ channel_type: channelType, channel_id: channelId })
    }).catch(() => {});
}

// Search
const searchForm = document.getElementById('chatSearchForm');
const searchResults = document.getElementById('searchResults');
searchForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const q = document.getElementById('chatSearch').value.trim();
    if (!q) { searchResults.classList.add('hidden'); return; }
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
        const r = await fetch('/chat/search', {
            method: 'POST', credentials: 'same-origin',
            headers: {'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({ channel_type: channelType, channel_id: channelId, q: q })
        });
        const data = await r.json();
        searchResults.innerHTML = data.messages.length ? data.messages.map(m =>
            `<a href="${m.url}" class="block p-2 hover:bg-slate-800 rounded text-sm text-slate-300"><span class="font-bold text-cyan-400">${escapeHtml(m.member.name)}</span> <span class="text-slate-500">${m.created_at}</span><br>${escapeHtml(m.message)}</a>`
        ).join('') : '<p class="text-slate-500 text-sm">No results.</p>';
        searchResults.classList.remove('hidden');
    } catch (err) {}
});

// Echo / polling
const channelName = `company.{{ request()->input('bconnect_company_id') }}.chat.${channelType === 'project' ? 'Project' : 'Ticket'}.${channelId}`;
const pollUrl = `/chat/poll?channel_type=${channelType}&channel_id=${channelId}`;
let lastMessageId = Math.max(...Array.from(document.querySelectorAll('.chat-msg')).map(el => parseInt(el.dataset.id) || 0));
let pollInterval = null;

async function fetchNewMessages() {
    try {
        const r = await fetch(`${pollUrl}&after_id=${lastMessageId}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await r.json();
        if (!data.messages || !data.messages.length) return;
        data.messages.forEach(m => {
            if (document.querySelector(`.chat-msg[data-id="${m.id}"]`)) return;
            appendMessage(m);
            if (m.id > lastMessageId) lastMessageId = m.id;
        });
    } catch (err) {
        console.warn('[Bmydesk Chat] Poll failed', err);
    }
}

if (window.Echo && window.Echo.connector) {
    window.Echo.channel(channelName)
        .listen('.BconnectMessageSent', (e) => {
            if (e.id > lastMessageId) lastMessageId = e.id;
            appendMessage(e);
        })
        .listen('.BconnectTyping', (e) => {
            if (e.member_id !== currentMemberId) {
                document.getElementById('typingName').textContent = e.member_name;
                document.getElementById('typingIndicator').classList.remove('hidden');
                setTimeout(() => document.getElementById('typingIndicator').classList.add('hidden'), 3000);
            }
        });
} else {
    console.warn('[Bmydesk Chat] Reverb not connected, falling back to polling');
}

// Always use lightweight polling as a fallback / sync layer
pollInterval = setInterval(fetchNewMessages, 8000);

chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const hasMessage = chatInput.value.trim().length > 0;
    const hasFiles = document.getElementById('chatFiles').files.length > 0;
    if (!hasMessage && !hasFiles) return;
    const formData = new FormData(chatForm);
    const tempText = chatInput.value;
    chatInput.value = '';
    document.getElementById('chatFiles').value = '';
    document.getElementById('fileCount').textContent = '';
    document.getElementById('cancelReply').click();
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const r = await fetch('/chat/messages', {
            method: 'POST', credentials: 'same-origin',
            headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
            body: formData
        });
        const data = await r.json();
        if (data.success) {
            appendMessage({...data, member: {name: '{{ addslashes(request()->input('bconnect_member')->user->name ?? '') }}'}, member_id: currentMemberId});
        }
    } catch (err) {
        chatInput.value = tempText;
        chatForm.submit();
    }
});
</script>
@endsection
