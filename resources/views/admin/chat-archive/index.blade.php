@extends('layouts.admin')
@section('title', 'Chat Archive')
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;"><h1 class="page-title">Chat Archive</h1><a href="{{ route('chat-archive.export') }}" class="btn btn-secondary"><i class="fas fa-download"></i> Export CSV</a></div>
<div style="display:flex;gap:12px;margin-bottom:20px;">
  <form action="{{ route('admin.chat-archive.index') }}" method="GET" style="display:flex;gap:12px;flex:1;">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search email, session, transcript..." style="flex:1;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);">
    <input type="date" name="date" value="{{ request('date') }}" style="padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);">
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
</div>
<div class="data-table">
  <table>
    <thead><tr><th>Client</th><th>Email</th><th>Tags</th><th>Rating</th><th>Date</th><th>Summary</th><th>Transcript</th></tr></thead>
    <tbody>
      @forelse($archives as $a)
      <tr>
        <td style="font-weight:700;">{{ $a->client_name ?: 'Guest' }}</td>
        <td>{{ $a->client_email ?: '—' }}</td>
        <td>{{ $a->tags ?: '—' }}</td>
        <td>{{ $a->rating ? $a->rating . '⭐' : '—' }}</td>
        <td style="font-size:0.8rem;">{{ $a->created_at->format('d M Y') }}</td>
        <td style="font-size:0.8rem;">{{ Str::limit($a->summary, 50) }}</td>
        <td style="font-size:0.75rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $a->transcript }}</td>
      </tr>
      @empty
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No archived chats. They are saved when chats close.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
