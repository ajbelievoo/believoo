@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
<div class="page-header">
    <h1 class="page-title">Orders</h1>
    <p class="page-subtitle">Manage customer orders</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Orders</h3>
        <div class="table-actions">
            <select style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 10px 16px; color: #ffffff;">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Amount</th>
                <th>Payment Status</th>
                <th>Order Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td style="font-weight: 600;">#{{ $order->id }}</td>
                <td>{{ $order->user->name ?? 'Guest' }}</td>
                <td>{{ $order->service_name ?? 'N/A' }}</td>
                <td style="font-weight: 600; color: #00b7ff;">{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</td>
                <td>
                    <span class="badge badge-{{ $order->status === 'paid' ? 'success' : ($order->status === 'pending' ? 'warning' : 'danger') }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
                <td>
                    <span class="badge badge-{{ $order->status === 'completed' ? 'success' : ($order->status === 'processing' ? 'info' : ($order->status === 'pending' ? 'warning' : 'danger')) }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
                <td>{{ $order->created_at->format('M d, Y') }}</td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 60px; color: rgba(255,255,255,0.5);">
                    <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No orders found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($orders->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.06);">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
