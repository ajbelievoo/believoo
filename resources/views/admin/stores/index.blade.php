@extends('layouts.admin')

@section('title', 'Stores')

@section('content')
<div class="page-header">
    <h1 class="page-title">Stores</h1>
    <p class="page-subtitle">Manage client stores</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="table-title">All Stores</h3>
        <div class="table-actions">
            <a href="{{ route('admin.stores.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Store
            </a>
        </div>
    </div>
    <div class="card-body"><div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>URL</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stores as $store)
            <tr>
                <td style="font-weight: 600;">{{ $store->name }}</td>
                <td>
                    <a href="{{ $store->url }}" target="_blank" style="color: #00b7ff; text-decoration: none; font-size: 0.85rem;">
                        {{ $store->url }} <i class="fas fa-external-link-alt" style="font-size: 0.7rem;"></i>
                    </a>
                </td>
                <td>
                    <span class="badge badge-{{ $store->status === 'active' ? 'success' : 'danger' }}">
                        {{ ucfirst($store->status) }}
                    </span>
                </td>
                <td>{{ $store->created_at->format('M d, Y') }}</td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('admin.stores.edit', $store) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.stores.destroy', $store) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.3);" onclick="return confirm('Delete this store?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-store" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No stores found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div></div>

    @if($stores->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $stores->links() }}
    </div>
    @endif
</div>
@endsection
