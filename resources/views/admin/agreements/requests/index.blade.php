@extends('layouts.admin')

@section('title', 'Project Requests')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Project Requests</h1>
        <p class="page-subtitle">View and manage client project requests</p>
    </div>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Requests</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Request #</th>
                <th>Client</th>
                <th>Project Name</th>
                <th>Budget</th>
                <th>Timeline</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $request)
            <tr>
                <td style="font-weight: 600;">{{ $request->request_number }}</td>
                <td>{{ $request->client->name ?? '—' }}</td>
                <td>{{ $request->project_name }}</td>
                <td>{{ $request->budget_range ?? '—' }}</td>
                <td>{{ $request->timeline_expectation ?? '—' }}</td>
                <td>
                    <span class="badge badge-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : ($request->status === 'under_review' ? 'info' : ($request->status === 'converted' ? 'primary' : 'warning'))) }}">
                        {{ $request->status_label }}
                    </span>
                </td>
                <td>{{ $request->submitted_at?->format('M d, Y') ?? $request->created_at->format('M d, Y') }}</td>
                <td>
                    <a href="{{ route('admin.agreement-requests.show', $request) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No project requests found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($requests->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $requests->links() }}
    </div>
    @endif
</div>
@endsection
