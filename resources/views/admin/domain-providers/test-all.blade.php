@extends('layouts.admin')

@section('title', 'Test All Domain Providers - Believoo')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Test All Providers</h1>
        <p class="page-subtitle">Connection test results for every configured domain provider</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.domain-providers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>Back to Providers
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-plug"></i>Test Results</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Latency</th>
                        <th>Checked At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $result)
                    <tr>
                        <td style="font-weight: 600;">
                            {{ $result['name'] ?? ($result['provider'] ?? 'Unknown') }}
                        </td>
                        <td>
                            @if($result['success'] ?? false)
                                <span class="badge badge-success">Connected</span>
                            @else
                                <span class="badge badge-danger">Failed</span>
                            @endif
                        </td>
                        <td>{{ $result['message'] ?? '—' }}</td>
                        <td>{{ isset($result['latency_ms']) ? $result['latency_ms'] . ' ms' : '—' }}</td>
                        <td>{{ isset($result['checked_at']) ? \Carbon\Carbon::parse($result['checked_at'])->format('M d, Y H:i') : now()->format('M d, Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-plug"></i>
                            <div>No provider test results available</div>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 6px;">Run a connection test from the providers list.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
