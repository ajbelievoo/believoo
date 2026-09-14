@extends('layouts.admin')

@section('title', 'Ticket - ' . $ticket->ticket_id)

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $ticket->ticket_id }}</h1>
    <p class="page-subtitle">{{ $ticket->subject }}</p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Messages</h3></div>
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                @forelse($ticket->messages as $message)
                <div style="padding: 16px; border-radius: 12px; background: {{ $message->is_admin ? 'rgba(0,183,255,0.08)' : 'var(--bg-tertiary)' }}; border: 1px solid {{ $message->is_admin ? 'rgba(0,183,255,0.2)' : 'var(--border-color)' }};">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600; font-size: 0.85rem; color: {{ $message->is_admin ? '#00b7ff' : 'var(--text-primary)' }};">
                            {{ $message->is_admin ? 'Admin' : ($ticket->user->name ?? $ticket->name) }}
                        </span>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">{{ $message->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">{{ $message->message }}</p>
                </div>
                @empty
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">No messages yet</p>
                @endforelse
            </div>
        </div>

        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Reply</h3></div>
            <div style="padding: 24px;">
                <form action="{{ route('admin.tickets.reply', $ticket) }}" method="POST">
                    @csrf
                    <textarea name="message" rows="4" placeholder="Type your reply..." required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-family: inherit; font-size: 0.9rem; resize: vertical; margin-bottom: 12px;"></textarea>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Ticket Info</h3></div>
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">From</div>
                    <div style="font-weight: 600;">{{ $ticket->user->name ?? $ticket->name }}</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">{{ $ticket->user->email ?? $ticket->email }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Priority</div>
                    <span class="badge badge-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'info') }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Category</div>
                    <div>{{ ucfirst($ticket->category ?? '—') }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Created</div>
                    <div>{{ $ticket->created_at->format('M d, Y') }}</div>
                </div>
            </div>
        </div>

        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Update Status</h3></div>
            <div style="padding: 24px;">
                <form action="{{ route('admin.tickets.update-status', $ticket) }}" method="POST">
                    @csrf
                    <select name="status" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-bottom: 12px;">
                        @foreach(['open','in_progress','resolved','closed'] as $s)
                        <option value="{{ $s }}" {{ $ticket->status === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
