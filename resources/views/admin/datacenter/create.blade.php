@extends('layouts.admin')

@section('title', 'Add Datacenter Node - BelieVoo')

@section('content')
<style>
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    .form-section {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        padding: 24px;
    }
    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #8b9bb4;
        margin-bottom: 8px;
    }
    .form-input {
        width: 100%;
        padding: 12px 16px;
        background: rgba(0,0,0,0.3);
        border: 2px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #fff;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    .form-input:focus {
        outline: none;
        border-color: #00b7ff;
    }
    .form-select {
        width: 100%;
        padding: 12px 16px;
        background: rgba(0,0,0,0.3);
        border: 2px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #fff;
        font-size: 0.95rem;
    }
    .form-select option {
        background: #1e293b;
        color: #fff;
    }
    .status-selector {
        display: flex;
        gap: 12px;
    }
    .status-option {
        flex: 1;
        padding: 12px;
        border: 2px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .status-option:hover {
        border-color: rgba(255,255,255,0.2);
    }
    .status-option.active {
        border-color: #22c55e;
        background: rgba(34, 197, 94, 0.1);
    }
    .status-option.coming_soon {
        border-color: #8b9bb4;
        background: rgba(139, 155, 180, 0.1);
    }
    .status-option.maintenance {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.1);
    }
    .status-icon {
        font-size: 1.5rem;
        margin-bottom: 6px;
    }
    .status-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: rgba(0, 183, 255, 0.1);
        border: 1px solid rgba(0, 183, 255, 0.3);
        border-radius: 10px;
    }
    .checkbox-wrapper input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: #00b7ff;
    }
    .hint-text {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 6px;
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-plus-circle" style="margin-right: 12px; color: #00b7ff;"></i>
        Add New Datacenter Node
    </h1>
</div>

<form action="{{ route('admin.datacenter.store') }}" method="POST">
    @csrf
    
    <div class="form-grid">
        <!-- Basic Info -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                Basic Information
            </div>
            
            <div class="form-group">
                <label class="form-label">Node Name (Unique ID)</label>
                <input type="text" class="form-input" name="name" placeholder="e.g., sg1-proxmox" required>
                <div class="hint-text">Unique identifier, no spaces. Example: in1-proxmox, us1-proxmox</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Display Name</label>
                <input type="text" class="form-input" name="display_name" placeholder="e.g., Singapore Node 1" required>
                <div class="hint-text">What clients see on the dashboard</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Country</label>
                <select class="form-select" name="country_code" required>
                    @foreach($regions as $region => $countries)
                    <optgroup label="{{ $region }}">
                        @foreach($countries as $code)
                        <option value="{{ $code }}">
                            {{ $countryFlags[$code] ?? '' }} {{ $code }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" class="form-input" name="city" placeholder="e.g., Mumbai, Singapore" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Region</label>
                <select class="form-select" name="region">
                    <option value="">Select Region</option>
                    <option value="Asia">Asia</option>
                    <option value="Europe">Europe</option>
                    <option value="North America">North America</option>
                    <option value="South America">South America</option>
                    <option value="Oceania">Oceania</option>
                    <option value="Africa">Africa</option>
                </select>
            </div>
        </div>
        
        <!-- Connection Details -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-network-wired" style="margin-right: 8px;"></i>
                Connection Details
            </div>
            
            <div class="form-group">
                <label class="form-label">Hostname / IP Address</label>
                <input type="text" class="form-input" name="hostname" placeholder="e.g., 192.168.1.100 or proxmox.yourdomain.com" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">API Port</label>
                <input type="number" class="form-input" name="port" value="8006" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">API Token (Optional)</label>
                <input type="password" class="form-input" name="api_token" placeholder="PVEAPIToken=root@pam!token=xxxxx">
                <div class="hint-text">Encrypted with AES-256 before storage</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Root Password (Optional)</label>
                <input type="password" class="form-input" name="root_password" placeholder="For emergency SSH access">
                <div class="hint-text">Encrypted with AES-256 before storage</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Network Gateway</label>
                <input type="text" class="form-input" name="network_gateway" placeholder="e.g., 192.168.1.1">
            </div>
        </div>
        
        <!-- Capacity -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-server" style="margin-right: 8px;"></i>
                Hardware Capacity
            </div>
            
            <div class="form-group">
                <label class="form-label">Max VMs Capacity</label>
                <input type="number" class="form-input" name="max_vms" value="100" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Total CPU Cores</label>
                <input type="number" class="form-input" name="total_cpu_cores" placeholder="e.g., 32" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Total Memory (GB)</label>
                <input type="number" class="form-input" name="total_memory_gb" placeholder="e.g., 128" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Total Disk (GB)</label>
                <input type="number" class="form-input" name="total_disk_gb" placeholder="e.g., 2000" required>
            </div>
        </div>
        
        <!-- Status & Settings -->
        <div class="form-section">
            <div class="section-title">
                <i class="fas fa-cog" style="margin-right: 8px;"></i>
                Status & Settings
            </div>
            
            <div class="form-group">
                <label class="form-label">Initial Status</label>
                <div class="status-selector">
                    <label class="status-option active" style="cursor: pointer;">
                        <input type="radio" name="status" value="active" style="display: none;" checked>
                        <div class="status-icon">🟢</div>
                        <div class="status-label" style="color: #22c55e;">Active</div>
                    </label>
                    <label class="status-option coming_soon" style="cursor: pointer;">
                        <input type="radio" name="status" value="coming_soon" style="display: none;">
                        <div class="status-icon">⏳</div>
                        <div class="status-label" style="color: #8b9bb4;">Coming Soon</div>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" name="is_default" value="1" id="isDefault">
                    <div>
                        <div style="font-weight: 600; color: #fff;">Set as Default Node</div>
                        <div style="font-size: 0.8rem; color: #8b9bb4;">New VMs will use this node by default</div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Provider (Optional)</label>
                <input type="text" class="form-input" name="provider_name" placeholder="e.g., OVH, Hetzner, AWS">
            </div>
            
            <div class="form-group">
                <label class="form-label">Latency Hint (Optional)</label>
                <input type="text" class="form-input" name="latency_hint" placeholder="e.g., 15ms from Mumbai">
                <div class="hint-text">Shown to clients when selecting location</div>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
        <a href="{{ route('admin.datacenter.index') }}" class="btn btn-secondary" style="padding: 12px 24px;">
            Cancel
        </a>
        <button type="submit" class="btn btn-primary" style="padding: 12px 32px;">
            <i class="fas fa-save" style="margin-right: 8px;"></i>
            Create Node
        </button>
    </div>
</form>

<script>
// Status selector styling
const statusOptions = document.querySelectorAll('.status-option');
statusOptions.forEach(option => {
    option.addEventListener('click', function() {
        statusOptions.forEach(o => o.classList.remove('active', 'coming_soon'));
        this.classList.add(this.querySelector('input').value === 'coming_soon' ? 'coming_soon' : 'active');
    });
});
</script>
@endsection
