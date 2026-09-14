@extends('layouts.admin')

@section('title', 'Order Details')

@section('content')

@if(session('success'))
    <div style="margin-bottom: 20px; padding: 14px 20px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); border-radius: 12px; color: #22c55e; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

{{-- Page Header --}}
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
    <div>
        <a href="{{ route('admin.orders.index') }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700; color: #00b7ff; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        <h1 style="font-size: 1.75rem; font-weight: 900; color: var(--text-primary); text-transform: uppercase; letter-spacing: -0.02em; margin: 0;">
            Order <span style="color: #00b7ff;">{{ $order->order_number }}</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">{{ $order->created_at->format('F d, Y — h:i A') }}</p>
    </div>
    @php
        $statusMap = [
            'paid'       => ['#22c55e', 'rgba(34,197,94,0.1)', 'rgba(34,197,94,0.3)', 'fa-check-circle'],
            'pending'    => ['#f59e0b', 'rgba(245,158,11,0.1)', 'rgba(245,158,11,0.3)', 'fa-clock'],
            'processing' => ['#00b7ff', 'rgba(0,183,255,0.1)', 'rgba(0,183,255,0.3)', 'fa-spinner'],
            'failed'     => ['#ef4444', 'rgba(239,68,68,0.1)', 'rgba(239,68,68,0.3)', 'fa-times-circle'],
            'cancelled'  => ['#6b7280', 'rgba(107,114,128,0.1)', 'rgba(107,114,128,0.3)', 'fa-ban'],
            'refunded'   => ['#8b5cf6', 'rgba(139,92,246,0.1)', 'rgba(139,92,246,0.3)', 'fa-undo'],
        ];
        $sc = $statusMap[$order->status] ?? ['#6b7280', 'rgba(107,114,128,0.1)', 'rgba(107,114,128,0.3)', 'fa-circle'];
    @endphp
    <span style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 50px; background: {{ $sc[1] }}; border: 1px solid {{ $sc[2] }}; color: {{ $sc[0] }}; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;">
        <i class="fas {{ $sc[3] }}"></i> {{ ucfirst($order->status) }}
    </span>
</div>

{{-- Main Grid --}}
<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">

    {{-- LEFT COLUMN --}}
    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- Order Info Card --}}
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(0,183,255,0.1); display: flex; align-items: center; justify-content: center; color: #00b7ff;">
                    <i class="fas fa-receipt"></i>
                </div>
                <h3 style="font-size: 0.9rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Order Information</h3>
            </div>
            <div style="padding: 24px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">Order Number</div>
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem; font-family: monospace;">{{ $order->order_number }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">Service</div>
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">{{ $order->service_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">Tier / Plan</div>
                    <div style="font-weight: 700; color: #00b7ff; font-size: 0.9rem;">{{ $order->tier_name ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">Billing Period</div>
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">{{ $order->billing_months ?? 1 }} {{ ($order->billing_months ?? 1) == 1 ? 'Month' : 'Months' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">Payment Gateway</div>
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem; text-transform: capitalize;">{{ $order->payment_gateway ?? 'N/A' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">Order Date</div>
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">{{ $order->created_at->format('M d, Y') }}</div>
                </div>
            </div>
            @if($order->payment_id)
                <div style="padding: 16px 24px; border-top: 1px solid var(--border-color); background: var(--bg-tertiary);">
                    <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em;">Payment ID: </span>
                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); font-family: monospace;">{{ $order->payment_id }}</span>
                </div>
            @endif
        </div>

        {{-- Payment Breakdown Card --}}
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(34,197,94,0.1); display: flex; align-items: center; justify-content: center; color: #22c55e;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 style="font-size: 0.9rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Payment Breakdown</h3>
            </div>
            <div style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary); font-size: 0.9rem;">Subtotal</span>
                    <span style="font-weight: 700; color: var(--text-primary);">{{ $currencySymbol }}{{ number_format($order->getSubtotalAmount(), 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary); font-size: 0.9rem;">GST ({{ ($order->gst_rate ?? 0.18) * 100 }}%)</span>
                    <span style="font-weight: 700; color: var(--text-primary);">{{ $currencySymbol }}{{ number_format($order->getGstAmount(), 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; margin-top: 12px; background: rgba(0,183,255,0.08); border: 1px solid rgba(0,183,255,0.2); border-radius: 12px;">
                    <span style="font-weight: 900; color: var(--text-primary); font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em;">Total</span>
                    <span style="font-weight: 900; color: #00b7ff; font-size: 1.4rem;">{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</span>
                </div>
                @if($order->paid_at)
                    <div style="margin-top: 16px; padding: 12px 16px; background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2); border-radius: 10px; display: flex; align-items: center; gap: 8px; color: #22c55e; font-size: 0.85rem; font-weight: 600;">
                        <i class="fas fa-check-circle"></i>
                        Paid on {{ $order->paid_at->format('M d, Y — h:i A') }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- Customer Card --}}
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(139,92,246,0.1); display: flex; align-items: center; justify-content: center; color: #8b5cf6;">
                    <i class="fas fa-user"></i>
                </div>
                <h3 style="font-size: 0.9rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Customer</h3>
            </div>
            <div style="padding: 24px;">
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #00b7ff, #8b5cf6); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.1rem; color: white; flex-shrink: 0;">
                        {{ strtoupper(substr($order->user->name ?? 'G', 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight: 800; color: var(--text-primary); font-size: 1rem;">{{ $order->user->name ?? 'Guest' }}</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 2px;">{{ $order->user->email ?? 'N/A' }}</div>
                    </div>
                </div>
                @if($order->user)
                    <a href="{{ route('admin.users.show', $order->user) }}" class="btn btn-secondary" style="width: 100%; justify-content: center; text-align: center;">
                        <i class="fas fa-external-link-alt"></i> View Profile
                    </a>
                @endif
            </div>
        </div>

        {{-- Update Status Card --}}
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(245,158,11,0.1); display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                    <i class="fas fa-edit"></i>
                </div>
                <h3 style="font-size: 0.9rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Update Status</h3>
            </div>
            <div style="padding: 24px;">
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Payment Status</label>
                        <select name="status" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.9rem; font-weight: 600;">
                            <option value="pending"    {{ $order->status === 'pending'    ? 'selected' : '' }}>Pending</option>
                            <option value="paid"       {{ $order->status === 'paid'       ? 'selected' : '' }}>Paid</option>
                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="failed"     {{ $order->status === 'failed'     ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled"  {{ $order->status === 'cancelled'  ? 'selected' : '' }}>Cancelled</option>
                            <option value="refunded"   {{ $order->status === 'refunded'   ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
        </div>

        {{-- Notes Card --}}
        @if($order->notes)
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 0.9rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Notes</h3>
                </div>
                <div style="padding: 20px 24px;">
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">{{ $order->notes }}</p>
                </div>
            </div>
        @endif

        {{-- Hosting Link --}}
        @if($order->hosting)
            <div style="background: var(--bg-secondary); border: 1px solid rgba(0,183,255,0.3); border-radius: 20px; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(0,183,255,0.1); display: flex; align-items: center; justify-content: center; color: #00b7ff;"><i class="fas fa-server"></i></div>
                    <h3 style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Hosting Plan</h3>
                </div>
                <div style="padding: 20px 24px;">
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-bottom: 4px;">Plan</div>
                        <div style="font-weight: 700; color: var(--text-primary);">{{ $order->hosting->plan_name }}</div>
                    </div>
                    @php
                        $hsc = ['active'=>['#22c55e','rgba(34,197,94,0.1)'],'pending'=>['#f59e0b','rgba(245,158,11,0.1)'],'suspended'=>['#ef4444','rgba(239,68,68,0.1)']];
                        $hc = $hsc[$order->hosting->status] ?? ['#6b7280','rgba(107,114,128,0.1)'];
                    @endphp
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 50px; background: {{ $hc[1] }}; color: {{ $hc[0] }}; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 16px;">
                        {{ ucfirst($order->hosting->status) }}
                    </span>
                    <a href="{{ route('admin.hostings.edit', $order->hosting) }}" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-server"></i>
                        @if($order->hosting->status === 'pending') Setup & Activate Plan @else Manage Hosting @endif
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>

<style>
    @media (max-width: 1024px) {
        .grid-3 { grid-template-columns: 1fr !important; }
    }
</style>

@endsection
