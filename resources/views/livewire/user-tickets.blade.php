<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Support Tickets</h3>
        <button wire:click="$toggle('showCreate')" class="px-4 py-2 bg-blue-600 text-white text-xs font-black rounded-xl hover:bg-blue-700 transition-all uppercase tracking-widest">
            {{ $showCreate ? 'Cancel' : 'New Ticket' }}
        </button>
    </div>

    @if (session()->has('ticket_success'))
        <div class="p-4 bg-green-50 border border-green-100 text-green-700 text-sm rounded-xl">
            {{ session('ticket_success') }}
        </div>
    @endif

    @if($showCreate)
        <div class="bg-gray-50 p-6 rounded-3xl border border-gray-100 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
            <form wire:submit.prevent="createTicket" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Subject</label>
                        <input type="text" wire:model="subject" class="w-full px-4 py-3 rounded-xl border-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none">
                        @error('subject') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Priority</label>
                        <select wire:model="priority" class="w-full px-4 py-3 rounded-xl border-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Message</label>
                    <textarea wire:model="message" rows="4" class="w-full px-4 py-3 rounded-xl border-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"></textarea>
                    @error('message') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="w-full py-4 bg-blue-600 text-white font-black rounded-xl hover:bg-blue-700 hover:scale-[1.02] transition-all shadow-lg shadow-blue-500/20 uppercase tracking-widest text-xs">
                    Submit Ticket
                </button>
            </form>
        </div>
    @endif

    <div class="overflow-hidden border border-gray-100 rounded-3xl shadow-sm bg-white">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">ID</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Subject</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Priority</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 text-xs font-bold text-gray-400">#{{ $ticket->id }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ $ticket->subject }}</div>
                            <div class="text-[10px] text-gray-400">{{ $ticket->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-[8px] font-black uppercase tracking-widest rounded-full 
                                {{ $ticket->status == 'open' ? 'bg-blue-50 text-blue-600' : '' }}
                                {{ $ticket->status == 'in_progress' ? 'bg-amber-50 text-amber-600' : '' }}
                                {{ $ticket->status == 'resolved' ? 'bg-green-50 text-green-600' : '' }}
                                {{ $ticket->status == 'closed' ? 'bg-gray-50 text-gray-600' : '' }}
                            ">
                                {{ $ticket->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-[8px] font-black uppercase tracking-widest rounded-full 
                                {{ $ticket->priority == 'high' ? 'bg-red-50 text-red-600' : '' }}
                                {{ $ticket->priority == 'medium' ? 'bg-blue-50 text-blue-600' : '' }}
                                {{ $ticket->priority == 'low' ? 'bg-gray-50 text-gray-400' : '' }}
                            ">
                                {{ $ticket->priority }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if(in_array($ticket->status, ['resolved', 'closed']))
                                <button wire:click="reopenTicket({{ $ticket->id }})" class="text-blue-600 hover:text-blue-800 text-[10px] font-black uppercase tracking-widest">
                                    Reopen
                                </button>
                            @else
                                <span class="text-gray-300 text-[10px] font-black uppercase tracking-widest">Active</span>
                            @endif
                        </td>
                    </tr>
                    @if($ticket->admin_response)
                        <tr class="bg-blue-50/30">
                            <td colspan="5" class="px-6 py-4">
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-[10px] text-white font-black">S</div>
                                    <div class="flex-1">
                                        <div class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Support Team Response</div>
                                        <div class="text-sm text-gray-700 italic">"{{ $ticket->admin_response }}"</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No tickets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
