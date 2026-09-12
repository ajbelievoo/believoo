@extends('layouts.admin')

@section('title', 'Announcement Analytics')

@push('styles')
<style>
.chart-box { background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; }
.chart-box h3 { font-size: 0.9rem; color: var(--text-primary); margin: 0 0 12px; }
.chart-canvas { height: 260px; }
.ab-winner { font-weight: 700; color: var(--success); }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title">Announcement Analytics</h1>
    <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Back to Announcements</a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent);">{{ $total }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Total</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 1.8rem; font-weight: 800; color: var(--success);">{{ $sent }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Sent</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 1.8rem; font-weight: 800; color: var(--warning);">{{ $scheduled }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Scheduled</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 1.8rem; font-weight: 800; color: var(--info);">{{ $totalRecipients }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Recipients</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent);">{{ $opens }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Opens</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent);">{{ $clicks }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Clicks</div>
    </div>
    <div class="card" style="text-align:center; padding:18px;">
        <div style="font-size: 1.8rem; font-weight: 800; color: var(--info);">{{ $abTests }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">A/B Tests</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px; margin-bottom: 30px;">
    <div class="chart-box">
        <h3><i class="fas fa-chart-line"></i> Opens & Clicks Over Time</h3>
        <div class="chart-canvas"><canvas id="timeSeriesChart"></canvas></div>
    </div>
    <div class="chart-box">
        <h3><i class="fas fa-chart-bar"></i> Engagement by Product</h3>
        <div class="chart-canvas"><canvas id="productChart"></canvas></div>
    </div>
    <div class="chart-box">
        <h3><i class="fas fa-chart-pie"></i> Announcements by Type</h3>
        <div class="chart-canvas"><canvas id="typeChart"></canvas></div>
    </div>
    <div class="chart-box">
        <h3><i class="fas fa-trophy"></i> Top Campaigns by Open Rate</h3>
        <div class="chart-canvas"><canvas id="topCampaignsChart"></canvas></div>
    </div>
</div>

@if($activeAbTests->count())
<div class="data-table" style="margin-bottom: 30px;">
    <div class="table-header"><h3 class="table-title">A/B Tests</h3></div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Variant A (sent / opens / clicks)</th>
                <th>Variant B (sent / opens / clicks)</th>
                <th>Reserve</th>
                <th>Winner</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activeAbTests as $t)
            <tr>
                <td>{{ $t->title }}</td>
                <td><span class="badge badge-info">{{ ucfirst($t->ab_status) }}</span></td>
                <td>{{ $t->a_sent }} / {{ $t->a_opens }} / {{ $t->a_clicks }}</td>
                <td>{{ $t->b_sent }} / {{ $t->b_opens }} / {{ $t->b_clicks }}</td>
                <td>{{ $t->reserve_count }}</td>
                <td>{{ $t->ab_winner ? strtoupper($t->ab_winner) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="data-table">
    <div class="table-header"><h3 class="table-title">Recent Campaigns</h3></div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Sent</th>
                <th>Recipients</th>
                <th>Opens</th>
                <th>Clicks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recent as $a)
            <tr>
                <td>{{ $a->title }}</td>
                <td>{{ $a->sent_at?->diffForHumans() }}</td>
                <td>{{ $a->recipients_count }}</td>
                <td>{{ $a->opened_count }}</td>
                <td>{{ $a->clicked_count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const chartColors = {
    accent: '#00b7ff',
    success: '#10b981',
    warning: '#f59e0b',
    info: '#6366f1',
    text: '#94a3b8',
    grid: 'rgba(148, 163, 184, 0.1)',
};

fetch('{{ route('admin.announcements.analytics.data') }}')
    .then(r => r.json())
    .then(data => {
        renderTimeSeries(data.timeSeries);
        renderProductChart(data.byProduct);
        renderTypeChart(data.byType);
        renderTopCampaigns(data.topCampaigns);
    });

function commonOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: chartColors.text } } },
        scales: {
            x: { grid: { color: chartColors.grid }, ticks: { color: chartColors.text } },
            y: { grid: { color: chartColors.grid }, ticks: { color: chartColors.text } },
        },
    };
}

function renderTimeSeries(series) {
    new Chart(document.getElementById('timeSeriesChart'), {
        type: 'line',
        data: {
            labels: series.labels,
            datasets: [
                { label: 'Opens', data: series.opens, borderColor: chartColors.accent, backgroundColor: 'rgba(0,183,255,0.08)', fill: true, tension: 0.4 },
                { label: 'Clicks', data: series.clicks, borderColor: chartColors.success, backgroundColor: 'rgba(16,185,129,0.08)', fill: true, tension: 0.4 },
            ],
        },
        options: commonOptions(),
    });
}

function renderProductChart(rows) {
    new Chart(document.getElementById('productChart'), {
        type: 'bar',
        data: {
            labels: rows.map(r => r.product),
            datasets: [
                { label: 'Opens', data: rows.map(r => r.opens), backgroundColor: chartColors.accent },
                { label: 'Clicks', data: rows.map(r => r.clicks), backgroundColor: chartColors.success },
            ],
        },
        options: commonOptions(),
    });
}

function renderTypeChart(types) {
    const labels = Object.keys(types).map(t => t.charAt(0).toUpperCase() + t.slice(1));
    const values = Object.values(types);
    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [chartColors.accent, chartColors.warning, chartColors.info],
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: chartColors.text } } },
        },
    });
}

function renderTopCampaigns(campaigns) {
    new Chart(document.getElementById('topCampaignsChart'), {
        type: 'bar',
        data: {
            labels: campaigns.map(c => c.title.length > 20 ? c.title.slice(0, 20) + '…' : c.title),
            datasets: [{
                label: 'Open Rate %',
                data: campaigns.map(c => c.open_rate),
                backgroundColor: chartColors.accent,
            }],
        },
        options: commonOptions(),
    });
}
</script>
@endpush
