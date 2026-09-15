@extends('layouts.admin')

@section('title', 'Edit VPS Plan: ' . $vpsPlan->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Plan: {{ $vpsPlan->name }}</h1>
        <p class="page-subtitle">Update VPS plan configuration</p>
    </div>
    <div>
        <a href="{{ route('admin.vps-plans.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back
        </a>
    </div>
</div>

@if(session('error'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #ef4444;">
    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>{{ session('error') }}
</div>
@endif

<form action="{{ route('admin.vps-plans.update', $vpsPlan) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
        
        {{-- Basic Info --}}
        <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
            <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
                <i class="fas fa-info-circle" style="margin-right: 8px; color: #00b7ff;"></i>Basic Information
            </h4>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Plan Name <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $vpsPlan->name) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Category <span style="color: #ef4444;">*</span>
                </label>
                <select name="category" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                    @foreach($categories as $key => $cat)
                    <option value="{{ $key }}" {{ old('category', $vpsPlan->category) == $key ? 'selected' : '' }}>
                        {{ $cat['name'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Display Name <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="display_name" value="{{ old('display_name', $vpsPlan->display_name) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Description
                </label>
                <textarea name="description" rows="3"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem; resize: vertical;">{{ old('description', $vpsPlan->description) }}</textarea>
            </div>
        </div>

        {{-- Resources --}}
        <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
            <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
                <i class="fas fa-server" style="margin-right: 8px; color: #22c55e;"></i>Resources
            </h4>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                        vCores <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="cpu_cores" value="{{ old('cpu_cores', $vpsPlan->cpu_cores) }}" required min="1"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                        RAM (GB) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="memory_gb" value="{{ old('memory_gb', $vpsPlan->memory_gb) }}" required min="1"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                        Disk (GB) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="disk_gb" value="{{ old('disk_gb', $vpsPlan->disk_gb) }}" required min="10"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                        Disk Type <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="disk_type" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                        <option value="SSD" {{ old('disk_type', $vpsPlan->disk_type) == 'SSD' ? 'selected' : '' }}>SSD</option>
                        <option value="NVMe" {{ old('disk_type', $vpsPlan->disk_type) == 'NVMe' ? 'selected' : '' }}>NVMe (Faster)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Bandwidth <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="bandwidth" value="{{ old('bandwidth', $vpsPlan->bandwidth) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            </div>

            <div style="display: flex; gap: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="unlimited_traffic" value="1" {{ old('unlimited_traffic', $vpsPlan->unlimited_traffic) ? 'checked' : '' }}
                        style="width: 18px; height: 18px; accent-color: #22c55e;">
                    <span style="color: #8b9bb4; font-size: 0.9rem;">Unlimited Traffic</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="daily_backup" value="1" {{ old('daily_backup', $vpsPlan->daily_backup) ? 'checked' : '' }}
                        style="width: 18px; height: 18px; accent-color: #22c55e;">
                    <span style="color: #8b9bb4; font-size: 0.9rem;">Daily Backup</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Pricing --}}
    <div style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
            <i class="fas fa-rupee-sign" style="margin-right: 8px; color: #eab308;"></i>Pricing (INR)
        </h4>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Cost Price (Cloud Price) <span style="color: #ef4444;">*</span>
                </label>
                <input type="number" name="cost_price" value="{{ old('cost_price', $vpsPlan->cost_price) }}" required step="0.01" min="0"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                <small style="color: #6b7280; font-size: 0.75rem;">What you pay to Cloud</small>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Selling Price (2.5x) <span style="color: #ef4444;">*</span>
                </label>
                <input type="number" name="price_monthly" value="{{ old('price_monthly', $vpsPlan->price_monthly) }}" required step="0.01" min="0"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                <small style="color: #6b7280; font-size: 0.75rem;">Customer pays this (2.5 × cost price)</small>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Setup Fee
                </label>
                <input type="number" name="setup_fee" value="{{ old('setup_fee', $vpsPlan->setup_fee) }}" step="0.01" min="0"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                <small style="color: #6b7280; font-size: 0.75rem;">One-time fee (usually 0)</small>
            </div>
        </div>

        <div style="margin-top: 16px;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                <i class="fas fa-video" style="margin-right: 6px; color: #8b5cf6;"></i>Streaming Addon Price (USD)
            </label>
            <input type="number" name="streaming_addon_price" value="{{ old('streaming_addon_price', $vpsPlan->streaming_addon_price) }}" step="0.01" min="0" placeholder="e.g., 49.99"
                style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            <small style="color: #6b7280; font-size: 0.75rem;">Price for streaming addon on this VPS tier (0 = disabled)</small>
        </div>
    </div>

    {{-- Status & Settings --}}
    <div style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
            <i class="fas fa-cog" style="margin-right: 8px; color: #8b5cf6;"></i>Status & Settings
        </h4>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
            <div>
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $vpsPlan->is_active) ? 'checked' : '' }}
                        style="width: 20px; height: 20px; accent-color: #22c55e;">
                    <span style="color: #fff; font-size: 0.95rem;">Active</span>
                </label>
                <small style="display: block; margin-top: 6px; color: #6b7280; font-size: 0.75rem;">Show on website</small>
            </div>

            <div>
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" name="is_sold_out" value="1" {{ old('is_sold_out', $vpsPlan->is_sold_out) ? 'checked' : '' }}
                        style="width: 20px; height: 20px; accent-color: #ef4444;">
                    <span style="color: #fff; font-size: 0.95rem;">Sold Out</span>
                </label>
                <small style="display: block; margin-top: 6px; color: #6b7280; font-size: 0.75rem;">Mark as unavailable</small>
            </div>

            <div>
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" name="is_recommended" value="1" {{ old('is_recommended', $vpsPlan->is_recommended) ? 'checked' : '' }}
                        style="width: 20px; height: 20px; accent-color: #00b7ff;">
                    <span style="color: #fff; font-size: 0.95rem;">Recommended</span>
                </label>
                <small style="display: block; margin-top: 6px; color: #6b7280; font-size: 0.75rem;">Show "Recommended" badge</small>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Sort Order
                </label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $vpsPlan->sort_order) }}" min="0"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; color: var(--text-primary); font-size: 0.95rem;">
            </div>
        </div>

        <div style="margin-top: 20px;">
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                <input type="checkbox" name="installation_free" value="1" {{ old('installation_free', $vpsPlan->installation_free) ? 'checked' : '' }}
                    style="width: 20px; height: 20px; accent-color: #22c55e;">
                <span style="color: #fff; font-size: 0.95rem;">Free Installation</span>
                <small style="color: #6b7280; font-size: 0.75rem;">(Show "Installation fees: Free")</small>
            </label>
        </div>
    </div>

    {{-- Cloud Mapping --}}
    <div style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
            <i class="fas fa-cloud" style="margin-right: 8px; color: #00b7ff;"></i>Cloud Mapping
        </h4>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Cloud Plan Code
                </label>
                <input type="text" name="ovh_plan_code" value="{{ old('ovh_plan_code', $vpsPlan->ovh_plan_code) }}" placeholder="e.g., vps-2024-1-2-40"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                <small style="color: #6b7280; font-size: 0.75rem;">Leave empty to use local Proxmox provisioning.</small>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Cloud Region / Datacenter
                </label>
                <input type="text" name="ovh_region" value="{{ old('ovh_region', $vpsPlan->ovh_config['region'] ?? '') }}" placeholder="e.g., gra / sbg / rbx"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            </div>
        </div>
    </div>

    {{-- Submit --}}
    <div style="margin-top: 24px; display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 16px;">
            <i class="fas fa-save" style="margin-right: 8px;"></i>Update VPS Plan
        </button>
        <a href="{{ route('admin.vps-plans.index') }}" class="btn btn-secondary" style="padding: 16px 32px;">
            Cancel
        </a>
    </div>
</form>
@endsection
