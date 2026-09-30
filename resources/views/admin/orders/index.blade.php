@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Orders</h1>
        <p class="page-subtitle">Manage customer orders</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-shopping-cart"></i>All Orders</div>
        <div class="table-actions">
            <select name="status" class="form-select" style="width:auto;min-width:140px;" onchange="this.form?.submit?.()">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
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
                    <td style="font-weight: 600; color: var(--accent);">{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</td>
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
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-state"><i class="fas fa-shopping-cart"></i><div>No orders found</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="card-footer">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
