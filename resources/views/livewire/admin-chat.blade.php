<div class="flex h-[calc(100vh-160px)] bg-white dark:bg-gray-900 rounded-3xl overflow-hidden shadow-xl border border-gray-100 dark:border-gray-800"
     wire:poll.60s="ping"
     x-data="{
        scrollToBottom() { 
            $nextTick(() => {
                const container = $refs.chatContainer;
                if (container) container.scrollTop = container.scrollHeight;
            });
        }
     }" 
     x-init="scrollToBottom()"
     x-on:scroll-chat-to-bottom.window="scrollToBottom()">
    
    <div x-data="{ 
            audio: new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'),
            playNotification() {
                this.audio.play().catch(e => console.log('Audio play blocked'));
            }
        }" @notification-received.window="playNotification()" @play-notification-sound.window="playNotification()" @play-ping-sound.window="playNotification()"></div>

    <!-- Sessions Sidebar -->
    <div class="w-80 border-r border-gray-100 dark:border-gray-800 flex flex-col">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Active Chats</h3>
        </div>
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            @foreach($sessions as $session)
                <div wire:click="selectSession('{{ $session['session_id'] }}')" 
                     class="p-4 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50 border-b border-gray-50 dark:border-gray-800/50 {{ $activeSessionId === $session['session_id'] ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500' : '' }}">
                    <div class="flex justify-between items-start mb-1">
                        <span class="font-bold text-sm text-gray-900 dark:text-white truncate max-w-[150px]">{{ $session['sender_name'] }}</span>
                        <span class="text-[10px] text-gray-400">{{ $session['last_time'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-xs text-gray-500 truncate max-w-[180px]">{{ $session['last_message'] }}</p>
                        @if($session['unread_count'] > 0)
                            <span class="w-5 h-5 bg-blue-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center animate-pulse">
                                {{ $session['unread_count'] }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Chat Window -->
    <div class="flex-1 flex flex-col bg-gray-50/30 dark:bg-gray-900/30">
        @if($activeSessionId)
            <!-- Chat Header -->
            <div class="p-6 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                <div>
                    @php 
                        $activeSession = collect($sessions)->firstWhere('session_id', $activeSessionId);
                    @endphp
                    <h4 class="font-black text-gray-900 dark:text-white">{{ $activeSession['sender_name'] ?? 'Chat' }}</h4>
                    <p class="text-xs text-gray-500">{{ $activeSession['sender_email'] ?? '' }}</p>
                </div>
                <div class="flex items-center space-x-2" x-data="{ showClose: false, consent: true }">
                    @if($activeSession['phone_number'] ?? null)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $activeSession['phone_number']) }}" target="_blank" class="w-10 h-10 rounded-xl bg-green-500/10 text-green-500 flex items-center justify-center hover:bg-green-500 hover:text-white transition-all">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="tel:{{ $activeSession['phone_number'] }}" class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center hover:bg-blue-500 hover:text-white transition-all">
                            <i class="fas fa-phone"></i>
                        </a>
                    @endif
                    <button type="button" @click="showClose = true" class="px-4 h-10 rounded-xl bg-red-500/10 text-red-500 text-xs font-bold hover:bg-red-500 hover:text-white transition-all">
                        <i class="fas fa-times-circle mr-1"></i> End Chat
                    </button>

                    <!-- End Chat consent modal -->
                    <div x-show="showClose" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showClose = false">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-80 shadow-2xl">
                            <h5 class="font-bold text-gray-900 dark:text-white mb-2">End this chat?</h5>
                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 mb-4">
                                <input type="checkbox" x-model="consent" class="rounded">
                                Client agreed to close the chat
                            </label>
                            <p x-show="!consent" class="text-[10px] text-red-500 mb-3">Warning: Closing without client consent is a ZTP violation and will be recorded on your profile.</p>
                            <div class="flex gap-2 justify-end">
                                <button type="button" @click="showClose = false" class="px-4 py-2 rounded-xl text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">Cancel</button>
                                <button type="button" @click="$wire.closeChat(consent); showClose = false" class="px-4 py-2 rounded-xl text-xs bg-red-500 text-white font-bold">End Chat</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar space-y-6" x-ref="chatContainer">
                @foreach($messages as $msg)
                    <div class="flex {{ $msg['type'] === 'admin' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[70%] flex flex-col {{ $msg['type'] === 'admin' ? 'items-end' : 'items-start' }}">
                            <div class="flex items-center space-x-2 mb-1">
                                @if($msg['type'] === 'admin')
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $msg['sender_name'] }}</span>
                                    @if($msg['sender_photo'])
                                        <img src="{{ $msg['sender_photo'] }}" loading="lazy" class="w-5 h-5 rounded-full object-cover">
                                    @else
                                        <div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center text-[8px] text-white font-bold">
                                            {{ substr($msg['sender_name'], 0, 1) }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $msg['sender_name'] }}</span>
                                @endif
                            </div>
                            <div class="px-5 py-3 rounded-2xl text-sm shadow-sm {{ $msg['type'] === 'admin' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-tl-none border border-gray-100 dark:border-gray-700' }}">
                                {{ $msg['message'] }}
                                
                                @if(isset($msg['attachment']) && $msg['attachment'])
                                    <div class="mt-2 pt-2 border-t {{ $msg['type'] === 'admin' ? 'border-white/20' : 'border-gray-100 dark:border-gray-700' }}">
                                        <a href="{{ Storage::url($msg['attachment']) }}" target="_blank" class="flex items-center space-x-2 text-[10px] {{ $msg['type'] === 'admin' ? 'text-white/80 hover:text-white' : 'text-blue-500 hover:text-blue-600' }} transition-colors">
                                            <i class="fas fa-paperclip"></i>
                                            <span class="font-bold truncate max-w-[150px]">{{ basename($msg['attachment']) }}</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                            <span class="text-[8px] text-gray-400 mt-1 uppercase tracking-tighter">{{ $msg['created_at'] }}</span>
                        </div>
                    </div>
                @endforeach
                @if($clientIsTyping)
                    <div class="flex justify-start">
                        <div class="max-w-[70%] flex flex-col items-start">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $activeSession['sender_name'] ?? 'User' }}</span>
                            </div>
                            <div class="px-5 py-3 rounded-2xl text-sm shadow-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-tl-none border border-gray-100 dark:border-gray-700 italic">
                                <span class="animate-pulse">Typing...</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Reply Box -->
            <div class="p-6 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
                <div class="mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Live Response</span>
                </div>
                <form wire:submit.prevent="sendReply" class="flex items-end space-x-4 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-[2rem] border border-gray-100 dark:border-gray-700/50 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all">
                    <div class="flex-1">
                        <textarea 
                               wire:model="replyMessage" 
                               x-on:keydown.enter.prevent="if(!$event.shiftKey) $wire.sendReply()"
                               placeholder="Type your reply here..." 
                               rows="1"
                               x-data="{ 
                                   resize() { 
                                       $el.style.height = 'auto'; 
                                       $el.style.height = ($el.scrollHeight) + 'px'; 
                                   } 
                               }"
                               x-init="resize()"
                               x-on:input="resize()"
                               class="w-full bg-transparent border-none focus:ring-0 text-sm py-2 px-2 resize-none custom-scrollbar min-h-[40px] max-h-[150px] dark:text-white"
                        ></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="flex-shrink-0 w-12 h-12 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center group">
                        <i class="fas fa-paper-plane text-sm group-hover:rotate-12 transition-transform"></i>
                    </button>
                </form>
                <div class="mt-3 flex justify-between items-center px-4">
                    <div class="flex items-center space-x-4">
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest flex items-center">
                            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded mr-1 font-sans">Enter</kbd> to send
                        </span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest flex items-center">
                            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded mr-1 font-sans">Shift + Enter</kbd> for new line
                        </span>
                    </div>
                    @if($activeSession['last_time'] ?? null)
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                            Client last active: {{ $activeSession['last_time'] }}
                        </span>
                    @endif
                </div>
            </div>
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center p-8">
                <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-comments text-4xl text-gray-300"></i>
                </div>
                <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Select a conversation</h3>
                <p class="text-gray-500 text-sm max-w-xs mt-2">Choose a chat from the sidebar to start responding to your clients in real-time.</p>
            </div>
        @endif
    </div>

    <!-- Notification Sound -->
    <audio id="ping-sound" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3" preload="auto"></audio>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('play-ping-sound', () => {
                const audio = document.getElementById('ping-sound');
                if (audio) {
                    audio.play().catch(e => console.log('Audio play failed:', e));
                }
            });

            let clientTypingTimeout;
            Livewire.on('reset-client-typing', () => {
                clearTimeout(clientTypingTimeout);
                clientTypingTimeout = setTimeout(() => {
                    @this.set('clientIsTyping', false);
                }, 3000);
            });
        });
    </script>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.2);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.5);
        }
    </style>
</div>
