@extends('layouts.admin')

@section('title', 'Agreements')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Agreements</h1>
        <p class="page-subtitle">Manage all client agreements</p>
    </div>
    <a href="{{ route('admin.agreements.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Agreement
    </a>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Agreements</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Agreement #</th>
                <th>Client</th>
                <th>Project</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agreements as $agreement)
            <tr>
                <td style="font-weight: 600;">{{ $agreement->agreement_number }}</td>
                <td>{{ $agreement->client->name ?? $agreement->client_name }}</td>
                <td>{{ $agreement->project_name }}</td>
                <td style="font-weight: 600; color: #00b7ff;">{{ $agreement->formatted_total }}</td>
                <td>
                    <span class="badge badge-{{ $agreement->status === 'signed' ? 'success' : ($agreement->status === 'sent' ? 'info' : ($agreement->status === 'cancelled' ? 'danger' : 'warning')) }}">
                        {{ $agreement->status_label }}
                    </span>
                </td>
                <td>{{ $agreement->created_at->format('M d, Y') }}</td>
                <td>
                    <a href="{{ route('admin.agreements.show', $agreement) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-file-contract" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No agreements found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($agreements->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $agreements->links() }}
    </div>
    @endif
</div>
@endsection
