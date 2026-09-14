@extends('layouts.admin')

@section('title', 'Stream Details - ' . ($stream->stream_id ?? '#' . $stream->id))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Stream #{{ $stream->id }}</h1>
        <a href="{{ route('admin.stream-analytics.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Analytics
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Stream Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><strong>Stream ID:</strong> {{ $stream->stream_id ?? '—' }}</div>
                        <div class="col-md-6 mb-3"><strong>Channel:</strong> {{ $stream->channel_name ?? '—' }}</div>
                        <div class="col-md-6 mb-3"><strong>Type:</strong> {{ $stream->stream_type ?? '—' }}</div>
                        <div class="col-md-6 mb-3"><strong>Resolution:</strong> {{ $stream->resolution ?? '—' }}</div>
                        <div class="col-md-6 mb-3"><strong>Codec:</strong> {{ $stream->codec ?? '—' }}</div>
                        <div class="col-md-6 mb-3"><strong>Country:</strong> {{ $stream->country_code ?? '—' }}</div>
                        <div class="col-md-6 mb-3"><strong>Region:</strong> {{ $stream->region ?? '—' }}</div>
                        <div class="col-md-6 mb-3"><strong>User Agent Hash:</strong> <code>{{ $stream->user_agent_hash ?? '—' }}</code></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Usage Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3"><strong>Viewers:</strong> {{ $stream->viewer_count ?? 0 }}</div>
                        <div class="col-md-4 mb-3"><strong>Bandwidth:</strong> {{ $stream->bandwidth_used_mb ?? 0 }} MB</div>
                        <div class="col-md-4 mb-3"><strong>Duration:</strong> {{ $stream->duration_minutes ?? 0 }} min</div>
                        <div class="col-md-4 mb-3"><strong>Avg Bitrate:</strong> {{ $stream->avg_bitrate_kbps ?? 0 }} kbps</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Timeline</h5>
                </div>
                <div class="card-body">
                    <p><strong>Started:</strong> {{ $stream->started_at?->format('M d, Y H:i') ?? '—' }}</p>
                    <p><strong>Ended:</strong> {{ $stream->ended_at?->format('M d, Y H:i') ?? 'Still streaming' }}</p>
                    <p><strong>End Reason:</strong> {{ $stream->end_reason ?? '—' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">API Key</h5>
                </div>
                <div class="card-body">
                    @if($stream->apiKey)
                        <p><strong>Key ID:</strong> {{ $stream->apiKey->id }}</p>
                        <p><strong>App ID:</strong> {{ $stream->apiKey->app_id ?? '—' }}</p>
                    @else
                        <p class="text-muted">No API key linked.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
