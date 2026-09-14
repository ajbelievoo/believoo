<x-layouts.believoo>
<div style="max-width:900px;margin:120px auto 60px;padding:24px;">
  <h1 style="font-size:2rem;font-weight:800;margin-bottom:12px;">Help Center & FAQ</h1>
  <form action="{{ route('faq') }}" method="GET" style="margin-bottom:32px;">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search help articles..." style="width:100%;padding:14px 18px;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:12px;color:var(--text-primary);font-size:1rem;">
  </form>
  @forelse($articles as $a)
  <div style="padding:18px 24px;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:12px;margin-bottom:12px;">
    <h3 style="font-weight:700;margin-bottom:6px;">{{ $a->title }}</h3>
    <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.6;">{{ $a->content }}</p>
    @if($a->category) <span class="badge badge-info" style="font-size:0.7rem;">{{ $a->category }}</span> @endif
  </div>
  @empty
  <p style="text-align:center;color:var(--text-muted);padding:40px;">No articles found. Try another keyword.</p>
  @endforelse
</div>
</x-layouts.believoo>
