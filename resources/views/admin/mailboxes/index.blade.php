@extends('layouts.admin')

@section('title', 'Email Accounts')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="page-title">Email Accounts</h1>
        <p class="page-subtitle">Manage @believoo.com mailboxes — webmail at <a href="https://mail.believoo.com" target="_blank" style="color: var(--accent-primary);">mail.believoo.com</a></p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="https://mail.believoo.com" target="_blank" class="btn btn-secondary">
            <i class="fas fa-external-link-alt"></i> Webmail
        </a>
        <a href="{{ route('admin.mailboxes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Email
        </a>
    </div>
</div>

@if(session('success'))
<div style="background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.35); color: #16a34a; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

@if($errors->any())
<div style="background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.35); color: #dc2626; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">
    @foreach($errors->all() as $error)
        <div><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
    @endforeach
</div>
@endif

<div class="card">
    <div class="table-header">
        <h3 class="table-title">All Email Accounts</h3>
    </div>
    <div class="card-body"><div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Email Address</th>
                <th>Name</th>
                <th>Domain</th>
                <th>Quota</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mailboxes as $mailbox)
            <tr>
                <td style="font-weight: 600;">
                    <i class="fas fa-envelope" style="color: var(--accent-primary); margin-right: 6px;"></i>{{ $mailbox->username }}
                </td>
                <td>{{ $mailbox->full_name ?: '—' }}</td>
                <td><span class="badge badge-info">{{ $mailbox->domain }}</span></td>
                <td>{{ $mailbox->quota_formatted }}</td>
                <td>
                    <span class="badge badge-{{ $mailbox->active ? 'success' : 'danger' }}">
                        {{ $mailbox->active ? 'Active' : 'Disabled' }}
                    </span>
                </td>
                <td>{{ $mailbox->created ? \Carbon\Carbon::parse($mailbox->created)->format('M d, Y') : '—' }}</td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <a href="{{ route('admin.mailboxes.edit', $mailbox->username) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.mailboxes.destroy', $mailbox->username) }}" method="POST" onsubmit="return confirm('Delete {{ $mailbox->username }}? The account will stop working immediately.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; color: #ef4444;" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-envelope-open" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No email accounts yet — create your first mailbox like <b>help@believoo.com</b></p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($mailboxes->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $mailboxes->links() }}
    </div>
    @endif
</div>
@endsection
