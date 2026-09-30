@extends('layouts.admin')

@section('title', 'SSO Providers')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">SSO Providers</h1>
        <p class="page-subtitle">Manage single sign-on identity providers</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.sso-providers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i>Add Provider
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-shield-alt"></i>All Providers</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Slug</th>
                        <th>Active</th>
                        <th>Auto Provision</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                    <tr>
                        <td style="font-weight: 600;">{{ $provider->name }}</td>
                        <td>{{ strtoupper($provider->type) }}</td>
                        <td>{{ $provider->slug }}</td>
                        <td>
                            @if($provider->is_active)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-slate">No</span>
                            @endif
                        </td>
                        <td>
                            @if($provider->auto_provision)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-slate">No</span>
                            @endif
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('sso.metadata', $provider->slug) }}" target="_blank" class="btn btn-info btn-sm">Metadata</a>
                            <a href="{{ route('admin.sso-providers.edit', $provider) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="{{ route('admin.sso-providers.destroy', $provider) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete provider?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="empty-state"><i class="fas fa-shield-alt"></i><div>No SSO providers found</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($providers->hasPages())
    <div class="card-footer">
        {{ $providers->links() }}
    </div>
    @endif
</div>
@endsection
