@extends('layouts.admin')

@section('title', $user->name)

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $user->name }}</h1>
    <p class="page-subtitle">{{ $user->email }}</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <div>
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Profile</h3></div>
            <div style="padding: 24px; text-align: center;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #00b7ff, #0099ff); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.5rem; margin: 0 auto 16px;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px;">{{ $user->name }}</div>
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 12px;">{{ $user->email }}</div>
                @if($user->is_admin)
                <span class="badge badge-info">Admin</span>
                @endif
            </div>
            <div style="padding: 0 24px 24px; display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 2px;">Phone</div>
                    <div>{{ $user->phone ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 2px;">Joined</div>
                    <div>{{ $user->created_at->format('M d, Y') }}</div>
                </div>
            </div>
            <div style="padding: 0 24px 24px; display: flex; gap: 8px;">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary" style="flex: 1; justify-content: center;">Edit</a>
            </div>
        </div>

        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-wallet" style="margin-right: 8px;"></i>Wallet</h3>
            </div>
            <div style="padding: 24px;">
                <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(0, 183, 255, 0.06) 100%); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 12px; margin-bottom: 18px;">
                    <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 8px;">Available Balance</div>
                    <div style="font-size: 2.2rem; font-weight: 800; color: #10b981;">₹{{ number_format($user->wallet_balance ?? 0, 2) }}</div>
                </div>

                <form action="{{ route('admin.users.wallet-adjust', $user) }}" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                    @csrf
                    <select name="type" required style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="credit">Credit Balance</option>
                        <option value="debit">Debit Balance</option>
                    </select>
                    <input type="number" step="0.01" min="1" name="amount" required placeholder="Amount in INR"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <input type="text" name="description" placeholder="Reason / note"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <button type="submit" class="btn btn-primary" style="justify-content: center;">Update Wallet</button>
                </form>
            </div>
        </div>

        {{-- Server Management Card --}}
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-server" style="margin-right: 8px;"></i>Server Management</h3>
            </div>
            <div style="padding: 24px;">
                @if($user->whmcs_client_id || $user->vps_ids)
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        @if($user->whmcs_client_id)
                            <div style="background: rgba(0, 183, 255, 0.1); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 10px; padding: 16px;">
                                <div style="font-size: 0.75rem; color: #00b7ff; text-transform: uppercase; margin-bottom: 4px;">WHMCS Client ID</div>
                                <div style="font-size: 1.25rem; font-weight: 700; color: #fff;">{{ $user->whmcs_client_id }}</div>
                            </div>
                        @endif
                        @if($user->vps_ids)
                            <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 10px; padding: 16px;">
                                <div style="font-size: 0.75rem; color: #22c55e; text-transform: uppercase; margin-bottom: 4px;">VPS IDs</div>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    @if(is_array($user->vps_ids))
                                        @foreach($user->vps_ids as $vpsId)
                                            <span style="background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                                                {{ $vpsId }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span style="color: #8b9bb4; font-size: 0.85rem;">{{ $user->vps_ids }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div style="text-align: center; padding-top: 8px;">
                            <a href="{{ route('client.servers') }}" target="_blank" class="btn btn-secondary" style="font-size: 0.8rem;">
                                <i class="fas fa-external-link-alt" style="margin-right: 6px;"></i>View Dashboard
                            </a>
                        </div>
                    </div>
                @else
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-server" style="font-size: 2rem; color: rgba(139, 155, 180, 0.3); margin-bottom: 12px;"></i>
                        <p style="color: #8b9bb4; font-size: 0.85rem; margin-bottom: 16px;">No server management configured for this user.</p>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary" style="font-size: 0.8rem;">
                            <i class="fas fa-cog" style="margin-right: 6px;"></i>Configure Now
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Billing Status Card --}}
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-credit-card" style="margin-right: 8px;"></i>Billing Status</h3>
            </div>
            <div style="padding: 24px;">
                @if($user->plan_price)
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, rgba(0, 183, 255, 0.1) 0%, rgba(0, 183, 255, 0.05) 100%); border-radius: 12px;">
                            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 8px;">Current Plan</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 4px;">{{ $user->current_plan_name ?? 'Standard Plan' }}</div>
                            <div style="font-size: 2rem; font-weight: 800; color: #00b7ff;">₹{{ number_format($user->plan_price, 2) }}<span style="font-size: 0.85rem; color: #8b9bb4;">/month</span></div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div style="display: flex; justify-content: space-between; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px;">
                                <span style="color: #8b9bb4; font-size: 0.85rem;">Billing Status</span>
                                <span style="padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;
                                    {{ $user->billing_status === 'active' ? 'background: rgba(34, 197, 94, 0.15); color: #22c55e;' : ($user->billing_status === 'suspended' ? 'background: rgba(239, 68, 68, 0.15); color: #ef4444;' : 'background: rgba(139, 155, 180, 0.15); color: #8b9bb4;') }}">
                                    {{ ucfirst($user->billing_status ?? 'Unknown') }}
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px;">
                                <span style="color: #8b9bb4; font-size: 0.85rem;">Next Due Date</span>
                                <span style="color: #fff; font-weight: 600;">{{ $user->next_due_date ? $user->next_due_date->format('M d, Y') : 'Not set' }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px;">
                                <span style="color: #8b9bb4; font-size: 0.85rem;">Last Payment</span>
                                <span style="color: #fff; font-weight: 600;">{{ $user->last_payment_date ? $user->last_payment_date->format('M d, Y') : 'No payment' }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-credit-card" style="font-size: 2rem; color: rgba(139, 155, 180, 0.3); margin-bottom: 12px;"></i>
                        <p style="color: #8b9bb4; font-size: 0.85rem; margin-bottom: 16px;">No billing plan configured.</p>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary" style="font-size: 0.8rem;">
                            <i class="fas fa-cog" style="margin-right: 6px;"></i>Set Up Billing
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Orders ({{ $user->orders->count() }})</h3></div>
            <table>
                <thead><tr><th>ID</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($user->orders->take(5) as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</td>
                        <td><span class="badge badge-{{ $order->status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($order->status) }}</span></td>
                        <td>{{ $order->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center; padding: 20px; color: var(--text-muted);">No orders</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Wallet Transactions</h3></div>
            <table>
                <thead><tr><th>Type</th><th>Amount</th><th>Balance</th><th>Source</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($user->walletTransactions as $transaction)
                    <tr>
                        <td><span class="badge badge-{{ $transaction->type === 'credit' ? 'success' : 'warning' }}">{{ ucfirst($transaction->type) }}</span></td>
                        <td>{{ $transaction->type === 'credit' ? '+' : '-' }}₹{{ number_format($transaction->amount, 2) }}</td>
                        <td>₹{{ number_format($transaction->balance_after, 2) }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($transaction->source)) }}</td>
                        <td>{{ $transaction->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center; padding: 20px; color: var(--text-muted);">No wallet transactions</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php $userTickets = \App\Models\Ticket::where('email', $user->email)->latest()->take(5)->get(); @endphp
        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Tickets ({{ \App\Models\Ticket::where('email', $user->email)->count() }})</h3></div>
            <table>
                <thead><tr><th>ID</th><th>Subject</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($userTickets as $ticket)
                    <tr>
                        <td style="font-size: 0.8rem; color: #00b7ff;">{{ $ticket->ticket_id }}</td>
                        <td>{{ Str::limit($ticket->subject, 40) }}</td>
                        <td><span class="badge badge-{{ $ticket->status === 'resolved' ? 'success' : 'warning' }}">{{ ucfirst($ticket->status) }}</span></td>
                        <td>{{ $ticket->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center; padding: 20px; color: var(--text-muted);">No tickets</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
