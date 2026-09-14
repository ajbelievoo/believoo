<div class="flex h-[calc(100vh-40px)] bg-slate-100"
     wire:poll.10s="ping"
     x-data="{
        scrollToBottom() {
            $nextTick(() => {
                const container = $refs.chatContainer;
                if (container) container.scrollTop = container.scrollHeight;
            });
        },
        audio: new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'),
        playNotification() { this.audio.play().catch(e => console.log('Audio play blocked')); },
        notify(from, preview) {
            this.playNotification();
            if (window.Notification && Notification.permission === 'granted') {
                new Notification('New message from ' + from, { body: preview, icon: '/favicon.ico' });
            }
        },
        sidebarOpen: true
     }"
     x-on:scroll-chat-to-bottom.window="scrollToBottom()"
     x-on:play-ping-sound.window="playNotification()"
     x-on:agent-new-message.window="notify($event.detail[0].from, $event.detail[0].preview)">

    <!-- ══════ Left Sidebar ══════ -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="flex-shrink-0 bg-slate-900 flex flex-col transition-all duration-300">
        <!-- Logo -->
        <div class="p-5 border-b border-slate-800 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-headset text-white"></i>
            </div>
            <div x-show="sidebarOpen" x-cloak class="min-w-0">
                <div class="text-white font-black text-sm tracking-tight">BELIEVOO</div>
                <div class="text-slate-400 text-[10px] uppercase tracking-widest">Agent Portal</div>
            </div>
        </div>

        <!-- Agent card -->
        <div class="p-4 border-b border-slate-800" x-show="sidebarOpen" x-cloak>
            <div class="flex items-center gap-3">
                @if($agent->avatar)
                    <img src="{{ Storage::url($agent->avatar) }}" class="w-10 h-10 rounded-full object-cover border-2 border-amber-500">
                @else
                    <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                        {{ substr($agent->display_name ?: $agent->name, 0, 1) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="text-white text-sm font-bold truncate">{{ $agent->display_name ?: $agent->name }}</div>
                    <button wire:click="toggleStatus" class="flex items-center gap-1.5 text-[10px] mt-0.5 {{ $agent->is_online ? 'text-green-400' : 'text-slate-500' }}">
                        <span class="w-2 h-2 rounded-full {{ $agent->is_online ? 'bg-green-400 animate-pulse' : 'bg-slate-500' }}"></span>
                        {{ $agent->is_online ? 'Online — tap to go offline' : 'Offline — tap to go online' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            <div x-show="sidebarOpen" class="text-[9px] font-black text-slate-500 uppercase tracking-widest px-3 py-2">Work</div>
            <button wire:click="showSection('chats')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $activeSection === 'chats' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-comments w-5 text-center"></i>
                <span x-show="sidebarOpen" x-cloak>Live Chats</span>
                @php $unread = collect($sessions)->sum('unread_count'); @endphp
                @if($unread > 0)
                    <span x-show="sidebarOpen" class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $unread }}</span>
                @endif
            </button>
            <button wire:click="showSection('tickets')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $activeSection === 'tickets' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-ticket-alt w-5 text-center"></i>
                <span x-show="sidebarOpen" x-cloak>Tickets</span>
            </button>
            <button wire:click="showSection('stats')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $activeSection === 'stats' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-chart-bar w-5 text-center"></i>
                <span x-show="sidebarOpen" x-cloak>My Performance</span>
            </button>

            <div x-show="sidebarOpen" class="text-[9px] font-black text-slate-500 uppercase tracking-widest px-3 py-2 pt-4">Account</div>
            <button wire:click="showSection('profile')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $activeSection === 'profile' ? 'bg-amber-500 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <i class="fas fa-user-cog w-5 text-center"></i>
                <span x-show="sidebarOpen" x-cloak>My Profile</span>
            </button>
        </nav>

        <!-- Bottom -->
        <div class="p-3 border-t border-slate-800 space-y-1">
            <button type="button" onclick="if(window.Notification && Notification.permission!=='granted') Notification.requestPermission().then(p=>{if(p==='granted')alert('Notifications enabled!')})" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                <i class="fas fa-bell w-5 text-center"></i>
                <span x-show="sidebarOpen" x-cloak>Enable Alerts</span>
            </button>
            <form method="POST" action="{{ url((request()->getHost() === 'agent.believoo.com' ? '' : '/agent') . '/logout') }}">
                @csrf
                <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-red-400 hover:bg-red-500/10 transition-all">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    <span x-show="sidebarOpen" x-cloak>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- ══════ Main content ══════ -->
    <main class="flex-1 flex flex-col min-w-0">

        @if($activeSection === 'chats')
        <div class="flex flex-1 min-h-0">
            <!-- Sessions list -->
            <div class="w-80 bg-white border-r border-slate-200 flex flex-col">
                <div class="p-5 border-b border-slate-100">
                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">My Chats</h3>
                    <p class="text-[10px] text-slate-400 mt-1">{{ $agent->active_chats }}/{{ $agent->max_chats }} active</p>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    @forelse($sessions as $session)
                        <div wire:click="selectSession('{{ $session['session_id'] }}')"
                             class="p-4 cursor-pointer transition-all hover:bg-slate-50 border-b border-slate-50 {{ $activeSessionId === $session['session_id'] ? 'bg-amber-50 border-l-4 border-l-amber-500' : '' }}">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-sm text-slate-900 truncate max-w-[150px]">{{ $session['sender_name'] }}</span>
                                <span class="text-[10px] text-slate-400">{{ $session['last_time'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <p class="text-xs text-slate-500 truncate max-w-[180px]">{{ $session['last_message'] }}</p>
                                @if($session['unread_count'] > 0)
                                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $session['unread_count'] }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400 text-sm">
                            <i class="fas fa-inbox text-3xl mb-3 block"></i>
                            No chats assigned yet.<br>When AI connects a client, it appears here.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Chat area -->
            <div class="flex-1 flex flex-col bg-white min-w-0">
                @if($activeSessionId)
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                        <div>
                            @php $activeSession = collect($sessions)->firstWhere('session_id', $activeSessionId); @endphp
                            <h4 class="font-black text-slate-900">{{ $activeSession['sender_name'] ?? 'Client' }}</h4>
                            <p class="text-xs text-slate-500">{{ $activeSession['sender_email'] ?? '' }}</p>
                        </div>
                        <div x-data="{ showClose: false, consent: true }">
                            <button type="button" @click="showClose = true" class="px-4 h-10 rounded-xl bg-red-500/10 text-red-500 text-xs font-bold hover:bg-red-500 hover:text-white transition-all">
                                <i class="fas fa-times-circle mr-1"></i> End Chat
                            </button>
                            <div x-show="showClose" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showClose = false">
                                <div class="bg-white rounded-2xl p-6 w-80 shadow-2xl">
                                    <h5 class="font-bold text-slate-900 mb-2">End this chat?</h5>
                                    <label class="flex items-center gap-2 text-sm text-slate-600 mb-4">
                                        <input type="checkbox" x-model="consent" class="rounded">
                                        Client agreed to close the chat
                                    </label>
                                    <p x-show="!consent" class="text-[10px] text-red-500 mb-3">Warning: Closing without client consent is a ZTP violation and will be recorded on your profile.</p>
                                    <div class="flex gap-2 justify-end">
                                        <button type="button" @click="showClose = false" class="px-4 py-2 rounded-xl text-xs bg-slate-100 text-slate-600">Cancel</button>
                                        <button type="button" @click="$wire.closeChat(consent); showClose = false" class="px-4 py-2 rounded-xl text-xs bg-red-500 text-white font-bold">End Chat</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto p-8 custom-scrollbar space-y-6 bg-slate-50" x-ref="chatContainer">
                        @foreach($messages as $msg)
                            <div class="flex {{ $msg['type'] === 'admin' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] flex flex-col {{ $msg['type'] === 'admin' ? 'items-end' : 'items-start' }}">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $msg['sender_name'] }}</span>
                                    </div>
                                    <div class="px-5 py-3 rounded-2xl text-sm shadow-sm {{ $msg['type'] === 'admin' ? 'bg-amber-500 text-white rounded-tr-none' : 'bg-white text-slate-800 rounded-tl-none border border-slate-100' }}">
                                        {{ $msg['message'] }}
                                        @if(!empty($msg['attachment']))
                                            <div class="mt-2 pt-2 border-t {{ $msg['type'] === 'admin' ? 'border-white/20' : 'border-slate-100' }}">
                                                <a href="{{ Storage::url($msg['attachment']) }}" target="_blank" class="flex items-center space-x-2 text-[10px] {{ $msg['type'] === 'admin' ? 'text-white/80' : 'text-amber-600' }}">
                                                    <i class="fas fa-paperclip"></i>
                                                    <span class="font-bold truncate max-w-[150px]">{{ basename($msg['attachment']) }}</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                    <span class="text-[8px] text-slate-400 mt-1 uppercase tracking-tighter">{{ $msg['created_at'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- AI Suggest + Quick replies + reply box -->
                    <div class="p-5 bg-white border-t border-slate-100 space-y-3">
                        {{-- AI suggestion banner --}}
                        @if($aiSuggestion)
                            <div class="p-3 rounded-xl bg-purple-50 border border-purple-200 text-xs">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <span class="font-bold text-purple-700"><i class="fas fa-robot mr-1"></i>AI Suggestion</span>
                                    <button type="button" wire:click="$set('aiSuggestion', '')" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                                </div>
                                <p class="text-slate-700 whitespace-pre-line">{{ $aiSuggestion }}</p>
                                <button type="button" wire:click="useAiSuggestion" class="mt-2 px-3 py-1 rounded-lg bg-purple-500 text-white font-bold text-[11px] hover:bg-purple-600"><i class="fas fa-check mr-1"></i>Use this reply</button>
                            </div>
                        @endif
                        <div class="flex items-center gap-2 mb-2">
                            <button type="button" wire:click="askAiSuggestion" wire:loading.attr="disabled" wire:target="askAiSuggestion" class="px-3 py-1.5 rounded-lg bg-purple-100 text-purple-700 text-[11px] font-bold hover:bg-purple-200 transition-all disabled:opacity-50">
                                <i class="fas fa-robot mr-1" wire:loading.remove wire:target="askAiSuggestion"></i>
                                <i class="fas fa-spinner fa-spin mr-1" wire:loading wire:target="askAiSuggestion"></i>
                                AI Suggest
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <button type="button" wire:click="openTransferModal" class="px-3 py-1.5 rounded-lg bg-orange-100 text-orange-700 text-[11px] font-bold hover:bg-orange-200"><i class="fas fa-exchange-alt mr-1"></i>Transfer</button>
                            <button type="button" wire:click="loadCustomerHistory" class="px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-[11px] font-bold hover:bg-blue-200"><i class="fas fa-history mr-1"></i>History</button>
                            <button type="button" wire:click="$set('translateLang', 'hi'); translateReply" class="px-3 py-1.5 rounded-lg bg-green-100 text-green-700 text-[11px] font-bold hover:bg-green-200"><i class="fas fa-language mr-1"></i>→ Hindi</button>
                            <button type="button" wire:click="$set('translateLang', 'en'); translateReply" class="px-3 py-1.5 rounded-lg bg-indigo-100 text-indigo-700 text-[11px] font-bold hover:bg-indigo-200"><i class="fas fa-language mr-1"></i>→ English</button>
                            <button type="button" wire:click="startAudioCall" class="px-3 py-1.5 rounded-lg bg-teal-100 text-teal-700 text-[11px] font-bold hover:bg-teal-200"><i class="fas fa-phone mr-1"></i>Audio Call</button>
                            <button type="button" wire:click="startVideoCall" class="px-3 py-1.5 rounded-lg bg-pink-100 text-pink-700 text-[11px] font-bold hover:bg-pink-200"><i class="fas fa-video mr-1"></i>Video Call</button>
                        </div>
                        <div x-data="{ showReplies: false, showManager: @entangle('showCannedManager') }" class="relative">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="showReplies = !showReplies" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-bold hover:bg-amber-100 hover:text-amber-600 transition-all">
                                    <i class="fas fa-bolt mr-1"></i> Quick Replies
                                </button>
                                <button type="button" wire:click="$set('showCannedManager', true)" class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-[11px] hover:bg-slate-200 transition-all" title="Manage replies">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                            <div x-show="showReplies" x-cloak @click.away="showReplies = false" class="absolute bottom-full left-0 mb-2 w-full sm:w-[480px] max-h-56 overflow-y-auto bg-white rounded-2xl shadow-2xl border border-slate-100 p-3 z-10 custom-scrollbar">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach($cannedReplies as $index => $reply)
                                        <button type="button" wire:click="useCannedReplyByIndex({{ $index }})" @click="showReplies = false"
                                            class="text-left px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-amber-50 border border-slate-100 hover:border-amber-200 transition-all">
                                            <span class="block text-[11px] font-bold text-amber-600 mb-0.5">{{ $reply['title'] }}</span>
                                            <span class="block text-[10px] text-slate-500 line-clamp-2">{{ $reply['message'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div x-show="showManager" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showManager = false">
                                <div class="bg-white rounded-2xl p-6 w-[460px] max-h-[80vh] overflow-y-auto shadow-2xl custom-scrollbar">
                                    <div class="flex justify-between items-center mb-4">
                                        <h4 class="font-bold text-slate-900">Manage Quick Replies</h4>
                                        <button type="button" wire:click="$set('showCannedManager', false)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200"><i class="fas fa-times"></i></button>
                                    </div>
                                    <div class="space-y-3 mb-5">
                                        <input type="text" wire:model="newCannedTitle" placeholder="Title e.g. Greeting" class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:border-amber-500 outline-none">
                                        <textarea wire:model="newCannedMessage" rows="2" placeholder="Full reply message..." class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:border-amber-500 outline-none resize-none"></textarea>
                                        <button type="button" wire:click="addCannedReply" class="w-full py-2 rounded-xl bg-amber-500 text-white text-sm font-bold hover:bg-amber-600 transition-all"><i class="fas fa-plus mr-1"></i> Add</button>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($cannedReplies as $index => $reply)
                                            <div class="flex items-start justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-slate-700 truncate">{{ $reply['title'] }}</div>
                                                    <div class="text-[11px] text-slate-500 line-clamp-1">{{ $reply['message'] }}</div>
                                                </div>
                                                <button type="button" wire:click="removeCannedReply({{ $index }})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between">
                                        <button type="button" wire:click="resetCannedReplies" class="text-xs text-slate-500 hover:text-amber-600 font-bold">Reset defaults</button>
                                        <button type="button" wire:click="$set('showCannedManager', false)" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200">Done</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form wire:submit.prevent="sendReply" class="flex items-end space-x-3 bg-slate-50 p-3 rounded-2xl border border-slate-100 focus-within:ring-2 focus-within:ring-amber-500/20 transition-all">
                            <div class="flex-1">
                                <textarea wire:model="replyMessage"
                                       x-on:keydown.enter.prevent="if(!$event.shiftKey) $wire.sendReply()"
                                       placeholder="Type your reply..."
                                       rows="1"
                                       class="w-full bg-transparent border-none focus:ring-0 text-sm py-2 px-2 resize-none custom-scrollbar min-h-[38px] max-h-[140px] text-slate-900"></textarea>
                            </div>
                            <label class="flex-shrink-0 w-11 h-11 rounded-xl bg-white text-slate-400 hover:text-amber-600 flex items-center justify-center cursor-pointer transition-all border border-slate-200">
                                <input type="file" wire:model="replyAttachment" class="hidden" accept="image/*,.pdf">
                                <i class="fas fa-paperclip text-sm"></i>
                            </label>
                            <button type="submit" class="flex-shrink-0 w-11 h-11 bg-amber-500 text-white rounded-xl hover:bg-amber-600 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-amber-500/20 flex items-center justify-center">
                                <i class="fas fa-paper-plane text-sm"></i>
                            </button>
                        </form>
                        <div class="flex justify-between items-center px-2">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><kbd class="px-1.5 py-0.5 bg-slate-100 rounded font-sans">Enter</kbd> send</span>
                            <div class="flex items-center gap-3">
                                <div wire:loading wire:target="replyAttachment" class="text-[10px] text-amber-600 italic">Uploading...</div>
                                @if($replyAttachment)
                                    <span class="text-[10px] text-green-600 font-bold"><i class="fas fa-check mr-1"></i>File ready</span>
                                @endif
                            </div>
                        </div>

                        {{-- Transfer modal --}}
                        @if(count($availableAgents) > 0)
                            <div class="mt-3 p-3 bg-orange-50 rounded-xl border border-orange-200">
                                <p class="text-[11px] font-bold text-orange-700 mb-2"><i class="fas fa-exchange-alt mr-1"></i>Transfer chat</p>
                                <div class="flex gap-2">
                                    <select wire:model="transferTo" class="text-[11px] p-2 rounded-lg border border-slate-200 bg-white">
                                        <option value="">Select agent</option>
                                        @foreach($availableAgents as $a)
                                            <option value="{{ $a->id }}">{{ $a->display_name ?: $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" wire:model="transferReason" placeholder="Reason" class="text-[11px] p-2 rounded-lg border border-slate-200 flex-1">
                                    <button type="button" wire:click="transferChat" class="px-3 py-1 rounded-lg bg-orange-500 text-white text-[11px] font-bold">Transfer</button>
                                </div>
                            </div>
                        @endif

                        {{-- Tags --}}
                        <div class="mt-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <p class="text-[11px] font-bold text-slate-600 mb-2"><i class="fas fa-tags mr-1"></i>Tags</p>
                            <div class="flex flex-wrap gap-1 mb-2">
                                @foreach($chatTags as $t)
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold flex items-center gap-1">{{ $t->tag }} <button wire:click="removeTag({{ $t->id }})" class="text-amber-900 hover:text-red-500">×</button></span>
                                @endforeach
                            </div>
                            <div class="flex gap-2">
                                <input type="text" wire:model="newTag" placeholder="Add tag" class="text-[11px] p-2 rounded-lg border border-slate-200 flex-1">
                                <button type="button" wire:click="addTag" class="px-3 py-1 rounded-lg bg-slate-600 text-white text-[11px] font-bold">Add</button>
                            </div>
                        </div>

                        {{-- Internal notes --}}
                        <div class="mt-3 p-3 bg-yellow-50 rounded-xl border border-yellow-200">
                            <p class="text-[11px] font-bold text-yellow-700 mb-2"><i class="fas fa-sticky-note mr-1"></i>Internal Notes (client can't see)</p>
                            <div class="space-y-2 mb-2 max-h-32 overflow-y-auto">
                                @foreach($notes as $n)
                                    <div class="text-[10px] p-2 bg-white rounded border border-yellow-100">
                                        <span class="font-bold">{{ $n->agent->display_name ?? 'Agent' }}</span>: {{ $n->note }}
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex gap-2">
                                <input type="text" wire:model="agentNote" placeholder="Add note" class="text-[11px] p-2 rounded-lg border border-yellow-200 flex-1">
                                <button type="button" wire:click="addNote" class="px-3 py-1 rounded-lg bg-yellow-500 text-white text-[11px] font-bold">Add</button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center text-center p-8">
                        <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-6">
                            <i class="fas fa-comments text-4xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">No chat selected</h3>
                        <p class="text-slate-500 text-sm max-w-xs mt-2">When a client is connected to you, their chat will appear in the list.</p>
                    </div>
                @endif
            </div>
        </div>
        @endif

        @if($activeSection === 'tickets')
        <div class="flex-1 overflow-y-auto p-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h4 class="font-bold text-slate-900 mb-4"><i class="fas fa-ticket-alt mr-2 text-amber-500"></i>All Support Tickets</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[10px] text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-2 pr-4">Ticket</th><th class="pb-2 pr-4">Client</th><th class="pb-2 pr-4">Subject</th><th class="pb-2 pr-4">Priority</th><th class="pb-2 pr-4">Status</th><th class="pb-2">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $t)
                                <tr class="border-b border-slate-50">
                                    <td class="py-2.5 pr-4 font-mono text-xs">{{ $t['ticket_id'] }}</td>
                                    <td class="py-2.5 pr-4">{{ $t['name'] }}<br><span class="text-[10px] text-slate-400">{{ $t['email'] }}</span></td>
                                    <td class="py-2.5 pr-4 text-xs">{{ Str::limit($t['subject'], 40) }}</td>
                                    <td class="py-2.5 pr-4"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $t['priority'] === 'high' ? 'bg-red-100 text-red-600' : ($t['priority'] === 'medium' ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-500') }}">{{ ucfirst($t['priority']) }}</span></td>
                                    <td class="py-2.5 pr-4"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $t['status'] === 'open' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500' }}">{{ ucfirst($t['status']) }}</span></td>
                                    <td class="py-2.5 text-[10px] text-slate-400">{{ $t['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-8 text-slate-400 text-sm">No tickets yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($activeSection === 'stats')
        <div class="flex-1 overflow-y-auto p-6">
            <h4 class="font-bold text-slate-900 mb-4"><i class="fas fa-chart-bar mr-2 text-amber-500"></i>My Performance</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @php
                    $cards = [
                        ['Total Chats', $stats['total_chats'] ?? 0, 'fa-comments', 'text-amber-500'],
                        ['Active Now', $stats['active_chats'] ?? 0, 'fa-circle', 'text-green-500'],
                        ['Today', $stats['today_chats'] ?? 0, 'fa-calendar-day', 'text-blue-500'],
                        ['This Week', $stats['week_chats'] ?? 0, 'fa-calendar-week', 'text-indigo-500'],
                        ['Msgs Sent', $stats['messages_sent'] ?? 0, 'fa-paper-plane', 'text-emerald-500'],
                        ['AI Convos', $stats['ai_conversations'] ?? 0, 'fa-robot', 'text-purple-500'],
                        ['Avg Rating', ($stats['avg_rating'] ?? 0) ? number_format($stats['avg_rating'],1).' ★' : '—', 'fa-star', 'text-yellow-500'],
                        ['Avg 1st Reply', isset($stats['avg_first_reply']) && $stats['avg_first_reply'] ? round($stats['avg_first_reply']).'s' : '—', 'fa-clock', 'text-cyan-500'],
                        ['Late Replies', $stats['late_replies'] ?? 0, 'fa-exclamation-triangle', 'text-orange-500'],
                        ['ZTP Flags', $stats['ztp_flags'] ?? 0, 'fa-flag', 'text-red-500'],
                        ['Ratings', $stats['rating_count'] ?? 0, 'fa-heart', 'text-pink-500'],
                        ['Max Chats', $agent->max_chats, 'fa-layer-group', 'text-slate-500'],
                    ];
                @endphp
                @foreach($cards as $c)
                    <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm">
                        <i class="fas {{ $c[2] }} {{ $c[3] }} text-xl mb-2"></i>
                        <div class="text-2xl font-black text-slate-800">{{ $c[1] }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $c[0] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($activeSection === 'profile')
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-xl bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h4 class="font-bold text-slate-900 mb-5"><i class="fas fa-user-cog mr-2 text-amber-500"></i>My Profile</h4>

                @if($profileSaved)
                    <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-700 text-sm font-medium"><i class="fas fa-check-circle mr-1"></i>Profile updated!</div>
                @endif

                <div class="flex items-center gap-4 mb-6">
                    @if($agent->avatar)
                        <img src="{{ Storage::url($agent->avatar) }}" class="w-16 h-16 rounded-full object-cover border-2 border-amber-500">
                    @else
                        <div class="w-16 h-16 rounded-full bg-amber-500 flex items-center justify-center text-white font-bold text-2xl">{{ substr($agent->display_name ?: $agent->name, 0, 1) }}</div>
                    @endif
                    <label class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-amber-100 hover:text-amber-600 cursor-pointer transition-all">
                        <input type="file" wire:model="profilePhoto" class="hidden" accept="image/*">
                        <i class="fas fa-camera mr-1"></i> Change Photo
                    </label>
                    <div wire:loading wire:target="profilePhoto" class="text-[10px] text-amber-600 italic">Uploading...</div>
                    @if($profilePhoto)
                        <span class="text-[10px] text-green-600 font-bold"><i class="fas fa-check mr-1"></i>New photo ready</span>
                    @endif
                </div>
                @error('profilePhoto')<p class="text-red-500 text-xs mb-3">{{ $message }}</p>@enderror

                <div class="space-y-4">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-500">
                        <i class="fas fa-info-circle mr-1 text-amber-500"></i> Name, email and phone are managed by the admin. Contact admin to change them.
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Real Name</label>
                        <input type="text" value="{{ $profileName }}" readonly class="w-full mt-1 px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-sm text-slate-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Display Name (clients see this)</label>
                        <input type="text" value="{{ $profileDisplayName }}" readonly class="w-full mt-1 px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-sm text-slate-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Login Email</label>
                        <input type="email" value="{{ $profileEmail }}" readonly class="w-full mt-1 px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-sm text-slate-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Phone</label>
                        <input type="text" value="{{ $profilePhone }}" readonly class="w-full mt-1 px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-sm text-slate-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">New Password (blank = keep current)</label>
                        <input type="password" wire:model="profilePassword" class="w-full mt-1 px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm focus:border-amber-500 outline-none">
                        @error('profilePassword')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button wire:click="saveProfile" class="w-full py-3 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-all">
                        <i class="fas fa-save mr-1"></i> Save Profile
                    </button>
                </div>
            </div>
        </div>
        @endif
    </main>
</div>
