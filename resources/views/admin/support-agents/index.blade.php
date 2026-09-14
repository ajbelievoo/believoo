@extends('layouts.admin')

@section('title', 'Support Team')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1 class="page-title">Support Team</h1>
        <p class="page-subtitle">Live chat agents — they log in at <a href="{{ url('/agent/login') }}" target="_blank" style="color:var(--primary);">{{ url('/agent/login') }}</a></p>
    </div>
    <a href="{{ route('admin.support-agents.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Agent</a>
</div>

<div class="data-table">
    <div class="table-header"><h3 class="table-title">All Agents</h3></div>
    <table>
        <thead>
            <tr>
                <th>Agent</th>
                <th>Login Email</th>
                <th>Status</th>
                <th>Chats</th>
                <th>Rating</th>
                <th>Late Replies</th>
                <th>ZTP Flags</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agents as $agent)
            <tr>
                <td>
                    <div style="font-weight:600;">{{ $agent->display_name ?: $agent->name }}</div>
                    <div style="font-size:0.75rem;color:var(--text-muted);">{{ $agent->name }}</div>
                </td>
                <td style="font-size:0.85rem;">{{ $agent->email }}</td>
                <td>
                    @if($agent->is_online)
                        <span class="badge badge-success">Online</span>
                    @else
                        <span class="badge badge-secondary">Offline</span>
                    @endif
                    @if(!$agent->is_active)
                        <span class="badge badge-danger">Disabled</span>
                    @endif
                </td>
                <td>{{ $agent->active_chats }}/{{ $agent->max_chats }} <span style="color:var(--text-muted);font-size:0.75rem;">({{ $agent->total_chats }} total)</span></td>
                <td>{{ $agent->avg_rating ? number_format($agent->avg_rating,1).' ★' : '—' }}</td>
                <td>
                    @if($agent->late_replies > 0)
                        <span class="badge badge-warning">{{ $agent->late_replies }}</span>
                    @else
                        <span style="color:var(--text-muted);">0</span>
                    @endif
                </td>
                <td>
                    @if($agent->unpermitted_closes > 0)
                        <span class="badge badge-danger">{{ $agent->unpermitted_closes }}</span>
                    @else
                        <span style="color:var(--text-muted);">0</span>
                    @endif
                </td>
                <td style="white-space:nowrap;">
                    <form action="{{ route('admin.support-agents.toggle-online', $agent) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-secondary" style="padding:6px 10px;font-size:0.75rem;" title="{{ $agent->is_online ? 'Set offline' : 'Set online' }}">
                            <i class="fas {{ $agent->is_online ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        </button>
                    </form>
                    <a href="{{ route('admin.support-agents.performance', $agent) }}" class="btn btn-secondary" style="padding:6px 10px;font-size:0.75rem;" title="Performance"><i class="fas fa-chart-line"></i></a>
                    <a href="{{ route('admin.support-agents.edit', $agent) }}" class="btn btn-secondary" style="padding:6px 10px;font-size:0.75rem;" title="Edit"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('admin.support-agents.destroy', $agent) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this agent?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary" style="padding:6px 10px;font-size:0.75rem;color:#ef4444;" title="Delete"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">No agents yet. Click "Add Agent" to create your first support team member.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
