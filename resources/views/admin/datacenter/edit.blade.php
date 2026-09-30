@extends('layouts.admin')

@section('title', 'Edit Node - ' . $node->display_name)

@section('content')
<style>
    .edit-container {
        max-width: 800px;
        margin: 0 auto;
    }
    .form-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 32px;
        margin-top: 24px;
    }
    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
    }
    .section-title i {
        margin-right: 12px;
        color: #00b7ff;
    }
    .form-group {
        margin-bottom: 24px;
    }
    .form-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #8b9bb4;
        margin-bottom: 8px;
    }
    .form-input {
        width: 100%;
        padding: 12px 16px;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #fff;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    .form-input:focus {
        outline: none;
        border-color: #00b7ff;
        box-shadow: 0 0 0 3px rgba(0, 183, 255, 0.1);
    }
    .form-select {
        width: 100%;
        padding: 12px 16px;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #fff;
        font-size: 0.95rem;
    }
    .form-select option {
        background: #1e293b;
        color: #fff;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .checkbox-wrapper input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: #00b7ff;
    }
    .checkbox-wrapper label {
        color: #fff;
        font-size: 0.95rem;
    }
    .btn-group {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .btn-save {
        padding: 12px 32px;
        background: linear-gradient(135deg, #00b7ff, #0066cc);
        border: none;
        border-radius: 8px;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 183, 255, 0.3);
    }
    .btn-cancel {
        padding: 12px 32px;
        background: rgba(255,255,255,0.1);
        border: none;
        border-radius: 8px;
        color: #fff;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        transition: all 0.2s;
    }
    .btn-cancel:hover {
        background: rgba(255,255,255,0.15);
    }
    .api-status {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
    }
    .api-status i {
        color: #22c55e;
        font-size: 1.5rem;
        margin-right: 16px;
    }
    .api-status-text {
        color: #fff;
        font-weight: 600;
    }
    .api-status-sub {
        color: #8b9bb4;
        font-size: 0.85rem;
        margin-top: 2px;
    }
</style>

<div class="edit-container">
    <div class="page-header" style="margin-bottom: 0;">
        <h1 class="page-title">
            <span style="font-size: 2rem; margin-right: 12px;">{{ $node->flag_emoji }}</span>
            Edit Node: {{ $node->display_name }}
        </h1>
        <div class="page-actions">
            <a href="{{ route('admin.datacenter.show', $node) }}" class="btn-cancel">
                <i class="fas fa-arrow-left" style="margin-right: 6px;"></i>
                Back to Details
            </a>
        </div>
    </div>

    <form action="{{ route('admin.datacenter.update', $node) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-card">
            <div class="section-title">
                <i class="fas fa-info-circle"></i>
                Basic Information
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Display Name</label>
                    <input type="text" name="display_name" class="form-input" value="{{ old('display_name', $node->display_name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Node ID</label>
                    <input type="text" class="form-input" value="{{ $node->name }}" disabled>
                    <small style="color: #6b7280; font-size: 0.8rem;">Node ID cannot be changed</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Hostname</label>
                    <input type="text" name="hostname" class="form-input" value="{{ old('hostname', $node->hostname) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Port</label>
                    <input type="number" name="port" class="form-input" value="{{ old('port', $node->port) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ $node->status === 'active' ? 'selected' : '' }}>🟢 Active</option>
                        <option value="coming_soon" {{ $node->status === 'coming_soon' ? 'selected' : '' }}>⏳ Coming Soon</option>
                        <option value="maintenance" {{ $node->status === 'maintenance' ? 'selected' : '' }}>🔧 Maintenance</option>
                        <option value="offline" {{ $node->status === 'offline' ? 'selected' : '' }}>🔴 Offline</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="checkbox-wrapper" style="margin-top: 32px;">
                        <input type="checkbox" name="is_default" id="is_default" value="1" {{ $node->is_default ? 'checked' : '' }}>
                        <label for="is_default">Set as Default Node</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="section-title">
                <i class="fas fa-server"></i>
                Hardware Resources
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Total CPU Cores</label>
                    <input type="number" name="total_cpu_cores" class="form-input" value="{{ old('total_cpu_cores', $node->total_cpu_cores) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Memory (GB)</label>
                    <input type="number" name="total_memory_gb" class="form-input" value="{{ old('total_memory_gb', round($node->total_memory_bytes / 1024 / 1024 / 1024)) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Total Disk (GB)</label>
                    <input type="number" name="total_disk_gb" class="form-input" value="{{ old('total_disk_gb', round($node->total_disk_bytes / 1024 / 1024 / 1024)) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max VMs</label>
                    <input type="number" name="max_vms" class="form-input" value="{{ old('max_vms', $node->max_vms) }}" required>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="section-title">
                <i class="fas fa-network-wired"></i>
                Network Configuration
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Gateway</label>
                    <input type="text" name="network_gateway" class="form-input" value="{{ old('network_gateway', $node->network_gateway) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Subnet</label>
                    <input type="text" name="network_subnet" class="form-input" value="{{ old('network_subnet', $node->network_subnet) }}">
                </div>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn-save">
                <i class="fas fa-save" style="margin-right: 6px;"></i>
                Save Changes
            </button>
            <a href="{{ route('admin.datacenter.show', $node) }}" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
@endsection
