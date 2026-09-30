@extends('layouts.admin')

@section('title', 'Stream Analytics')

@section('content')
<div wire:poll.5s id="stream-analytics-container">
    <div class="page-header">
        <h1 class="page-title">Stream Analytics</h1>
        <p class="page-subtitle">
            <span class="live-indicator">
                <span class="live-dot"></span> Live Data
            </span>
        </p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-signal"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value" id="active-streams">{{ $activeStreams }}</div>
                <div class="stat-label">Active Streams</div>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value" id="live-viewers">{{ number_format($totalViewers) }}</div>
                <div class="stat-label">Live Viewers</div>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value" id="bandwidth">{{ number_format($todayBandwidth, 2) }} MB</div>
                <div class="stat-label">Today's Bandwidth</div>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-database"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value" id="total-records">{{ $streams->total() }}</div>
                <div class="stat-label">Total Records</div>
            </div>
        </div>
    </div>

    <!-- Streams Table -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Stream History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th>App ID</th>
                            <th>Type</th>
                            <th>Viewers</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Started</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($streams as $stream)
                        <tr>
                            <td>
                                <a href="{{ route('admin.stream-analytics.show', $stream->id) }}" class="font-weight-bold">
                                    {{ $stream->channel_name }}
                                </a>
                            </td>
                            <td><code>{{ $stream->apiKey?->app_id ?? 'N/A' }}</code></td>
                            <td>
                                <span class="badge badge-{{ $stream->stream_type === 'broadcast' ? 'success' : 'info' }}">
                                    {{ ucfirst($stream->stream_type) }}
                                </span>
                            </td>
                            <td>{{ number_format($stream->viewer_count) }}</td>
                            <td>{{ gmdate('H:i:s', $stream->duration_seconds) }}</td>
                            <td>
                                @if($stream->ended_at === null)
                                    <span class="badge badge-success"><i class="fas fa-circle mr-1"></i>Live</span>
                                @else
                                    <span class="badge badge-secondary">Ended</span>
                                @endif
                            </td>
                            <td>{{ $stream->started_at?->format('M d, Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x mb-2 text-muted"></i>
                                <p class="text-muted">No streams found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $streams->links() }}
        </div>
    </div>
</div>

<style>
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #00ff80;
        font-weight: 600;
    }
    .live-dot {
        width: 8px;
        height: 8px;
        background: #00ff80;
        border-radius: 50%;
        animation: pulse 1s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    .stat-card {
        display: flex;
        align-items: center;
        padding: 20px;
        border-radius: 12px;
        color: white;
        gap: 16px;
    }
    .stat-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-card.success { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); }
    .stat-card.info { background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%); }
    .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-icon {
        width: 48px;
        height: 48px;
        background: rgba(255,255,255,0.2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .stat-info {
        flex: 1;
    }
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }
    .stat-label {
        font-size: 12px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .badge-success { background: #00b09b; }
    .badge-info { background: #3a7bd5; }
    .badge-secondary { background: #6c757d; }
</style>
@endsection
