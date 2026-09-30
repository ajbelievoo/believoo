@extends('layouts.admin')

@section('title', 'Support Tickets')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Support Tickets</h1>
        <p class="page-subtitle">Manage all support requests</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-ticket-alt"></i>All Tickets</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
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
                        <td style="font-weight: 600; color: var(--accent);">{{ $ticket->ticket_id }}</td>
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
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="empty-state"><i class="fas fa-ticket-alt"></i><div>No tickets found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($tickets->hasPages())
    <div class="card-footer">
        {{ $tickets->links() }}
    </div>
    @endif
</div>
@endsection
