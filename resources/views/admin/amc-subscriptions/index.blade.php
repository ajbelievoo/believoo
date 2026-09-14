@extends('layouts.admin')

@section('title', 'AMC Subscriptions')

@section('content')
<div class="page-header">
    <h1 class="page-title">AMC Subscriptions</h1>
    <p class="page-subtitle">Manage annual maintenance contracts</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Subscriptions</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Subscription #</th>
                <th>Client</th>
                <th>Plan</th>
                <th>Monthly Amount</th>
                <th>Status</th>
                <th>Expires</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subscriptions as $sub)
            <tr>
                <td style="font-weight: 600; color: #00b7ff;">{{ $sub->subscription_number }}</td>
                <td>{{ $sub->client->name ?? '—' }}</td>
                <td>{{ $sub->plan_label }}</td>
                <td style="font-weight: 600;">{{ $currencySymbol }}{{ number_format($sub->monthly_amount, 2) }}</td>
                <td>
                    <span class="badge badge-{{ $sub->status === 'active' ? 'success' : ($sub->status === 'expired' ? 'danger' : ($sub->status === 'cancelled' ? 'danger' : 'warning')) }}">
                        {{ ucfirst($sub->status) }}
                    </span>
                </td>
                <td>{{ $sub->end_date->format('M d, Y') }}</td>
                <td>
                    <a href="{{ route('admin.amc-subscriptions.show', $sub) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-sync-alt" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No subscriptions found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($subscriptions->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $subscriptions->links() }}
    </div>
    @endif
</div>
@endsection
