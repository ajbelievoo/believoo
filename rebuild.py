import re

with open('/www/wwwroot/believoo/resources/views/livewire/client-dashboard.blade.php', 'r') as f:
    lines = f.readlines()

# Find content_start: first line with "<!-- Projects Tab -->"
content_start_idx = None
for i, line in enumerate(lines):
    if '<!-- Projects Tab -->' in line:
        content_start_idx = i
        break

# Find content_end: last </div> before end of file that closes the root
# The last line should be "</div>{{-- end root div --> or similar
# We'll go backwards from end to find where the "content" we want to keep starts
content_end_idx = None
for i in range(len(lines)-1, -1, -1):
    if 'end root div' in lines[i] or lines[i].strip() == '</div>':
        content_end_idx = i
        break

if content_start_idx is None or content_end_idx is None:
    print(f"ERROR: start={content_start_idx}, end={content_end_idx}")
    exit(1)

preserved = ''.join(lines[content_start_idx:content_end_idx])

new_file = '''<div class="flex min-h-screen" style="background: #0f0f13;"
     x-data="{ lastUnreadCount: {{ Auth::user()->unreadNotifications->count() }}, activeTab: $wire.$entangle('activeTab'), sidebarOpen: false }"
     x-on:clear-server-message.window="setTimeout(() => $wire.clearServerMessage(), 5000)"
     @if(isset($hasProvisioning) && $hasProvisioning) wire:poll.30000ms @endif>

    <style>
        .gm-style * { cursor: default !important; }
        .gm-style { cursor: default !important; }
        .client-sidebar-link { display:flex; align-items:center; gap:12px; padding:10px 16px; border-radius:10px; color:#9ca3af; font-size:13px; font-weight:600; transition:all .15s; }
        .client-sidebar-link:hover, .client-sidebar-link.active { background:rgba(225,29,72,0.1); color:#f43f5e; }
        .client-sidebar-link.active { background:rgba(225,29,72,0.15); }
        .client-card { background:#1a1a20; border:1px solid rgba(255,255,255,0.05); border-radius:16px; }
        .client-card:hover { border-color:rgba(255,255,255,0.08); }
        .client-btn { background:#e11d48; color:#fff; border-radius:10px; font-weight:700; font-size:11px; letter-spacing:0.05em; text-transform:uppercase; padding:8px 16px; transition:all .15s; }
        .client-btn:hover { background:#be123c; }
        .custom-scroll::-webkit-scrollbar { width:4px; }
        .custom-scroll::-webkit-scrollbar-track { background:transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:4px; }
    </style>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/60 z-40 lg:hidden" x-cloak></div>

    <aside class="fixed lg:static inset-y-0 left-0 z-50 w-64 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           style="background:#16161d; border-right:1px solid rgba(255,255,255,0.05);">
        <div class="p-6 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-sm" style="background:#e11d48;">B</div>
            <span class="text-white font-bold text-lg tracking-tight">Believoo</span>
        </div>
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto custom-scroll">
            <p class="px-4 text-[10px] font-bold text-gray-600 uppercase tracking-widest mt-4 mb-2">Menu</p>
            <button @click="activeTab = 'projects'; $wire.switchTab('projects'); sidebarOpen = false"
                    :class="activeTab === 'projects' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-rocket w-4 text-center text-xs"></i> Projects
            </button>
            <button @click="activeTab = 'tickets'; $wire.switchTab('tickets'); sidebarOpen = false"
                    :class="activeTab === 'tickets' ? 'active' : ''" class="client-sidebar-link w-full text-left">
                <i class="fas fa-headset w-4 text-center text-xs"></i> Support
            </button>
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
            <a href="{{ route('wallet.topup') }}" class="client-sidebar-link">
                <i class="fas fa-wallet w-4 text-center text-xs"></i> Wallet
            </a>
            <a href="{{ route('client.orders') }}" class="client-sidebar-link">
                <i class="fas fa-file-invoice w-4 text-center text-xs"></i> Orders
            </a>
            <a href="{{ route('profile.edit') }}" class="client-sidebar-link">
                <i class="fas fa-user-circle w-4 text-center text-xs"></i> Profile
            </a>
        </nav>
        <div class="p-4 border-t border-white/5">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="client-sidebar-link w-full text-left text-gray-500 hover:text-red-400">
                    <i class="fas fa-sign-out-alt w-4 text-center text-xs"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 min-w-0">
        <header class="sticky top-0 z-30 px-6 py-4 flex items-center justify-between"
                style="background:rgba(15,15,19,0.85); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.05);">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-gray-400">
                    <i class="fas fa-bars text-xs"></i>
                </button>
                <div>
                    <h1 class="text-lg font-bold text-white tracking-tight">Dashboard</h1>
                    <p class="text-[11px] text-gray-500">Welcome back, {{ Auth::user()->name }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10">
                    <i class="fas fa-wallet text-emerald-400 text-[10px]"></i>
                    <span class="text-xs font-bold text-white">₹{{ number_format(Auth::user()->wallet_balance ?? 0, 2) }}</span>
                </div>
                <a href="{{ route('wallet.topup') }}" class="client-btn hidden md:inline-flex items-center gap-1">
                    <i class="fas fa-plus text-[9px]"></i> Add Funds
                </a>
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
                                <button x-on:click="$wire.markNotificationsAsRead(); open = false" class="text-[10px] font-bold text-[#e11d48]">Mark read</button>
                            @endif
                        </div>
                        <div class="p-2">
                            @forelse(Auth::user()->notifications()->latest()->take(5)->get() as $n)
                                <div class="p-3 rounded-xl hover:bg-white/5 transition-all {{ $n->read_at ? 'opacity-50' : '' }}">
                                    <p class="text-xs text-white font-bold">{{ $n->data['title'] ?? 'Notification' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $n->data['body'] ?? '' }}</p>
                                    <p class="text-[9px] text-gray-600 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <div class="p-6 text-center text-gray-500 text-xs">No notifications</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold" style="background:#e11d48;">
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
                            <a href="{{ route('profile.edit') }}" class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all"><i class="fas fa-user-circle mr-2 text-[#e11d48]"></i> Profile</a>
                            <a href="{{ route('client.orders') }}" class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/5 rounded-xl transition-all"><i class="fas fa-file-invoice mr-2 text-amber-400"></i> Orders</a>
                            <form method="POST" action="{{ route('logout') }}" class="block mt-1">
                                @csrf
                                <button type="submit" class="w-full flex items-center px-3 py-2 text-xs text-gray-300 hover:text-red-400 hover:bg-white/5 rounded-xl transition-all text-left"><i class="fas fa-sign-out-alt mr-2 text-red-400"></i> Logout</button>
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

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="client-card p-4 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(225,29,72,0.12);">
                        <i class="fas fa-rocket text-[#f43f5e] text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-white">{{ $projects->where('status', '!=', 'Delivered')->count() }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Projects</p>
                    </div>
                </div>
                <div class="client-card p-4 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(139,92,246,0.12);">
                        <i class="fas fa-headset text-violet-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-white">{{ $tickets->where('status', 'open')->count() }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Open Tickets</p>
                    </div>
                </div>
                <div class="client-card p-4 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(16,185,129,0.12);">
                        <i class="fas fa-server text-emerald-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-white">{{ $hostings->whereIn('status', ['active','pending'])->count() }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Hostings</p>
                    </div>
                </div>
                <div class="client-card p-4 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(245,158,11,0.12);">
                        <i class="fas fa-file-signature text-amber-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-white">{{ $agreements->count() }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Agreements</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <div class="lg:col-span-2 client-card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-chart-line text-[#f43f5e] text-xs"></i>
                            <h2 class="text-xs font-bold text-white uppercase tracking-widest">Wallet Ledger</h2>
                        </div>
                        <a href="{{ route('wallet.topup') }}" class="px-3 py-1.5 rounded-lg text-[#f43f5e] font-bold uppercase tracking-widest text-[9px] hover:bg-white/5 transition-all" style="border:1px solid rgba(244,63,94,0.2);">+ Top Up</a>
                    </div>
                    <div style="height:220px;">
                        <canvas id="walletChart"></canvas>
                    </div>
                </div>
                <div class="client-card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clock-rotate-left text-emerald-400 text-xs"></i>
                            <h2 class="text-xs font-bold text-white uppercase tracking-widest">Renewals</h2>
                        </div>
                        <a href="{{ route('client.dashboard') }}?tab=hosting" class="px-3 py-1.5 rounded-lg text-emerald-400 font-bold uppercase tracking-widest text-[9px]" style="border:1px solid rgba(16,185,129,0.2);">Manage</a>
                    </div>
                    <div class="space-y-3 max-h-[220px] overflow-y-auto custom-scroll">
                        @php
                            $tl = ($userHostings ?? collect())->whereIn('status', ['active','pending'])->sortBy('expiry_date')->take(5);
                        @endphp
                        @if($tl->count() > 0)
                            @foreach($tl as $h)
                                @php
                                    $exp = $h->expiry_date ? \\Carbon\\Carbon::parse($h->expiry_date) : null;
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
                    data: { labels: ls, datasets: [{ label: 'Balance', data: ds, borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,0.06)', borderWidth: 2, tension: 0.4, pointRadius: 3, pointBackgroundColor: '#f43f5e', pointBorderColor: '#0f0f13', pointBorderWidth: 2, fill: true }] },
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

'''

# Add preserved content
new_file += preserved

# Add closing tags
new_file += '''        </div>
    </main>
</div>
'''

with open('/www/wwwroot/believoo/resources/views/livewire/client-dashboard.blade.php', 'w') as f:
    f.write(new_file)

print(f"Done. Preserved {content_end_idx - content_start_idx} lines of content.")
