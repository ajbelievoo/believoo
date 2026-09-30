@extends('layouts.admin')
@section('title', 'Manage Hosting — ' . $hosting->plan_name)
@section('content')

<div class="page-header">
    <div>
        <a href="{{ route('admin.hostings.index') }}" style="font-size: 0.75rem; font-weight: 700; color: #00b7ff; text-transform: uppercase; letter-spacing: 0.1em; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
            <i class="fas fa-arrow-left"></i> Back to Hostings
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 900; color: var(--text-primary); margin: 0;">
            Manage: <span style="color: #00b7ff;">{{ $hosting->plan_name }}</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">
            {{ $hosting->user->name ?? '—' }} · {{ $hosting->order->order_number ?? 'No order' }}
        </p>
    </div>
    @php
        $sc = ['active'=>['#22c55e','rgba(34,197,94,0.1)','rgba(34,197,94,0.3)'],'pending'=>['#00b7ff','rgba(0,183,255,0.1)','rgba(0,183,255,0.3)'],'suspended'=>['#ef4444','rgba(239,68,68,0.1)','rgba(239,68,68,0.3)']];
        $c = $sc[$hosting->status] ?? ['#6b7280','rgba(107,114,128,0.1)','rgba(107,114,128,0.3)'];
    @endphp
    <span style="padding: 8px 18px; border-radius: 50px; background: {{ $c[1] }}; border: 1px solid {{ $c[2] }}; color: {{ $c[0] }}; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;">
        {{ ucfirst($hosting->status) }}
    </span>
</div>

@if(session('success'))
    <div style="margin-bottom: 20px; padding: 14px 20px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); border-radius: 12px; color: #22c55e; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.hostings.update', $hosting) }}" method="POST">
    @csrf
    @method('PUT')

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">

        {{-- LEFT --}}
        <div style="display: flex; flex-direction: column; gap: 24px;">

            {{-- Status & Basic --}}
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(0,183,255,0.1); display: flex; align-items: center; justify-content: center; color: #00b7ff; font-size: 0.85rem;"><i class="fas fa-cog"></i></div>
                    <h3 style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Plan Status & Info</h3>
                </div>
                <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Status *</label>
                        <select name="status" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem; font-weight: 600;">
                            @foreach(['pending','active','suspended','cancelled','expired'] as $s)
                                <option value="{{ $s }}" {{ $hosting->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 6px;">Set to <strong style="color: #22c55e;">Active</strong> to show plan to client</p>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Hosting Type</label>
                        <select name="hosting_type" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                            @foreach(['shared','vps','dedicated','cloud','reseller'] as $t)
                                <option value="{{ $t }}" {{ $hosting->hosting_type === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Plan Name</label>
                        <input type="text" name="plan_name" value="{{ $hosting->plan_name }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Expiry Date</label>
                        <input type="date" name="expiry_date" value="{{ $hosting->expiry_date?->format('Y-m-d') }}" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Primary Domain</label>
                        <input type="text" name="primary_domain" value="{{ $hosting->primary_domain }}" placeholder="example.com" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                </div>
            </div>

            {{-- Server Details --}}
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(34,197,94,0.1); display: flex; align-items: center; justify-content: center; color: #22c55e; font-size: 0.85rem;"><i class="fas fa-server"></i></div>
                    <h3 style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Server Details</h3>
                </div>
                <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    @php
                        $fields = [
                            ['server_ip', 'Server IP (IPv4)', 'text', '192.168.1.1'],
                            ['ipv6', 'IPv6 Address', 'text', '2001:db8::1'],
                            ['gateway', 'Gateway', 'text', '192.168.1.254'],
                            ['server_hostname', 'Hostname', 'text', 'vps-001.believoo.com'],
                            ['datacenter_location', 'Datacenter Location', 'text', 'Singapore'],
                            ['os_name', 'OS / Distribution', 'text', 'Ubuntu 22.04 LTS'],
                            ['boot_mode', 'Boot Mode', 'text', 'Normal'],
                        ];
                    @endphp
                    @foreach($fields as [$name, $label, $type, $placeholder])
                        <div>
                            <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">{{ $label }}</label>
                            <input type="{{ $type }}" name="{{ $name }}" value="{{ $hosting->$name }}" placeholder="{{ $placeholder }}"
                                   style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem; font-family: {{ in_array($name, ['server_ip','ipv6','gateway']) ? 'monospace' : 'inherit' }};">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Specs --}}
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(139,92,246,0.1); display: flex; align-items: center; justify-content: center; color: #8b5cf6; font-size: 0.85rem;"><i class="fas fa-microchip"></i></div>
                    <h3 style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Server Specifications</h3>
                </div>
                <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">vCPU Cores</label>
                        <input type="number" name="cpu_cores" value="{{ $hosting->cpu_cores }}" min="1" placeholder="4"
                               style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">RAM</label>
                        <input type="text" name="ram_size" value="{{ $hosting->ram_size }}" placeholder="8 GB"
                               style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Storage</label>
                        <input type="text" name="storage_size" value="{{ $hosting->storage_size }}" placeholder="100 GB SSD"
                               style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Bandwidth</label>
                        <input type="text" name="bandwidth" value="{{ $hosting->bandwidth }}" placeholder="Unlimited"
                               style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Backup Status</label>
                        <input type="text" name="backup_status" value="{{ $hosting->backup_status }}" placeholder="Enabled"
                               style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; padding-top: 24px;">
                        <input type="checkbox" name="automated_backup" id="auto_backup" value="1" {{ $hosting->automated_backup ? 'checked' : '' }}
                               style="width: 18px; height: 18px; accent-color: #00b7ff;">
                        <label for="auto_backup" style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); cursor: pointer;">Automated Backup</label>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT --}}
        <div style="display: flex; flex-direction: column; gap: 24px;">

            {{-- Client Info --}}
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Client</h3>
                </div>
                <div style="padding: 20px 24px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #00b7ff, #8b5cf6); display: flex; align-items: center; justify-content: center; font-weight: 900; color: white; font-size: 1rem; flex-shrink: 0;">
                            {{ strtoupper(substr($hosting->user->name ?? 'G', 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-primary);">{{ $hosting->user->name ?? '—' }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $hosting->user->email ?? '' }}</div>
                        </div>
                    </div>
                    @if($hosting->order)
                        <div style="padding: 10px 14px; background: var(--bg-tertiary); border-radius: 10px; font-size: 0.8rem; color: var(--text-muted);">
                            Order: <span style="color: #00b7ff; font-weight: 700; font-family: monospace;">{{ $hosting->order->order_number }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Streaming Addon --}}
            <div style="background: var(--bg-secondary); border: 1px solid {{ $hosting->has_streaming_addon ? 'rgba(139,92,246,0.4)' : 'var(--border-color)' }}; border-radius: 20px; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(139,92,246,0.1); display: flex; align-items: center; justify-content: center; color: #8b5cf6; font-size: 0.85rem;"><i class="fas fa-broadcast-tower"></i></div>
                    <h3 style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Streaming Addon</h3>
                    @if($hosting->has_streaming_addon)
                        <span style="margin-left: auto; padding: 4px 10px; background: rgba(139,92,246,0.15); color: #8b5cf6; border-radius: 20px; font-size: 0.65rem; font-weight: 700;">ACTIVE</span>
                    @else
                        <span style="margin-left: auto; padding: 4px 10px; background: rgba(107,114,128,0.15); color: #6b7280; border-radius: 20px; font-size: 0.65rem; font-weight: 700;">INACTIVE</span>
                    @endif
                </div>
                <div style="padding: 20px 24px; display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="has_streaming_addon" id="has_streaming_addon" value="1" {{ $hosting->has_streaming_addon ? 'checked' : '' }}
                               style="width: 18px; height: 18px; accent-color: #8b5cf6;">
                        <label for="has_streaming_addon" style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); cursor: pointer;">Enable Live Streaming Addon</label>
                    </div>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">Allow client to activate streaming on this VPS. Auto-deploys SRS server.</p>

                    @if($hosting->streaming_api_key_id)
                        <div style="padding: 10px 14px; background: rgba(139,92,246,0.08); border-radius: 10px; border: 1px solid rgba(139,92,246,0.2);">
                            <p style="font-size: 0.75rem; color: #8b5cf6; margin: 0; font-weight: 600;"><i class="fas fa-link mr-1"></i>Linked API Key ID: {{ $hosting->streaming_api_key_id }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Control Panel --}}
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Control Panel Access</h3>
                </div>
                <div style="padding: 20px 24px; display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Panel URL</label>
                        <input type="url" name="control_panel_url" value="{{ $hosting->control_panel_url }}" placeholder="https://panel.example.com"
                               style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.85rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Username</label>
                        <input type="text" name="control_panel_username" value="{{ $hosting->control_panel_username }}" placeholder="root"
                               style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.85rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Root Password</label>
                        <input type="text" name="root_password" value="{{ $hosting->root_password }}" placeholder="••••••••"
                               style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.85rem; font-family: monospace;">
                    </div>
                </div>
            </div>

            {{-- Admin Notes --}}
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 0.85rem; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Admin Notes</h3>
                </div>
                <div style="padding: 20px 24px;">
                    <textarea name="admin_notes" rows="4" placeholder="Internal notes..."
                              style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; color: var(--text-primary); font-size: 0.85rem; resize: vertical;">{{ $hosting->admin_notes }}</textarea>
                </div>
            </div>

            {{-- Save Button --}}
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 0.9rem;">
                <i class="fas fa-save"></i> Save & Activate Plan
            </button>

            {{-- Recreate VM Button --}}
            @if($hosting->hosting_type === 'vps' || str_contains(strtolower($hosting->plan_name ?? ''), 'vps'))
            <form action="{{ route('admin.hostings.recreate-vm', $hosting) }}" method="POST" style="margin-top: 12px;">
                @csrf
                <button type="submit"
                        class="btn btn-primary"
                        style="width: 100%; justify-content: center; padding: 14px; font-size: 0.9rem; background: linear-gradient(90deg, #f59e0b, #d97706); border-color: #f59e0b;"
                        onclick="return confirm('This will create a new Proxmox VM with correct plan specs for {{ $hosting->plan_name }}. Continue?')">
                    <i class="fas fa-redo"></i> Recreate VM (Fix Specs)
                </button>
                <p style="font-size: 0.7rem; color: var(--text-muted); text-align: center; margin-top: 6px;">
                    Deletes old VM reference and creates fresh VM with plan specs
                </p>
            </form>
            @endif

        </div>
    </div>
</form>
@endsection
