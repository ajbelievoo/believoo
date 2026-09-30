@extends('layouts.admin')

@section('title', 'Resellers')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Resellers</h1>
        <p class="page-subtitle">Manage white-label reseller partners</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.resellers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i>Add Reseller
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-store"></i>All Resellers</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Owner</th>
                        <th>Slug</th>
                        <th>Custom Domain</th>
                        <th>Margin %</th>
                        <th>Approved</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $reseller)
                    <tr>
                        <td style="font-weight: 600;">{{ $reseller->company_name }}</td>
                        <td>{{ $reseller->owner?->name }}</td>
                        <td>{{ $reseller->slug }}</td>
                        <td>{{ $reseller->custom_domain ?? '—' }}</td>
                        <td>{{ $reseller->default_margin_percent }}</td>
                        <td>
                            @if($reseller->approved_at)
                                <span class="badge badge-success">{{ $reseller->approved_at->format('Y-m-d') }}</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            @if(!$reseller->approved_at)
                            <form action="{{ route('admin.resellers.approve', $reseller) }}" method="POST" style="display: inline;" onsubmit="return confirm('Approve this reseller?')">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-success">Approve</button>
                            </form>
                            @endif
                            <a href="{{ route('admin.resellers.edit', $reseller) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="{{ route('admin.resellers.destroy', $reseller) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete reseller?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="empty-state"><i class="fas fa-store"></i><div>No resellers found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($resellers->hasPages())
    <div class="card-footer">
        {{ $resellers->links() }}
    </div>
    @endif
</div>
@endsection
