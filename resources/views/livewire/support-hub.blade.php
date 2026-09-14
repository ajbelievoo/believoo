<div class="fixed bottom-0 right-0 z-[12000000] flex flex-col items-end p-6 sm:p-10 pointer-events-none support-hub-container" x-data="{
    isOpen: @entangle('isOpen').live,
    activeTab: @entangle('activeTab').live,
    listening: false,
    speaking: false,
    muted: false,
    pendingAi: [],
    aiTyping: false,
    proactiveShown: false,
    showProactive() {
        // Auto-greet after 30s if chat not opened yet
        if (!this.proactiveShown && !this.isOpen) {
            this.proactiveShown = true;
            const el = this.$el.querySelector('.proactive-bubble');
            if (el) el.style.display = 'block';
        }
    },
    openFromProactive() {
        this.isOpen = true;
        this.activeTab = 'ai';
        const el = this.$el.querySelector('.proactive-bubble');
        if (el) el.style.display = 'none';
    },
    onAiSubmit() {
        const input = this.$refs.aiQuestionInput;
        const q = input ? input.value.trim() : '';
        if (q.length >= 2) {
            // Show the user message instantly, before the server responds.
            this.pendingAi.push(q);
            this.aiTyping = true;
            this.scrollToBottom();

            // Make sure Livewire has the question before calling askAi(),
            // then send the message. askAi() itself clears the input on success.
            this.$wire.aiQuestion = q;
            this.$wire.askAi();

            // Failsafe: clear typing state if server never replies
            setTimeout(() => { this.pendingAi = []; this.aiTyping = false; }, 45000);
        }
    },
    scrollToBottom() {
        $nextTick(() => {
            const container = $refs.chatContainer;
            if (container) container.scrollTop = container.scrollHeight;
        });
    },
    startListening() {
        if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
            alert('Voice input is not supported in this browser. Please use Chrome or Edge.');
            return;
        }
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        const curLang = this.$wire.get('aiLang') || 'en';
        recognition.lang = this.langMap ? this.langMap(curLang) : (curLang === 'hi' ? 'hi-IN' : 'en-IN');
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        this.listening = true;
        const self = this;
        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            self.$wire.set('aiQuestion', transcript).then(() => {
                self.$wire.askAi();
            });
        };
        recognition.onerror = function() {
            self.listening = false;
        };
        recognition.onend = function() {
            self.listening = false;
        };
        recognition.start();
    },
    langMap(lang) {
        const map = { 'hi': 'hi-IN', 'ur': 'ur-PK', 'bn': 'bn-IN', 'ta': 'ta-IN', 'te': 'te-IN', 'gu': 'gu-IN', 'pa': 'pa-IN', 'kn': 'kn-IN', 'ml': 'ml-IN', 'en': 'en-IN' };
        return map[lang] || 'en-IN';
    },
    pickVoice(voices, lang) {
        const target = this.langMap(lang);
        const langPrefix = target.split('-')[0];
        const femaleNames = /zira|samantha|tessa|veena|swara|lekha|heera|kalpana|neerja|jenny|aria|victoria|fiona|female|madhur|lekha|aditi|neural/i;
        // Exact language match first
        let voice = voices.find(v => v.lang === target && femaleNames.test(v.name));
        if (!voice) voice = voices.find(v => v.lang.startsWith(langPrefix) && femaleNames.test(v.name));
        if (!voice) voice = voices.find(v => v.lang === target);
        if (!voice) voice = voices.find(v => v.lang.startsWith(langPrefix));
        return voice || null;
    },
    speak(text, lang) {
        if (this.muted) return;
        if (!window.speechSynthesis) return;
        window.speechSynthesis.cancel();

        const doSpeak = () => {
            const voices = window.speechSynthesis.getVoices();
            if (!voices.length) {
                setTimeout(doSpeak, 100);
                return;
            }
            const targetLang = this.langMap(lang);
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = targetLang;
            const voice = this.pickVoice(voices, lang);
            if (voice) utterance.voice = voice;
            // Try to make it sound more like a natural female voice
            utterance.pitch = 1.12;
            utterance.rate = 0.95;
            utterance.volume = 1.0;
            this.speaking = true;
            utterance.onend = () => this.speaking = false;
            utterance.onerror = () => this.speaking = false;
            window.speechSynthesis.speak(utterance);
        };

        doSpeak();
    },
    toggleMute() {
        this.muted = !this.muted;
        if (this.muted) window.speechSynthesis.cancel();
    }
}" x-on:scroll-chat-to-bottom.window="scrollToBottom()"
   x-init="
        $wire.on('ai-reply', (event) => {
            this.pendingAi = [];
            this.aiTyping = false;
            const payload = Array.isArray(event) ? event[0] : event;
            const message = payload?.message || '';
            const lang = payload?.lang || 'en';
            speak(message, lang);
        });
        $wire.on('scroll-chat-to-bottom', () => {});
        if (window.speechSynthesis) {
            if (window.speechSynthesis.getVoices().length) {
                window.speechSynthesis.getVoices();
            } else if (window.speechSynthesis.onvoiceschanged !== undefined) {
                window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
            }
        }
        // Clear optimistic messages once Livewire has rendered the real ones
        if (window.Livewire) {
            Livewire.hook('commit', ({ component, succeed, fail }) => {
                if (component.name === 'support-hub') {
                    succeed(() => { this.pendingAi = []; this.aiTyping = false; });
                    fail(() => { this.pendingAi = []; this.aiTyping = false; });
                }
            });
        }
    ">

    <!-- Chat Window -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-10 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-10 scale-95"
         class="mb-6 w-[380px] sm:w-[420px] h-[620px] sm:h-[650px] bg-white rounded-[2rem] shadow-2xl border border-slate-200 flex flex-col overflow-hidden relative z-[12000001] pointer-events-auto"
         @click.away="isOpen = false"
         wire:key="support-hub-window"
         x-cloak>
        <!-- Header -->
        <div class="p-6 bg-gradient-to-r from-amber-500 to-amber-600 flex-shrink-0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-white tracking-tight">Support Hub</h3>
                <button wire:click="toggleChat" class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors">
                    <i class="fas fa-times text-white text-sm"></i>
                </button>
            </div>
            <div class="grid grid-cols-4 gap-1 bg-white/20 p-1 rounded-xl">
                <button wire:click="$set('activeTab', 'chat')" class="py-2.5 rounded-lg font-semibold text-[10px] sm:text-xs transition-all {{ $activeTab == 'chat' ? 'bg-white text-amber-600 shadow-sm' : 'text-white/80 hover:bg-white/10' }}">Chat</button>
                <button wire:click="$set('activeTab', 'ai')" class="py-2.5 rounded-lg font-semibold text-[10px] sm:text-xs transition-all {{ $activeTab == 'ai' ? 'bg-white text-amber-600 shadow-sm' : 'text-white/80 hover:bg-white/10' }}">Agent</button>
                <button wire:click="$set('activeTab', 'callback')" class="py-2.5 rounded-lg font-semibold text-[10px] sm:text-xs transition-all {{ $activeTab == 'callback' ? 'bg-white text-amber-600 shadow-sm' : 'text-white/80 hover:bg-white/10' }}">Call</button>
                <button wire:click="$set('activeTab', 'tickets')" class="py-2.5 rounded-lg font-semibold text-[10px] sm:text-xs transition-all {{ $activeTab == 'tickets' ? 'bg-white text-amber-600 shadow-sm' : 'text-white/80 hover:bg-white/10' }}">Ticket</button>
            </div>
            @if($whatsappLink)
                <a href="{{ $whatsappLink }}" target="_blank" class="mt-3 flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white rounded-xl py-2.5 text-xs font-bold transition-all shadow-md">
                    <i class="fab fa-whatsapp text-base"></i> Chat on WhatsApp
                </a>
            @endif
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-slate-50" x-ref="chatContainer">
            @if($activeTab == 'chat')
                <div @if($agentChat) wire:poll.15s="loadMessages" @endif>
                    {{-- Offline form: no agents online → leave a message --}}
                    @php $agentsOnline = \App\Models\SupportAgent::where('is_online', true)->where('is_active', true)->count(); @endphp
                    @if(!$isRegistered && count($chatMessages) == 0 && $agentsOnline == 0)
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 rounded-full bg-slate-200 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-moon text-slate-400 text-xl"></i>
                            </div>
                            <p class="text-slate-500 text-sm font-medium">Our team is offline right now.</p>
                            <p class="text-slate-400 text-xs mt-1">Leave a message — we'll reply within 24 hours via email.</p>
                        </div>
                        <form wire:submit.prevent="sendOfflineMessage" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Your Name</label>
                                <input type="text" wire:model="name" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                @error('name') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Email Address</label>
                                <input type="email" wire:model="email" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                @error('email') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Your Message</label>
                                <textarea wire:model="message" rows="3" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900"></textarea>
                                @error('message') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl transition-all">
                                <i class="fas fa-envelope mr-2"></i> Leave a Message
                            </button>
                        </form>
                    @elseif(!$isRegistered && count($chatMessages) == 0)
                        <div class="text-center mb-6">
                            <p class="text-slate-500 text-sm">Welcome! Please introduce yourself to start a live chat with our team.</p>
                        </div>
                        <form wire:submit.prevent="sendMessage" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Your Name</label>
                                <input type="text" wire:model="name" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                @error('name') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Email Address</label>
                                <input type="email" wire:model="email" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                @error('email') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Phone Number (Optional)</label>
                                <input type="tel" wire:model="phone" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                @error('phone') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Initial Message</label>
                                <textarea wire:model="message" rows="3" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900"></textarea>
                                @error('message') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="w-full py-3.5 rounded-xl bg-amber-500 text-white font-bold text-sm shadow-md hover:bg-amber-600 transition-colors">Start Chat</button>
                        </form>
                    @else
                        <div class="space-y-4 mb-4">
                            @foreach($chatMessages as $msg)
                                @php if (!is_array($msg)) continue; @endphp
                                <div class="flex {{ ($msg['type'] ?? 'user') == 'user' ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[85%] flex flex-col {{ $msg['type'] == 'user' ? 'items-end' : 'items-start' }}">
                                        @if(($msg['type'] ?? 'user') == 'admin')
                                            <div class="flex items-center space-x-2 mb-1">
                                                @if($msg['sender_photo'] ?? null)
                                                    <img src="{{ $msg['sender_photo'] }}" loading="lazy" class="w-5 h-5 rounded-full object-cover">
                                                @else
                                                    <div class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-[8px] text-white font-bold">
                                                        {{ is_string($msg['sender_name'] ?? null) ? substr($msg['sender_name'], 0, 1) : 'A' }}
                                                    </div>
                                                @endif
                                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ is_string($msg['sender_name'] ?? null) ? $msg['sender_name'] : 'Agent' }}</span>
                                            </div>
                                        @endif
                                        <div class="px-4 py-3 rounded-2xl text-sm shadow-sm {{ ($msg['type'] ?? 'user') == 'user' ? 'bg-amber-500 text-white font-medium rounded-tr-none' : 'bg-white text-slate-700 rounded-tl-none border border-slate-100' }}">
                                            {{ $msg['message'] ?? '' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1 {{ ($msg['type'] ?? 'user') == 'user' ? 'justify-end' : '' }}">
                                            {{ $msg['created_at'] ?? '' }}
                                            @if(($msg['type'] ?? 'user') == 'user')
                                                @if(!empty($msg['is_read']))
                                                    <i class="fas fa-check-double text-sky-500" title="Seen"></i>
                                                @else
                                                    <i class="fas fa-check text-slate-300" title="Sent"></i>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @if($adminIsTyping)
                                <div class="flex justify-start">
                                    <div class="max-w-[85%] flex flex-col items-start">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <div class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-[8px] text-white font-bold">
                                                {{ substr($adminName, 0, 1) }}
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $adminName }}</span>
                                        </div>
                                        <div class="px-4 py-3 rounded-2xl text-sm shadow-sm bg-white text-slate-500 rounded-tl-none border border-slate-100 italic">
                                            <span class="animate-pulse">Typing...</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Agent rating card --}}
                            @if($agentChat)
                                <div class="mt-6 bg-white border border-amber-100 rounded-2xl p-4 text-center">
                                    @if($agentChat['rated'])
                                        <p class="text-xs text-green-600 font-bold"><i class="fas fa-check-circle mr-1"></i>Thanks for rating {{ $agentChat['agent_name'] }}!</p>
                                    @else
                                        <p class="text-xs font-bold text-slate-700 mb-1">How was your chat with {{ $agentChat['agent_name'] }}?</p>
                                        <p class="text-[10px] text-slate-400 mb-3">Your feedback helps us keep our team professional.</p>
                                        <div class="flex justify-center gap-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                <button type="button" wire:click="rateAgent({{ $i }})" class="w-9 h-9 rounded-full bg-amber-50 hover:bg-amber-500 hover:text-white text-amber-500 transition-all text-sm" title="{{ $i }} star"><i class="fas fa-star"></i></button>
                                            @endfor
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if($activeTab == 'tickets')
                <div class="space-y-6">
                    @if($selectedTicket)
                        <div class="flex flex-col h-full min-h-[400px]">
                            <div class="flex items-center gap-3 mb-4">
                                <button wire:click="backToTickets" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors">
                                    <i class="fas fa-chevron-left text-xs text-slate-500"></i>
                                </button>
                                <div>
                                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">{{ $selectedTicket['ticket_id'] }}</span>
                                    <h4 class="text-sm font-bold text-slate-900">{{ $selectedTicket['subject'] }}</h4>
                                </div>
                            </div>

                            @if(isset($selectedTicket['attachments']) && count($selectedTicket['attachments']) > 0)
                                <div class="px-4 py-3 bg-white border border-slate-100 mb-4 rounded-xl">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Files from Support</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($selectedTicket['attachments'] as $attachment)
                                            <a href="{{ Storage::url($attachment) }}" target="_blank" class="flex items-center space-x-2 px-3 py-2 bg-slate-50 rounded-xl border border-slate-100 hover:border-amber-300 transition-all">
                                                <i class="fas fa-file-pdf text-red-500 text-[10px]"></i>
                                                <span class="text-[10px] font-bold text-slate-700 uppercase truncate max-w-[80px]">{{ basename($attachment) }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex-grow space-y-4 overflow-y-auto custom-scrollbar pr-2 max-h-[350px]">
                                @foreach($selectedTicket['messages'] as $msg)
                                    <div class="flex flex-col {{ $msg['sender_type'] === 'user' ? 'items-end' : 'items-start' }} mb-4">
                                        <div class="flex items-center gap-2 mb-1 {{ $msg['sender_type'] === 'user' ? 'flex-row-reverse' : '' }}">
                                            @if($msg['sender_type'] === 'admin')
                                                <div class="w-6 h-6 rounded-full bg-amber-500 flex items-center justify-center text-[10px] text-white font-bold">S</div>
                                            @else
                                                <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px] text-slate-700 font-bold">
                                                    {{ substr($msg['sender_name'], 0, 1) }}
                                                </div>
                                            @endif
                                            <span class="text-[10px] font-bold {{ $msg['sender_type'] === 'user' ? 'text-slate-500' : 'text-amber-600' }} uppercase tracking-wider">{{ $msg['sender_name'] }}</span>
                                            <span class="text-[10px] text-slate-400 italic">{{ \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() }}</span>
                                        </div>
                                        <div class="p-4 rounded-2xl max-w-[85%] {{ $msg['type'] === 'user' ? 'bg-amber-500 text-white rounded-tr-none' : 'bg-white border border-slate-100 rounded-tl-none text-slate-700' }}">
                                            <p class="text-xs leading-relaxed">{{ $msg['message'] }}</p>

                                            @if(isset($msg['attachment']) && $msg['attachment'])
                                                <div class="mt-2 pt-2 border-t border-white/20 flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-1 text-[10px] text-slate-300 overflow-hidden">
                                                        <i class="fas fa-paperclip shrink-0"></i>
                                                        <span class="truncate">{{ basename($msg['attachment']) }}</span>
                                                    </div>
                                                    <a href="{{ Storage::url($msg['attachment']) }}" target="_blank" class="p-1 rounded bg-white/20 hover:bg-amber-600 hover:text-white transition-all">
                                                        <i class="fas fa-external-link-alt text-[8px]"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                                <div class="flex gap-2">
                                    <div class="relative flex-grow">
                                        <input type="text" wire:model="ticketMessage" placeholder="Reply to ticket..." class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm pr-10 text-slate-900">
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                            <label class="cursor-pointer text-slate-400 hover:text-amber-600 transition-colors">
                                                <input type="file" wire:model="ticketAttachment" class="hidden" accept="image/*,application/pdf">
                                                <i class="fas fa-paperclip text-xs"></i>
                                            </label>
                                        </div>
                                    </div>
                                    <button wire:click="replyToTicket({{ $selectedTicket['id'] }})" class="p-3 rounded-xl bg-amber-500 text-white hover:bg-amber-600 transition-colors">
                                        <i class="fas fa-paper-plane text-sm"></i>
                                    </button>
                                </div>
                                <div class="flex justify-between items-center px-1">
                                    @error('ticketMessage') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                    <div wire:loading wire:target="ticketAttachment" class="text-[10px] text-amber-600 italic">Uploading...</div>
                                    @if($ticketAttachment)
                                        <div class="text-[10px] text-green-600 font-bold flex items-center">
                                            <i class="fas fa-check mr-1"></i> File ready
                                            <button wire:click="$set('ticketAttachment', null)" class="ml-1 text-red-500" aria-label="Remove attachment"><i class="fas fa-times"></i></button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        @if(!$isRegistered && empty($userTickets))
                            <div class="text-center mb-6">
                                <p class="text-slate-500 text-sm">Create a ticket for support. Please provide your contact info.</p>
                            </div>
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Name</label>
                                    <input type="text" wire:model="name" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                    @error('name') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Email</label>
                                    <input type="email" wire:model="email" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                    @error('email') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Subject</label>
                                <input type="text" wire:model="ticketSubject" placeholder="Brief issue description" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                @error('ticketSubject') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Priority</label>
                                <select wire:model="ticketPriority" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Message</label>
                                <textarea wire:model="ticketMessage" rows="4" placeholder="Detailed explanation..." class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900"></textarea>
                                @error('ticketMessage') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Attachment (Optional)</label>
                                <div class="flex items-center gap-4">
                                    <label class="flex-grow flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white border border-slate-200 border-dashed hover:border-amber-500 cursor-pointer transition-all group">
                                        <input type="file" wire:model="ticketAttachment" class="hidden" accept="image/*,application/pdf">
                                        <i class="fas fa-paperclip text-xs text-slate-400 group-hover:text-amber-600"></i>
                                        <span class="text-xs text-slate-500 group-hover:text-slate-700">{{ $ticketAttachment ? 'File selected' : 'Upload photo/pdf' }}</span>
                                    </label>
                                    @if($ticketAttachment)
                                        <button wire:click="$set('ticketAttachment', null)" class="p-3 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                                <div wire:loading wire:target="ticketAttachment" class="text-[10px] text-amber-600 italic ml-1">Uploading...</div>
                                @error('ticketAttachment') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>

                            <button wire:click="createTicket" class="w-full py-3.5 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i>
                                Create Ticket
                            </button>
                        </div>

                        @if(!empty($userTickets))
                            <div class="mt-8 pt-8 border-t border-slate-100">
                                <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-4">Your Recent Tickets</h4>
                                <div class="space-y-3">
                                    @foreach($userTickets as $ticket)
                                        <div wire:click="selectTicket({{ $ticket['id'] }})" class="p-4 rounded-2xl bg-white border border-slate-100 hover:border-amber-300 transition-colors cursor-pointer group">
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-[8px] font-bold text-amber-600 uppercase tracking-wider">{{ $ticket['ticket_id'] }}</span>
                                                <span class="text-[8px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600 uppercase tracking-wider">{{ $ticket['status'] }}</span>
                                            </div>
                                            <h5 class="text-xs font-bold text-slate-900 mb-1">{{ $ticket['subject'] }}</h5>
                                            <p class="text-[10px] text-slate-400 line-clamp-1">{{ count($ticket['messages']) }} messages</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            @if($activeTab == 'callback')
                <div class="space-y-6">
                    <div class="text-center mb-6">
                        <i class="fas fa-phone-alt text-3xl text-amber-500/30 mb-3"></i>
                        <p class="text-slate-500 text-sm">Need a quick call? Leave your number and our team will call you back within 15 minutes.</p>
                    </div>

                    @if (session()->has('call_success'))
                        <div class="p-4 rounded-xl bg-green-50 border border-green-100 text-green-600 text-xs font-bold text-center">
                            {{ session('call_success') }}
                        </div>
                    @endif

                    <div class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Phone Number</label>
                            <input type="tel" wire:model="phone" placeholder="+1 (555) 000-0000" class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900">
                            @error('phone') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <button type="button" wire:click="requestCall" class="w-full py-3.5 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-colors">
                            Request Callback
                        </button>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Available Mon-Fri, 9am - 6pm EST</p>
                    </div>
                </div>
            @endif

            @if($activeTab == 'ai')
                <div class="space-y-4 h-full">
                    <div class="text-center mb-4">
                        <div class="relative inline-block">
                            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 rounded-full border-4 border-amber-100 bg-white shadow-md" role="img" aria-label="Believoo AI">
                                <defs>
                                    <linearGradient id="faceGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#fce4d6"/>
                                        <stop offset="100%" stop-color="#f2d3b1"/>
                                    </linearGradient>
                                </defs>
                                <rect width="100" height="100" fill="#ffffff"/>
                                <!-- Hair back -->
                                <path d="M15,55 C10,25 30,10 50,10 C70,10 90,25 85,55 C90,75 80,95 50,95 C20,95 10,75 15,55" fill="#2c1b18"/>
                                <!-- Face -->
                                <circle cx="50" cy="54" r="28" fill="url(#faceGrad)"/>
                                <!-- Front hair -->
                                <path d="M22,48 C22,28 35,18 50,18 C65,18 78,28 78,48 C78,42 70,32 50,32 C30,32 22,42 22,48" fill="#2c1b18"/>
                                <!-- Eyes -->
                                <circle cx="40" cy="52" r="3.5" fill="#2c1b18"/>
                                <circle cx="60" cy="52" r="3.5" fill="#2c1b18"/>
                                <!-- Smile -->
                                <path d="M41,65 Q50,73 59,65" fill="none" stroke="#c2185b" stroke-width="2.2" stroke-linecap="round"/>
                                <!-- Blush -->
                                <circle cx="34" cy="60" r="3" fill="#ff8a80" opacity="0.5"/>
                                <circle cx="66" cy="60" r="3" fill="#ff8a80" opacity="0.5"/>
                            </svg>
                            <span class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full" x-show="!listening && !speaking"></span>
                            <span class="absolute bottom-0 right-0 w-4 h-4 bg-amber-500 border-2 border-white rounded-full animate-pulse" x-show="listening || speaking" x-cloak></span>
                        </div>
                        <h4 class="text-slate-800 font-bold text-sm mt-2">Believoo AI</h4>
                        <p class="text-slate-500 text-xs">Type or speak — I understand English, Hindi, Hinglish, Urdu, Bengali, Tamil &amp; more.</p>
                        <button type="button" wire:click="clearAiChat" class="mt-1 text-[10px] text-slate-400 hover:text-red-500 transition-colors" title="Clear conversation"><i class="fas fa-trash-alt mr-1"></i>Clear chat</button>
                    </div>

                    <div class="space-y-3 mb-4">
                        @foreach($aiMessages as $msg)
                            <div class="flex {{ $msg['type'] == 'user' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[90%]">
                                    <div class="px-4 py-3 rounded-2xl text-sm {{ $msg['type'] == 'user' ? 'bg-amber-500 text-white rounded-tr-none' : 'bg-white text-slate-700 rounded-tl-none border border-slate-100' }}">
                                        @if(!empty($msg['attachment']))
                                            @php $isImg = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $msg['attachment']); @endphp
                                            @if($isImg)
                                                <img src="{{ asset('storage/' . $msg['attachment']) }}" class="rounded-lg mb-2 max-w-full" alt="Attachment">
                                            @else
                                                <a href="{{ asset('storage/' . $msg['attachment']) }}" target="_blank" class="text-[10px] underline flex items-center gap-1 mb-2"><i class="fas fa-paperclip"></i> Attachment</a>
                                            @endif
                                        @endif
                                        {!! nl2br(e($msg['message'])) !!}
                                    </div>
                                    <div class="flex items-center gap-2 mt-1 {{ $msg['type'] == 'user' ? 'justify-end' : 'justify-start' }}">
                                        <span class="text-[10px] text-slate-400">{{ $msg['created_at'] }}</span>
                                        @if($msg['type'] == 'user')
                                            @php $aiSeen = collect($aiMessages)->slice($loop->index + 1)->contains('type', 'ai'); @endphp
                                            @if($aiSeen)
                                                <i class="fas fa-check-double text-sky-500 text-[10px]" title="Seen"></i>
                                            @else
                                                <i class="fas fa-check text-slate-300 text-[10px]" title="Sent"></i>
                                            @endif
                                        @endif
                                        @if($msg['type'] == 'ai')
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="speak(@js($msg['message']), @js($msg['lang'] ?? 'en'))" class="text-slate-400 hover:text-amber-600 transition-colors" title="Play voice"><i class="fas fa-volume-up text-[10px]"></i></button>
                                                <button type="button" @click="navigator.clipboard.writeText(@js($msg['message'])); const el = $el.querySelector('i'); el.className='fas fa-check text-[10px]'; setTimeout(() => el.className='fas fa-copy text-[10px]', 1200)" class="text-slate-400 hover:text-amber-600 transition-colors" title="Copy"><i class="fas fa-copy text-[10px]"></i></button>
                                                @if(isset($msg['id']) && $msg['id'])
                                                    <button type="button" wire:click="aiFeedback({{ $msg['id'] }}, 'good')" class="text-slate-400 hover:text-green-500 transition-colors" title="Helpful"><i class="fas fa-thumbs-up text-[10px]"></i></button>
                                                    <button type="button" wire:click="aiFeedback({{ $msg['id'] }}, 'bad')" class="text-slate-400 hover:text-red-500 transition-colors" title="Not helpful"><i class="fas fa-thumbs-down text-[10px]"></i></button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Optimistic user messages (shown instantly before server round-trip) -->
                        <template x-for="(pm, idx) in pendingAi" :key="'pending-'+idx">
                            <div class="flex justify-end">
                                <div class="max-w-[90%]">
                                    <div class="px-4 py-3 rounded-2xl text-sm bg-amber-500 text-white rounded-tr-none" x-text="pm"></div>
                                    <div class="flex items-center gap-2 mt-1 justify-end">
                                        <span class="text-[10px] text-slate-400">Sending...</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <!-- Typing indicator -->
                        <div x-show="aiTyping" x-cloak class="flex justify-start">
                            <div class="px-4 py-3 rounded-2xl text-sm bg-white text-slate-500 rounded-tl-none border border-slate-100">
                                <span class="inline-flex gap-1 items-center">
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                                    <span class="text-[10px] text-slate-400 ml-1">Believoo AI is typing...</span>
                                </span>
                            </div>
                        </div>
                        @if($aiIsLoading)
                            <div class="flex justify-start" x-show="!aiTyping">
                                <div class="px-4 py-3 rounded-2xl text-sm bg-white text-slate-500 rounded-tl-none border border-slate-100 italic">
                                    <span class="animate-pulse">Thinking...</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    @php
                        $currentUrl = request()->url();
                        $isServicePage = str_contains($currentUrl, '/services/');
                        $isDomainPage = str_contains($currentUrl, 'domain');
                        $isHostingPage = str_contains($currentUrl, 'hosting') || str_contains($currentUrl, 'vps');
                    @endphp
                    <div class="flex flex-wrap gap-2">
                        @if($isServicePage)
                            <button wire:click="$set('aiQuestion', 'Tell me more about this service')" class="px-3 py-1.5 rounded-full bg-amber-500 text-white text-xs hover:bg-amber-600 transition-colors">Tell me about this service</button>
                            <button wire:click="$set('aiQuestion', 'What is the price for this?')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-xs text-slate-600 hover:border-amber-500 hover:text-amber-600 transition-colors">Price for this?</button>
                        @endif
                        @if($isDomainPage)
                            <button wire:click="$set('aiQuestion', 'How to register a domain?')" class="px-3 py-1.5 rounded-full bg-amber-500 text-white text-xs hover:bg-amber-600 transition-colors">How to register a domain?</button>
                            <button wire:click="$set('aiQuestion', 'What is DNS?')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-xs text-slate-600 hover:border-amber-500 hover:text-amber-600 transition-colors">What is DNS?</button>
                        @endif
                        @if($isHostingPage)
                            <button wire:click="$set('aiQuestion', 'Which VPS plan is best for me?')" class="px-3 py-1.5 rounded-full bg-amber-500 text-white text-xs hover:bg-amber-600 transition-colors">Best VPS for me?</button>
                            <button wire:click="$set('aiQuestion', 'What is managed VPS?')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-xs text-slate-600 hover:border-amber-500 hover:text-amber-600 transition-colors">What is managed VPS?</button>
                        @endif
                        @if(!$isServicePage && !$isDomainPage && !$isHostingPage)
                            <button wire:click="$set('aiQuestion', 'How to buy a domain?')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-xs text-slate-600 hover:border-amber-500 hover:text-amber-600 transition-colors">How to buy a domain?</button>
                            <button wire:click="$set('aiQuestion', 'How do I get hosting?')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-xs text-slate-600 hover:border-amber-500 hover:text-amber-600 transition-colors">How do I get hosting?</button>
                            <button wire:click="$set('aiQuestion', 'Create a ticket')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-xs text-slate-600 hover:border-amber-500 hover:text-amber-600 transition-colors">Create a ticket</button>
                            <button wire:click="$set('aiQuestion', 'What is the price?')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-xs text-slate-600 hover:border-amber-500 hover:text-amber-600 transition-colors">What is the price?</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Chat Input (Sticky at bottom) -->
        @if($activeTab == 'chat' && ($isRegistered || count($chatMessages) > 0))
            <div class="p-4 border-t border-slate-100 bg-white flex-shrink-0 space-y-3">
                <div class="flex gap-2">
                    <div class="relative flex-grow">
                        <input type="text" wire:model="message" wire:keydown.enter="sendMessage" placeholder="Type your message..." class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm pr-10 text-slate-900">
                        <div class="absolute right-4 top-1/2 -translate-y-1/2">
                            <label class="cursor-pointer text-slate-400 hover:text-amber-600 transition-colors">
                                <input type="file" wire:model="chatAttachment" class="hidden" accept="image/*,application/pdf">
                                <i class="fas fa-paperclip text-sm"></i>
                            </label>
                        </div>
                    </div>
                    <button wire:click="sendMessage" class="w-12 rounded-xl bg-amber-500 text-white flex items-center justify-center hover:bg-amber-600 transition-colors">
                        <i class="fas fa-paper-plane text-sm"></i>
                    </button>
                </div>
                <div class="flex justify-between items-center px-1">
                    @error('message') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="chatAttachment" class="text-[10px] text-amber-600 italic">Uploading...</div>
                    @if($chatAttachment)
                        <div class="text-[10px] text-green-600 font-bold flex items-center">
                            <i class="fas fa-check mr-1"></i> File ready
                            <button wire:click="$set('chatAttachment', null)" class="ml-1 text-red-500" aria-label="Remove attachment"><i class="fas fa-times"></i></button>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($activeTab == 'ai')
            <div class="p-4 border-t border-slate-100 bg-white flex-shrink-0 space-y-3">
                {{-- Smart suggestions (history + page aware) --}}
                @php $suggestions = $this->getSmartSuggestions(); @endphp
                @if($suggestions)
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($suggestions as $s)
                            <button type="button" wire:click="$set('aiQuestion', '{{ addslashes($s) }}')" @click="$refs.aiQuestionInput.value = '{{ addslashes($s) }}'" class="px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-bold hover:bg-amber-100 transition-all border border-amber-200">
                                {{ $s }}
                            </button>
                        @endforeach
                    </div>
                @endif
                <form x-on:submit.prevent="onAiSubmit()" class="flex gap-2 items-center">
                    <button type="button" @click="startListening()" :class="listening ? 'bg-red-500 animate-pulse' : 'bg-slate-100 hover:bg-slate-200'" class="w-12 h-12 rounded-xl text-slate-600 flex items-center justify-center transition-colors" title="Speak (English/Hindi)">
                        <i class="fas fa-microphone" :class="listening ? 'text-white' : 'text-slate-600'"></i>
                    </button>
                    <input type="text" wire:model="aiQuestion" placeholder="Type or speak..." class="flex-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 focus:border-amber-500 outline-none transition-all text-sm text-slate-900" x-ref="aiQuestionInput">
                    <button type="button" @click="toggleMute()" :class="muted ? 'text-red-500' : 'text-slate-400 hover:text-amber-600'" class="w-10 h-10 flex items-center justify-center transition-colors" title="AI voice on/off">
                        <i class="fas" :class="muted ? 'fa-volume-mute' : 'fa-volume-up'"></i>
                    </button>
                    <label class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-amber-600 cursor-pointer transition-colors" title="Attach image/file">
                        <input type="file" wire:model="chatAttachment" class="hidden" accept="image/*,.pdf">
                        <i class="fas fa-paperclip"></i>
                    </label>
                    <button type="submit" class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center hover:bg-amber-600 transition-colors">
                        <i class="fas fa-paper-plane text-sm"></i>
                    </button>
                </form>
                <div class="flex justify-between items-center px-1">
                    <button type="button" wire:click="askHuman" class="text-[11px] text-slate-500 hover:text-amber-600 transition-colors flex items-center gap-1" title="Talk to a human agent">
                        <i class="fas fa-user-headset"></i> Talk to human agent
                    </button>
                    <div class="flex items-center gap-3">
                        @error('aiQuestion') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="chatAttachment" class="text-[10px] text-amber-600 italic">Uploading...</div>
                        @if($chatAttachment)
                            <div class="text-[10px] text-green-600 font-bold flex items-center">
                                <i class="fas fa-check mr-1"></i> File attached
                                <button type="button" wire:click="$set('chatAttachment', null)" class="ml-1 text-red-500" aria-label="Remove attachment"><i class="fas fa-times"></i></button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Proactive greeting bubble — shows after 30s on page -->
    <div class="proactive-bubble" style="display:none;"
         x-init="setTimeout(() => showProactive(), 30000)"
         class="mb-3 max-w-[220px] bg-white rounded-2xl rounded-br-none shadow-xl border border-amber-200 p-4 cursor-pointer pointer-events-auto"
         @click="openFromProactive()">
        <p class="text-xs font-bold text-slate-700 mb-1"><i class="fas fa-robot text-amber-500 mr-1"></i>Hi! 👋</p>
        <p class="text-[11px] text-slate-500 leading-relaxed">Need help with hosting, domains, or anything? I'm here — tap to chat!</p>
        <p class="text-[9px] text-amber-600 mt-1 font-bold">Click to chat →</p>
    </div>

    <button wire:click="toggleChat"
            wire:key="support-hub-toggle"
            class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-tr from-amber-500 to-amber-600 shadow-lg shadow-amber-500/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-500 group relative z-[12000001] cursor-pointer pointer-events-auto">
        @if($isOpen)
            <i class="fas fa-times text-2xl text-white"></i>
        @else
            <div class="flex items-center justify-center">
                <i class="fas fa-comment-dots text-2xl sm:text-3xl text-white group-hover:rotate-12 transition-transform"></i>
                <span class="absolute -top-1 -right-1 w-5 h-5 sm:w-6 sm:h-6 bg-red-500 rounded-full border-4 border-white flex items-center justify-center shadow-lg">
                    <span class="w-2 h-2 bg-white rounded-full animate-ping"></span>
                </span>
            </div>
        @endif
    </button>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(245, 158, 11, 0.4);
        }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            let adminTypingTimeout;
            window.addEventListener('reset-admin-typing', () => {
                clearTimeout(adminTypingTimeout);
                adminTypingTimeout = setTimeout(() => {
                    @this.set('adminIsTyping', false);
                }, 3000);
            });

            // Play notification sound + browser notification for the client
            window.addEventListener('play-notification-sound', () => {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3');
                audio.play().catch(e => console.log('Audio play failed:', e));
                if (window.Notification && Notification.permission === 'granted' && document.hidden) {
                    new Notification('Believoo Support', { body: 'You have a new message from our team.', icon: '/favicon.ico' });
                }
            });

            // Ask notification permission once when the widget opens
            window.addEventListener('request-notification-permission', () => {
                if (window.Notification && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            });
        });
    </script>
</div>
