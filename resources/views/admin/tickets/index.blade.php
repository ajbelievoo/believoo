@extends('layouts.admin')

@section('title', 'Support Tickets')

@section('content')
<div class="page-header">
    <h1 class="page-title">Support Tickets</h1>
    <p class="page-subtitle">Manage all support requests</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Tickets</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Subject</th>
                <th>From</th>
                <th>Priority</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td style="font-weight: 600; color: #00b7ff;">{{ $ticket->ticket_id }}</td>
                <td>{{ $ticket->subject }}</td>
                <td>{{ $ticket->user->name ?? $ticket->name }}</td>
                <td>
                    <span class="badge badge-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'info') }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </td>
                <td>{{ ucfirst($ticket->category ?? '—') }}</td>
                <td>
                    <span class="badge badge-{{ $ticket->status === 'resolved' || $ticket->status === 'closed' ? 'success' : ($ticket->status === 'in_progress' ? 'info' : 'warning') }}">
                        {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                </td>
                <td>{{ $ticket->created_at->format('M d, Y') }}</td>
                <td>
                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-ticket-alt" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No tickets found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($tickets->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $tickets->links() }}
    </div>
    @endif
</div>
@endsection
