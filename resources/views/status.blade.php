<x-layouts.believoo>
<div style="max-width:900px;margin:120px auto 60px;padding:24px;">
  <h1 style="font-size:2rem;font-weight:800;margin-bottom:8px;">Believoo System Status</h1>
  <p style="color:var(--text-muted);margin-bottom:32px;">Real-time status of our infrastructure</p>
  @foreach($services as $s)
  <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:12px;margin-bottom:12px;">
    <div>
      <div style="font-weight:700;font-size:1.1rem;">{{ $s['name'] }}</div>
      <div style="font-size:0.8rem;color:var(--text-muted);">Uptime: {{ $s['uptime'] }}</div>
    </div>
    <div>
      <span class="badge badge-{{ $s['status'] == 'operational' ? 'success' : 'danger' }}" style="font-size:0.85rem;padding:6px 14px;">
        <i class="fas fa-{{ $s['status'] == 'operational' ? 'check' : 'exclamation' }}-circle mr-1"></i>{{ ucfirst($s['status']) }}
      </span>
    </div>
  </div>
  @endforeach
  <p style="text-align:center;color:var(--text-muted);margin-top:32px;font-size:0.85rem;">Last updated: {{ now()->format('d M Y H:i T') }}</p>
</div>
</x-layouts.believoo>
