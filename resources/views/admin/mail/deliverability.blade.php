@extends('layouts.admin')

@section('title', 'Mail Deliverability')

@section('content')
<div class="page-header">
    <h1 class="page-title">Gmail / Deliverability Monitor</h1>
    <p class="page-subtitle">DNS, reputation, TLS and bounce health for {{ $checks['domain'] }}</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 2.5rem; font-weight: 800; color: {{ $score >= 80 ? 'var(--success)' : ($score >= 50 ? 'var(--warning)' : 'var(--danger)') }};">{{ $score }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Deliverability Score</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 2rem; font-weight: 800; color: {{ $checks['blacklist_status'] === 'clean' ? 'var(--success)' : 'var(--danger)' }};">{{ ucfirst($checks['blacklist_status']) }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Blacklist Status</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--info);">{{ $checks['ip'] }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Mail IP</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 2rem; font-weight: 800; color: {{ $checks['cert_expiry_days'] !== null && $checks['cert_expiry_days'] > 7 ? 'var(--success)' : 'var(--warning)' }};">
            {{ $checks['cert_expiry_days'] !== null ? $checks['cert_expiry_days'] . 'd' : 'N/A' }}
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Cert Expiry</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 30px;">
    <div class="card" style="padding: 20px;">
        <h3 style="font-size: 1rem; margin: 0 0 14px; color: var(--text-primary);"><i class="fas fa-network-wired"></i> DNS Records</h3>
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                <span style="color: var(--text-secondary); font-size: 0.85rem;">SPF</span>
                <span class="badge {{ $checks['spf'] ? 'badge-success' : 'badge-danger' }}">{{ $checks['spf'] ? 'Present' : 'Missing' }}</span>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                <span style="color: var(--text-secondary); font-size: 0.85rem;">DMARC</span>
                <span class="badge {{ $checks['dmarc'] ? 'badge-success' : 'badge-danger' }}">{{ $checks['dmarc'] ? 'Present' : 'Missing' }}</span>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                <span style="color: var(--text-secondary); font-size: 0.85rem;">DKIM</span>
                <span class="badge {{ $checks['dkim'] ? 'badge-success' : 'badge-danger' }}">{{ $checks['dkim'] ? 'Present' : 'Missing' }}</span>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                <span style="color: var(--text-secondary); font-size: 0.85rem;">MX</span>
                <span style="font-size: 0.85rem; color: var(--text-primary);">{{ $checks['mx_count'] > 0 ? implode(', ', $checks['mx']) : 'Missing' }}</span>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0;">
                <span style="color: var(--text-secondary); font-size: 0.85rem;">PTR</span>
                <span style="font-size: 0.85rem; color: var(--text-primary);">{{ $checks['ptr'] ?? 'None' }}</span>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 20px;">
        <h3 style="font-size: 1rem; margin: 0 0 14px; color: var(--text-primary);"><i class="fas fa-ban"></i> Blacklists</h3>
        @if($checks['blacklist_status'] === 'clean')
            <p style="color: var(--success); font-size: 0.9rem;"><i class="fas fa-check-circle"></i> Not listed on any checked RBL.</p>
        @else
            <p style="color: var(--danger); font-size: 0.9rem; margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Listed on {{ count($checks['blacklist']['listed']) }} blocklist(s):</p>
            <ul style="margin: 0; padding-left: 18px; color: var(--text-primary); font-size: 0.85rem;">
                @foreach($checks['blacklist']['listed'] as $rbl)
                    <li>{{ $rbl }}</li>
                @endforeach
            </ul>
        @endif
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 12px;">Checked: {{ implode(', ', $checks['rbls_checked']) }}</p>
    </div>

    <div class="card" style="padding: 20px;">
        <h3 style="font-size: 1rem; margin: 0 0 14px; color: var(--text-primary);"><i class="fas fa-server"></i> Server Health</h3>
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
            <span style="color: var(--text-secondary); font-size: 0.85rem;">{{ $checks['mail_host'] }}:587</span>
            <span class="badge {{ $checks['mail_server_up'] ? 'badge-success' : 'badge-danger' }}">{{ $checks['mail_server_up'] ? 'Reachable' : 'Unreachable' }}</span>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0;">
            <span style="color: var(--text-secondary); font-size: 0.85rem;">TLS cert expiry</span>
            <span style="font-size: 0.85rem; color: var(--text-primary);">{{ $checks['cert_expiry_days'] !== null ? $checks['cert_expiry_days'] . ' days' : 'Could not read' }}</span>
        </div>
    </div>

    <div class="card" style="padding: 20px;">
        <h3 style="font-size: 1rem; margin: 0 0 14px; color: var(--text-primary);"><i class="fas fa-lightbulb"></i> Recommendations</h3>
        <ul style="margin: 0; padding-left: 18px; color: var(--text-secondary); font-size: 0.85rem;">
            @foreach($recommendations as $rec)
                <li style="margin-bottom: 8px;">{{ $rec }}</li>
            @endforeach
        </ul>
    </div>
</div>

@if(count($recentLog) > 0)
<div class="card" style="padding: 20px; margin-bottom: 24px;">
    <h3 style="font-size: 1rem; margin: 0 0 14px; color: var(--text-primary);"><i class="fas fa-terminal"></i> Recent Mail Log Alerts</h3>
    <div style="background: var(--bg-tertiary); border-radius: 10px; padding: 14px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.8rem;">
        @foreach($recentLog as $line)
            <div style="padding: 4px 0; border-bottom: 1px solid var(--border-color); color: var(--text-primary);">{{ $line }}</div>
        @endforeach
    </div>
</div>
@endif

@if($recentBounces->count())
<div class="card">
    <div class="table-header"><h3 class="table-title">Recent Announcement Bounces / Errors</h3></div>
    <div class="card-body"><div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Announcement</th>
                <th>Email</th>
                <th>Product</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentBounces as $log)
            <tr>
                <td>{{ $log->created_at?->diffForHumans() }}</td>
                <td>#{{ $log->announcement_id }}</td>
                <td>{{ $log->email ?? '—' }}</td>
                <td>{{ $log->product ?? '—' }}</td>
                <td>{{ $log->message }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
