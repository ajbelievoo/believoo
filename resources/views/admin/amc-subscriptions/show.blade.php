@extends('layouts.admin')

@section('title', 'AMC Subscription')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $amcSubscription->subscription_number }}</h1>
    <p class="page-subtitle">{{ $amcSubscription->client->name ?? '—' }}</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 900px;">
    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Subscription Details</h3></div>
        <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Client</div>
                <div style="font-weight: 600;">{{ $amcSubscription->client->name ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Plan</div>
                <div>{{ $amcSubscription->plan_label }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Monthly Amount</div>
                <div style="font-weight: 600; color: #00b7ff;">{{ $currencySymbol }}{{ number_format($amcSubscription->monthly_amount, 2) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Status</div>
                <span class="badge badge-{{ $amcSubscription->status === 'active' ? 'success' : ($amcSubscription->status === 'expired' ? 'danger' : 'warning') }}">
                    {{ ucfirst($amcSubscription->status) }}
                </span>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Period</div>
                <div>{{ $amcSubscription->start_date->format('M d, Y') }} → {{ $amcSubscription->end_date->format('M d, Y') }}</div>
            </div>
            @if($amcSubscription->notes)
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Notes</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">{{ $amcSubscription->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    @if($amcSubscription->agreement)
    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Linked Agreement</h3></div>
        <div style="padding: 24px; display: flex; flex-direction: column; gap: 12px;">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Agreement #</div>
                <div style="font-weight: 600;">{{ $amcSubscription->agreement->agreement_number }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Project</div>
                <div>{{ $amcSubscription->agreement->project_name }}</div>
            </div>
            <a href="{{ route('admin.agreements.show', $amcSubscription->agreement) }}" class="btn btn-secondary" style="margin-top: 8px;">
                <i class="fas fa-external-link-alt"></i> View Agreement
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
