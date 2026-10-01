<div class="min-h-screen bg-white text-slate-800 pt-28 pb-20 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-1">Support Tickets</h1>
                <p class="text-slate-500 text-sm">All your tickets from Believoo, GHC and Bmydesk in one place.</p>
            </div>
            <button wire:click="$toggle('showCreate')" class="px-6 py-3 rounded-full bg-[#00b7ff] text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all shadow-lg shadow-blue-200">
                <i class="fas fa-plus mr-1"></i> New Ticket
            </button>
        </div>

        @if($showCreate)
            <div class="rounded-2xl border border-slate-100 p-6 md:p-8 mb-8 bg-slate-50">
                <h2 class="text-xl font-black text-slate-900 mb-4">Create Ticket</h2>
                <form wire:submit.prevent="createTicket" class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Platform</label>
                            <select wire:model.live="category" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:border-[#00b7ff]">
                                <option value="Believoo">Believoo</option>
                                <option value="GHC">GHC Cloud</option>
                                <option value="Bmydesk">Bmydesk</option>
                                <option value="Webmail">Webmail</option>
                                <option value="Other">Other</option>
                            </select>
                            @error('category') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Priority</label>
                            <select wire:model.live="priority" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:border-[#00b7ff]">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            @error('priority') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Subject</label>
                        <input type="text" wire:model.live="subject" placeholder="Brief issue description" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:border-[#00b7ff]">
                        @error('subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Message</label>
                        <textarea wire:model.live="message" rows="4" placeholder="Describe your issue in detail..." class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:border-[#00b7ff]"></textarea>
                        @error('message') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Attachment (optional)</label>
                        <input type="file" wire:model.live="attachment" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-[#00b7ff]/10 file:text-[#00b7ff] hover:file:bg-[#00b7ff]/20">
                        @error('attachment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$toggle('showCreate')" class="px-6 py-3 rounded-full border border-slate-200 text-slate-600 font-black uppercase tracking-widest text-xs hover:bg-slate-100 transition-all">Cancel</button>
                        <button type="submit" class="px-6 py-3 rounded-full bg-[#00b7ff] text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all shadow-lg shadow-blue-200">Create Ticket</button>
                    </div>
                </form>
            </div>
        @endif

        @if(session('ticket_success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-bold border border-emerald-200">
                <i class="fas fa-check-circle mr-2"></i> {{ session('ticket_success') }}
            </div>
        @endif

        @if(count($tickets))
            <div class="rounded-2xl border border-slate-100 overflow-hidden">
                @foreach($tickets as $ticket)
                    <div class="p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-2">
                            <div>
                                <span class="text-[10px] font-black text-[#00b7ff] uppercase tracking-widest">{{ $ticket['ticket_id'] }}</span>
                                <h3 class="font-bold text-slate-900">{{ $ticket['subject'] }}</h3>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border
                                    @if($ticket['status'] === 'open') bg-emerald-50 text-emerald-600 border-emerald-200
                                    @elseif(in_array($ticket['status'], ['resolved','closed'])) bg-blue-50 text-blue-600 border-blue-200
                                    @else bg-slate-100 text-slate-500 border-slate-200 @endif">
                                    {{ ucfirst($ticket['status']) }}
                                </span>
                                <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border
                                    @if($ticket['platform'] === 'Believoo') bg-indigo-50 text-indigo-600 border-indigo-200
                                    @elseif($ticket['platform'] === 'GHC') bg-cyan-50 text-cyan-600 border-cyan-200
                                    @elseif($ticket['platform'] === 'Bmydesk') bg-emerald-50 text-emerald-600 border-emerald-200
                                    @else bg-slate-50 text-slate-600 border-slate-200 @endif">
                                    {{ $ticket['platform'] }}
                                </span>
                                @if($ticket['url'])
                                    <a href="{{ $ticket['url'] }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border bg-slate-50 text-slate-600 border-slate-200 hover:border-[#00b7ff] transition-all">
                                        <i class="fas fa-external-link-alt mr-1"></i> Open
                                    </a>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-slate-500 mb-3">{{ Str::limit($ticket['message'], 180) }}</p>
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>Created {{ $ticket['created_at']?->diffForHumans() ?? '' }}</span>
                            @if($ticket['source'] === 'Believoo' && in_array($ticket['status'], ['resolved', 'closed']))
                                <button wire:click="reopenTicket({{ $ticket['id'] }})" class="text-[#00b7ff] font-bold hover:underline">Reopen</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-20 rounded-2xl border border-slate-100 bg-slate-50">
                <div class="w-16 h-16 rounded-full bg-[#00b7ff]/10 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-ticket-alt text-2xl text-[#00b7ff]"></i>
                </div>
                <h3 class="text-lg font-black text-slate-900 mb-2">No tickets yet</h3>
                <p class="text-sm text-slate-500 mb-6">Create a ticket and we'll respond shortly.</p>
                <button wire:click="$set('showCreate', true)" class="px-6 py-3 rounded-full bg-[#00b7ff] text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all shadow-lg shadow-blue-200">Create Ticket</button>
            </div>
        @endif
    </div>
</div>
