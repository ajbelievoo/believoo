<div class="min-h-screen bg-dark pt-32 pb-20 px-6 relative overflow-hidden">
    {{-- Background Effects --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full opacity-10"
             style="background: radial-gradient(circle, rgba(0,183,255,0.4) 0%, transparent 70%); filter: blur(80px);"></div>
        <div class="absolute bottom-20 -left-40 w-[400px] h-[400px] rounded-full opacity-10"
             style="background: radial-gradient(circle, rgba(112,0,255,0.4) 0%, transparent 70%); filter: blur(60px);"></div>
        <div class="absolute inset-0 opacity-20"
             style="background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 64px 64px;"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">

        {{-- Session Messages --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-400 flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="font-semibold text-sm">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span class="font-semibold text-sm">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div>
                <p class="text-[10px] font-black text-electric-blue uppercase tracking-[0.3em] mb-2">Billing</p>
                <h1 class="text-5xl font-black text-white uppercase tracking-tighter">
                    My <span class="text-electric-blue">Orders</span>
                </h1>
                <p class="text-gray-400 mt-2 text-sm">Track and manage all your purchases</p>
            </div>
            <a href="{{ route('services.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                <i class="fas fa-plus"></i> Browse Services
            </a>
        </div>

        {{-- Stats Row --}}
        @php
            $totalOrders = $orders->total();
            $paidOrders = $orders->getCollection()->where('status', 'paid')->count();
            $pendingOrders = $orders->getCollection()->where('status', 'pending')->count();
            $totalSpent = $orders->getCollection()->where('status', 'paid')->sum('amount');
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="glass rounded-2xl p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Total Orders</span>
                    <i class="fas fa-shopping-bag text-electric-blue text-sm"></i>
                </div>
                <div class="text-3xl font-black text-white">{{ $totalOrders }}</div>
            </div>
            <div class="glass rounded-2xl p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Paid</span>
                    <i class="fas fa-check-circle text-green-400 text-sm"></i>
                </div>
                <div class="text-3xl font-black text-white">{{ $paidOrders }}</div>
            </div>
            <div class="glass rounded-2xl p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Pending</span>
                    <i class="fas fa-clock text-yellow-400 text-sm"></i>
                </div>
                <div class="text-3xl font-black text-white">{{ $pendingOrders }}</div>
            </div>
            <div class="glass rounded-2xl p-5 border border-white/5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Total Spent</span>
                    <i class="fas fa-wallet text-electric-violet text-sm"></i>
                </div>
                <div class="text-2xl font-black text-white">{{ $currencySymbol }}{{ number_format($totalSpent, 0) }}</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="glass rounded-2xl border border-white/5 p-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Search by order ID or service..."
                        class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-electric-blue/50 transition-all"
                    >
                </div>
                <div class="sm:w-52">
                    <select
                        wire:model.live="statusFilter"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-electric-blue/50 transition-all appearance-none"
                    >
                        <option value="" class="bg-gray-900">All Status</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" class="bg-gray-900">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Orders Table --}}
        <div class="glass rounded-3xl border border-white/5 overflow-hidden">
            @if($orders->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Order</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Service</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Amount</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Date</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Payment</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Track</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($orders as $order)
                                @php
                                    $statusConfig = [
                                        'pending'   => ['bg-yellow-500/10 text-yellow-400 border-yellow-500/20', 'fa-clock'],
                                        'paid'      => ['bg-green-500/10 text-green-400 border-green-500/20', 'fa-check-circle'],
                                        'failed'    => ['bg-red-500/10 text-red-400 border-red-500/20', 'fa-times-circle'],
                                        'cancelled' => ['bg-gray-500/10 text-gray-400 border-gray-500/20', 'fa-ban'],
                                        'refunded'  => ['bg-blue-500/10 text-blue-400 border-blue-500/20', 'fa-undo'],
                                    ];
                                    $sc = $statusConfig[$order->status] ?? ['bg-gray-500/10 text-gray-400 border-gray-500/20', 'fa-circle'];
                                @endphp
                                <tr class="hover:bg-white/[0.02] transition-all group">
                                    {{-- Order ID --}}
                                    <td class="px-6 py-5">
                                        <div class="font-black text-white text-sm tracking-tight">{{ $order->order_number }}</div>
                                        @if($order->tier_name)
                                            <div class="text-[10px] font-bold text-electric-blue uppercase tracking-widest mt-1">{{ $order->tier_name }}</div>
                                        @endif
                                    </td>

                                    {{-- Service --}}
                                    <td class="px-6 py-5">
                                        <div class="font-bold text-white text-sm">{{ $order->service_name }}</div>
                                        @if($order->service)
                                            <a href="{{ route('services.show', $order->service) }}"
                                               class="text-[10px] font-black text-electric-blue uppercase tracking-widest hover:underline mt-1 inline-block">
                                                View Service →
                                            </a>
                                        @endif
                                        @if($order->billing_months && $order->billing_months > 1)
                                            <div class="text-[10px] text-gray-500 mt-1">{{ $order->billing_months }} months</div>
                                        @endif
                                    </td>

                                    {{-- Amount --}}
                                    <td class="px-6 py-5">
                                        <div class="font-black text-electric-blue text-base">{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</div>
                                        @if($order->gst_amount > 0)
                                            <div class="text-[10px] text-gray-500 mt-1">Incl. {{ $currencySymbol }}{{ number_format($order->gst_amount, 2) }} GST</div>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-6 py-5">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $sc[0] }}">
                                            <i class="fas {{ $sc[1] }} text-[8px]"></i>
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>

                                    {{-- Date --}}
                                    <td class="px-6 py-5">
                                        <div class="text-sm font-bold text-white">{{ $order->created_at->format('M d, Y') }}</div>
                                        <div class="text-[10px] text-gray-500 mt-1">{{ $order->created_at->format('h:i A') }}</div>
                                    </td>

                                    {{-- Payment --}}
                                    <td class="px-6 py-5">
                                        @if($order->payment_gateway)
                                            <div class="flex items-center gap-2">
                                                @if($order->payment_gateway === 'cashfree')
                                                    <div class="w-6 h-6 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                                        <i class="fas fa-credit-card text-blue-400 text-[10px]"></i>
                                                    </div>
                                                @elseif($order->payment_gateway === 'razorpay')
                                                    <div class="w-6 h-6 rounded-lg bg-electric-blue/20 flex items-center justify-center">
                                                        <i class="fas fa-bolt text-electric-blue text-[10px]"></i>
                                                    </div>
                                                @else
                                                    <div class="w-6 h-6 rounded-lg bg-white/10 flex items-center justify-center">
                                                        <i class="fas fa-wallet text-gray-400 text-[10px]"></i>
                                                    </div>
                                                @endif
                                                <span class="text-sm font-bold text-white capitalize">{{ $order->payment_gateway }}</span>
                                            </div>
                                            @if($order->payment_id)
                                                <div class="text-[10px] text-gray-600 mt-1 truncate max-w-[140px]" title="{{ $order->payment_id }}">
                                                    {{ $order->payment_id }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-[10px] font-bold text-gray-600 uppercase tracking-widest">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5">
                                        <button wire:click="viewOrder({{ $order->id }})"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border bg-electric-blue/10 text-electric-blue border-electric-blue/20 hover:bg-electric-blue/20 transition-all">
                                            <i class="fas fa-route text-[8px]"></i> Track
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($orders->hasPages())
                    <div class="px-6 py-5 border-t border-white/5">
                        {{ $orders->links() }}
                    </div>
                @endif

            @else
                {{-- Empty State --}}
                <div class="text-center py-24 px-6">
                    <div class="w-20 h-20 rounded-full bg-electric-blue/10 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shopping-bag text-3xl text-electric-blue"></i>
                    </div>
                    <h3 class="text-2xl font-black text-white uppercase tracking-tight mb-2">No Orders Yet</h3>
                    <p class="text-gray-400 text-sm mb-8 max-w-sm mx-auto">You haven't placed any orders. Explore our services and get started today.</p>
                    <a href="{{ route('services.index') }}"
                       class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                        <i class="fas fa-rocket"></i> Browse Services
                    </a>
                </div>
            @endif
        </div>

    </div>

    @if($viewingOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm px-4" wire:click.self="viewOrder(null)">
        <div class="glass w-full max-w-2xl rounded-3xl border border-white/10 p-6 md:p-8 relative max-h-[90vh] overflow-y-auto">
            <button wire:click="viewOrder(null)" class="absolute right-4 top-4 w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                <i class="fas fa-times text-xs"></i>
            </button>

            <div class="mb-8">
                <p class="text-[10px] font-black text-electric-blue uppercase tracking-[0.3em] mb-2">Order Tracking</p>
                <h2 class="text-3xl font-black text-white uppercase tracking-tight">{{ $viewingOrder->order_number }}</h2>
                <p class="text-gray-400 text-sm mt-1">{{ $viewingOrder->service_name }}</p>
            </div>

            <div class="relative pl-8 space-y-8">
                <div class="absolute left-[11px] top-2 bottom-2 w-px bg-white/10"></div>
                @foreach($viewingOrder->timeline as $step)
                    @php
                        $stepIcon = match($step['status']) {
                            'completed' => ['fa-check', 'bg-green-500 text-dark border-green-500'],
                            'failed' => ['fa-times', 'bg-red-500 text-dark border-red-500'],
                            default => ['fa-circle', 'bg-dark text-electric-blue border-electric-blue'],
                        };
                    @endphp
                    <div class="relative">
                        <div class="absolute -left-8 top-0.5 w-6 h-6 rounded-full border-2 {{ $stepIcon[1] }} flex items-center justify-center z-10">
                            <i class="fas {{ $stepIcon[0] }} text-[8px]"></i>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <h3 class="text-sm font-black text-white">{{ $step['label'] }}</h3>
                            @if($step['time'])
                                <span class="text-[10px] text-gray-500 font-bold">{{ $step['time']->format('M d, Y \a\t h:i A') }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-400 mt-1 leading-relaxed">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
