@extends('layouts.admin')

@section('title', 'Create VPS - BelieVoo Cloud')

@section('content')
<style>
    .hostinger-layout {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 24px;
        max-width: 1200px;
        margin: 0 auto;
    }
    @media (max-width: 1024px) {
        .hostinger-layout {
            grid-template-columns: 1fr;
        }
    }
    
    /* Section Cards */
    .section-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .section-header {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #8b9bb4;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Location Selector */
    .location-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    .location-card {
        padding: 16px;
        background: rgba(255,255,255,0.03);
        border: 2px solid rgba(255,255,255,0.06);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .location-card:hover {
        border-color: rgba(0, 183, 255, 0.4);
        background: rgba(0, 183, 255, 0.05);
    }
    .location-card.selected {
        border-color: #00b7ff;
        background: rgba(0, 183, 255, 0.1);
    }
    .location-card .flag {
        font-size: 2rem;
        margin-bottom: 8px;
    }
    .location-card .name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
    }
    .location-card .latency {
        font-size: 0.7rem;
        color: #22c55e;
        margin-top: 4px;
    }
    
    /* Image Selection Tabs */
    .image-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        padding: 4px;
        background: rgba(0,0,0,0.2);
        border-radius: 10px;
    }
    .image-tab {
        padding: 8px 16px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #8b9bb4;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .image-tab:hover {
        color: #fff;
    }
    .image-tab.active {
        color: #fff;
        background: rgba(0, 183, 255, 0.2);
    }
    
    /* Image Grid */
    .image-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }
    @media (max-width: 768px) {
        .image-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .image-card {
        padding: 16px 12px;
        background: rgba(255,255,255,0.03);
        border: 2px solid rgba(255,255,255,0.06);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .image-card:hover {
        border-color: rgba(0, 183, 255, 0.4);
        transform: translateY(-2px);
    }
    .image-card.selected {
        border-color: #00b7ff;
        background: rgba(0, 183, 255, 0.1);
    }
    .image-card .icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }
    .image-card .name {
        font-size: 0.8rem;
        font-weight: 600;
        color: #fff;
    }
    .image-card .version {
        font-size: 0.65rem;
        color: #6b7280;
        margin-top: 4px;
    }
    
    /* Resource Sliders */
    .resource-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .resource-item {
        padding: 16px;
        background: rgba(255,255,255,0.03);
        border-radius: 12px;
    }
    .resource-label {
        font-size: 0.75rem;
        color: #8b9bb4;
        margin-bottom: 8px;
    }
    .resource-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
    }
    .resource-slider {
        width: 100%;
        margin-top: 12px;
        -webkit-appearance: none;
        height: 6px;
        background: rgba(255,255,255,0.1);
        border-radius: 3px;
        outline: none;
    }
    .resource-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        background: #00b7ff;
        border-radius: 50%;
        cursor: pointer;
    }
    
    /* Summary Sidebar */
    .summary-sidebar {
        position: sticky;
        top: 20px;
    }
    .summary-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
        border: 1px solid rgba(0, 183, 255, 0.2);
        border-radius: 16px;
        padding: 24px;
    }
    .summary-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .summary-item:last-child {
        border-bottom: none;
    }
    .summary-label {
        font-size: 0.8rem;
        color: #8b9bb4;
    }
    .summary-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
    }
    .summary-total {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid rgba(0, 183, 255, 0.3);
    }
    .summary-price {
        font-size: 2rem;
        font-weight: 800;
        color: #00b7ff;
    }
    .summary-period {
        font-size: 0.75rem;
        color: #8b9bb4;
    }
    .create-btn {
        width: 100%;
        padding: 16px;
        margin-top: 20px;
        background: linear-gradient(90deg, #00b7ff, #0066cc);
        border: none;
        border-radius: 12px;
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    .create-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 183, 255, 0.3);
    }
    
    /* Toggle Switch */
    .advanced-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        padding: 16px;
        background: rgba(255,255,255,0.03);
        border-radius: 12px;
        margin-bottom: 16px;
    }
    .toggle-switch {
        position: relative;
        width: 48px;
        height: 24px;
        background: rgba(255,255,255,0.1);
        border-radius: 12px;
        transition: all 0.2s;
    }
    .toggle-switch.active {
        background: #00b7ff;
    }
    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background: #fff;
        border-radius: 50%;
        transition: all 0.2s;
    }
    .toggle-switch.active::after {
        left: 26px;
    }
    .toggle-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
    }
    .toggle-hint {
        font-size: 0.75rem;
        color: #6b7280;
        margin-left: auto;
    }
    
    /* Hidden Advanced Panel */
    .advanced-panel {
        display: none;
        padding: 20px;
        background: rgba(0,0,0,0.2);
        border-radius: 12px;
        margin-top: 12px;
    }
    .advanced-panel.open {
        display: block;
    }
    
    /* Form Inputs */
    .form-input {
        width: 100%;
        padding: 14px 16px;
        background: rgba(0,0,0,0.2);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #fff;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    .form-input:focus {
        outline: none;
        border-color: #00b7ff;
    }
    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #8b9bb4;
        margin-bottom: 8px;
    }
    
    /* App Categories */
    .app-category {
        display: none;
    }
    .app-category.active {
        display: block;
    }
    
    /* Quick Plans */
    .quick-plans {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 16px;
    }
    .quick-plan {
        padding: 12px;
        background: rgba(255,255,255,0.03);
        border: 2px solid rgba(255,255,255,0.06);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .quick-plan:hover {
        border-color: rgba(34, 197, 94, 0.4);
    }
    .quick-plan.selected {
        border-color: #22c55e;
        background: rgba(34, 197, 94, 0.1);
    }
    .quick-plan .name {
        font-size: 0.8rem;
        font-weight: 700;
        color: #fff;
    }
    .quick-plan .specs {
        font-size: 0.7rem;
        color: #8b9bb4;
        margin-top: 4px;
    }
    .quick-plan .price {
        font-size: 0.85rem;
        font-weight: 700;
        color: #22c55e;
        margin-top: 6px;
    }
</style>

@if(session('error'))
<div style="margin: 0 24px 20px; padding: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #ef4444;">
    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>{{ session('error') }}
</div>
@endif

<form action="{{ route('admin.proxmox.vms.store') }}" method="POST" id="createVmForm">
    @csrf
    <input type="hidden" name="mac_address" id="macAddressInput" value="{{ $autoMac }}">
    <input type="hidden" name="control_panel" id="controlPanelInput" value="">
    <input type="hidden" name="start_after_create" value="1">
    <input type="hidden" name="node" id="nodeInput" value="ns548195">
    <input type="hidden" name="storage" value="local">
    <input type="hidden" name="iso_storage" value="local">
    
    <div class="hostinger-layout">
        {{-- Main Form Area --}}
        <div class="main-form">
            
            {{-- Step 1: Data Center Location - Singapore Active, Rest Coming Soon --}}
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-globe"></i>
                    1. Select Data Center Location
                    <span style="margin-left: auto; padding: 4px 12px; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border-radius: 20px; font-size: 0.65rem; font-weight: 700;">LIVE</span>
                </div>
                <div class="location-grid" style="grid-template-columns: repeat(4, 1fr);">
                    {{-- SINGAPORE - ACTIVE (User's current server) --}}
                    <div class="location-card selected active-location" data-location="singapore" data-status="active" onclick="selectLocation('singapore')">
                        <div class="flag">��</div>
                        <div class="name">Singapore</div>
                        <div class="latency" style="color: #22c55e;">� Active • &lt;5ms</div>
                        <div style="margin-top: 8px; padding: 2px 8px; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border-radius: 10px; font-size: 0.6rem; font-weight: 700; display: inline-block;">DEFAULT</div>
                    </div>
                    
                    {{-- INDIA - COMING SOON --}}
                    <div class="location-card coming-soon" data-location="india" data-status="coming_soon" style="opacity: 0.6; cursor: not-allowed; position: relative; overflow: hidden;">
                        <div class="flag">🇮🇳</div>
                        <div class="name" style="color: #8b9bb4;">India</div>
                        <div class="latency" style="color: #f59e0b;">⏳ Coming Soon</div>
                        <div style="margin-top: 8px; padding: 2px 8px; background: rgba(139, 155, 180, 0.3); color: #8b9bb4; border-radius: 10px; font-size: 0.6rem; font-weight: 700; display: inline-block;">Q3 2026</div>
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,0.1) 10px, rgba(0,0,0,0.1) 20px); pointer-events: none;"></div>
                    </div>
                    
                    {{-- EUROPE - COMING SOON --}}
                    <div class="location-card coming-soon" data-location="europe" data-status="coming_soon" style="opacity: 0.6; cursor: not-allowed; position: relative; overflow: hidden;">
                        <div class="flag">🇩🇪</div>
                        <div class="name" style="color: #8b9bb4;">Europe</div>
                        <div class="latency" style="color: #f59e0b;">⏳ Coming Soon</div>
                        <div style="margin-top: 8px; padding: 2px 8px; background: rgba(139, 155, 180, 0.3); color: #8b9bb4; border-radius: 10px; font-size: 0.6rem; font-weight: 700; display: inline-block;">Q4 2026</div>
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,0.1) 10px, rgba(0,0,0,0.1) 20px); pointer-events: none;"></div>
                    </div>
                    
                    {{-- USA - COMING SOON --}}
                    <div class="location-card coming-soon" data-location="usa" data-status="coming_soon" style="opacity: 0.6; cursor: not-allowed; position: relative; overflow: hidden;">
                        <div class="flag">🇺🇸</div>
                        <div class="name" style="color: #8b9bb4;">USA</div>
                        <div class="latency" style="color: #f59e0b;">⏳ Coming Soon</div>
                        <div style="margin-top: 8px; padding: 2px 8px; background: rgba(139, 155, 180, 0.3); color: #8b9bb4; border-radius: 10px; font-size: 0.6rem; font-weight: 700; display: inline-block;">Q4 2026</div>
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,0.1) 10px, rgba(0,0,0,0.1) 20px); pointer-events: none;"></div>
                    </div>
                </div>
                <input type="hidden" name="datacenter" id="datacenterInput" value="singapore">
                <input type="hidden" name="node" id="nodeInput" value="ns548195">
                
                {{-- Location Legend --}}
                <div style="margin-top: 16px; padding: 12px 16px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-info-circle" style="color: #22c55e; font-size: 1.2rem;"></i>
                    <div style="font-size: 0.85rem; color: #22c55e;">
                        <strong>Singapore</strong> is our primary active datacenter. India, EU & USA launching soon.
                    </div>
                </div>
            </div>
            
            {{-- Step 2: Select Image (OS/Apps/Control Panels) --}}
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-compact-disc"></i>
                    2. Select Image
                </div>
                
                <div class="image-tabs">
                    <button type="button" class="image-tab active" onclick="switchImageTab('os')">Operating Systems</button>
                    <button type="button" class="image-tab" onclick="switchImageTab('apps')">Applications</button>
                    <button type="button" class="image-tab" onclick="switchImageTab('panels')">Control Panels</button>
                </div>
                
                {{-- Operating Systems --}}
                <div class="app-category active" id="tab-os">
                    <div class="image-grid">
                        <div class="image-card selected" data-iso="ubuntu-22.04-live-server-amd64.iso" onclick="selectImage('ubuntu-22.04-live-server-amd64.iso', 'Ubuntu 22.04 LTS')">
                            <div class="icon"><i class="fab fa-ubuntu" style="color: #e95420;"></i></div>
                            <div class="name">Ubuntu</div>
                            <div class="version">22.04 LTS</div>
                        </div>
                        <div class="image-card" data-iso="debian-12.iso" onclick="selectImage('debian-12.iso', 'Debian 12')">
                            <div class="icon"><i class="fab fa-debian" style="color: #a80030;"></i></div>
                            <div class="name">Debian</div>
                            <div class="version">12 (Bookworm)</div>
                        </div>
                        <div class="image-card" data-iso="centos-9.iso" onclick="selectImage('centos-9.iso', 'CentOS Stream 9')">
                            <div class="icon"><i class="fab fa-centos" style="color: #932279;"></i></div>
                            <div class="name">CentOS</div>
                            <div class="version">Stream 9</div>
                        </div>
                        <div class="image-card" data-iso="rocky-9.iso" onclick="selectImage('rocky-9.iso', 'Rocky Linux 9')">
                            <div class="icon"><i class="fas fa-mountain" style="color: #10b981;"></i></div>
                            <div class="name">Rocky Linux</div>
                            <div class="version">9 (Blue Onyx)</div>
                        </div>
                        <div class="image-card" data-iso="almalinux-9.iso" onclick="selectImage('almalinux-9.iso', 'AlmaLinux 9')">
                            <div class="icon"><i class="fas fa-shield-alt" style="color: #0e3b5c;"></i></div>
                            <div class="name">AlmaLinux</div>
                            <div class="version">9</div>
                        </div>
                        <div class="image-card" data-iso="fedora-39.iso" onclick="selectImage('fedora-39.iso', 'Fedora 39')">
                            <div class="icon"><i class="fab fa-fedora" style="color: #51a2da;"></i></div>
                            <div class="name">Fedora</div>
                            <div class="version">39</div>
                        </div>
                        <div class="image-card" data-iso="windows-server-2022.iso" onclick="selectImage('windows-server-2022.iso', 'Windows Server 2022')">
                            <div class="icon"><i class="fab fa-windows" style="color: #00a4ef;"></i></div>
                            <div class="name">Windows Server</div>
                            <div class="version">2022</div>
                        </div>
                        <div class="image-card" data-iso="arch-linux.iso" onclick="selectImage('arch-linux.iso', 'Arch Linux')">
                            <div class="icon"><i class="fas fa-terminal" style="color: #1793d1;"></i></div>
                            <div class="name">Arch Linux</div>
                            <div class="version">Rolling</div>
                        </div>
                    </div>
                </div>
                
                {{-- Applications --}}
                <div class="app-category" id="tab-apps">
                    <div class="image-grid">
                        <div class="image-card" data-iso="docker-ubuntu-22.04.iso" data-app="docker" onclick="selectImage('docker-ubuntu-22.04.iso', 'Docker + Portainer', 'docker')">
                            <div class="icon"><i class="fab fa-docker" style="color: #2496ed;"></i></div>
                            <div class="name">Docker</div>
                            <div class="version">+ Portainer</div>
                        </div>
                        <div class="image-card" data-iso="wordpress-ubuntu-22.04.iso" data-app="wordpress" onclick="selectImage('wordpress-ubuntu-22.04.iso', 'WordPress Stack', 'wordpress')">
                            <div class="icon"><i class="fab fa-wordpress" style="color: #21759b;"></i></div>
                            <div class="name">WordPress</div>
                            <div class="version">Pre-installed</div>
                        </div>
                        <div class="image-card" data-iso="lamp-ubuntu-22.04.iso" data-app="lamp" onclick="selectImage('lamp-ubuntu-22.04.iso', 'LAMP Stack', 'lamp')">
                            <div class="icon"><i class="fas fa-layer-group" style="color: #f59e0b;"></i></div>
                            <div class="name">LAMP Stack</div>
                            <div class="version">Apache/MySQL/PHP</div>
                        </div>
                        <div class="image-card" data-iso="node-ubuntu-22.04.iso" data-app="nodejs" onclick="selectImage('node-ubuntu-22.04.iso', 'Node.js Stack', 'nodejs')">
                            <div class="icon"><i class="fab fa-node-js" style="color: #339933;"></i></div>
                            <div class="name">Node.js</div>
                            <div class="version">Latest LTS</div>
                        </div>
                        <div class="image-card" data-iso="minecraft-ubuntu-22.04.iso" data-app="minecraft" onclick="selectImage('minecraft-ubuntu-22.04.iso', 'Minecraft Server', 'minecraft')">
                            <div class="icon"><i class="fas fa-cube" style="color: #22c55e;"></i></div>
                            <div class="name">Minecraft</div>
                            <div class="version">Java Server</div>
                        </div>
                    </div>
                </div>
                
                {{-- Control Panels --}}
                <div class="app-category" id="tab-panels">
                    <div class="image-grid">
                        <div class="image-card" data-iso="aapanel-ubuntu-22.04.iso" data-app="aapanel" onclick="selectImage('aapanel-ubuntu-22.04.iso', 'aaPanel', 'aapanel')">
                            <div class="icon"><i class="fas fa-server" style="color: #22c55e;"></i></div>
                            <div class="name">aaPanel</div>
                            <div class="version">Free • Best for Beginners</div>
                        </div>
                        <div class="image-card" data-iso="cyberpanel-ubuntu-22.04.iso" data-app="cyberpanel" onclick="selectImage('cyberpanel-ubuntu-22.04.iso', 'CyberPanel', 'cyberpanel')">
                            <div class="icon"><i class="fas fa-bolt" style="color: #8b5cf6;"></i></div>
                            <div class="name">CyberPanel</div>
                            <div class="version">OpenLiteSpeed</div>
                        </div>
                        <div class="image-card" data-iso="cloudpanel-ubuntu-22.04.iso" data-app="cloudpanel" onclick="selectImage('cloudpanel-ubuntu-22.04.iso', 'CloudPanel', 'cloudpanel')">
                            <div class="icon"><i class="fas fa-cloud" style="color: #00b7ff;"></i></div>
                            <div class="name">CloudPanel</div>
                            <div class="version">Modern & Fast</div>
                        </div>
                        <div class="image-card" data-iso="hestiacp-ubuntu-22.04.iso" data-app="hestiacp" onclick="selectImage('hestiacp-ubuntu-22.04.iso', 'HestiaCP', 'hestiacp')">
                            <div class="icon"><i class="fas fa-shield-alt" style="color: #ef4444;"></i></div>
                            <div class="name">HestiaCP</div>
                            <div class="version">Open Source</div>
                        </div>
                        <div class="image-card" data-iso="cpanel-centos-9.iso" data-app="cpanel" onclick="selectImage('cpanel-centos-9.iso', 'cPanel & WHM', 'cpanel')">
                            <div class="icon"><i class="fab fa-cpanel" style="color: #ff6c2c;"></i></div>
                            <div class="name">cPanel</div>
                            <div class="version">License Required</div>
                        </div>
                        <div class="image-card" data-iso="plesk-ubuntu-22.04.iso" data-app="plesk" onclick="selectImage('plesk-ubuntu-22.04.iso', 'Plesk Obsidian', 'plesk')">
                            <div class="icon"><i class="fas fa-gem" style="color: #00aeef;"></i></div>
                            <div class="name">Plesk</div>
                            <div class="version">License Required</div>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="iso" id="isoInput" value="ubuntu-22.04-live-server-amd64.iso" required>
            </div>
            
            {{-- Step 3: Server Configuration --}}
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-sliders-h"></i>
                    3. Server Configuration
                </div>
                
                {{-- Quick Plans - Matches VpsPlanSeeder --}}
                <div class="quick-plans">
                    <div class="quick-plan selected" onclick="selectQuickPlan(4, 8192, 75, 1400, this)">
                        <div class="name">VPS-1</div>
                        <div class="specs">4 vCPU • 8GB RAM • 75GB SSD</div>
                        <div class="price">₹1,400/mo</div>
                    </div>
                    <div class="quick-plan" onclick="selectQuickPlan(6, 12288, 100, 2153, this)">
                        <div class="name">VPS-2</div>
                        <div class="specs">6 vCPU • 12GB RAM • 100GB NVMe</div>
                        <div class="price">₹2,153/mo</div>
                    </div>
                    <div class="quick-plan" onclick="selectQuickPlan(8, 24576, 200, 4308, this)">
                        <div class="name">VPS-3 ⭐</div>
                        <div class="specs">8 vCPU • 24GB RAM • 200GB NVMe</div>
                        <div class="price" style="color: #00b7ff;">₹4,308/mo</div>
                    </div>
                    <div class="quick-plan" onclick="selectQuickPlan(16, 49152, 300, 7970, this)">
                        <div class="name">VPS-4</div>
                        <div class="specs">16 vCPU • 48GB RAM • 300GB NVMe</div>
                        <div class="price">₹7,970/mo</div>
                    </div>
                </div>
                
                {{-- Custom Resources --}}
                <div class="resource-row">
                    <div class="resource-item">
                        <div class="resource-label">vCPU Cores</div>
                        <div class="resource-value" id="cpuDisplay">4</div>
                        <input type="range" name="cpu" id="cpuSlider" class="resource-slider" min="1" max="32" value="4" oninput="updateResource('cpu', this.value)">
                    </div>
                    <div class="resource-item">
                        <div class="resource-label">RAM</div>
                        <div class="resource-value" id="memoryDisplay">8 GB</div>
                        <input type="range" id="memorySlider" class="resource-slider" min="1" max="64" value="8" oninput="updateResource('memory', this.value)">
                        <input type="hidden" name="memory" id="memoryInput" value="8192">
                    </div>
                    <div class="resource-item">
                        <div class="resource-label">Storage</div>
                        <div class="resource-value" id="diskDisplay">75 GB</div>
                        <input type="range" name="disk" id="diskSlider" class="resource-slider" min="20" max="500" value="75" step="5" oninput="updateResource('disk', this.value)">
                    </div>
                </div>
            </div>
            
            {{-- Step 4: Server Details --}}
            <div class="section-card">
                <div class="section-header">
                    <i class="fas fa-info-circle"></i>
                    4. Server Details
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label class="form-label">Server Name</label>
                    <input type="text" name="name" id="serverNameInput" class="form-input" 
                        placeholder="my-vps-server" required
                        oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '').replace(/^-+|-+$/g, '')">
                    <small style="color: #6b7280; font-size: 0.75rem;">Only lowercase letters, numbers, and hyphens allowed</small>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label class="form-label">Hostname (Optional)</label>
                    <input type="text" name="hostname" class="form-input" placeholder="vps.yourdomain.com">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label class="form-label">Assign to Client</label>
                    <select name="user_id" class="form-input" style="cursor: pointer;">
                        <option value="">-- Unassigned --</option>
                        @foreach(\App\Models\User::where('is_admin', false)->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            {{-- Step 5: Advanced Networking (Hidden Toggle) --}}
            <div class="section-card">
                <div class="advanced-toggle" onclick="toggleAdvanced()">
                    <div class="toggle-switch" id="advancedToggle"></div>
                    <span class="toggle-label">Advanced Networking</span>
                    <span class="toggle-hint">Optional • Virtual MAC & IP Config</span>
                </div>
                
                <div class="advanced-panel" id="advancedPanel">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <label class="form-label">Additional IPv4</label>
                            <input type="text" name="ip_address" class="form-input" placeholder="139.99.122.xxx">
                        </div>
                        <div>
                            <label class="form-label">Virtual MAC (Auto: {{ $autoMac ?? '02:00:00:xx:xx:xx' }})</label>
                            <input type="text" name="custom_mac" class="form-input" placeholder="{{ $autoMac ?? '02:00:00:xx:xx:xx' }}" oninput="document.getElementById('macAddressInput').value = this.value || '{{ $autoMac }}'">
                        </div>
                    </div>
                    <div style="margin-top: 16px;">
                        <label class="form-label">Static IP Config (Cloud-init)</label>
                        <input type="text" name="ipconfig0" class="form-input" placeholder="ip=192.168.1.100/24,gw=192.168.1.1">
                    </div>
                </div>
            </div>
            
        </div>
        
        {{-- Summary Sidebar --}}
        <div class="summary-sidebar">
            <div class="summary-card">
                <div class="summary-title">
                    <i class="fas fa-receipt"></i>
                    Order Summary
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">Location</span>
                    <span class="summary-value" id="summaryLocation">Singapore �� (Active)</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Image</span>
                    <span class="summary-value" id="summaryImage">Ubuntu 22.04</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">vCPU</span>
                    <span class="summary-value" id="summaryCpu">4 Cores</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">RAM</span>
                    <span class="summary-value" id="summaryMemory">8 GB</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Storage</span>
                    <span class="summary-value" id="summaryDisk">75 GB SSD</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Bandwidth</span>
                    <span class="summary-value">1 Gbps Unmetered</span>
                </div>
                
                <div class="summary-total">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="summary-label" style="font-size: 0.9rem;">Estimated Monthly</span>
                        <span class="summary-price" id="summaryPrice">₹1,400</span>
                    </div>
                    <div class="summary-period">+ Setup fees may apply</div>
                </div>
                
                <button type="submit" class="create-btn">
                    <i class="fas fa-rocket" style="margin-right: 8px;"></i>
                    Create VPS Now
                </button>
                
                <div style="margin-top: 16px; font-size: 0.7rem; color: #6b7280; text-align: center;">
                    <i class="fas fa-shield-alt" style="margin-right: 4px; color: #22c55e;"></i>
                    SSL Enabled • DDoS Protection • 99.9% Uptime
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Location Selection - Only allow active locations
function selectLocation(location) {
    const selectedCard = document.querySelector('[data-location="' + location + '"]');
    const status = selectedCard.getAttribute('data-status');
    
    // Block coming soon locations
    if (status === 'coming_soon') {
        // Show coming soon popup
        alert('🚀 ' + location.charAt(0).toUpperCase() + location.slice(1) + ' datacenter is launching soon!\n\nExpected launch: Q3-Q4 2026\nJoin waitlist: support@believoo.com');
        return;
    }
    
    document.querySelectorAll('.location-card').forEach(card => {
        card.classList.remove('selected');
    });
    selectedCard.classList.add('selected');
    document.getElementById('datacenterInput').value = location;
    document.getElementById('nodeInput').value = 'ns548195'; // Fixed node name
    
    const locations = {
        'singapore': 'Singapore 🇸🇬 (Active)',
        'india': 'India 🇮🇳 (Coming Soon)',
        'europe': 'Europe 🇩🇪 (Coming Soon)',
        'usa': 'USA 🇺🇸 (Coming Soon)'
    };
    document.getElementById('summaryLocation').textContent = locations[location];
}

// Image Tab Switching
function switchImageTab(tab) {
    document.querySelectorAll('.image-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.app-category').forEach(c => c.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

// Image Selection
function selectImage(iso, name, app = '') {
    document.querySelectorAll('.image-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    
    document.getElementById('isoInput').value = iso;
    document.getElementById('summaryImage').textContent = name;
    
    // Set control panel if it's an app/panel
    if (app) {
        document.getElementById('controlPanelInput').value = app;
    } else {
        document.getElementById('controlPanelInput').value = '';
    }
}

// Quick Plan Selection
function selectQuickPlan(cpu, memory, disk, price, element) {
    document.querySelectorAll('.quick-plan').forEach(p => p.classList.remove('selected'));
    element.classList.add('selected');
    
    document.getElementById('cpuSlider').value = cpu;
    document.getElementById('memorySlider').value = memory / 1024;
    document.getElementById('diskSlider').value = disk;
    
    updateResource('cpu', cpu);
    updateResource('memory', memory / 1024);
    updateResource('disk', disk);
    updatePrice(price);
}

// Resource Slider Updates
function updateResource(type, value) {
    const cpu = parseInt(document.getElementById('cpuSlider').value);
    const memory = parseInt(document.getElementById('memorySlider').value);
    const disk = parseInt(document.getElementById('diskSlider').value);
    
    // Update displays
    document.getElementById('cpuDisplay').textContent = cpu;
    document.getElementById('memoryDisplay').textContent = memory + ' GB';
    document.getElementById('diskDisplay').textContent = disk + ' GB';
    
    // Update hidden memory field in MB (backend expects MB)
    document.getElementById('memoryInput').value = memory * 1024;
    
    // Update summary
    document.getElementById('summaryCpu').textContent = cpu + ' Core' + (cpu > 1 ? 's' : '');
    document.getElementById('summaryMemory').textContent = memory + ' GB';
    document.getElementById('summaryDisk').textContent = disk + ' GB SSD';
    
    // Calculate estimated price (realistic VPS pricing)
    // Base: ₹350 + ₹250 per CPU core + ₹150 per GB RAM + ₹5 per GB disk
    const price = 350 + (cpu * 250) + (memory * 150) + (disk * 5);
    updatePrice(price);
    
    // Remove quick plan selection if custom
    document.querySelectorAll('.quick-plan').forEach(p => p.classList.remove('selected'));
}

function updatePrice(price) {
    document.getElementById('summaryPrice').textContent = '₹' + Math.round(price).toLocaleString();
}

// Advanced Toggle
function toggleAdvanced() {
    const toggle = document.getElementById('advancedToggle');
    const panel = document.getElementById('advancedPanel');
    
    toggle.classList.toggle('active');
    panel.classList.toggle('open');
}

// Form validation
const vmForm = document.getElementById('createVmForm');
if (vmForm) {
    vmForm.addEventListener('submit', function(e) {
        console.log('Form submit event fired!');
        const name = document.getElementById('serverNameInput').value;
        console.log('Server name:', name);
        
        if (!name || name.length < 3) {
            e.preventDefault();
            alert('Please enter a valid server name (at least 3 characters)');
            return false;
        }
        
        // Disable button to prevent double-submit
        const btn = document.querySelector('.create-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Creating VPS...';
        }
        
        console.log('Form submitting to:', this.action);
        return true;
    });
} else {
    console.error('ERROR: createVmForm not found in DOM!');
}
</script>
@endsection
