<div class="client-dashboard client-dashboard-bg flex min-h-screen"
     x-data="{ lastUnreadCount: {{ Auth::user()->unreadNotifications->count() }}, activeTab: $wire.$entangle('activeTab'), sidebarOpen: false }"
     x-on:clear-server-message.window="setTimeout(() => $wire.clearServerMessage(), 5000)"
     @if(isset($hasProvisioning) && $hasProvisioning) wire:poll.30000ms @endif>

    <style>
        .gm-style * { cursor: default !important; }
        .gm-style { cursor: default !important; }
        .client-dashboard-bg { background:#0f0f13; }
        .client-sidebar-bg { background:#16161d; border-right:1px solid rgba(255,255,255,0.05); }
        .client-header-bg { background:rgba(15,15,19,0.9); border-bottom:1px solid rgba(255,255,255,0.05); backdrop-filter:blur(12px); }
        .client-sidebar-link { display:flex; align-items:center; gap:12px; padding:10px 16px; border-radius:10px; color:#9ca3af; font-size:13px; font-weight:600; transition:all .15s; }
        .client-sidebar-link:hover, .client-sidebar-link.active { background:rgba(0,102,255,0.1); color:#3b82f6; }
        .client-sidebar-link.active { background:rgba(0,102,255,0.15); }
        .client-card { background:#1a1a20; border:1px solid rgba(255,255,255,0.05); border-radius:16px; }
        .client-card:hover { border-color:rgba(255,255,255,0.08); }
        .client-btn { background:#0066FF; color:#fff; border-radius:10px; font-weight:700; font-size:11px; letter-spacing:0.05em; text-transform:uppercase; padding:8px 16px; transition:all .15s; }
        .client-btn:hover { background:#0052CC; }
        .custom-scroll::-webkit-scrollbar { width:4px; }
        .custom-scroll::-webkit-scrollbar-track { background:transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:4px; }

        html.light .client-dashboard-bg { background:#f8fafc; }
        html.light .client-sidebar-bg { background:#ffffff; border-right-color:rgba(0,0,0,0.05); }
        html.light .client-header-bg { background:rgba(255,255,255,0.9); border-bottom-color:rgba(0,0,0,0.05); }
        html.light .client-sidebar-link { color:#475569; }
        html.light .client-sidebar-link:hover, html.light .client-sidebar-link.active { background:rgba(0,102,255,0.1); color:#2563eb; }
        html.light .client-card { background:#ffffff; border-color:rgba(0,0,0,0.05); }
        html.light .client-card:hover { border-color:rgba(0,0,0,0.08); }
        html.light .custom-scroll::-webkit-scrollbar-thumb { background:rgba(0,0,0,0.1); }
        html.light .client-dashboard, html.light .client-dashboard .text-white, html.light .client-dashboard .text-gray-100, html.light .client-dashboard .text-gray-200, html.light .client-dashboard .text-gray-300, html.light .client-dashboard .text-gray-400 { color:#111827 !important; }
    </style>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/60 z-40 lg:hidden" x-cloak></div>

    <aside class="client-sidebar-bg fixed lg:static inset-y-0 left-0 z-50 w-64 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="p-6 flex items-center gap-3">
            <a href="{{ route('home') }}" class="flex items-center">
                <x-site-logo class="h-12 w-auto" />
            </a>
        </div>
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto custom-scroll">
            <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mt-4 mb-2">Menu</p>
            <button @click="activeTab = 'dashboard'; $wire.switchTab('dashboard'); sidebarOpen = false"
                    :class="activeTab === 'dashboard' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-home w-4 text-center text-xs"></i> Dashboard
            </button>
            <button @click="activeTab = 'projects'; $wire.switchTab('projects'); sidebarOpen = false"
                    :class="activeTab === 'projects' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-rocket w-4 text-center text-xs"></i> Projects
            </button>
            <a href="https://support.believoo.com/tickets" target="_blank" rel="noopener"
                    class="client-sidebar-link w-full text-left flex items-center gap-3">
                <i class="fas fa-headset w-4 text-center text-xs"></i> Support
            </a>
            <button @click="activeTab = 'vault'; $wire.switchTab('vault'); sidebarOpen = false"
                    :class="activeTab === 'vault' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-vault w-4 text-center text-xs"></i> File Vault
            </button>
            <button @click="activeTab = 'hosting'; $wire.switchTab('hosting'); sidebarOpen = false"
                    :class="activeTab === 'hosting' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-server w-4 text-center text-xs"></i> Hosting
            </button>
            <button @click="activeTab = 'streaming'; $wire.switchTab('streaming'); sidebarOpen = false"
                    :class="activeTab === 'streaming' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-broadcast-tower w-4 text-center text-xs"></i> Live Stream
            </button>
            <button @click="activeTab = 'teams'; $wire.switchTab('teams'); sidebarOpen = false"
                    :class="activeTab === 'teams' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-users w-4 text-center text-xs"></i> Teams
            </button>
            <button @click="activeTab = 'agreements'; $wire.switchTab('agreements'); sidebarOpen = false"
                    :class="activeTab === 'agreements' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-file-signature w-4 text-center text-xs"></i> Agreements
            </button>
            <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mt-6 mb-2">Account</p>
            <button @click="activeTab = 'wallet'; $wire.switchTab('wallet'); sidebarOpen = false"
                    :class="activeTab === 'wallet' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-wallet w-4 text-center text-xs"></i> Wallet
            </button>
            <button @click="activeTab = 'orders'; $wire.switchTab('orders'); sidebarOpen = false"
                    :class="activeTab === 'orders' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-file-invoice w-4 text-center text-xs"></i> Orders
            </button>
            <button @click="activeTab = 'profile'; $wire.switchTab('profile'); sidebarOpen = false"
                    :class="activeTab === 'profile' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-user-circle w-4 text-center text-xs"></i> Profile
            </button>
            <a href="{{ route('client.referrals') }}" class="client-sidebar-link w-full text-left flex items-center">
                <i class="fas fa-user-plus w-4 text-center text-xs"></i> Refer & Earn
            </a>
        </nav>
        <div class="p-4 border-t border-white/5">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="client-sidebar-link w-full text-left text-gray-500 hover:text-blue-400">
                    <i class="fas fa-sign-out-alt w-4 text-center text-xs"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 min-w-0">
        <header class="client-header-bg sticky top-0 z-30 px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all">
                    <i class="fas fa-bars text-xs"></i>
                </button>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10">
                    <i class="fas fa-wallet text-emerald-400 text-[10px]"></i>
                    <span class="text-xs font-bold text-white">₹{{ number_format(Auth::user()->wallet_balance ?? 0, 2) }}</span>
                </div>
                <button @click="activeTab = 'wallet'; $wire.switchTab('wallet')" class="client-btn hidden md:inline-flex items-center gap-1">
                    <i class="fas fa-plus text-[9px]"></i> Add Funds
                </button>
                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                    <button @click="open = !open" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all relative">
                        <i class="fas fa-bell text-xs"></i>
                        @if(Auth::user()->unreadNotifications->count() > 0)
                            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-red-500 text-white text-[7px] font-black rounded-full flex items-center justify-center border border-[#0f0f13]">{{ Auth::user()->unreadNotifications->count() }}</span>
                        @endif
                    </button>
                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-72 client-card rounded-2xl z-50 shadow-2xl max-h-80 overflow-y-auto custom-scroll" x-cloak>
                        <div class="p-4 border-b border-white/10 flex justify-between items-center">
                            <h3 class="text-xs font-bold text-white uppercase tracking-widest">Notifications</h3>
                            @if(Auth::user()->unreadNotifications->count() > 0)
                                <button x-on:click="$wire.markNotificationsAsRead(); open = false" class="text-[10px] font-bold text-[#0066FF]">Mark read</button>
                            @endif
                        </div>
                        <div class="p-2">
                            @forelse(Auth::user()->notifications()->latest()->take(5)->get() as $n)
                                <a href="{{ \App\Support\NotificationLink::url($n) }}"
                                   wire:click.prevent="openNotification('{{ $n->id }}')"
                                   class="block p-3 rounded-xl hover:bg-white/5 transition-all cursor-pointer {{ $n->read_at ? 'opacity-50' : '' }}">
                                    <p class="text-xs text-white font-bold">{{ $n->data['title'] ?? 'Notification' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $n->data['body'] ?? ($n->data['message'] ?? '') }}</p>
                                    <p class="text-[9px] text-gray-600 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <div class="p-6 text-center text-gray-500 text-xs">No notifications</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                {{-- Workspace Switcher --}}
                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                    <button @click="open = !open" class="hidden md:flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-xs font-bold text-white">
                        <i class="fas fa-th-large text-[#0066FF]"></i>
                        <span>Workspace</span>
                        <i class="fas fa-chevron-down text-[9px] text-gray-500"></i>
                    </button>
                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-64 client-card rounded-2xl z-50 shadow-2xl" x-cloak>
                        <div class="p-3">
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest px-3 py-1">Switch Workspace</p>
                            <div class="border-t border-white/5 my-1"></div>
                            <a href="{{ route('client.dashboard') }}" class="w-full flex items-center gap-3 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                                <i class="fas fa-briefcase text-[#0066FF]"></i> Believoo Client
                            </a>
                            <a href="https://ghc.believoo.com/dashboard" target="_blank" rel="noopener" class="w-full flex items-center gap-3 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                                <i class="fas fa-cloud text-cyan-400"></i> GHC Cloud
                            </a>
                            <a href="https://bmydesk.believoo.com/dashboard" target="_blank" rel="noopener" class="w-full flex items-center gap-3 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                                <i class="fas fa-comments text-emerald-400"></i> Bmydesk
                            </a>
                            <a href="https://mail.believoo.com" target="_blank" rel="noopener" class="w-full flex items-center gap-3 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                                <i class="fas fa-envelope text-amber-400"></i> Webmail
                            </a>
                            @if(Auth::user()->is_admin ?? false)
                                <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center gap-3 px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                                    <i class="fas fa-cog text-red-400"></i> Admin Panel
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold" style="background:#0066FF;">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span class="hidden md:block text-xs font-bold text-white">{{ Auth::user()->name }}</span>
                        <i class="fas fa-chevron-down text-[9px] text-gray-500"></i>
                    </button>
                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-56 client-card rounded-2xl z-50 shadow-2xl" x-cloak>
                        <div class="p-3">
                            <p class="text-xs font-bold text-white px-3 py-1">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] text-gray-500 px-3 pb-2">{{ Auth::user()->email }}</p>
                            <div class="border-t border-white/5 my-1"></div>
                            <button @click="activeTab = 'profile'; $wire.switchTab('profile'); open = false" class="w-full flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all text-left"><i class="fas fa-user-circle mr-2 text-[#0066FF]"></i> Profile</button>
                            <button @click="activeTab = 'orders'; $wire.switchTab('orders'); open = false" class="w-full flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all text-left"><i class="fas fa-file-invoice mr-2 text-amber-400"></i> Orders</button>
                            <form method="POST" action="{{ route('logout') }}" class="block mt-1">
                                @csrf
                                <button type="submit" class="w-full flex items-center px-3 py-2 text-xs text-gray-300 hover:text-blue-400 hover:bg-white/5 rounded-xl transition-all text-left"><i class="fas fa-sign-out-alt mr-2 text-blue-400"></i> Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 max-w-[1400px] mx-auto">
            @if(session('error'))
                <div class="mb-5 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>{{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="mb-5 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>{{ session('success') }}
                </div>
            @endif

            {{-- Dashboard Overview: Stats + Chart + Renewals + Actions --}}
            <div x-show="activeTab === 'dashboard'" x-transition.opacity.duration.300ms>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="client-card p-5 flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-white">{{ $projects->where('status', '!=', 'Delivered')->count() }}</p>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mt-0.5">Projects</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(0,102,255,0.12);">
                            <i class="fas fa-rocket text-blue-500 text-sm"></i>
                        </div>
                    </div>
                    <div class="client-card p-5 flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-white">{{ $tickets->where('status', 'open')->count() }}</p>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mt-0.5">Open Tickets</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(139,92,246,0.12);">
                            <i class="fas fa-headset text-violet-400 text-sm"></i>
                        </div>
                    </div>
                    <div class="client-card p-5 flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-white">{{ $hostings->whereIn('status', ['active','pending'])->count() }}</p>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mt-0.5">Hostings</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(16,185,129,0.12);">
                            <i class="fas fa-server text-emerald-400 text-sm"></i>
                        </div>
                    </div>
                    <div class="client-card p-5 flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-white">{{ $agreements->count() }}</p>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mt-0.5">Agreements</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(245,158,11,0.12);">
                            <i class="fas fa-file-signature text-amber-400 text-sm"></i>
                        </div>
                    </div>
                </div>

                @php
                    $activeHostings = ($userHostings ?? collect())->whereIn('status', ['active','pending','provisioning']);
                @endphp
                @if($activeHostings->count() > 0)
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-server text-emerald-400 text-xs"></i>
                            <h2 class="text-xs font-bold text-white uppercase tracking-widest">Your Active Services</h2>
                        </div>
                        <button @click="activeTab = 'hosting'; $wire.switchTab('hosting')" class="text-[10px] font-bold text-blue-400 hover:text-blue-300 uppercase tracking-widest">View All <i class="fas fa-arrow-right text-[8px] ml-1"></i></button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($activeHostings as $h)
                            @php
                                $exp = $h->expiry_date ? \Carbon\Carbon::parse($h->expiry_date) : null;
                                $dl = $exp ? now()->diffInDays($exp, false) : null;
                                $over = $dl !== null && $dl < 0;
                                $soon = $dl !== null && $dl >= 0 && $dl <= 7;
                                $statusColor = $h->status === 'active' ? 'bg-emerald-500/15 text-emerald-400' : ($h->status === 'provisioning' ? 'bg-blue-500/15 text-blue-400' : 'bg-yellow-500/15 text-yellow-400');
                                $iconColor = $h->status === 'active' ? 'text-emerald-400' : ($h->status === 'provisioning' ? 'text-blue-400' : 'text-yellow-400');
                                $bgIcon = $h->status === 'active' ? 'rgba(16,185,129,0.12)' : ($h->status === 'provisioning' ? 'rgba(0,102,255,0.12)' : 'rgba(245,158,11,0.12)');
                            @endphp
                            <div class="client-card p-4 flex items-center gap-3 hover:border-white/10 transition-all">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background:{{ $bgIcon }};">
                                    <i class="fas fa-cloud {{ $iconColor }} text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-bold text-white truncate">{{ $h->plan_name ?? $h->hosting_type }}</p>
                                        <span class="px-1.5 py-0.5 rounded-full text-[8px] font-bold uppercase {{ $statusColor }}">{{ $h->status }}</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-0.5">{{ $h->domain ?? ($h->server_ip ?? 'No domain') }}</p>
                                    @if($exp)
                                        <p class="text-[10px] {{ $over ? 'text-red-400' : ($soon ? 'text-amber-400' : 'text-gray-500') }} mt-0.5">
                                            {{ $over ? abs($dl).'d overdue' : $dl.'d left' }} &middot; {{ $exp->format('M d, Y') }}
                                        </p>
                                    @endif
                                </div>
                                <button @click="activeTab = 'hosting'; $wire.switchTab('hosting')" class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center text-gray-500 hover:text-white hover:bg-white/10 transition-all flex-shrink-0">
                                    <i class="fas fa-chevron-right text-[10px]"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                    <div class="lg:col-span-2 client-card p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-chart-line text-blue-500 text-xs"></i>
                                <h2 class="text-xs font-bold text-white uppercase tracking-widest">Wallet Ledger</h2>
                            </div>
                            <button @click="activeTab = 'wallet'; $wire.switchTab('wallet')" class="px-3 py-1.5 rounded-lg text-blue-500 font-bold uppercase tracking-widest text-[9px] hover:bg-white/5 transition-all" style="border:1px solid rgba(59,130,246,0.2);">+ Top Up</button>
                        </div>
                        <div style="height:260px;">
                            <canvas id="walletChart"></canvas>
                        </div>
                    </div>
                    <div class="client-card p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock-rotate-left text-emerald-400 text-xs"></i>
                                <h2 class="text-xs font-bold text-white uppercase tracking-widest">Renewals</h2>
                            </div>
                            <a href="{{ route('client.dashboard') }}?tab=hosting" wire:navigate class="px-3 py-1.5 rounded-lg text-emerald-400 font-bold uppercase tracking-widest text-[9px]" style="border:1px solid rgba(16,185,129,0.2);">Manage</a>
                        </div>
                        <div class="space-y-3 max-h-[260px] overflow-y-auto custom-scroll">
                            @php
                                $tl = ($userHostings ?? collect())->whereIn('status', ['active','pending'])->sortBy('expiry_date')->take(5);
                            @endphp
                            @if($tl->count() > 0)
                                @foreach($tl as $h)
                                    @php
                                        $exp = $h->expiry_date ? \Carbon\Carbon::parse($h->expiry_date) : null;
                                        $dl = $exp ? now()->diffInDays($exp, false) : null;
                                        $over = $dl !== null && $dl < 0;
                                        $soon = $dl !== null && $dl >= 0 && $dl <= 7;
                                        $dc = $over ? 'text-red-400' : ($soon ? 'text-amber-400' : 'text-emerald-400');
                                    @endphp
                                    <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                                        <div>
                                            <p class="text-xs font-bold text-white">{{ $h->plan_name ?? $h->hosting_type }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $exp ? $exp->format('M d, Y') : 'No expiry' }}</p>
                                        </div>
                                        <span class="text-[10px] font-bold {{ $dc }}">{{ $dl !== null ? ($over ? abs($dl).'d overdue' : $dl.'d left') : 'N/A' }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-6 text-gray-500 text-xs">No active services</div>
                            @endif
                        </div>
                    </div>
                </div>

                @php
                    $run = Auth::user()->wallet_balance ?? 0;
                    $lbs = []; $dts = [];
                    foreach ($walletTransactions as $t) {
                        $lbs[] = $t->created_at->format('M d');
                        if ($t->type === 'credit') { $run -= (float) $t->amount; } else { $run += (float) $t->amount; }
                        $dts[] = round($run, 2);
                    }
                    $lbs = array_reverse($lbs); $dts = array_reverse($dts);
                @endphp
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var c = document.getElementById('walletChart');
                    if (!c || typeof Chart === 'undefined') return;
                    var ls = {{ json_encode($lbs) }};
                    var ds = {{ json_encode($dts) }};
                    if (!ls.length) { ls = ['Now']; ds = [{{ Auth::user()->wallet_balance ?? 0 }}]; }
                    new Chart(c, {
                        type: 'line',
                        data: { labels: ls, datasets: [{ label: 'Balance', data: ds, borderColor: '#0066FF', backgroundColor: 'rgba(0,102,255,0.06)', borderWidth: 2, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#0066FF', pointBorderColor: '#0f0f13', pointBorderWidth: 2, fill: true }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false }, ticks: { color: '#475569', font: { size: 10 } } }, y: { grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false }, ticks: { color: '#475569', font: { size: 10 }, callback: function(v){ return '₹'+v; } } } }, interaction: { mode: 'index', intersect: false }, animation: { duration: 1000 } }
                    });
                });
                </script>

                <div class="flex items-center gap-3 mb-6">
                    <button type="button" wire:click="openAgreementRequestModal()" class="client-btn inline-flex items-center gap-1">
                        <i class="fas fa-plus text-[9px]"></i> New Project
                    </button>
                    <button type="button" wire:click="openTicketModal()" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white font-bold uppercase tracking-widest text-[10px] hover:bg-white/10 transition-all">
                        <i class="fas fa-headset text-[9px] mr-1"></i> Ticket
                    </button>
                    <a href="{{ route('teams.index') }}" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white font-bold uppercase tracking-widest text-[10px] hover:bg-white/10 transition-all">
                        <i class="fas fa-users text-[9px] mr-1"></i> Teams
                    </a>
                </div>
            </div>

        <!-- Projects Tab -->
        <div x-show="activeTab === 'projects'" x-cloak>
            @include('livewire.partials.dashboard-kanban')
        </div>

        <!-- Tickets/Support Tab -->
        <div x-show="activeTab === 'tickets'" x-cloak>
            @include('livewire.partials.dashboard-tickets')
        </div>

        <!-- File Vault Tab -->
        <div x-show="activeTab === 'vault'" x-cloak>
            @include('livewire.partials.dashboard-files')
        </div>

        <!-- Hosting Tab -->
        <div x-show="activeTab === 'hosting'" x-cloak x-transition.opacity>
            @include('livewire.partials.dashboard-hosting')
        </div>

        <!-- Streaming Tab -->
        <div x-show="activeTab === 'streaming'" x-cloak x-transition.opacity>
            @include('livewire.partials.dashboard-streaming')
        </div>

        <!-- Teams Tab -->
        <div x-show="activeTab === 'teams'" x-cloak>
            <div class="space-y-8">
                <!-- Teams Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">My Teams</h2>
                        <p class="text-gray-400 text-sm mt-1">Manage your teams and collaborate with members.</p>
                    </div>
                    <a href="{{ route('teams.create') }}" class="px-6 py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all text-center">
                        <i class="fas fa-plus mr-2"></i>Create New Team
                    </a>
                </div>

                <!-- Owned Teams -->
                @php
                    $ownedTeams = Auth::user()->ownedTeams()->with('users')->get();
                    $joinedTeams = Auth::user()->teams()->with('owner')->get();
                @endphp

                @if($ownedTeams->count() > 0)
                    <div>
                        <h3 class="text-sm font-black text-gray-500 uppercase tracking-[0.2em] mb-4">Teams I Own</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($ownedTeams as $team)
                                <div class="p-6 glass rounded-3xl border border-white/10 hover:border-electric-blue/30 transition-all">
                                    <div class="flex justify-between items-start mb-4">
                                        <h4 class="font-black text-white uppercase tracking-tight">{{ $team->name }}</h4>
                                        @if($team->is_active)
                                            <span class="px-2 py-1 rounded-full bg-green-500/20 text-green-400 text-[10px] font-black uppercase tracking-widest">Active</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full bg-gray-500/20 text-gray-400 text-[10px] font-black uppercase tracking-widest">Inactive</span>
                                        @endif
                                    </div>
                                    
                                    @if($team->description)
                                        <p class="text-gray-400 text-xs mb-4 line-clamp-2">{{ $team->description }}</p>
                                    @endif
                                    
                                    <div class="flex items-center text-xs text-gray-400 mb-4">
                                        <i class="fas fa-users w-5 text-electric-blue"></i>
                                        <span>{{ $team->users->count() }} members</span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('teams.show', $team) }}" class="flex-1 py-2 rounded-xl bg-electric-blue/10 text-electric-blue font-black uppercase tracking-widest text-[10px] hover:bg-electric-blue hover:text-dark transition-all text-center">
                                            View
                                        </a>
                                        <a href="{{ route('teams.members', $team) }}" class="flex-1 py-2 rounded-xl bg-white/5 text-gray-300 font-black uppercase tracking-widest text-[10px] hover:bg-white/10 transition-all text-center">
                                            Members
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Joined Teams -->
                @if($joinedTeams->count() > 0)
                    <div>
                        <h3 class="text-sm font-black text-gray-500 uppercase tracking-[0.2em] mb-4">Teams I've Joined</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($joinedTeams as $team)
                                <div class="p-6 glass rounded-3xl border border-white/10 hover:border-electric-blue/30 transition-all">
                                    <div class="flex justify-between items-start mb-4">
                                        <h4 class="font-black text-white uppercase tracking-tight">{{ $team->name }}</h4>
                                        @if($team->is_active)
                                            <span class="px-2 py-1 rounded-full bg-green-500/20 text-green-400 text-[10px] font-black uppercase tracking-widest">Active</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full bg-gray-500/20 text-gray-400 text-[10px] font-black uppercase tracking-widest">Inactive</span>
                                        @endif
                                    </div>
                                    
                                    @if($team->description)
                                        <p class="text-gray-400 text-xs mb-4 line-clamp-2">{{ $team->description }}</p>
                                    @endif
                                    
                                    <div class="flex items-center text-xs text-gray-400 mb-2">
                                        <i class="fas fa-crown w-5 text-electric-blue"></i>
                                        <span>Owner: {{ $team->owner->name }}</span>
                                    </div>
                                    
                                    <div class="flex items-center text-xs text-gray-400 mb-4">
                                        <i class="fas fa-users w-5 text-electric-blue"></i>
                                        <span>{{ $team->users->count() }} members</span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('teams.show', $team) }}" class="flex-1 py-2 rounded-xl bg-electric-blue/10 text-electric-blue font-black uppercase tracking-widest text-[10px] hover:bg-electric-blue hover:text-dark transition-all text-center">
                                            View
                                        </a>
                                        <a href="{{ route('teams.members', $team) }}" class="flex-1 py-2 rounded-xl bg-white/5 text-gray-300 font-black uppercase tracking-widest text-[10px] hover:bg-white/10 transition-all text-center">
                                            Members
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- No Teams -->
                @if($ownedTeams->count() === 0 && $joinedTeams->count() === 0)
                    <div class="text-center py-16 glass rounded-[3rem]">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-electric-blue/20 to-electric-violet/20 flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-users text-4xl text-electric-blue"></i>
                        </div>
                        <h3 class="text-3xl font-black text-white uppercase mb-4">No Teams Yet</h3>
                        <p class="text-gray-400 mb-8 max-w-md mx-auto">Create a team to collaborate with others and manage access to your resources.</p>
                        <a href="{{ route('teams.create') }}" class="inline-flex items-center px-8 py-4 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                            <i class="fas fa-plus mr-2"></i>Create Your First Team
                        </a>
                    </div>
                @endif
            </div>
        </div>

    <!-- Add Domain Modal -->
    @if($showDomainModal)
        <div class="fixed inset-0 z-[120] flex items-center justify-center p-6">
            <div class="absolute inset-0 bg-dark/90 backdrop-blur-xl" wire:click="closeDomainModal()"></div>
            <div class="relative w-full max-w-md glass rounded-[3rem] overflow-hidden animate-in zoom-in duration-300">
                <div class="p-8 bg-gradient-to-r from-electric-blue/20 to-electric-violet/20 border-b border-white/10">
                    <h3 class="text-xl font-black text-white uppercase tracking-tight">Add Domain Name</h3>
                    <p class="text-gray-400 text-xs mt-1">Point your domain to this VPS using our nameservers</p>
                </div>
                <div class="p-8">
                    {{-- Nameservers info --}}
                    <div class="mb-6 p-4 bg-electric-blue/10 rounded-2xl border border-electric-blue/20">
                        <p class="text-xs font-black text-electric-blue uppercase tracking-widest mb-3">Step 1: Update Nameservers</p>
                        <p class="text-[11px] text-gray-400 mb-3">Set these nameservers at your domain registrar:</p>
                        <div class="space-y-2">
                            @foreach(['ns1.believoo.com', 'ns2.believoo.com'] as $ns)
                                <div class="flex items-center justify-between p-2 bg-white/5 rounded-xl">
                                    <span class="text-sm font-mono text-white">{{ $ns }}</span>
                                    <button onclick="navigator.clipboard.writeText('{{ $ns }}')" class="text-electric-blue hover:text-white transition-all">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Step 2: Enter Your Domain</p>
                    <input type="text" wire:model="newDomain"
                           placeholder="e.g., example.com or sub.example.com"
                           class="w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white text-sm focus:outline-none focus:border-electric-blue/50 mb-2"
                           x-on:keydown.enter="$wire.addDomain()">
                    @error('newDomain')
                        <p class="text-blue-400 text-xs mb-3">{{ $message }}</p>
                    @enderror
                    <p class="text-[10px] text-gray-500 mb-6">DNS propagation may take up to 24-48 hours after updating nameservers.</p>

                    <div class="flex justify-end space-x-4">
                        <button type="button" wire:click="closeDomainModal()" class="px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs text-gray-400 hover:text-white transition-all cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" wire:click="addDomain()" wire:loading.attr="disabled"
                                class="px-6 py-3 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all disabled:opacity-50 cursor-pointer">
                            <span wire:loading.remove wire:target="addDomain">Add Domain</span>
                            <span wire:loading wire:target="addDomain"><i class="fas fa-spinner fa-spin mr-1"></i>Adding...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Server Action Confirmation Modal -->
    @if($showActionModal)
        <div class="fixed inset-0 z-[120] flex items-center justify-center p-6">
            <div class="absolute inset-0 bg-dark/90 backdrop-blur-xl" wire:click="cancelServerAction()"></div>
            <div class="relative w-full max-w-md glass rounded-[3rem] overflow-hidden animate-in zoom-in duration-300">
                <div class="p-8 bg-gradient-to-r from-red-500/20 to-orange-500/20 border-b border-white/10">
                    <h3 class="text-xl font-black text-white uppercase tracking-tight">{{ $pendingActionTitle }}</h3>
                </div>
                <div class="p-8">
                    <p class="text-gray-300 text-sm mb-8">{{ $pendingActionMessage }}</p>
                    <div class="flex justify-end space-x-4">
                        <button type="button" wire:click="cancelServerAction()" class="px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs text-gray-400 hover:text-white transition-all cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" wire:click="executeServerAction()" wire:loading.attr="disabled"
                                class="px-6 py-3 rounded-2xl bg-red-500 text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all disabled:opacity-50 cursor-pointer">
                            <span wire:loading.remove wire:target="executeServerAction">Confirm</span>
                            <span wire:loading wire:target="executeServerAction"><i class="fas fa-spinner fa-spin mr-1"></i>Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Ticket Modal -->
    @if($isTicketModalOpen)
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-6">
            <div class="absolute inset-0 bg-dark/90 backdrop-blur-xl" wire:click="closeTicketModal()"></div>
            <div class="relative w-full max-w-2xl glass rounded-[3rem] overflow-hidden animate-in zoom-in duration-300">
                <div class="p-10 bg-gradient-to-r from-electric-blue to-electric-violet">
                    <h3 class="text-3xl font-black text-dark uppercase tracking-tighter">Create Support Ticket</h3>
                    <p class="text-dark/60 font-bold text-sm">Tell us what's on your mind and we'll help you out.</p>
                </div>
                <form wire:submit.prevent="createTicket" class="p-10 space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">Subject</label>
                        <input type="text" wire:model="subject" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all">
                        @error('subject') <span class="text-red-500 text-[10px] ml-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">Priority</label>
                            <select wire:model="priority" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">Message</label>
                        <textarea wire:model="message" rows="5" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all"></textarea>
                        @error('message') <span class="text-red-500 text-[10px] ml-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">Attachment (Optional)</label>
                        <input type="file" wire:model="ticketAttachment" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-xs text-gray-400">
                        <div wire:loading wire:target="ticketAttachment" class="text-[10px] text-electric-blue ml-2 italic">Uploading...</div>
                        @error('ticketAttachment') <span class="text-red-500 text-[10px] ml-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" wire:click="closeTicketModal()" class="px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-xs text-gray-400 hover:text-white transition-all cursor-pointer">Cancel</button>
                        <button type="submit" class="px-8 py-4 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">Submit Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Upload Modal -->
    @if($isUploadModalOpen)
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-6">
            <div class="absolute inset-0 bg-dark/90 backdrop-blur-xl" wire:click="closeUploadModal()"></div>
            <div class="relative w-full max-w-xl glass rounded-[3rem] overflow-hidden animate-in zoom-in duration-300">
                <div class="p-10 bg-gradient-to-r from-electric-blue to-electric-violet">
                    <h3 class="text-3xl font-black text-dark uppercase tracking-tighter">Upload to Vault</h3>
                    <p class="text-dark/60 font-bold text-sm">Upload assets, requirements or feedback.</p>
                </div>
                <form wire:submit.prevent="uploadAsset" class="p-10 space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">Project</label>
                        <select wire:model="selectedProjectId" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all">
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedProjectId') <span class="text-red-500 text-[10px] ml-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">File Name</label>
                        <input type="text" wire:model="assetName" class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all" placeholder="e.g., Logo Requirements">
                        @error('assetName') <span class="text-red-500 text-[10px] ml-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">File</label>
                        <div class="relative group">
                            <input type="file" wire:model="assetFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="w-full px-6 py-10 rounded-2xl bg-white/5 border-2 border-dashed border-white/10 group-hover:border-electric-blue/50 transition-all flex flex-col items-center justify-center">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-600 mb-4 group-hover:text-electric-blue transition-colors"></i>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest group-hover:text-white transition-colors">
                                    {{ $assetFile ? $assetFile->getClientOriginalName() : 'Click or drag file to upload' }}
                                </span>
                            </div>
                        </div>
                        @error('assetFile') <span class="text-red-500 text-[10px] ml-2">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" wire:click="closeUploadModal()" class="px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-xs text-gray-400 hover:text-white transition-all cursor-pointer">Cancel</button>
                        <button type="submit" class="px-8 py-4 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">Upload File</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Agreements Tab -->
    <div x-show="activeTab === 'agreements'" x-cloak>
        @php
            if(!Auth::user()->referral_code) {
                Auth::user()->generateReferralCode();
            }
        @endphp
            <div class="p-6 bg-gradient-to-r from-pink-500/10 via-purple-500/10 to-electric-blue/10 rounded-3xl border border-pink-500/30 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-pink-500/20 rounded-full -mr-16 -mt-16 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-electric-blue/20 rounded-full -ml-12 -mb-12 blur-xl"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-pink-500 to-purple-500 flex items-center justify-center">
                                <i class="fas fa-gift text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white uppercase tracking-tight">Refer a Friend & Get 10% Off!</h3>
                                <p class="text-sm text-gray-400">Share your unique referral link and earn discounts on your next milestone</p>
                            </div>
                        </div>
                        
                        <!-- Referral Stats -->
                        <div class="flex flex-wrap gap-4 mb-4">
                            <div class="px-4 py-2 bg-white/5 rounded-xl">
                                <span class="text-[10px] text-gray-500 uppercase">Total Referrals</span>
                                <p class="text-lg font-black text-white">{{ Auth::user()->total_referrals }}</p>
                            </div>
                            <div class="px-4 py-2 bg-white/5 rounded-xl">
                                <span class="text-[10px] text-gray-500 uppercase">Successful</span>
                                <p class="text-lg font-black text-green-500">{{ Auth::user()->successful_referrals }}</p>
                            </div>
                            <div class="px-4 py-2 bg-white/5 rounded-xl">
                                <span class="text-[10px] text-gray-500 uppercase">Discount Balance</span>
                                <p class="text-lg font-black text-electric-blue">${{ number_format(Auth::user()->referral_discount_balance, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Referral Link & Form -->
                    <div class="flex flex-col gap-3 md:w-96">
                        <div class="flex gap-2">
                            <input type="text" value="{{ Auth::user()->referral_link }}" readonly 
                                class="flex-1 px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:border-electric-blue outline-none"
                                id="referralLink">
                            <button onclick="copyReferralLink()" class="px-4 py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        
                        <form action="{{ route('client.referrals.store') }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="email" name="referred_email" placeholder="Friend's email address..." required
                                class="flex-1 px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:border-pink-500 outline-none placeholder-gray-500">
                            <button type="submit" class="px-4 py-3 rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all whitespace-nowrap">
                                <i class="fas fa-paper-plane mr-1"></i>Send
                            </button>
                        </form>
                    </div>
                </div>

                @if(session()->has('referral_success'))
                    <div class="mt-4 p-3 rounded-xl bg-green-500/20 text-green-500 text-sm flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('referral_success') }}
                    </div>
                @endif
                @if(session()->has('referral_error'))
                    <div class="mt-4 p-3 rounded-xl bg-red-500/20 text-red-500 text-sm flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('referral_error') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Agreements Tab -->
        <div x-show="activeTab === 'agreements'" x-cloak>
            <script>
                function copyReferralLink() {
                    const linkInput = document.getElementById('referralLink');
                    linkInput.select();
                    document.execCommand('copy');

                    // Show toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-4 right-4 px-6 py-3 bg-green-500 text-white rounded-xl font-bold z-50 animate-in fade-in';
                    toast.innerHTML = '<i class="fas fa-check mr-2"></i>Referral link copied!';
                    document.body.appendChild(toast);

                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                }
            </script>

            <!-- Agreements Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-white uppercase tracking-tight">Service Agreements</h2>
                    <p class="text-gray-400 text-sm mt-1">View, sign, and track your project agreements</p>
                </div>
                <button type="button" wire:click="openAgreementRequestModal()" class="px-6 py-3 rounded-xl bg-gradient-to-r from-electric-blue to-electric-violet text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all cursor-pointer">
                    <i class="fas fa-plus mr-2"></i>New Project Request
                </button>
            </div>

            @if(session()->has('request_success'))
                <div class="p-4 rounded-xl bg-green-500/20 text-green-500 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    {{ session('request_success') }}
                </div>
            @endif

            <!-- Agreement Requests History -->
            @if($agreementRequests->count() > 0)
                <div class="space-y-4">
                    <h3 class="text-lg font-black text-white uppercase tracking-tight">Your Project Requests</h3>
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($agreementRequests as $request)
                            <div class="p-6 glass rounded-2xl border border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $request->status == 'pending' ? 'bg-yellow-500/20 text-yellow-500' : ($request->status == 'under_review' ? 'bg-blue-500/20 text-blue-500' : ($request->status == 'converted' ? 'bg-green-500/20 text-green-500' : 'bg-gray-500/20 text-gray-400')) }}">
                                            {{ $request->status_label }}
                                        </span>
                                        <span class="text-[10px] font-black text-gray-500 uppercase">#{{ $request->request_number }}</span>
                                    </div>
                                    <h4 class="font-bold text-white text-lg">{{ $request->project_name }}</h4>
                                    <p class="text-gray-400 text-sm mt-1 line-clamp-2">{{ strip_tags($request->project_description) }}</p>
                                    <div class="flex items-center gap-4 mt-3">
                                        @if($request->budget_range)
                                            <span class="text-[10px] text-gray-500"><i class="fas fa-wallet mr-1"></i> Budget: {{ $request->budget_range }}</span>
                                        @endif
                                        @if($request->timeline_expectation)
                                            <span class="text-[10px] text-gray-500"><i class="fas fa-clock mr-1"></i> Timeline: {{ $request->timeline_expectation }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($request->isConverted() && $request->agreement)
                                        <button type="button" wire:click="viewAgreement({{ $request->agreement->id }})" class="px-4 py-2 rounded-lg bg-electric-blue text-dark font-bold text-xs uppercase tracking-wider hover:scale-105 transition-all cursor-pointer">
                                            View Agreement
                                        </button>
                                    @elseif($request->status === 'rejected')
                                        <span class="px-4 py-2 rounded-lg bg-red-500/20 text-red-500 font-bold text-xs uppercase">
                                            Rejected
                                        </span>
                                    @else
                                        <span class="px-4 py-2 rounded-lg bg-white/10 text-gray-400 font-bold text-xs uppercase">
                                            <i class="fas fa-clock mr-1"></i> In Progress
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($selectedAgreement)
                <!-- Agreement Detail View -->
                <div class="glass rounded-[3rem] border border-white/10 overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <!-- Header -->
                    <div class="p-8 bg-gradient-to-r from-electric-blue/20 to-electric-violet/20 border-b border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <button type="button" wire:click="closeAgreementView()" class="mb-4 text-[10px] font-black text-electric-blue uppercase tracking-widest flex items-center hover:translate-x-[-4px] transition-all cursor-pointer">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Agreements
                            </button>
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $selectedAgreement->status == 'signed' ? 'bg-green-500/20 text-green-500' : ($selectedAgreement->status == 'sent' ? 'bg-blue-500/20 text-blue-500' : 'bg-gray-500/20 text-gray-400') }}">
                                    {{ $selectedAgreement->status_label }}
                                </span>
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">#{{ $selectedAgreement->agreement_number }}</span>
                            </div>
                            <h2 class="text-3xl font-black text-white uppercase tracking-tight">{{ $selectedAgreement->title }}</h2>
                            <p class="text-gray-400 text-sm mt-1">{{ $selectedAgreement->project_name }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if(!$selectedAgreement->isSigned())
                                <span class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest bg-yellow-500/20 text-yellow-500">
                                    <i class="fas fa-clock mr-1"></i>Awaiting Signature
                                </span>
                            @else
                                <span class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest bg-green-500/20 text-green-500">
                                    <i class="fas fa-check-circle mr-1"></i>Active
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-8 space-y-8">
                        <!-- Parties Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-6 glass rounded-2xl border border-white/10">
                                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-4">Service Provider</h3>
                                <p class="text-lg font-bold text-white">{{ $selectedAgreement->service_provider_name }}</p>
                                <p class="text-sm text-electric-blue">{{ $selectedAgreement->lead_developer }}</p>
                            </div>
                            <div class="p-6 glass rounded-2xl border border-white/10">
                                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-4">Client</h3>
                                <p class="text-lg font-bold text-white">{{ $selectedAgreement->client_name }}</p>
                                <p class="text-sm text-gray-400">{{ $selectedAgreement->client->email }}</p>
                            </div>
                        </div>

                        <!-- Project Overview -->
                        <div class="p-6 glass rounded-2xl border border-white/10">
                            <h3 class="text-sm font-black text-white uppercase tracking-widest mb-4">Project Overview</h3>
                            <div class="prose prose-invert max-w-none text-gray-300 text-sm">
                                {!! $selectedAgreement->project_overview !!}
                            </div>
                        </div>

                        <!-- Work Items -->
                        @if($selectedAgreement->workItems->count() > 0)
                            <div class="space-y-4">
                                <h3 class="text-sm font-black text-white uppercase tracking-widest">Work Items & Costing</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="border-b border-white/10">
                                                <th class="text-left py-4 px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Item</th>
                                                <th class="text-left py-4 px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Description</th>
                                                <th class="text-right py-4 px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedAgreement->workItems as $item)
                                                <tr class="border-b border-white/5">
                                                    <td class="py-4 px-4 text-sm text-white font-bold">{{ $item->item_name }}</td>
                                                    <td class="py-4 px-4 text-sm text-gray-400">{{ $item->description }}</td>
                                                    <td class="py-4 px-4 text-sm text-white font-bold text-right">${{ number_format($item->amount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="bg-electric-blue/10">
                                                <td class="py-4 px-4 text-sm font-black text-electric-blue uppercase" colspan="2">Total Project Value</td>
                                                <td class="py-4 px-4 text-lg font-black text-electric-blue text-right">${{ number_format($selectedAgreement->total_amount, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- Milestones -->
                        @if($selectedAgreement->milestones->count() > 0)
                            <div class="space-y-4">
                                <h3 class="text-sm font-black text-white uppercase tracking-widest">Timeline & Milestones</h3>
                                <div class="space-y-3">
                                    @foreach($selectedAgreement->milestones as $milestone)
                                        <div class="p-4 glass rounded-2xl border border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="w-8 h-8 rounded-full bg-electric-blue/20 flex items-center justify-center text-electric-blue text-xs font-black">{{ $milestone->timeline_month }}</span>
                                                    <h4 class="font-bold text-white">{{ $milestone->phase_name }}</h4>
                                                    <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $milestone->status == 'paid' ? 'bg-green-500/20 text-green-500' : ($milestone->status == 'completed' ? 'bg-blue-500/20 text-blue-500' : 'bg-gray-500/20 text-gray-400') }}">
                                                        {{ $milestone->status }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-400 ml-11">{{ $milestone->description }}</p>
                                            </div>
                                            <div class="text-right flex flex-col items-end gap-2">
                                                <p class="text-lg font-black text-white">${{ number_format($milestone->payment_amount, 2) }}</p>
                                                @if($milestone->due_date)
                                                    <p class="text-[10px] text-gray-500 uppercase">Due: {{ $milestone->due_date->format('M d, Y') }}</p>
                                                @endif
                                                @php
                                                    $pendingProof = \App\Models\PaymentProof::where('milestone_id', $milestone->id)
                                                        ->where('status', 'pending_verification')
                                                        ->first();
                                                @endphp

                                                @if($milestone->status !== 'paid' && $selectedAgreement->isSigned())
                                                    @if($pendingProof)
                                                        <div class="flex gap-2 mt-2">
                                                            <span class="px-4 py-2 rounded-lg bg-yellow-500/20 text-yellow-500 font-bold text-xs uppercase tracking-wider">
                                                                <i class="fas fa-clock mr-1"></i>Pending Verification
                                                            </span>
                                                        </div>
                                                    @else
                                                        <div class="flex gap-2 mt-2" wire:ignore>
                                                            <button type="button" onclick="window.open('{{ route('milestone.pay', $milestone) }}?t={{ time() }}', '_blank'); return false;" class="px-4 py-2 rounded-lg bg-green-500 text-white font-bold text-xs uppercase tracking-wider hover:scale-105 transition-all">
                                                                <i class="fas fa-credit-card mr-1"></i>Pay Now
                                                            </button>
                                                            <button type="button" onclick="document.getElementById('payment-proof-modal-{{ $milestone->id }}').classList.remove('hidden'); return false;" class="px-4 py-2 rounded-lg bg-electric-blue text-dark font-bold text-xs uppercase tracking-wider hover:scale-105 transition-all">
                                                                <i class="fas fa-upload mr-1"></i>Upload Proof
                                                            </button>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Payment Proof Modal for this milestone -->
                                        @if($milestone->status !== 'paid' && $selectedAgreement->isSigned() && !$pendingProof)
                                            <div id="payment-proof-modal-{{ $milestone->id }}" wire:ignore.self class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-6">
                                                <div class="w-full max-w-md glass rounded-[3rem] border border-white/10 overflow-hidden">
                                                    <div class="p-6 bg-gradient-to-r from-electric-blue/20 to-electric-violet/20 border-b border-white/10 flex items-center justify-between">
                                                        <h3 class="text-lg font-black text-white uppercase">Submit Payment Proof</h3>
                                                        <button onclick="document.getElementById('payment-proof-modal-{{ $milestone->id }}').classList.add('hidden')" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <form wire:ignore action="{{ route('milestone.payment-proof', $milestone) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                                                        @csrf
                                                        <div>
                                                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Payment Method</label>
                                                            <select name="payment_method" required class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-electric-blue outline-none">
                                                                <option value="easypaisa">EasyPaisa</option>
                                                                <option value="jazzcash">JazzCash</option>
                                                                <option value="bank_transfer">Bank Transfer</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Transaction ID</label>
                                                            <input type="text" name="transaction_id" required placeholder="e.g., T123456789" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-electric-blue outline-none">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Screenshot (Optional)</label>
                                                            <input type="file" name="screenshot" accept="image/*" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-electric-blue outline-none">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Notes</label>
                                                            <textarea name="notes" rows="2" placeholder="Any additional information..." class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-electric-blue outline-none resize-none"></textarea>
                                                        </div>
                                                        <div class="pt-4 border-t border-white/10">
                                                            <p class="text-sm text-gray-400 mb-4">Amount to pay: <span class="text-white font-bold">${{ number_format($milestone->payment_amount, 2) }}</span></p>
                                                            <button type="submit" class="w-full px-6 py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                                                                Submit Payment Proof
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Terms -->
                        @if($selectedAgreement->payment_terms || $selectedAgreement->deliverables || $selectedAgreement->support_terms)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                @if($selectedAgreement->payment_terms)
                                    <div class="p-6 glass rounded-2xl border border-white/10">
                                        <h4 class="text-[10px] font-black text-electric-blue uppercase tracking-[0.2em] mb-3">Payment Terms</h4>
                                        <div class="prose prose-invert max-w-none text-gray-400 text-sm">
                                            {!! $selectedAgreement->payment_terms !!}
                                        </div>
                                    </div>
                                @endif
                                @if($selectedAgreement->deliverables)
                                    <div class="p-6 glass rounded-2xl border border-white/10">
                                        <h4 class="text-[10px] font-black text-electric-blue uppercase tracking-[0.2em] mb-3">Deliverables</h4>
                                        <div class="prose prose-invert max-w-none text-gray-400 text-sm">
                                            {!! $selectedAgreement->deliverables !!}
                                        </div>
                                    </div>
                                @endif
                                @if($selectedAgreement->support_terms)
                                    <div class="p-6 glass rounded-2xl border border-white/10">
                                        <h4 class="text-[10px] font-black text-electric-blue uppercase tracking-[0.2em] mb-3">Support Terms</h4>
                                        <div class="prose prose-invert max-w-none text-gray-400 text-sm">
                                            {!! $selectedAgreement->support_terms !!}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- History -->
                        <div class="p-6 glass rounded-2xl border border-white/10">
                            <h3 class="text-sm font-black text-white uppercase tracking-widest mb-4">Agreement History</h3>
                            <div class="space-y-3">
                                @foreach($selectedAgreement->histories->take(10) as $history)
                                    <div class="flex items-center gap-4 p-3 rounded-xl {{ $history->action == 'signed' ? 'bg-green-500/10' : 'bg-white/5' }}">
                                        <div class="w-10 h-10 rounded-full {{ $history->action == 'signed' ? 'bg-green-500/20 text-green-500' : 'bg-electric-blue/20 text-electric-blue' }} flex items-center justify-center">
                                            <i class="fas {{ $history->action_icon }} text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-bold text-white">{{ $history->description }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $history->created_at->format('M d, Y • H:i') }}</p>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-white/10 text-gray-400">
                                            {{ $history->action }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Digital Signature Certificate (if signed) -->
                        @if($selectedAgreement->isSigned() && $selectedAgreement->client_signature_certificate_id)
                            <div class="p-6 bg-gradient-to-r from-green-500/10 to-emerald-500/10 rounded-2xl border border-green-500/30">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center">
                                        <i class="fas fa-certificate text-xl text-green-500"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-black text-white uppercase tracking-tight">Digital Signature Certificate</h3>
                                        <p class="text-[10px] text-green-400 font-bold uppercase tracking-widest">✓ Verified & Legally Binding</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Certificate ID:</span>
                                            <span class="text-green-400 font-mono font-bold">{{ $selectedAgreement->client_signature_certificate_id }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Signed By:</span>
                                            <span class="text-white font-bold">{{ $selectedAgreement->client_name }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Date & Time:</span>
                                            <span class="text-white">{{ $selectedAgreement->client_signed_at?->format('M d, Y \a\t h:i A') }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">IP Address:</span>
                                            <span class="text-white font-mono">{{ $selectedAgreement->client_signature_ip ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-gray-500 text-[10px] uppercase">Browser/Device:</span>
                                            <span class="text-gray-400 text-xs truncate" title="{{ $selectedAgreement->client_signature_user_agent }}">{{ $selectedAgreement->client_signature_user_agent ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-green-500/20">
                                    <p class="text-xs text-gray-400 text-center">
                                        This agreement has been electronically signed in accordance with the Information Technology Act.
                                        All signature data is stored securely for legal verification.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- AMC Subscription Section -->
                        @if($selectedAgreement->isSigned())
                            @php
                                $activeAmc = $selectedAgreement->activeAmcSubscription();
                            @endphp
                            @if($activeAmc)
                                <!-- Active AMC -->
                                <div class="p-6 bg-gradient-to-r from-purple-500/10 to-electric-violet/10 rounded-2xl border border-purple-500/30">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-12 h-12 rounded-full bg-purple-500/20 flex items-center justify-center">
                                            <i class="fas fa-shield-alt text-xl text-purple-500"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-black text-white uppercase tracking-tight">AMC Subscription Active</h3>
                                            <p class="text-[10px] text-purple-400 font-bold uppercase tracking-widest">✓ Priority Support Enabled</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-4">
                                        <div>
                                            <span class="text-gray-500">Plan:</span>
                                            <span class="text-white font-bold ml-2">{{ $activeAmc->plan_label }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Monthly:</span>
                                            <span class="text-white font-bold ml-2">${{ number_format($activeAmc->monthly_amount, 2) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Expires:</span>
                                            <span class="text-white font-bold ml-2">{{ $activeAmc->end_date->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-purple-500/20">
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-[10px] text-gray-300"><i class="fas fa-check text-green-500 mr-1"></i> 24/7 Support</span>
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-[10px] text-gray-300"><i class="fas fa-check text-green-500 mr-1"></i> Bug Fixes</span>
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-[10px] text-gray-300"><i class="fas fa-check text-green-500 mr-1"></i> Updates</span>
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-[10px] text-gray-300"><i class="fas fa-check text-green-500 mr-1"></i> Security Patches</span>
                                    </div>
                                </div>
                            @elseif($selectedAgreement->client_signed_at && $selectedAgreement->client_signed_at->diffInDays(now()) >= 83)
                                <!-- AMC Offer - Warranty expiring soon -->
                                <div class="p-6 bg-gradient-to-r from-orange-500/10 to-yellow-500/10 rounded-2xl border border-orange-500/30">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-full bg-orange-500/20 flex items-center justify-center">
                                                <i class="fas fa-exclamation-triangle text-xl text-orange-500"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-black text-white uppercase tracking-tight">Free Support Ending Soon!</h3>
                                                <p class="text-sm text-gray-400">Your 90-day warranty expires in {{ 90 - $selectedAgreement->client_signed_at->diffInDays(now()) }} days</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('client.amc.subscribe', $selectedAgreement) }}" class="px-6 py-3 rounded-xl bg-orange-500 text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all text-center">
                                            <i class="fas fa-shield-alt mr-2"></i>Subscribe to AMC
                                        </a>
                                    </div>
                                    <div class="mt-4 p-4 bg-white/5 rounded-xl">
                                        <p class="text-sm text-gray-300 mb-3"><strong>Continue worry-free with our AMC (Annual Maintenance Contract):</strong></p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-400">
                                            <div><i class="fas fa-check text-green-500 mr-2"></i>24/7 Priority Support</div>
                                            <div><i class="fas fa-check text-green-500 mr-2"></i>Bug Fixes & Updates</div>
                                            <div><i class="fas fa-check text-green-500 mr-2"></i>Performance Monitoring</div>
                                            <div><i class="fas fa-check text-green-500 mr-2"></i>Security Patches</div>
                                        </div>
                                        <p class="text-center mt-4 text-lg font-black text-orange-400">Starting from $200/month</p>
                                    </div>
                                </div>
                            @else
                                <!-- AMC Info - Within warranty period -->
                                <div class="p-6 bg-gradient-to-r from-gray-500/10 to-gray-600/10 rounded-2xl border border-gray-500/30">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-gray-500/20 flex items-center justify-center">
                                            <i class="fas fa-info-circle text-xl text-gray-400"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-black text-white uppercase tracking-tight">Free Support Active</h3>
                                            <p class="text-sm text-gray-400">
                                                @if($selectedAgreement->client_signed_at)
                                                    Your complimentary 90-day support ends on {{ $selectedAgreement->client_signed_at->addDays(90)->format('M d, Y') }}
                                                @else
                                                    90-day warranty included with your agreement
                                                @endif
                                            </p>
                                        </div>
                                        <span class="px-4 py-2 rounded-lg bg-green-500/20 text-green-500 text-[10px] font-black uppercase">
                                            {{ $selectedAgreement->client_signed_at ? (90 - $selectedAgreement->client_signed_at->diffInDays(now())) . ' Days Left' : 'Included' }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- Signature Section -->
                        @if(!$selectedAgreement->isSigned())
                            <div class="p-8 bg-gradient-to-r from-electric-blue/10 to-electric-violet/10 rounded-3xl border border-electric-blue/30">
                                <h3 class="text-xl font-black text-white uppercase tracking-tight mb-6">Sign Agreement</h3>
                                <p class="text-gray-400 text-sm mb-6">By signing this agreement, you agree to all terms and conditions outlined above. Please type your full name as your digital signature.</p>
                                
                                @if(session()->has('agreement_success'))
                                    <div class="p-4 rounded-xl bg-green-500/20 text-green-500 mb-6">
                                        <i class="fas fa-check-circle mr-2"></i>{{ session('agreement_success') }}
                                    </div>
                                @endif

                                <form wire:submit.prevent="signAgreement" class="space-y-4">
                                    <div>
                                        <input type="text" wire:model="signatureData" placeholder="Type your full name as signature..." 
                                            class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-white text-lg">
                                        @error('signatureData') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="flex gap-4">
                                        <button type="submit" class="flex-1 px-8 py-4 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                                            <i class="fas fa-signature mr-2"></i>Sign Agreement
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="p-8 bg-green-500/10 rounded-3xl border border-green-500/30">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center">
                                            <i class="fas fa-check-circle text-3xl text-green-500"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black text-white uppercase tracking-tight">Agreement Signed</h3>
                                            <p class="text-gray-400">Signed on {{ $selectedAgreement->client_signed_at?->format('M d, Y • H:i') }}</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="viewProjectTracking({{ $selectedAgreement->id }})" class="px-8 py-4 rounded-2xl bg-gradient-to-r from-green-500 to-electric-blue text-white font-black uppercase tracking-widest text-xs hover:scale-105 transition-all flex items-center cursor-pointer">
                                        <i class="fas fa-chart-line mr-2"></i>Track Project
                                    </button>
                                </div>
                                @if($selectedAgreement->client_signature_data)
                                    <div class="mt-6 p-6 bg-white/5 rounded-2xl">
                                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">Client Signature</p>
                                        <p class="text-lg text-electric-blue font-bold">{{ $selectedAgreement->client_signature_data }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($showProjectTracking && $selectedAgreement)
                <!-- Project Tracking View -->
                <div class="glass rounded-[3rem] border border-white/10 overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="p-8 bg-gradient-to-r from-green-500/20 to-electric-blue/20 border-b border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <button type="button" wire:click="closeProjectTracking()" class="mb-4 text-[10px] font-black text-electric-blue uppercase tracking-widest flex items-center hover:translate-x-[-4px] transition-all cursor-pointer">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Agreement
                            </button>
                            <h2 class="text-2xl font-black text-white uppercase tracking-tight">Project Tracking</h2>
                            <p class="text-gray-400 mt-1">{{ $selectedAgreement->project_name }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Overall Progress</p>
                                <div class="flex items-center gap-2">
                                    <div class="w-32 h-3 bg-white/10 rounded-full overflow-hidden">
                                        @php
                                            $totalTasks = $selectedAgreement->tasks->count();
                                            $completedTasks = $selectedAgreement->tasks->where('status', 'completed')->count();
                                            $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                                        @endphp
                                        <div class="h-full bg-gradient-to-r from-green-500 to-electric-blue rounded-full" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-lg font-black text-white">{{ $progress }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 space-y-8">
                        <!-- Progress Stats -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-6 glass rounded-2xl border border-white/10 text-center">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Total Tasks</p>
                                <p class="text-3xl font-black text-white">{{ $selectedAgreement->tasks->count() }}</p>
                            </div>
                            <div class="p-6 glass rounded-2xl border border-white/10 text-center">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Completed</p>
                                <p class="text-3xl font-black text-green-500">{{ $selectedAgreement->tasks->where('status', 'completed')->count() }}</p>
                            </div>
                            <div class="p-6 glass rounded-2xl border border-white/10 text-center">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">In Progress</p>
                                <p class="text-3xl font-black text-blue-500">{{ $selectedAgreement->tasks->where('status', 'in_progress')->count() }}</p>
                            </div>
                            <div class="p-6 glass rounded-2xl border border-white/10 text-center">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Pending</p>
                                <p class="text-3xl font-black text-yellow-500">{{ $selectedAgreement->tasks->where('status', 'pending')->count() }}</p>
                            </div>
                        </div>

                        <!-- Tasks by Status -->
                        @if($selectedAgreement->tasks->count() > 0)
                            <div class="space-y-4">
                                <h3 class="text-sm font-black text-white uppercase tracking-widest">Task Progress</h3>
                                <div class="space-y-3">
                                    @foreach($selectedAgreement->tasks as $task)
                                        <div class="p-4 glass rounded-2xl border border-white/10">
                                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black
                                                            {{ $task->status == 'completed' ? 'bg-green-500/20 text-green-500' : ($task->status == 'in_progress' ? 'bg-blue-500/20 text-blue-500' : 'bg-yellow-500/20 text-yellow-500') }}">
                                                            @if($task->status == 'completed')
                                                                <i class="fas fa-check"></i>
                                                            @elseif($task->status == 'in_progress')
                                                                <i class="fas fa-spinner fa-spin"></i>
                                                            @else
                                                                <i class="fas fa-clock"></i>
                                                            @endif
                                                        </span>
                                                        <h4 class="font-bold text-white">{{ $task->task_name }}</h4>
                                                        <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                                            {{ $task->priority == 'high' ? 'bg-red-500/20 text-red-500' : ($task->priority == 'medium' ? 'bg-yellow-500/20 text-yellow-500' : 'bg-gray-500/20 text-gray-400') }}">
                                                            {{ $task->priority }}
                                                        </span>
                                                    </div>
                                                    @if($task->description)
                                                        <p class="text-sm text-gray-400 ml-11">{{ $task->description }}</p>
                                                    @endif
                                                    <div class="flex items-center gap-4 ml-11 mt-2">
                                                        @if($task->assigned_to)
                                                            <span class="text-[10px] text-gray-500"><i class="fas fa-user mr-1"></i> {{ $task->assigned_to }}</span>
                                                        @endif
                                                        @if($task->due_date)
                                                            <span class="text-[10px] {{ $task->isOverdue() ? 'text-red-500' : 'text-gray-500' }}">
                                                                <i class="fas fa-calendar mr-1"></i> Due: {{ $task->due_date->format('M d, Y') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="w-24 h-2 bg-white/10 rounded-full overflow-hidden mb-2">
                                                        <div class="h-full bg-gradient-to-r from-electric-blue to-green-500 rounded-full" style="width: {{ $task->progress_percentage }}%"></div>
                                                    </div>
                                                    <p class="text-xs text-gray-400">{{ $task->progress_percentage }}% complete</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="py-12 text-center glass rounded-3xl border border-white/10">
                                <i class="fas fa-tasks text-4xl text-gray-700 mb-4"></i>
                                <h3 class="text-lg font-bold text-white uppercase mb-2">No Tasks Yet</h3>
                                <p class="text-gray-500">Tasks will appear here once the project starts.</p>
                            </div>
                        @endif

                        <!-- Milestone Progress -->
                        @if($selectedAgreement->milestones->count() > 0)
                            <div class="space-y-4">
                                <h3 class="text-sm font-black text-white uppercase tracking-widest">Milestone Progress</h3>
                                <div class="space-y-3">
                                    @foreach($selectedAgreement->milestones as $milestone)
                                        <div class="p-4 glass rounded-2xl border border-white/10">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-8 h-8 rounded-full bg-electric-blue/20 flex items-center justify-center text-electric-blue text-xs font-black">{{ $milestone->timeline_month }}</span>
                                                    <h4 class="font-bold text-white">{{ $milestone->phase_name }}</h4>
                                                </div>
                                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                                    {{ $milestone->status == 'paid' ? 'bg-green-500/20 text-green-500' : ($milestone->status == 'completed' ? 'bg-blue-500/20 text-blue-500' : 'bg-gray-500/20 text-gray-400') }}">
                                                    {{ $milestone->status }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm text-gray-400">{{ $milestone->description }}</p>
                                                <p class="text-lg font-black text-white">${{ number_format($milestone->payment_amount, 2) }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Agreements List -->
                @if($agreements->count() > 0)
                    <div class="grid grid-cols-1 gap-6">
                        @foreach($agreements as $agreement)
                            <div wire:click="viewAgreement({{ $agreement->id }})" class="p-8 glass rounded-[2.5rem] border border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:border-electric-blue/30 transition-all cursor-pointer group">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $agreement->status == 'signed' ? 'bg-green-500/20 text-green-500' : ($agreement->status == 'sent' ? 'bg-blue-500/20 text-blue-500' : 'bg-gray-500/20 text-gray-400') }}">
                                            {{ $agreement->status_label }}
                                        </span>
                                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">#{{ $agreement->agreement_number }}</span>
                                    </div>
                                    <h3 class="text-xl font-black text-white uppercase tracking-tight mb-2 group-hover:text-electric-blue transition-colors">{{ $agreement->title }}</h3>
                                    <p class="text-gray-400 text-sm">{{ $agreement->project_name }}</p>
                                    <div class="flex items-center gap-4 mt-4">
                                        <span class="text-[10px] font-bold text-gray-500">{{ $agreement->timeline_months }} months timeline</span>
                                        <span class="text-[10px] font-bold text-electric-blue">{{ $agreement->formatted_total }}</span>
                                        @if($agreement->isPending())
                                            <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-yellow-500/20 text-yellow-500 animate-pulse">
                                                Action Required
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="text-right hidden md:block">
                                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Sent On</p>
                                        <p class="text-white text-xs font-bold">{{ $agreement->sent_at?->format('M d, Y') ?? $agreement->created_at->format('M d, Y') }}</p>
                                    </div>
                                    <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center group-hover:bg-electric-blue group-hover:text-dark transition-all">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-20 text-center glass rounded-[3rem]">
                        <i class="fas fa-file-signature text-5xl text-gray-800 mb-6"></i>
                        <h3 class="text-2xl font-black text-white uppercase mb-2">No Agreements Yet</h3>
                        <p class="text-gray-500">Your service agreements will appear here once they're ready.</p>
                    </div>
                @endif

                <!-- Portfolio & Success Stories -->
                <div class="mt-12 pt-12 border-t border-white/10">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-2xl font-black text-white uppercase tracking-tight">Our Success Stories</h3>
                            <p class="text-gray-400 text-sm mt-1">Explore our portfolio of completed projects</p>
                        </div>
                        <a href="{{ route('portfolio.index') }}" class="text-[10px] font-black text-electric-blue uppercase tracking-widest hover:underline">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @forelse($portfolioItems as $portfolio)
                            <div class="glass rounded-3xl border border-white/10 overflow-hidden group hover:border-electric-blue/30 transition-all">
                                <div class="h-48 bg-gradient-to-br {{ $portfolio->gradient_from ?? 'from-electric-blue/20' }} {{ $portfolio->gradient_to ?? 'to-electric-violet/20' }} flex items-center justify-center relative overflow-hidden">
                                    <i class="fas {{ $portfolio->icon ?? 'fa-briefcase' }} text-6xl text-electric-blue/50 group-hover:scale-110 transition-transform"></i>
                                    <div class="absolute top-4 right-4 px-3 py-1 rounded-full bg-green-500/20 text-green-500 text-[10px] font-black uppercase">
                                        {{ ucfirst($portfolio->status) }}
                                    </div>
                                </div>
                                <div class="p-6">
                                    <h4 class="font-bold text-white text-lg mb-2">{{ $portfolio->title }}</h4>
                                    <p class="text-gray-400 text-sm mb-4">{!! strip_tags($portfolio->description) !!}</p>
                                    @if(!empty($portfolio->tech_stack))
                                        <div class="flex items-center gap-3 flex-wrap">
                                            @foreach($portfolio->tech_stack as $tech)
                                                <span class="px-2 py-1 rounded bg-white/5 text-[10px] text-gray-400">{{ $tech['name'] ?? $tech }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3 py-12 text-center text-gray-500">
                                <i class="fas fa-briefcase text-4xl mb-4"></i>
                                <p>No portfolio items available.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Testimonials -->
                    <div class="mt-12">
                        <h3 class="text-xl font-black text-white uppercase tracking-tight mb-6">Client Testimonials</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @forelse($testimonials as $testimonial)
                                <div class="p-6 glass rounded-2xl border border-white/10">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="w-12 h-12 rounded-full bg-electric-blue/20 flex items-center justify-center overflow-hidden">
                                            @if($testimonial->image)
                                                <img src="{{ Storage::url($testimonial->image) }}" alt="{{ $testimonial->name }}" class="w-full h-full object-cover">
                                            @else
                                                <i class="fas fa-user text-electric-blue"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-bold text-white">{{ $testimonial->name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $testimonial->title }}{{ $testimonial->company ? ', ' . $testimonial->company : '' }}</p>
                                        </div>
                                    </div>
                                    <p class="text-gray-400 text-sm italic">"{{ $testimonial->content }}"</p>
                                    <div class="flex items-center gap-1 mt-4">
                                        @for($i = 0; $i < 5; $i++)
                                            <i class="fas fa-star {{ $i < $testimonial->rating ? 'text-yellow-500' : 'text-gray-600' }} text-xs"></i>
                                        @endfor
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-2 py-12 text-center text-gray-500">
                                    <i class="fas fa-comments text-4xl mb-4"></i>
                                    <p>No testimonials available.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>

    <!-- Agreement Request Modal — inside root div, hidden via x-show -->
    <div
        x-data="{ open: $wire.entangle('isAgreementRequestModalOpen') }"
        x-show="open"
        x-cloak
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-6"
        @click.self="$wire.closeAgreementRequestModal()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="w-full max-w-2xl glass rounded-[3rem] border border-white/10 overflow-hidden" @click.stop>
                <div class="p-8 bg-gradient-to-r from-electric-blue/20 to-electric-violet/20 border-b border-white/10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">New Project Request</h2>
                        <p class="text-gray-400 text-sm mt-1">Tell us about your project requirements</p>
                    </div>
                    <button type="button" wire:click="closeAgreementRequestModal()" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-all cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form wire:submit.prevent="createAgreementRequest" class="p-8 space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3">Project Name</label>
                        <input type="text" wire:model="projectName" placeholder="e.g., E-commerce Mobile App" 
                            class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-white">
                        @error('projectName') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3">Project Description</label>
                        <textarea wire:model="projectDescription" rows="4" placeholder="Describe your project, goals, and what you want to achieve..." 
                            class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-white resize-none"></textarea>
                        @error('projectDescription') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3">Specific Requirements</label>
                        <textarea wire:model="requirements" rows="3" placeholder="List any specific features, integrations, or technical requirements..." 
                            class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-white resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3">Budget Range</label>
                            <input type="text" wire:model="budgetRange" placeholder="e.g., $5,000 - $10,000" 
                                class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3">Expected Timeline</label>
                            <input type="text" wire:model="timelineExpectation" placeholder="e.g., 3-4 months" 
                                class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3">Preferred Technology</label>
                            <input type="text" wire:model="preferredTechnology" placeholder="e.g., React Native, Laravel" 
                                class="w-full px-6 py-4 rounded-2xl bg-white/5 border border-white/10 focus:border-electric-blue outline-none transition-all text-white">
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 pt-4 border-t border-white/10">
                        <button type="button" wire:click="closeAgreementRequestModal()" class="px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-xs text-gray-400 hover:text-white transition-all cursor-pointer">Cancel</button>
                        <button type="submit" class="px-8 py-4 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                            <i class="fas fa-paper-plane mr-2"></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Wallet Tab -->
        <div x-show="activeTab === 'wallet'" x-cloak x-transition.opacity>
            <div class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-5 client-card p-6">
                        <div class="rounded-2xl p-5 mb-5 border border-emerald-500/20" style="background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(0,102,255,0.06));">
                            <p class="text-[10px] uppercase tracking-[0.25em] text-gray-500 font-bold mb-2">Available Balance</p>
                            <div class="text-3xl font-black text-emerald-400">₹{{ number_format(Auth::user()->wallet_balance ?? 0, 2) }}</div>
                            <p class="text-xs text-gray-500 mt-2">Funds are credited instantly after successful payment verification.</p>
                        </div>
                        @php
                            $walletGateways = [];
                            if (($razorpaySettings['razorpay_enabled'] ?? '0') === '1' && !empty($razorpaySettings['razorpay_key_id']) && !empty($razorpaySettings['razorpay_key_secret'])) {
                                $walletGateways[] = ['id' => 'razorpay', 'name' => 'Razorpay', 'icon' => 'fa-credit-card', 'color' => '#00B7FF'];
                            }
                            if (($razorpaySettings['cashfree_enabled'] ?? '0') === '1' && ($razorpaySettings['cashfree_mode'] ?? 'sandbox') === 'production' && !empty($razorpaySettings['cashfree_app_id']) && !empty($razorpaySettings['cashfree_secret_key'])) {
                                $walletGateways[] = ['id' => 'cashfree', 'name' => 'Cashfree', 'icon' => 'fa-wallet', 'color' => '#00D1C1'];
                            }
                            if (($razorpaySettings['paypal_enabled'] ?? '0') === '1' && !empty($razorpaySettings['paypal_client_id']) && !empty($razorpaySettings['paypal_client_secret'])) {
                                $walletGateways[] = ['id' => 'paypal', 'name' => 'PayPal', 'icon' => 'fa-paypal', 'color' => '#0070BA'];
                            }
                            if (($razorpaySettings['payu_enabled'] ?? '0') === '1' && !empty($razorpaySettings['payu_key']) && !empty($razorpaySettings['payu_salt'])) {
                                $walletGateways[] = ['id' => 'payu', 'name' => 'PayU', 'icon' => 'fa-university', 'color' => '#FF6B35'];
                            }
                            $primaryGateway = count($walletGateways) > 0 ? $walletGateways[0] : null;
                        @endphp

                        @if(count($walletGateways) > 0)
                            <div class="space-y-4" x-data="{ step: 1, selectedGateway: '{{ $primaryGateway['id'] ?? '' }}', amount: 1000 }">
                                <!-- STEP 1: Amount -->
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-5 h-5 rounded-full bg-[#0066FF] flex items-center justify-center text-[10px] font-black text-white">1</div>
                                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold">Enter Amount</p>
                                    </div>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm">₹</span>
                                        <input type="number" x-model="amount" min="10" max="500000" step="1"
                                            class="w-full pl-10 pr-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-lg font-bold focus:outline-none focus:border-[#0066FF]">
                                    </div>
                                    <p class="text-[10px] text-gray-600 mt-1">Minimum ₹10, maximum ₹5,00,000 per top-up.</p>
                                    <div class="grid grid-cols-3 gap-2 mt-3">
                                        @foreach([500, 1000, 2500, 5000, 10000, 25000] as $quickAmount)
                                            <button type="button" @click="amount = {{ $quickAmount }}" class="py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-bold hover:border-[#0066FF]/60 hover:bg-[#0066FF]/10 transition-all">₹{{ number_format($quickAmount) }}</button>
                                        @endforeach
                                    </div>
                                    <button @click="step = 2" class="mt-3 w-full py-3 rounded-xl bg-[#0066FF]/20 border border-[#0066FF]/30 text-[#0066FF] font-bold uppercase tracking-widest text-xs hover:bg-[#0066FF]/30 transition-all">
                                        Continue <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
                                    </button>
                                </div>

                                <!-- STEP 2: Payment Method -->
                                <div x-show="step >= 2" x-transition class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-[#0066FF] flex items-center justify-center text-[10px] font-black text-white">2</div>
                                            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold">Select Payment Method</p>
                                        </div>
                                        <button @click="step = 1" class="text-[10px] text-gray-500 hover:text-white"><i class="fas fa-edit mr-1"></i>Edit Amount: ₹<span x-text="amount"></span></button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($walletGateways as $g)
                                            <button type="button"
                                                @click="selectedGateway = '{{ $g['id'] }}'"
                                                :class="selectedGateway === '{{ $g['id'] }}' ? 'border-[#0066FF] bg-[#0066FF]/10' : 'border-white/10 bg-white/5'"
                                                class="flex items-center gap-2 px-3 py-3 rounded-xl border text-xs font-bold transition-all">
                                                <i class="fas {{ $g['icon'] }} text-sm" style="color: {{ $g['color'] }};"></i>
                                                <div class="text-left">
                                                    <span class="text-white block">{{ $g['name'] }}</span>
                                                    <span class="text-[8px] text-emerald-400">Available</span>
                                                </div>
                                                <div class="ml-auto">
                                                    <div :class="selectedGateway === '{{ $g['id'] }}' ? 'border-[#0066FF] bg-[#0066FF]' : 'border-gray-600'" class="w-4 h-4 rounded-full border-2 flex items-center justify-center">
                                                        <div x-show="selectedGateway === '{{ $g['id'] }}'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                                    </div>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>

                                    <!-- Razorpay Pay -->
                                    <div x-show="selectedGateway === 'razorpay'" x-transition>
                                        <form id="topupFormRazorpay" method="POST" action="javascript:void(0);" onsubmit="event.preventDefault(); return false;" class="space-y-3">
                                            @csrf
                                            <div id="topupErrorRazorpay" class="hidden p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center gap-2">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span id="topupErrorTextRazorpay"></span>
                                            </div>
                                            <button id="payBtnRazorpay" type="button" class="w-full py-4 rounded-xl bg-[#0066FF] text-white font-bold uppercase tracking-widest text-xs hover:scale-[1.01] transition-all flex items-center justify-center">
                                                <span id="btnTextRazorpay">Pay ₹<span x-text="Number(amount).toLocaleString('en-IN')"></span> via Razorpay</span>
                                                <svg id="btnSpinnerRazorpay" class="animate-spin ml-2 h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </button>
                                        </form>
                                        <p class="text-center text-[10px] text-gray-600 mt-2"><i class="fas fa-lock mr-1"></i> Secure payment by Razorpay</p>
                                    </div>

                                    <!-- Cashfree Pay -->
                                    <div x-show="selectedGateway === 'cashfree'" x-transition>
                                        <form id="topupFormCashfree" method="POST" action="javascript:void(0);" onsubmit="event.preventDefault(); return false;" class="space-y-3">
                                            @csrf
                                            <div id="topupErrorCashfree" class="hidden p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center gap-2">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span id="topupErrorTextCashfree"></span>
                                            </div>
                                            <button id="payBtnCashfree" type="button" class="w-full py-4 rounded-xl bg-purple-600 text-white font-bold uppercase tracking-widest text-xs hover:scale-[1.01] transition-all flex items-center justify-center">
                                                <span id="btnTextCashfree">Pay ₹<span x-text="Number(amount).toLocaleString('en-IN')"></span> via Cashfree</span>
                                                <svg id="btnSpinnerCashfree" class="animate-spin ml-2 h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </button>
                                        </form>
                                        <p class="text-center text-[10px] text-gray-600 mt-2"><i class="fas fa-lock mr-1"></i> Secure payment by Cashfree</p>
                                    </div>

                                    <!-- PayPal Pay -->
                                    <div x-show="selectedGateway === 'paypal'" x-transition>
                                        <form id="topupFormPaypal" method="POST" action="javascript:void(0);" onsubmit="event.preventDefault(); return false;" class="space-y-3">
                                            @csrf
                                            <div id="topupErrorPaypal" class="hidden p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center gap-2">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span id="topupErrorTextPaypal"></span>
                                            </div>
                                            <button id="payBtnPaypal" type="button" class="w-full py-4 rounded-xl bg-blue-600 text-white font-bold uppercase tracking-widest text-xs hover:scale-[1.01] transition-all flex items-center justify-center">
                                                <span id="btnTextPaypal">Pay $<span x-text="Number(amount).toLocaleString('en-IN')"></span> via PayPal</span>
                                                <svg id="btnSpinnerPaypal" class="animate-spin ml-2 h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </button>
                                        </form>
                                        <p class="text-center text-[10px] text-gray-600 mt-2"><i class="fas fa-lock mr-1"></i> Secure payment by PayPal (USD)</p>
                                    </div>

                                    <!-- PayU Pay -->
                                    <div x-show="selectedGateway === 'payu'" x-transition>
                                        <form id="topupFormPayu" method="POST" action="javascript:void(0);" onsubmit="event.preventDefault(); return false;" class="space-y-3">
                                            @csrf
                                            <div id="topupErrorPayu" class="hidden p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center gap-2">
                                                <i class="fas fa-exclamation-circle"></i>
                                                <span id="topupErrorTextPayu"></span>
                                            </div>
                                            <button id="payBtnPayu" type="button" class="w-full py-4 rounded-xl bg-orange-600 text-white font-bold uppercase tracking-widest text-xs hover:scale-[1.01] transition-all flex items-center justify-center">
                                                <span id="btnTextPayu">Pay ₹<span x-text="Number(amount).toLocaleString('en-IN')"></span> via PayU</span>
                                                <svg id="btnSpinnerPayu" class="animate-spin ml-2 h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </button>
                                        </form>
                                        <p class="text-center text-[10px] text-gray-600 mt-2"><i class="fas fa-lock mr-1"></i> Secure payment by PayU</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 text-xs flex items-center gap-2 mb-4">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>No payment gateways are configured. Please contact support.</span>
                            </div>
                        @endif
                    </div>

                    <div class="lg:col-span-7 space-y-4">
                        <div class="client-card p-6">
                            <h2 class="text-sm font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2"><i class="fas fa-receipt text-[#0066FF] text-xs"></i> Wallet Ledger</h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-gray-500 uppercase text-[10px] tracking-widest border-b border-white/10">
                                            <th class="pb-3">Type</th>
                                            <th class="pb-3">Amount</th>
                                            <th class="pb-3">Balance</th>
                                            <th class="pb-3">Source</th>
                                            <th class="pb-3">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5">
                                        @forelse($walletTransactionsList as $transaction)
                                            <tr>
                                                <td class="py-3"><span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $transaction->type === 'credit' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-orange-500/15 text-orange-400' }}">{{ strtoupper($transaction->type) }}</span></td>
                                                <td class="py-3 text-white font-bold">{{ $transaction->type === 'credit' ? '+' : '-' }}₹{{ number_format($transaction->amount, 2) }}</td>
                                                <td class="py-3 text-gray-300">₹{{ number_format($transaction->balance_after, 2) }}</td>
                                                <td class="py-3 text-gray-400">{{ str_replace('_', ' ', ucfirst($transaction->source)) }}</td>
                                                <td class="py-3 text-gray-500">{{ $transaction->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="py-8 text-center text-gray-500 text-xs">No wallet transactions yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="client-card p-6">
                            <h2 class="text-sm font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2"><i class="fas fa-clock text-emerald-400 text-xs"></i> Recent Top-ups</h2>
                            <div class="space-y-2">
                                @forelse($walletTopups as $topup)
                                    <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/5 p-3">
                                        <div>
                                            <p class="text-white text-xs font-bold">{{ $topup->topup_number }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $topup->created_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-white text-xs font-bold">₹{{ number_format($topup->amount, 2) }}</p>
                                            <p class="text-[10px] {{ $topup->status === 'paid' ? 'text-emerald-400' : ($topup->status === 'failed' ? 'text-red-400' : 'text-yellow-400') }}">{{ strtoupper($topup->status) }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-6 text-center text-gray-500 text-xs">No top-up attempts yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
            <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
            @if(!empty($razorpaySettings['paypal_client_id']))
            <script src="https://www.paypal.com/sdk/js?client-id={{ $razorpaySettings['paypal_client_id'] }}&currency=USD"></script>
            @endif
            <script>
                window.walletTopupRoutes = {
                    'Razorpay': '{{ route('wallet.topup.create') }}',
                    'Cashfree': '{{ route('wallet.topup.cashfree.create') }}',
                    'Paypal': '{{ route('wallet.topup.paypal.create') }}',
                    'Payu': '{{ route('wallet.topup.create') }}'
                };
                (function() {
                    const csrfToken = '{{ csrf_token() }}';

                    function getElements(gateway) {
                        return {
                            errorDiv: document.getElementById('topupError' + gateway),
                            errorText: document.getElementById('topupErrorText' + gateway),
                            btn: document.getElementById('payBtn' + gateway),
                            btnText: document.getElementById('btnText' + gateway),
                            spinner: document.getElementById('btnSpinner' + gateway),
                        };
                    }

                    function showError(els, msg) {
                        if (els.errorDiv && els.errorText) { els.errorText.textContent = msg; els.errorDiv.classList.remove('hidden'); }
                    }
                    function hideError(els) {
                        if (els.errorDiv) els.errorDiv.classList.add('hidden');
                    }
                    function setLoading(els, loading) {
                        if (!els.btn) return;
                        if (loading) {
                            if (els.btnText) els.btnText.textContent = 'Processing...';
                            if (els.spinner) els.spinner.classList.remove('hidden');
                            els.btn.disabled = true;
                        } else {
                            if (els.spinner) els.spinner.classList.add('hidden');
                            els.btn.disabled = false;
                        }
                    }

                    document.addEventListener('click', function (e) {
                        const btn = e.target.closest('[id^="payBtn"]');
                        if (!btn || btn.disabled) return;
                        const gateway = btn.id.replace('payBtn', '');
                        const createRoute = window.walletTopupRoutes[gateway];
                        if (!createRoute) return;
                        e.preventDefault();
                        e.stopPropagation();

                        const els = getElements(gateway);
                        hideError(els);
                        const amountInput = document.querySelector('input[x-model="amount"]');
                        const amount = amountInput ? amountInput.value : 1000;
                        setLoading(els, true);

                        if (gateway === 'Payu') {
                            setLoading(els, false);
                            showError(els, 'PayU wallet top-up is not yet implemented. Please use another gateway.');
                            return;
                        }

                        fetch(createRoute, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ amount: amount }),
                        })
                        .then(r => r.json())
                        .then(data => {
                            setLoading(els, false);
                            if (data.error) { showError(els, data.error); return; }

                            if (gateway === 'Razorpay') {
                                const options = {
                                    key: data.key_id, amount: data.amount, currency: data.currency, order_id: data.order_id,
                                    name: data.name, description: data.description, prefill: data.prefill, notes: data.notes,
                                    handler: function(response) {
                                        const f = document.createElement('form');
                                        f.method = 'POST'; f.action = '{{ route('wallet.topup.callback') }}';
                                        const fields = { _token: csrfToken, razorpay_order_id: response.razorpay_order_id, razorpay_payment_id: response.razorpay_payment_id, razorpay_signature: response.razorpay_signature };
                                        Object.entries(fields).forEach(([name, value]) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = name; i.value = value; f.appendChild(i); });
                                        document.body.appendChild(f); f.submit();
                                    },
                                    theme: { color: '#0066FF' },
                                };
                                const r = new Razorpay(options); r.open();
                                r.on('payment.failed', function(response) { showError(els, 'Payment failed: ' + response.error.description); });
                            } else if (gateway === 'Cashfree') {
                                if (typeof Cashfree === 'undefined') {
                                    showError(els, 'Cashfree SDK not loaded. Please refresh and try again.'); return;
                                }
                                const cashfree = Cashfree({ mode: data.environment || 'sandbox' });
                                cashfree.checkout({ paymentSessionId: data.payment_session_id, redirectTarget: '_self' });
                            } else if (gateway === 'Paypal') {
                                if (data.approval_url) {
                                    window.location.href = data.approval_url;
                                } else {
                                    showError(els, 'Failed to get PayPal approval URL.');
                                }
                            }
                        })
                        .catch(err => {
                            setLoading(els, false);
                            console.error(err); showError(els, 'Failed to initiate wallet top-up. Please try again.');
                        });
                    });
                })();
            </script>
        </div>

        <!-- Orders Tab -->
        <div x-show="activeTab === 'orders'" x-cloak x-transition.opacity>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-widest">My Orders</h2>
                        <p class="text-xs text-gray-500 mt-1">Track and manage all your purchases</p>
                    </div>
                </div>
                <div class="client-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-gray-500 uppercase text-[10px] tracking-widest border-b border-white/10 bg-white/5">
                                    <th class="px-5 py-3">Order</th>
                                    <th class="px-5 py-3">Service</th>
                                    <th class="px-5 py-3">Amount</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @forelse($ordersList as $order)
                                    <tr class="hover:bg-white/5 transition-all">
                                        <td class="px-5 py-3 text-white font-bold">{{ $order->order_number }}</td>
                                        <td class="px-5 py-3 text-gray-300">{{ $order->service_name }}</td>
                                        <td class="px-5 py-3 text-white font-bold">₹{{ number_format($order->amount, 2) }}</td>
                                        <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $order->status === 'paid' ? 'bg-emerald-500/15 text-emerald-400' : ($order->status === 'pending' ? 'bg-yellow-500/15 text-yellow-400' : 'bg-red-500/15 text-red-400') }}">{{ strtoupper($order->status) }}</span></td>
                                        <td class="px-5 py-3 text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No orders found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($ordersList, 'hasPages') && $ordersList->hasPages())
                        <div class="px-5 py-3 border-t border-white/5">{{ $ordersList->links() }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Profile Tab -->
        <div x-show="activeTab === 'profile'" x-cloak x-transition.opacity>
            <div class="space-y-6">
                <div>
                    <h2 class="text-sm font-bold text-white uppercase tracking-widest">Profile Settings</h2>
                    <p class="text-xs text-gray-500 mt-1">Manage your account settings</p>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-4">
                        <div class="client-card p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(0,102,255,0.12);"><i class="fas fa-user text-blue-500 text-xs"></i></div>
                                <div>
                                    <h3 class="text-xs font-bold text-white uppercase tracking-widest">Profile Information</h3>
                                    <p class="text-[10px] text-gray-500">Update your personal details</p>
                                </div>
                            </div>
                            <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
                                @csrf
                                @method('patch')
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Name</label>
                                    <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}" required class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:border-[#0066FF]">
                                    @error('name')<p class="text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Email</label>
                                    <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}" required class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:border-[#0066FF]">
                                    @error('email')<p class="text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone', Auth::user()->phone) }}" class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:border-[#0066FF]">
                                    @error('phone')<p class="text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="pt-2">
                                    <button type="submit" class="client-btn">Save Changes</button>
                                </div>
                            </form>
                        </div>

                        <div class="client-card p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(16,185,129,0.12);"><i class="fas fa-lock text-emerald-400 text-xs"></i></div>
                                <div>
                                    <h3 class="text-xs font-bold text-white uppercase tracking-widest">Security</h3>
                                    <p class="text-[10px] text-gray-500">Update your password</p>
                                </div>
                            </div>
                            <form method="post" action="{{ route('password.update') }}" class="space-y-4">
                                @csrf
                                @method('put')
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Current Password</label>
                                    <input type="password" name="current_password" required class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:border-[#0066FF]">
                                    @error('current_password')<p class="text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">New Password</label>
                                    <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:border-[#0066FF]">
                                    @error('password')<p class="text-red-400 text-[10px] mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Confirm Password</label>
                                    <input type="password" name="password_confirmation" required class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:border-[#0066FF]">
                                </div>
                                <div class="pt-2">
                                    <button type="submit" class="client-btn">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="client-card p-6 sticky top-24">
                            <div class="text-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto" style="background:#0066FF;">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <h3 class="mt-3 text-sm font-bold text-white">{{ Auth::user()->name }}</h3>
                                <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                @if(Auth::user()->phone)
                                    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-phone mr-1 text-gray-600"></i>{{ Auth::user()->phone }}</p>
                                @endif
                            </div>
                            <div class="mt-5 pt-5 border-t border-white/5">
                                <h4 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Account Status</h4>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span>
                                    <span class="text-xs text-gray-400">Active</span>
                                </div>
                                <p class="text-[10px] text-gray-600 mt-1">Member since {{ Auth::user()->created_at->format('M Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div>
    </main>
</div>
