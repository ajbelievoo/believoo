<div class="min-h-screen bg-dark pt-32 pb-20 px-6 relative overflow-hidden">
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full opacity-10"
             style="background: radial-gradient(circle, rgba(0,183,255,0.4) 0%, transparent 70%); filter: blur(80px);"></div>
        <div class="absolute bottom-20 -left-40 w-[400px] h-[400px] rounded-full opacity-10"
             style="background: radial-gradient(circle, rgba(112,0,255,0.4) 0%, transparent 70%); filter: blur(60px);"></div>
        <div class="absolute inset-0 opacity-20"
             style="background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 64px 64px;"></div>
    </div>

    <div class="max-w-5xl mx-auto relative z-10">
        <a href="{{ route('client.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-bold text-electric-blue hover:underline mb-6">
            <i class="fas fa-arrow-left text-xs"></i> Back to Dashboard
        </a>

        <div class="text-center mb-10">
            <p class="text-[10px] font-black text-electric-blue uppercase tracking-[0.3em] mb-2">Refer & Earn</p>
            <h1 class="text-5xl font-black text-white uppercase tracking-tighter">Invite <span class="text-electric-blue">Friends</span></h1>
            <p class="text-gray-400 mt-3 text-sm max-w-xl mx-auto">Share your unique link. When they sign up and make their first payment, you earn credit.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-4 mb-10">
            <div class="glass rounded-2xl p-5 border border-white/5 text-center">
                <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Total Referrals</div>
                <div class="text-3xl font-black text-white">{{ $stats['total'] }}</div>
            </div>
            <div class="glass rounded-2xl p-5 border border-white/5 text-center">
                <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Successful</div>
                <div class="text-3xl font-black text-emerald-400">{{ $stats['successful'] }}</div>
            </div>
            <div class="glass rounded-2xl p-5 border border-white/5 text-center">
                <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Credit Balance</div>
                <div class="text-3xl font-black text-electric-blue">₹{{ number_format($stats['balance'], 2) }}</div>
            </div>
        </div>

        <div class="glass rounded-3xl border border-white/5 p-6 md:p-8 mb-10">
            <h2 class="text-xl font-black text-white uppercase tracking-tight mb-4">Your Referral Link</h2>
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" value="{{ $referralLink }}" readonly
                       class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-electric-blue/50 transition-all" id="referral-link">
                <button onclick="navigator.clipboard.writeText(document.getElementById('referral-link').value); this.innerHTML='<i class=\'fas fa-check\'></i> Copied'; setTimeout(()=>this.innerHTML='<i class=\'fas fa-copy\'></i> Copy', 2000)"
                        class="px-6 py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div class="glass rounded-3xl border border-white/5 overflow-hidden">
            <div class="p-6 border-b border-white/5">
                <h2 class="text-xl font-black text-white uppercase tracking-tight">Referred Users</h2>
            </div>
            @if($referrals->count())
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Name</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Email</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($referrals as $ref)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-6 py-4 text-sm font-bold text-white">{{ $ref->referred_name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-400">{{ $ref->referred_email ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border
                                            @if($ref->status === 'rewarded') bg-green-500/10 text-green-400 border-green-500/20
                                            @elseif($ref->status === 'converted') bg-blue-500/10 text-blue-400 border-blue-500/20
                                            @elseif($ref->status === 'registered') bg-yellow-500/10 text-yellow-400 border-yellow-500/20
                                            @else bg-gray-500/10 text-gray-400 border-gray-500/20 @endif">
                                            {{ ucfirst($ref->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-400">{{ $ref->registered_at?->format('M d, Y') ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-16">
                    <div class="w-16 h-16 rounded-full bg-electric-blue/10 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-plus text-2xl text-electric-blue"></i>
                    </div>
                    <h3 class="text-lg font-black text-white uppercase tracking-tight mb-2">No referrals yet</h3>
                    <p class="text-gray-400 text-sm">Share your link to start earning.</p>
                </div>
            @endif
        </div>
    </div>
</div>
