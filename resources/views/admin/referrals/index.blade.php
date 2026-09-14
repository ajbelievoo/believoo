@extends('layouts.admin')
@section('title', 'Referrals & Loyalty')
@section('content')
<div class="page-header"><h1 class="page-title">Referral & Loyalty Program</h1></div>
<div class="data-table" style="margin-bottom:24px;padding:20px;text-align:center;max-width:300px;">
  <h2 style="font-size:2rem;font-weight:800;color:#f59e0b;">{{ $total_points }}</h2>
  <p>Total Loyalty Points in System</p>
</div>
<div class="data-table">
  <div class="table-header"><h3 class="table-title">Referral Codes</h3></div>
  <table>
    <thead><tr><th>User</th><th>Code</th><th>Uses</th><th>Reward</th><th>Status</th></tr></thead>
    <tbody>
      @forelse($referrals as $r)
      <tr>
        <td>{{ $r->user?->name ?? '—' }}</td>
        <td><code style="background:var(--bg-tertiary);padding:4px 8px;border-radius:4px;">{{ $r->code }}</code></td>
        <td>{{ $r->uses }}</td>
        <td>₹{{ $r->reward_amount }}</td>
        <td>{!! $r->is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' !!}</td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">No referral codes yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
