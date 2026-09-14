@extends('layouts.admin')
@section('title', 'Security')
@section('content')
<div class="page-header"><h1 class="page-title">Security — IP Blocking</h1></div>
<div class="data-table" style="max-width:500px;margin-bottom:24px;">
  <div style="padding:24px;">
    <h3 style="margin-bottom:12px;">Block IP Address</h3>
    <form action="{{ route('admin.security.block') }}" method="POST">@csrf
      <div style="display:flex;gap:12px;">
        <input type="text" name="ip_address" placeholder="192.168.1.1" required style="flex:1;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);">
        <input type="text" name="reason" placeholder="Reason" style="flex:1;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);">
        <button type="submit" class="btn btn-primary">Block</button>
      </div>
    </form>
  </div>
</div>
<div class="data-table">
  <div class="table-header"><h3 class="table-title">Blocked IPs</h3></div>
  <table>
    <thead><tr><th>IP Address</th><th>Reason</th><th>Blocked At</th><th>Action</th></tr></thead>
    <tbody>
      @forelse($ips as $ip)
      <tr>
        <td><code>{{ $ip->ip_address }}</code></td>
        <td>{{ $ip->reason ?: '—' }}</td>
        <td>{{ $ip->created_at->format('d M Y H:i') }}</td>
        <td>
          <form action="{{ route('admin.security.unblock', $ip) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
            <button class="btn btn-secondary" style="padding:4px 10px;font-size:0.7rem;color:#ef4444;"><i class="fas fa-unlock"></i> Unblock</button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--text-muted);">No blocked IPs.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
