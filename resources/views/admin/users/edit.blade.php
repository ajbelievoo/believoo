@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit User</h1>
    <p class="page-subtitle">{{ $user->name }}</p>
</div>

<div class="data-table" style="max-width: 600px;">
    <div class="table-header"><h3 class="table-title">User Details</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Name *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }}>
                    <span style="font-size: 0.9rem; font-weight: 500;">Admin Access</span>
                </label>
            </div>

            {{-- Server Management Section --}}
            <div style="margin: 24px 0; padding: 20px; background: linear-gradient(135deg, rgba(0, 183, 255, 0.05) 0%, rgba(0, 183, 255, 0.02) 100%); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px;">
                <h4 style="font-size: 1rem; font-weight: 600; color: #00b7ff; margin-bottom: 16px;">
                    <i class="fas fa-server" style="margin-right: 8px;"></i>Server Management (WHMCS & Virtualizor)
                </h4>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #8b9bb4;">
                        WHMCS Client ID
                    </label>
                    <input type="text" name="whmcs_client_id" value="{{ old('whmcs_client_id', $user->whmcs_client_id) }}"
                        placeholder="e.g., 12345"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <small style="display: block; margin-top: 4px; color: #6b7280; font-size: 0.75rem;">
                        User's WHMCS billing client ID. Get from WHMCS Admin > Clients.
                    </small>
                </div>

                <div style="margin-bottom: 8px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #8b9bb4;">
                        VPS IDs (Comma Separated)
                    </label>
                    <input type="text" name="vps_ids"
                        value="{{ old('vps_ids', is_array($user->vps_ids) ? implode(', ', $user->vps_ids) : $user->vps_ids) }}"
                        placeholder="e.g., 101, 102, 103"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <small style="display: block; margin-top: 4px; color: #6b7280; font-size: 0.75rem;">
                        Virtualizor VPS IDs this user can manage. Separate with commas.
                    </small>
                </div>
            </div>

            {{-- Billing Section --}}
            <div style="margin: 24px 0; padding: 20px; background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(34, 197, 94, 0.02) 100%); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 12px;">
                <h4 style="font-size: 1rem; font-weight: 600; color: #22c55e; margin-bottom: 16px;">
                    <i class="fas fa-credit-card" style="margin-right: 8px;"></i>Billing & Subscription
                </h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #8b9bb4;">
                            Plan Name
                        </label>
                        <input type="text" name="current_plan_name" value="{{ old('current_plan_name', $user->current_plan_name) }}"
                            placeholder="e.g., VPS Basic Plan"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #8b9bb4;">
                            Plan Price (₹)
                        </label>
                        <input type="number" step="0.01" name="plan_price" value="{{ old('plan_price', $user->plan_price) }}"
                            placeholder="e.g., 999.00"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #8b9bb4;">
                            Next Due Date
                        </label>
                        <input type="date" name="next_due_date" value="{{ old('next_due_date', $user->next_due_date?->format('Y-m-d')) }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #8b9bb4;">
                            Last Payment Date
                        </label>
                        <input type="date" name="last_payment_date" value="{{ old('last_payment_date', $user->last_payment_date?->format('Y-m-d')) }}"
                            style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #8b9bb4;">
                        Billing Status
                    </label>
                    <select name="billing_status"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="active" {{ old('billing_status', $user->billing_status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ old('billing_status', $user->billing_status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="terminated" {{ old('billing_status', $user->billing_status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                    <small style="display: block; margin-top: 4px; color: #6b7280; font-size: 0.75rem;">
                        Setting to "Suspended" will trigger auto-suspension of VPS via cron job if overdue.
                    </small>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="data-table" style="max-width: 600px; margin-top: 20px;">
    <div class="table-header"><h3 class="table-title">Change Password</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.users.password', $user) }}" method="POST">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">New Password *</label>
                <input type="password" name="password" required minlength="8"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Confirm New Password *</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="background: #f59e0b; border-color: #f59e0b;">Change Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
