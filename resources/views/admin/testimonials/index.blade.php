@extends('layouts.admin')

@section('title', 'Testimonials')

@section('content')
<div class="page-header">
    <h1 class="page-title">Testimonials</h1>
    <p class="page-subtitle">
        Manage client feedback shown on the homepage
        @if($pendingCount > 0)
            <span class="badge badge-warning" style="margin-left: 8px;">{{ $pendingCount }} pending approval</span>
        @endif
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="table-title">All Testimonials</h3>
        <div class="table-actions">
            <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Testimonial
            </a>
        </div>
    </div>
    <div class="card-body"><div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Feedback</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($testimonials as $t)
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; flex-shrink: 0;">
                            {{ strtoupper(substr($t->name, 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight: 600;">{{ $t->name }}</div>
                            @if($t->title || $t->company)
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ collect([$t->title, $t->company])->filter()->implode(' · ') }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="max-width: 320px;">
                    <span style="font-size: 0.85rem; color: var(--text-secondary);">{{ Str::limit($t->content, 90) }}</span>
                </td>
                <td>
                    <span style="color: #f59e0b; font-size: 0.85rem;">
                        @for($i = 1; $i <= 5; $i++)<i class="fas fa-star" style="{{ $i <= $t->rating ? '' : 'opacity: 0.25;' }}"></i>@endfor
                    </span>
                </td>
                <td>
                    <span class="badge badge-{{ $t->is_visible ? 'success' : 'warning' }}">
                        {{ $t->is_visible ? 'Live' : 'Pending' }}
                    </span>
                </td>
                <td>{{ $t->created_at->format('M d, Y') }}</td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <form action="{{ route('admin.testimonials.toggle', $t) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; {{ $t->is_visible ? '' : 'background: rgba(34,197,94,0.1); color: #22c55e; border-color: rgba(34,197,94,0.3);' }}" title="{{ $t->is_visible ? 'Hide from site' : 'Approve & publish' }}">
                                <i class="fas {{ $t->is_visible ? 'fa-eye-slash' : 'fa-check' }}"></i>
                            </button>
                        </form>
                        <a href="{{ route('admin.testimonials.edit', $t) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.testimonials.destroy', $t) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.3);" onclick="return confirm('Delete this testimonial?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    No testimonials yet. Client feedback submitted from the homepage will appear here.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div></div>
    @if($testimonials->hasPages())
    <div style="padding: 16px;">{{ $testimonials->links() }}</div>
    @endif
</div>
@endsection
