@extends('layouts.admin')

@section('title', 'Portfolio')

@section('content')
<div class="page-header">
    <h1 class="page-title">Portfolio</h1>
    <p class="page-subtitle">Manage portfolio items</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Portfolio Items</h3>
        <div class="table-actions">
            <a href="{{ route('admin.portfolio.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Item
            </a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Tech Stack</th>
                <th>Visible</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($portfolios as $portfolio)
            <tr>
                <td>
                    <div style="font-weight: 600;">{{ $portfolio->title }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $portfolio->slug }}</div>
                </td>
                <td>
                    @php
                        $techStack = is_array($portfolio->tech_stack) ? $portfolio->tech_stack : (is_string($portfolio->tech_stack) ? (json_decode($portfolio->tech_stack, true) ?? []) : []);
                    @endphp
                    @if(!empty($techStack))
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            @foreach(array_slice($techStack, 0, 3) as $tech)
                                @php
                                    $techName = '';
                                    $techIcon = null;
                                    if (is_array($tech)) {
                                        $rawName = $tech['name'] ?? null;
                                        $techName = is_scalar($rawName) ? (string) $rawName : '';
                                        $rawIcon = $tech['icon'] ?? null;
                                        $techIcon = is_string($rawIcon) ? $rawIcon : null;
                                    } elseif (is_scalar($tech)) {
                                        $techName = (string) $tech;
                                    }
                                @endphp
                                <span style="background: rgba(245,158,11,0.1); color: #d97706; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem;">
                                    @if($techIcon) <i class="{{ $techIcon }}" style="margin-right: 4px;"></i> @endif
                                    {{ $techName }}
                                </span>
                            @endforeach
                            @if(count($techStack) > 3)
                            <span style="color: var(--text-muted); font-size: 0.75rem;">+{{ count($techStack) - 3 }}</span>
                            @endif
                        </div>
                    @else
                        <span style="color: var(--text-muted);">—</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{ $portfolio->is_visible ? 'success' : 'danger' }}">
                        {{ $portfolio->is_visible ? 'Visible' : 'Hidden' }}
                    </span>
                </td>
                <td>{{ $portfolio->created_at->format('M d, Y') }}</td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('admin.portfolio.edit', $portfolio) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.portfolio.destroy', $portfolio) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.3);" onclick="return confirm('Delete this item?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No portfolio items found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($portfolios->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $portfolios->links() }}
    </div>
    @endif
</div>
@endsection
