@extends('layouts.admin')

@section('title', 'Test All Domain Providers - Believoo')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Test All Providers</h1>
        <a href="{{ route('admin.domain-providers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Providers
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if(!empty($results) && count($results) > 0)
                <table class="table table-hover">
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
                        @foreach($results as $result)
                        <tr>
                            <td>
                                <strong>{{ $result['name'] ?? ($result['provider'] ?? 'Unknown') }}</strong>
                            </td>
                            <td>
                                @if($result['success'] ?? false)
                                    <span class="badge bg-success">Connected</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                @endif
                            </td>
                            <td>{{ $result['message'] ?? '—' }}</td>
                            <td>{{ isset($result['latency_ms']) ? $result['latency_ms'] . ' ms' : '—' }}</td>
                            <td>{{ isset($result['checked_at']) ? \Carbon\Carbon::parse($result['checked_at'])->format('M d, Y H:i') : now()->format('M d, Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-plug" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <h5 class="text-muted">No provider test results available.</h5>
                    <p class="text-muted">Run a connection test from the providers list.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
