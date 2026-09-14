@extends('layouts.app')

@section('title', 'Top-up Wallet')

@section('content')
<div class="min-h-screen bg-[#05070A] pt-28 pb-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <div class="fixed inset-0 pointer-events-none">
        <div class="absolute -top-40 -right-32 w-[560px] h-[560px] rounded-full opacity-20" style="background: radial-gradient(circle, rgba(16,185,129,0.45), transparent 68%); filter: blur(90px);"></div>
        <div class="absolute bottom-0 -left-40 w-[520px] h-[520px] rounded-full opacity-20" style="background: radial-gradient(circle, rgba(0,183,255,0.45), transparent 68%); filter: blur(95px);"></div>
        <div class="absolute inset-0 opacity-30" style="background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px); background-size: 64px 64px;"></div>
    </div>

    <div class="max-w-6xl mx-auto relative z-10">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-10">
            <div>
                <span class="text-emerald-300 font-black uppercase tracking-[0.35em] text-xs">BelieVoo Wallet</span>
                <h1 class="text-4xl md:text-6xl font-black text-white uppercase tracking-tighter mt-3">Top-up <span class="text-[#00B7FF]">Balance</span></h1>
                <p class="text-gray-400 mt-3 max-w-2xl">Add funds once and pay for servers, renewals, streaming and services instantly from wallet balance.</p>
            </div>
            <a href="{{ route('client.dashboard') }}" class="inline-flex items-center justify-center px-5 py-3 rounded-2xl border border-white/10 bg-white/5 text-white font-bold hover:bg-white/10 transition-all">
                <i class="fas fa-arrow-left mr-2"></i> Dashboard
            </a>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-red-500/15 border border-red-500/30 text-red-300">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-5 rounded-3xl border border-white/10 bg-white/[0.06] backdrop-blur-xl p-6 shadow-2xl">
                <div class="rounded-3xl p-6 mb-6 border border-emerald-400/20" style="background: linear-gradient(135deg, rgba(16,185,129,0.18), rgba(0,183,255,0.08));">
                    <p class="text-xs uppercase tracking-[0.25em] text-gray-400 font-black mb-3">Available Balance</p>
                    <div class="text-5xl font-black text-emerald-300">₹{{ number_format($user->wallet_balance ?? 0, 2) }}</div>
                    <p class="text-sm text-gray-400 mt-3">Funds are credited instantly after successful payment verification.</p>
                </div>

@php
    $razorpayEnabled = ($settings['razorpay_enabled'] ?? '0') === '1' && !empty($settings['razorpay_key_id']) && !empty($settings['razorpay_key_secret']);
    $cashfreeEnabled = ($settings['cashfree_enabled'] ?? '0') === '1' && !empty($settings['cashfree_app_id']) && !empty($settings['cashfree_secret_key']);
    $paypalEnabled = ($settings['paypal_enabled'] ?? '0') === '1' && !empty($settings['paypal_client_id']) && !empty($settings['paypal_client_secret']);
    $anyGatewayEnabled = $razorpayEnabled || $cashfreeEnabled || $paypalEnabled;
@endphp

                <form id="topupForm" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-gray-400 font-black mb-2">Enter Amount</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-black">₹</span>
                            <input id="amount" type="number" min="10" max="500000" step="1" value="1000" required
                                class="w-full pl-10 pr-4 py-4 rounded-2xl bg-black/30 border border-white/10 text-white text-2xl font-black focus:outline-none focus:border-[#00B7FF]">
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Minimum ₹10, maximum ₹5,00,000 per top-up.</p>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        @foreach([500, 1000, 2500, 5000, 10000, 25000] as $quickAmount)
                            <button type="button" onclick="setAmount({{ $quickAmount }})" class="py-3 rounded-xl bg-white/5 border border-white/10 text-white font-bold hover:border-[#00B7FF]/60 hover:bg-[#00B7FF]/10 transition-all">₹{{ number_format($quickAmount) }}</button>
                        @endforeach
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs uppercase tracking-widest text-gray-400 font-black">Select Payment Method</label>

                        @if(!$anyGatewayEnabled)
                            <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
                                <i class="fas fa-exclamation-circle mr-2"></i> No payment gateway is configured. Please contact support.
                            </div>
                        @endif

                        @if($razorpayEnabled)
                            <label class="gateway-card flex items-center p-4 rounded-2xl cursor-pointer transition-all border-2 border-white/10 bg-white/5 hover:bg-white/10" data-gateway="razorpay">
                                <input type="radio" name="gateway" value="razorpay" class="sr-only gateway-radio" checked>
                                <div class="flex items-center flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center mr-3">
                                        <i class="fas fa-credit-card text-emerald-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white">Razorpay</p>
                                        <p class="text-xs text-gray-400">UPI, Card, NetBanking</p>
                                    </div>
                                </div>
                                <i class="fas fa-check-circle text-xl text-[#00B7FF] gateway-check hidden"></i>
                            </label>
                        @endif

                        @if($cashfreeEnabled)
                            <label class="gateway-card flex items-center p-4 rounded-2xl cursor-pointer transition-all border-2 border-white/10 bg-white/5 hover:bg-white/10" data-gateway="cashfree">
                                <input type="radio" name="gateway" value="cashfree" class="sr-only gateway-radio">
                                <div class="flex items-center flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center mr-3">
                                        <i class="fas fa-money-bill-wave text-purple-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white">Cashfree</p>
                                        <p class="text-xs text-gray-400">Card, UPI, Wallet, EMI</p>
                                    </div>
                                </div>
                                <i class="fas fa-check-circle text-xl text-[#00B7FF] gateway-check hidden"></i>
                            </label>
                        @endif

                        @if($paypalEnabled)
                            <label class="gateway-card flex items-center p-4 rounded-2xl cursor-pointer transition-all border-2 border-white/10 bg-white/5 hover:bg-white/10" data-gateway="paypal">
                                <input type="radio" name="gateway" value="paypal" class="sr-only gateway-radio">
                                <div class="flex items-center flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center mr-3">
                                        <i class="fab fa-paypal text-blue-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white">PayPal</p>
                                        <p class="text-xs text-gray-400">International Cards & PayPal</p>
                                    </div>
                                </div>
                                <i class="fas fa-check-circle text-xl text-[#00B7FF] gateway-check hidden"></i>
                            </label>
                        @endif
                    </div>

                    <button id="payBtn" type="submit" class="w-full py-5 rounded-2xl bg-[#00B7FF] text-black font-black uppercase tracking-widest shadow-[0_0_30px_rgba(0,183,255,0.35)] hover:scale-[1.02] transition-all flex items-center justify-center" {{ !$anyGatewayEnabled ? 'disabled' : '' }}>
                        <span id="btnText">Add Funds</span>
                        <svg id="btnSpinner" class="animate-spin ml-3 h-5 w-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>

                <p class="text-center text-xs text-gray-500 mt-5"><i class="fas fa-lock mr-1"></i> Secure payment via selected gateway</p>
            </div>

            <div class="lg:col-span-7 space-y-6">
                <div class="rounded-3xl border border-white/10 bg-white/[0.06] backdrop-blur-xl p-6">
                    <h2 class="text-xl font-black text-white uppercase mb-5 flex items-center gap-3"><i class="fas fa-receipt text-[#00B7FF]"></i> Wallet Ledger</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 uppercase text-xs tracking-widest border-b border-white/10">
                                    <th class="pb-3">Type</th>
                                    <th class="pb-3">Amount</th>
                                    <th class="pb-3">Balance</th>
                                    <th class="pb-3">Source</th>
                                    <th class="pb-3">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td class="py-4"><span class="px-3 py-1 rounded-full text-xs font-black {{ $transaction->type === 'credit' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-orange-500/15 text-orange-300' }}">{{ strtoupper($transaction->type) }}</span></td>
                                        <td class="py-4 text-white font-bold">{{ $transaction->type === 'credit' ? '+' : '-' }}₹{{ number_format($transaction->amount, 2) }}</td>
                                        <td class="py-4 text-gray-300">₹{{ number_format($transaction->balance_after, 2) }}</td>
                                        <td class="py-4 text-gray-400">{{ str_replace('_', ' ', ucfirst($transaction->source)) }}</td>
                                        <td class="py-4 text-gray-500">{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-10 text-center text-gray-500">No wallet transactions yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/[0.06] backdrop-blur-xl p-6">
                    <h2 class="text-xl font-black text-white uppercase mb-5 flex items-center gap-3"><i class="fas fa-clock text-emerald-300"></i> Recent Top-ups</h2>
                    <div class="grid gap-3">
                        @forelse($topups as $topup)
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-black/20 p-4">
                                <div>
                                    <p class="text-white font-bold">{{ $topup->topup_number }}</p>
                                    <p class="text-xs text-gray-500">{{ $topup->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-white font-black">₹{{ number_format($topup->amount, 2) }}</p>
                                    <p class="text-xs {{ $topup->status === 'paid' ? 'text-emerald-300' : ($topup->status === 'failed' ? 'text-red-300' : 'text-yellow-300') }}">{{ strtoupper($topup->status) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-gray-500">No top-up attempts yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($razorpayEnabled)
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endif
<script>
    window.cashfreeSdkLoaded = false;
    window.cashfreeSdkError = null;
</script>
<script src="https://sdk.cashfree.com/js/v3/cashfree.js"
    onload="window.cashfreeSdkLoaded = true; console.log('Cashfree SDK loaded successfully');"
    onerror="window.cashfreeSdkError = 'Failed to load Cashfree SDK from CDN'; console.error(window.cashfreeSdkError);"
></script>
<script>
    const csrfToken = '{{ csrf_token() }}';

    function setAmount(amount) {
        document.getElementById('amount').value = amount;
    }

    function setLoading(loading) {
        document.getElementById('btnText').textContent = loading ? 'Processing...' : 'Add Funds';
        document.getElementById('btnSpinner').classList.toggle('hidden', !loading);
        document.getElementById('payBtn').disabled = loading;
    }

    function getSelectedGateway() {
        const radio = document.querySelector('input[name="gateway"]:checked');
        return radio ? radio.value : 'razorpay';
    }

    // Gateway card selection UI
    document.querySelectorAll('.gateway-card').forEach(card => {
        card.addEventListener('click', function() {
            const gateway = this.dataset.gateway;
            document.querySelectorAll('.gateway-card').forEach(c => {
                c.classList.remove('border-[#00B7FF]', 'bg-[#00B7FF]/10');
                c.classList.add('border-white/10', 'bg-white/5');
                c.querySelector('.gateway-check').classList.add('hidden');
            });
            this.classList.remove('border-white/10', 'bg-white/5');
            this.classList.add('border-[#00B7FF]', 'bg-[#00B7FF]/10');
            this.querySelector('.gateway-check').classList.remove('hidden');
            document.querySelectorAll('.gateway-radio').forEach(r => {
                if (r.value === gateway) r.checked = true;
            });
        });
    });

    // Initialize first active gateway as selected
    (function() {
        const firstCard = document.querySelector('.gateway-card');
        if (firstCard) firstCard.click();
    })();

    document.getElementById('topupForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const gateway = getSelectedGateway();
        const amount = document.getElementById('amount').value;
        setLoading(true);

        if (gateway === 'razorpay') {
            createRazorpayTopup(amount);
        } else if (gateway === 'cashfree') {
            createCashfreeTopup(amount);
        } else if (gateway === 'paypal') {
            createPaypalTopup(amount);
        } else {
            setLoading(false);
            alert('Please select a payment method.');
        }
    });

    function createRazorpayTopup(amount) {
        fetch('{{ route('wallet.topup.create') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ amount: amount }),
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);

            if (data.error) {
                alert(data.error);
                return;
            }

            const options = {
                key: data.key_id,
                amount: data.amount,
                currency: data.currency,
                order_id: data.order_id,
                name: data.name,
                description: data.description,
                prefill: data.prefill,
                notes: data.notes,
                handler: function(response) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('wallet.topup.callback') }}';

                    const fields = {
                        _token: csrfToken,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature,
                    };

                    Object.entries(fields).forEach(([name, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                },
                theme: { color: '#00B7FF' },
            };

            const razorpay = new Razorpay(options);
            razorpay.open();
            razorpay.on('payment.failed', function(response) {
                alert('Payment failed: ' + response.error.description);
            });
        })
        .catch(error => {
            setLoading(false);
            console.error('Error:', error);
            alert('Failed to initiate wallet top-up. Please try again.');
        });
    }

    function createCashfreeTopup(amount) {
        fetch('{{ route('wallet.topup.cashfree.create') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ amount: amount }),
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);

            if (data.error) {
                alert(data.error);
                return;
            }

            console.log('Cashfree SDK check — typeof Cashfree:', typeof Cashfree, '| window.cashfreeSdkLoaded:', window.cashfreeSdkLoaded, '| window.cashfreeSdkError:', window.cashfreeSdkError);
            if (typeof Cashfree === 'undefined') {
                alert('Cashfree SDK failed to load. Please refresh the page.');
                return;
            }

            try {
                const cashfree = Cashfree({
                    mode: data.environment === 'production' ? 'production' : 'sandbox'
                });

                cashfree.checkout({
                    paymentSessionId: data.payment_session_id,
                    redirectTarget: '_self'
                });
            } catch (sdkError) {
                setLoading(false);
                console.error('Cashfree SDK error:', sdkError);
                alert('Cashfree error: ' + (sdkError.message || 'Unknown error'));
            }
        })
        .catch(error => {
            setLoading(false);
            console.error('Fetch/Parse error:', error);
            alert('Failed to initiate Cashfree top-up: ' + (error.message || 'Network error'));
        });
    }

    function createPaypalTopup(amount) {
        fetch('{{ route('wallet.topup.paypal.create') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ amount: amount }),
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);

            if (data.error) {
                alert(data.error);
                return;
            }

            if (data.approval_url) {
                window.location.href = data.approval_url;
            } else {
                alert('PayPal approval URL not received. Please try again.');
            }
        })
        .catch(error => {
            setLoading(false);
            console.error('Error:', error);
            alert('Failed to initiate PayPal top-up. Please try again.');
        });
    }
</script>
@endsection
