@extends('layouts.admin')

@section('title', 'Conversion Tracking')

@section('content')
<div class="page-header">
    <h1 class="page-title">Chat → Conversion Funnel</h1>
    <p class="page-subtitle">Last 7 days — visitor to customer journey</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
    @php
        $stages = [
            ['Visitors', $funnel['visitors'], 'fas fa-users', '#3b82f6'],
            ['Chatted', $funnel['chatted'], 'fas fa-comments', '#8b5cf6'],
            ['Leads', $funnel['leads'], 'fas fa-user-plus', '#f59e0b'],
            ['Tickets', $funnel['tickets'], 'fas fa-ticket-alt', '#06b6d4'],
            ['Orders', $funnel['orders'], 'fas fa-shopping-cart', '#f97316'],
            ['Paid', $funnel['paid'], 'fas fa-check-circle', '#22c55e'],
        ];
    @endphp
    @foreach($stages as $s)
        <div class="data-table" style="padding:20px;text-align:center;position:relative;">
            <i class="{{ $s[2] }}" style="font-size:1.5rem;color:{{ $s[3] }};margin-bottom:8px;display:block;"></i>
            <div style="font-size:2rem;font-weight:800;color:var(--text-primary);">{{ $s[1] }}</div>
            <div style="font-size:0.75rem;color:var(--text-muted);">{{ $s[0] }}</div>
            @if($loop->index > 0 && $funnel['visitors'] > 0)
                <div style="position:absolute;top:-8px;left:-8px;font-size:0.65rem;color:#22c55e;background:var(--bg-tertiary);padding:2px 6px;border-radius:4px;">
                    {{ round(($s[1] / $funnel['visitors']) * 100) }}%
                </div>
            @endif
        </div>
    @endforeach
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Peak Chat Hours</h3></div>
        <div style="padding:16px;">
            @forelse($peakHours as $h)
                @php $label = $h->hour < 12 ? ($h->hour == 0 ? '12 AM' : $h->hour . ' AM') : ($h->hour == 12 ? '12 PM' : ($h->hour - 12) . ' PM'); @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border-color);">
                    <span style="font-weight:600;">{{ $label }}</span>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:100px;height:6px;background:var(--bg-tertiary);border-radius:3px;overflow:hidden;">
                            <div style="width:{{ min(100, ($h->cnt / max(1,$peakHours->max('cnt'))) * 100) }}%;height:100%;background:#f59e0b;"></div>
                        </div>
                        <span class="badge badge-warning">{{ $h->cnt }}</span>
                    </div>
                </div>
            @empty
                <p style="color:var(--text-muted);">No data yet.</p>
            @endforelse
        </div>
    </div>

    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Daily Breakdown + CSAT</h3></div>
        <div style="padding:16px;">
            <div style="margin-bottom:16px;padding:12px;background:var(--bg-secondary);border-radius:10px;">
                <div style="font-size:0.8rem;color:var(--text-muted);">Customer Satisfaction (CSAT)</div>
                <div style="font-size:2rem;font-weight:800;color:{{ $csat >= 4 ? '#22c55e' : ($csat >= 3 ? '#f59e0b' : '#ef4444') }};">
                    {{ $csat ? number_format($csat, 1) . ' / 5.0' : '—' }}
                </div>
            </div>
            @forelse($daily as $d)
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-color);">
                    <span style="font-weight:600;">{{ \Carbon\Carbon::parse($d->day)->format('d M') }}</span>
                    <span class="badge badge-info">{{ $d->cnt }} chats</span>
                </div>
            @empty
                <p style="color:var(--text-muted);">No data yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
