@extends('layouts.admin')

@section('title', 'Leads')

@section('content')
<div class="page-header">
    <h1 class="page-title">Leads</h1>
    <p class="page-subtitle">Manage call requests and leads</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Leads</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Phone</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leads as $lead)
            <tr>
                <td style="font-weight: 600;">{{ $lead->phone_number }}</td>
                <td>
                    <span class="badge badge-{{ $lead->status === 'converted' ? 'success' : ($lead->status === 'lost' ? 'danger' : ($lead->status === 'contacted' || $lead->status === 'qualified' ? 'info' : 'warning')) }}">
                        {{ ucfirst($lead->status) }}
                    </span>
                </td>
                <td style="color: var(--text-muted); font-size: 0.85rem;">{{ Str::limit($lead->notes, 60) ?? '—' }}</td>
                <td>{{ $lead->created_at->format('M d, Y') }}</td>
                <td>
                    <a href="{{ route('admin.leads.show', $lead) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-user-plus" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No leads found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($leads->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $leads->links() }}
    </div>
    @endif
</div>
@endsection
