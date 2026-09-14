@extends('layouts.client')

@section('title', 'Migrate to BelieVoo - Smart Migration Wizard')

@section('content')
<style>
    .migration-wizard {
        max-width: 800px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    .wizard-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .wizard-title {
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
        margin-bottom: 10px;
    }
    .wizard-subtitle {
        color: #8b9bb4;
        font-size: 1rem;
    }
    .wizard-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
    }
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
    }
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 50px;
        right: 50px;
        height: 4px;
        background: rgba(255,255,255,0.1);
        border-radius: 2px;
    }
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
    }
    .step-number {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #8b9bb4;
        margin-bottom: 8px;
        transition: all 0.3s;
    }
    .step.active .step-number {
        background: linear-gradient(135deg, #00b7ff, #0066cc);
        color: #fff;
        box-shadow: 0 0 20px rgba(0, 183, 255, 0.4);
    }
    .step.completed .step-number {
        background: #22c55e;
        color: #fff;
    }
    .step-label {
        font-size: 0.75rem;
        color: #8b9bb4;
        text-transform: uppercase;
        font-weight: 600;
    }
    .step.active .step-label {
        color: #00b7ff;
    }
    .form-group {
        margin-bottom: 24px;
    }
    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #8b9bb4;
        margin-bottom: 10px;
    }
    .form-input {
        width: 100%;
        padding: 16px 20px;
        background: rgba(0,0,0,0.2);
        border: 2px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: #fff;
        font-size: 1rem;
        transition: all 0.2s;
    }
    .form-input:focus {
        outline: none;
        border-color: #00b7ff;
        box-shadow: 0 0 0 4px rgba(0, 183, 255, 0.1);
    }
    .form-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 16px;
    }
    .vm-select-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 16px;
    }
    .vm-card {
        padding: 20px;
        background: rgba(255,255,255,0.03);
        border: 2px solid rgba(255,255,255,0.06);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .vm-card:hover {
        border-color: rgba(0, 183, 255, 0.4);
    }
    .vm-card.selected {
        border-color: #00b7ff;
        background: rgba(0, 183, 255, 0.1);
    }
    .vm-name {
        font-weight: 700;
        color: #fff;
        margin-bottom: 6px;
    }
    .vm-specs {
        font-size: 0.8rem;
        color: #8b9bb4;
    }
    .vm-status {
        display: inline-block;
        padding: 4px 10px;
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-top: 10px;
    }
    .btn-wizard {
        width: 100%;
        padding: 18px;
        background: linear-gradient(90deg, #00b7ff, #0066cc);
        border: none;
        border-radius: 12px;
        color: #fff;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 10px;
    }
    .btn-wizard:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 183, 255, 0.3);
    }
    .btn-wizard:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Progress Overlay */
    .progress-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.95);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .progress-overlay.active {
        display: flex;
    }
    .progress-container {
        width: 100%;
        max-width: 500px;
        padding: 40px;
        text-align: center;
    }
    .progress-icon {
        font-size: 4rem;
        color: #00b7ff;
        margin-bottom: 30px;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .progress-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px;
    }
    .progress-message {
        color: #8b9bb4;
        margin-bottom: 30px;
    }
    .progress-bar-container {
        width: 100%;
        height: 12px;
        background: rgba(255,255,255,0.1);
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #00b7ff, #0066cc);
        border-radius: 6px;
        transition: width 0.5s ease;
        width: 0%;
    }
    .progress-percent {
        font-size: 2rem;
        font-weight: 800;
        color: #00b7ff;
    }
    .progress-details {
        margin-top: 30px;
        padding: 20px;
        background: rgba(255,255,255,0.03);
        border-radius: 12px;
        text-align: left;
    }
    .progress-log {
        font-family: monospace;
        font-size: 0.8rem;
        color: #8b9bb4;
        line-height: 1.6;
    }
    
    /* Security Badge */
    .security-badge {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: 12px;
        margin-bottom: 30px;
    }
    .security-icon {
        font-size: 1.5rem;
        color: #22c55e;
    }
    .security-text {
        font-size: 0.85rem;
        color: #8b9bb4;
    }
    .security-text strong {
        color: #22c55e;
    }
</style>

<div class="migration-wizard">
    <div class="wizard-header">
        <div class="wizard-title">
            <i class="fas fa-magic" style="color: #00b7ff; margin-right: 12px;"></i>
            Migrate to BelieVoo
        </div>
        <div class="wizard-subtitle">
            Transfer your websites and databases from any server in minutes
        </div>
    </div>
    
    <div class="wizard-card">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" id="step1-indicator">
                <div class="step-number">1</div>
                <div class="step-label">Connect</div>
            </div>
            <div class="step" id="step2-indicator">
                <div class="step-number">2</div>
                <div class="step-label">Select VPS</div>
            </div>
            <div class="step" id="step3-indicator">
                <div class="step-number">3</div>
                <div class="step-label">Migrate</div>
            </div>
        </div>
        
        <!-- Security Badge -->
        <div class="security-badge">
            <div class="security-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="security-text">
                <strong>AES-256 Encrypted</strong> • Your credentials are encrypted and never stored in plain text
            </div>
        </div>
        
        <!-- Step 1: Server Credentials -->
        <div id="step1" class="wizard-step">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-server" style="margin-right: 6px;"></i>
                    Source Server IP Address
                </label>
                <input type="text" class="form-input" id="sourceIp" placeholder="123.45.67.89" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">SSH Username</label>
                    <input type="text" class="form-input" id="sourceUser" value="root" required>
                </div>
                <div class="form-group">
                    <label class="form-label">SSH Port</label>
                    <input type="number" class="form-input" id="sourcePort" value="22" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-key" style="margin-right: 6px;"></i>
                    SSH Password
                </label>
                <input type="password" class="form-input" id="sourcePassword" placeholder="Your server password" required>
                <small style="color: #6b7280; font-size: 0.75rem; margin-top: 6px; display: block;">
                    <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                    We need root access to migrate /var/www and MySQL databases
                </small>
            </div>
            
            <button type="button" class="btn-wizard" onclick="goToStep2()">
                <i class="fas fa-arrow-right" style="margin-right: 8px;"></i>
                Continue to VPS Selection
            </button>
        </div>
        
        <!-- Step 2: Select VPS -->
        <div id="step2" class="wizard-step" style="display: none;">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-hdd" style="margin-right: 6px;"></i>
                    Select Your BelieVoo VPS (Destination)
                </label>
                
                @if($userVms->count() > 0)
                <div class="vm-select-grid">
                    @foreach($userVms as $vm)
                    <div class="vm-card" data-vm-id="{{ $vm->id }}" onclick="selectVm({{ $vm->id }})">
                        <div class="vm-name">{{ $vm->name }}</div>
                        <div class="vm-specs">
                            {{ $vm->cpu_cores }} CPU • {{ $vm->memory_mb / 1024 }}GB RAM • {{ $vm->disk_gb }}GB SSD
                        </div>
                        <div class="vm-status">{{ $vm->ip_address }}</div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="text-align: center; padding: 40px; color: #8b9bb4;">
                    <i class="fas fa-server" style="font-size: 3rem; margin-bottom: 16px; color: #6b7280;"></i>
                    <p>No running VPS found. Please create a VPS first.</p>
                    <a href="{{ route('client.servers') }}" class="btn-wizard" style="display: inline-block; margin-top: 20px; text-decoration: none;">
                        Create VPS
                    </a>
                </div>
                @endif
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="button" class="btn-wizard" style="flex: 1; background: rgba(255,255,255,0.1);" onclick="goToStep1()">
                    <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
                    Back
                </button>
                <button type="button" class="btn-wizard" style="flex: 2;" onclick="startMigration()" id="startBtn" disabled>
                    <i class="fas fa-rocket" style="margin-right: 8px;"></i>
                    Start Migration
                </button>
            </div>
        </div>
    </div>
    
    <!-- Info Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; color: #00b7ff; margin-bottom: 10px;">
                <i class="fas fa-bolt"></i>
            </div>
            <div style="font-weight: 600; color: #fff; margin-bottom: 6px;">Fast Transfer</div>
            <div style="font-size: 0.8rem; color: #8b9bb4;">Rsync for files, mysqldump for databases</div>
        </div>
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; color: #22c55e; margin-bottom: 10px;">
                <i class="fas fa-lock"></i>
            </div>
            <div style="font-weight: 600; color: #fff; margin-bottom: 6px;">Secure</div>
            <div style="font-size: 0.8rem; color: #8b9bb4;">AES-256 encryption for all credentials</div>
        </div>
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; color: #8b5cf6; margin-bottom: 10px;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div style="font-weight: 600; color: #fff; margin-bottom: 6px;">Live Progress</div>
            <div style="font-size: 0.8rem; color: #8b9bb4;">Real-time status with 0-100% progress</div>
        </div>
    </div>
</div>

<!-- Progress Overlay -->
<div class="progress-overlay" id="progressOverlay">
    <div class="progress-container">
        <div class="progress-icon">
            <i class="fas fa-sync-alt fa-spin"></i>
        </div>
        <div class="progress-title" id="progressTitle">Starting Migration...</div>
        <div class="progress-message" id="progressMessage">Connecting to your source server</div>
        
        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        <div class="progress-percent" id="progressPercent">0%</div>
        
        <div class="progress-details">
            <div class="progress-log" id="progressLog">
                <div>> Initializing migration...</div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedVmId = null;
let migrationId = null;
let progressInterval = null;

function goToStep1() {
    document.getElementById('step1').style.display = 'block';
    document.getElementById('step2').style.display = 'none';
    updateStepIndicator(1);
}

function goToStep2() {
    const ip = document.getElementById('sourceIp').value;
    const password = document.getElementById('sourcePassword').value;
    
    if (!ip || !password) {
        alert('Please enter both server IP and password');
        return;
    }
    
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    updateStepIndicator(2);
}

function updateStepIndicator(step) {
    document.querySelectorAll('.step').forEach((el, index) => {
        el.classList.remove('active', 'completed');
        if (index < step - 1) el.classList.add('completed');
        if (index === step - 1) el.classList.add('active');
    });
}

function selectVm(vmId) {
    document.querySelectorAll('.vm-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-vm-id="${vmId}"]`).classList.add('selected');
    selectedVmId = vmId;
    document.getElementById('startBtn').disabled = false;
}

function startMigration() {
    if (!selectedVmId) {
        alert('Please select a VPS');
        return;
    }
    
    const data = {
        source_ip: document.getElementById('sourceIp').value,
        source_ssh_port: document.getElementById('sourcePort').value,
        source_password: document.getElementById('sourcePassword').value,
        source_root_user: document.getElementById('sourceUser').value,
        proxmox_vm_id: selectedVmId,
    };
    
    // Show progress overlay
    document.getElementById('progressOverlay').classList.add('active');
    updateStepIndicator(3);
    
    // Send request
    fetch('{{ route("client.migration.start") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            migrationId = data.migration_id;
            startProgressPolling();
        } else {
            alert('Failed to start migration: ' + data.message);
            document.getElementById('progressOverlay').classList.remove('active');
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        document.getElementById('progressOverlay').classList.remove('active');
    });
}

function startProgressPolling() {
    progressInterval = setInterval(() => {
        if (!migrationId) return;
        
        fetch(`/client/migrations/${migrationId}/status`)
            .then(response => response.json())
            .then(data => {
                updateProgress(data);
                
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(progressInterval);
                    if (data.status === 'completed') {
                        setTimeout(() => {
                            window.location.href = `/client/migrations/${migrationId}`;
                        }, 2000);
                    }
                }
            });
    }, 2000);
}

function updateProgress(data) {
    document.getElementById('progressBar').style.width = data.progress + '%';
    document.getElementById('progressPercent').textContent = data.progress + '%';
    document.getElementById('progressTitle').textContent = getStatusTitle(data.status);
    document.getElementById('progressMessage').textContent = data.message;
    
    // Add to log
    const log = document.getElementById('progressLog');
    const time = new Date().toLocaleTimeString();
    log.innerHTML += `<div>> [${time}] ${data.message}</div>`;
    log.scrollTop = log.scrollHeight;
}

function getStatusTitle(status) {
    const titles = {
        'pending': 'Migration Queued...',
        'connecting': 'Connecting to Server...',
        'scanning': 'Scanning Source Server...',
        'migrating_files': 'Transferring Files...',
        'migrating_databases': 'Migrating Databases...',
        'verifying': 'Verifying Migration...',
        'completed': 'Migration Complete!',
        'failed': 'Migration Failed'
    };
    return titles[status] || 'Processing...';
}
</script>
@endsection
