<div wire:poll.30s="checkNotifications" x-on:notifications-updated.window="$wire.$refresh()" class="relative z-[10000000]">
    @auth
        <div x-data="{ open: false }" class="relative" @click.away="open = false">
            <button @click="open = !open; if(open) $wire.$refresh()" class="relative p-2 text-gray-400 hover:text-white transition-colors focus:outline-none z-[10000001] pointer-events-auto">
                <i class="fas fa-bell text-xl"></i>
                @php $unreadCount = Auth::user()->unreadNotifications->count(); @endphp
                @if($unreadCount > 0)
                    <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full z-[10000002]">
                        {{ $unreadCount }}
                    </span>
                @endif
            </button>

            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 scale-95" 
                 x-transition:enter-end="opacity-100 scale-100" 
                 class="absolute right-0 mt-4 w-80 glass rounded-[2rem] border border-white/10 shadow-2xl z-[10000010] overflow-hidden" 
                 x-cloak>
                <div class="p-4 border-b border-white/5 flex justify-between items-center">
                    <h3 class="text-xs font-black text-white uppercase tracking-widest">Notifications</h3>
                    @if(Auth::user()->unreadNotifications->count() > 0)
                        <button wire:click="markNotificationsAsRead" @click="open = false" class="text-[10px] font-bold text-electric-blue hover:underline uppercase">Mark all read</button>
                    @endif
                </div>
                <div class="max-h-96 overflow-y-auto custom-scrollbar">
                    @forelse(Auth::user()->notifications()->latest()->take(5)->get() as $notification)
                        @php
                            $title = $notification->data['title'] ?? 'Notification';
                            $body = $notification->data['body'] ?? ($notification->data['message'] ?? '');
                            $url = null;
                            
                            // Check for Filament actions
                            if (isset($notification->data['actions'])) {
                                foreach ($notification->data['actions'] as $action) {
                                    if (isset($action['url'])) {
                                        $url = $action['url'];
                                        break;
                                    }
                                }
                            }
                            
                            // Check for custom notification ticket_id/url
                            if (!$url && isset($notification->data['ticket_id'])) {
                                $url = route('client.dashboard') . '?ticket=' . $notification->data['ticket_id'];
                            }
                        @endphp
                        <a href="{{ $url ?? '#' }}" 
                           class="block p-4 border-b border-white/5 hover:bg-white/[0.05] transition-all {{ $notification->read_at ? 'opacity-50' : '' }} relative z-[120] cursor-pointer pointer-events-auto"
                           style="pointer-events: auto !important; position: relative; display: block;">
                            <p class="text-[11px] text-white font-black mb-1 uppercase tracking-wider">{{ $title }}</p>
                            <p class="text-[10px] text-gray-400 line-clamp-2 mb-2 font-medium">{{ $body }}</p>
                            <span class="text-[8px] font-black text-gray-600 uppercase tracking-widest">{{ $notification->created_at->diffForHumans() }}</span>
                        </a>
                    @empty
                        <div class="p-8 text-center">
                            <i class="fas fa-bell-slash text-xl text-gray-800 mb-2"></i>
                            <p class="text-[10px] font-black text-gray-600 uppercase tracking-widest">No notifications</p>
                        </div>
                    @endforelse
                </div>
                @if(Auth::user()->notifications->count() > 5)
                    <div class="p-3 bg-white/[0.02] text-center border-t border-white/5">
                        <a href="{{ route('client.dashboard') }}" class="text-[8px] font-black text-electric-blue uppercase tracking-widest hover:underline">View All</a>
                    </div>
                @endif
            </div>
        </div>
    @endauth
</div>
