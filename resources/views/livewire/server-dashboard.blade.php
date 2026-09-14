<div class="server-dashboard" x-data="{ autoRefresh: true }" x-init="
    if (autoRefresh) {
        setInterval(() => { $wire.loadData() }, 30000)
    }
" style="font-family: 'Inter', sans-serif;">
    {{-- Flash Messages --}}
    @if ($message)
        <div style="margin-bottom: 16px; padding: 16px; border-radius: 12px; {{ $messageType === 'success' ? 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);' : 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);' }}"
             x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => { show = false; $wire.clearMessage() }, 3000)">
            <i class="fas {{ $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' }}" style="margin-right: 8px;"></i>
            {{ $message }}
        </div>
    @endif

    {{-- Summary Cards --}}
    @if (!empty($summary))
        <div style="margin-bottom: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
            <div style="background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; right: 0; width: 80px; height: 80px; background: linear-gradient(135deg, rgba(0, 183, 255, 0.1) 0%, transparent 100%); border-radius: 0 16px 0 80px;"></div>
                <div style="font-size: 0.8rem; color: #8b9bb4; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Total Services</div>
                <div style="font-size: 2rem; font-weight: 700; color: #fff;">{{ $summary['total_services'] ?? 0 }}</div>
                <i class="fas fa-server" style="position: absolute; bottom: 16px; right: 16px; font-size: 1.5rem; color: rgba(0, 183, 255, 0.3);"></i>
            </div>
            <div style="background: linear-gradient(135deg, #1a2f23 0%, #1e3a2f 100%); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 16px; padding: 20px; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; right: 0; width: 80px; height: 80px; background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, transparent 100%); border-radius: 0 16px 0 80px;"></div>
                <div style="font-size: 0.8rem; color: #22c55e; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Running VPS</div>
                <div style="font-size: 2rem; font-weight: 700; color: #22c55e;">{{ $summary['active_vps'] ?? 0 }}</div>
                <i class="fas fa-play-circle" style="position: absolute; bottom: 16px; right: 16px; font-size: 1.5rem; color: rgba(34, 197, 94, 0.3);"></i>
            </div>
            <div style="background: linear-gradient(135deg, #2f2a1a 0%, #3a351e 100%); border: 1px solid rgba(234, 179, 8, 0.2); border-radius: 16px; padding: 20px; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; right: 0; width: 80px; height: 80px; background: linear-gradient(135deg, rgba(234, 179, 8, 0.1) 0%, transparent 100%); border-radius: 0 16px 0 80px;"></div>
                <div style="font-size: 0.8rem; color: #eab308; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Stopped VPS</div>
                <div style="font-size: 2rem; font-weight: 700; color: #eab308;">{{ $summary['stopped_vps'] ?? 0 }}</div>
                <i class="fas fa-stop-circle" style="position: absolute; bottom: 16px; right: 16px; font-size: 1.5rem; color: rgba(234, 179, 8, 0.3);"></i>
            </div>
            <div style="background: linear-gradient(135deg, #2f1a1a 0%, #3a1e1e 100%); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 16px; padding: 20px; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; right: 0; width: 80px; height: 80px; background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, transparent 100%); border-radius: 0 16px 0 80px;"></div>
                <div style="font-size: 0.8rem; color: #ef4444; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Unpaid Invoices</div>
                <div style="font-size: 2rem; font-weight: 700; color: {{ ($summary['unpaid_invoices'] ?? 0) > 0 ? '#ef4444' : '#22c55e' }};">{{ $summary['unpaid_invoices'] ?? 0 }}</div>
                <i class="fas fa-file-invoice-dollar" style="position: absolute; bottom: 16px; right: 16px; font-size: 1.5rem; color: {{ ($summary['unpaid_invoices'] ?? 0) > 0 ? 'rgba(239, 68, 68, 0.3)' : 'rgba(34, 197, 94, 0.3)' }};"></i>
            </div>
        </div>
    @endif

    {{-- Loading State --}}
    @if ($loading)
        <div style="display: flex; align-items: center; justify-content: center; padding: 48px 0;">
            <div style="width: 40px; height: 40px; border: 3px solid rgba(0, 183, 255, 0.2); border-top-color: #00b7ff; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <span style="margin-left: 16px; color: #8b9bb4; font-size: 0.9rem;">Loading servers...</span>
        </div>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
    @endif

    {{-- Server Cards Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
        @forelse ($servers as $server)
            <div style="background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; position: relative; overflow: hidden; transition: all 0.3s ease;">
                {{-- Glow Effect --}}
                <div style="position: absolute; top: -50%; left: -50%; width: 100%; height: 100%; background: radial-gradient(circle, rgba(0, 183, 255, 0.05) 0%, transparent 70%); pointer-events: none;"></div>

                {{-- Server Header --}}
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; position: relative; z-index: 1;">
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 600; color: #fff; margin-bottom: 4px;">{{ $server['whmcs_service']['name'] }}</h3>
                        <p style="font-size: 0.85rem; color: #8b9bb4;">{{ $server['whmcs_service']['domain'] ?? 'No Domain' }}</p>
                    </div>
                    @if ($server['virtualizor_status'])
                        <span style="padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
                            {{ $server['virtualizor_status']['state'] === 'running'
                                ? 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);'
                                : 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);' }}">
                            <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 6px;"></i>
                            {{ ucfirst($server['virtualizor_status']['state']) }}
                        </span>
                    @else
                        <span style="padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(139, 155, 180, 0.15); color: #8b9bb4; border: 1px solid rgba(139, 155, 180, 0.3);">
                            No Data
                        </span>
                    @endif
                </div>

                {{-- Resource Usage --}}
                @if ($server['virtualizor_status'])
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;">
                        <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-size: 0.75rem; color: #8b9bb4; margin-bottom: 6px;">CPU</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: {{ $server['virtualizor_status']['resources']['cpu']['usage_percent'] > 80 ? '#ef4444' : '#00b7ff' }};">{{ $server['virtualizor_status']['resources']['cpu']['usage_percent'] }}%</div>
                        </div>
                        <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-size: 0.75rem; color: #8b9bb4; margin-bottom: 6px;">RAM</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: {{ $server['virtualizor_status']['resources']['ram']['usage_percent'] > 80 ? '#ef4444' : '#8b5cf6' }};">{{ $server['virtualizor_status']['resources']['ram']['usage_percent'] }}%</div>
                        </div>
                        <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-size: 0.75rem; color: #8b9bb4; margin-bottom: 6px;">Disk</div>
                            <div style="font-size: 1.25rem; font-weight: 700; color: {{ $server['virtualizor_status']['resources']['disk']['usage_percent'] > 80 ? '#ef4444' : '#eab308' }};">{{ $server['virtualizor_status']['resources']['disk']['usage_percent'] }}%</div>
                        </div>
                    </div>

                    {{-- Bandwidth --}}
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 8px;">
                            <span style="color: #8b9bb4;">Bandwidth Usage</span>
                            <span style="color: #fff; font-weight: 600;">{{ $server['virtualizor_status']['resources']['bandwidth']['usage_percent'] }}%</span>
                        </div>
                        <div style="height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(90deg, #00b7ff 0%, #00d4ff 100%); border-radius: 3px; transition: width 0.5s ease; width: {{ min($server['virtualizor_status']['resources']['bandwidth']['usage_percent'], 100) }}%;"></div>
                        </div>
                    </div>
                @else
                    <div style="background: rgba(139, 155, 180, 0.1); border-radius: 12px; padding: 20px; margin-bottom: 20px; text-align: center;">
                        <i class="fas fa-info-circle" style="color: #8b9bb4; margin-bottom: 8px; font-size: 1.5rem;"></i>
                        <p style="font-size: 0.85rem; color: #8b9bb4;">Virtualizor data not available. Service may not be provisioned yet.</p>
                    </div>
                @endif

                {{-- Billing Info --}}
                <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 16px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 8px;">
                        <span style="color: #8b9bb4;">Next Due:</span>
                        <span style="color: #fff;">{{ $server['whmcs_service']['next_due_date'] ?? 'N/A' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                        <span style="color: #8b9bb4;">Billing Cycle:</span>
                        <span style="color: #fff;">{{ $server['whmcs_service']['billing_cycle'] ?? 'N/A' }}</span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                @if ($server['actions_available'])
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; position: relative; z-index: 1;">
                        @if ($server['virtualizor_status']['state'] === 'stopped')
                            <button wire:click="executeAction('start', {{ $server['virtualizor_status']['vps_id'] }})"
                                    wire:loading.attr="disabled"
                                    style="flex: 1; min-width: 80px; padding: 10px 16px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: #fff; border: none; border-radius: 10px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                <i class="fas fa-play"></i> Start
                            </button>
                        @else
                            <button wire:click="confirmAction('stop', {{ $server['virtualizor_status']['vps_id'] }}, 'Stop Server', 'This will gracefully shut down your server. Continue?')"
                                    wire:loading.attr="disabled"
                                    style="padding: 10px 16px; background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 10px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                <i class="fas fa-stop"></i>
                            </button>
                            <button wire:click="confirmAction('restart', {{ $server['virtualizor_status']['vps_id'] }}, 'Restart Server', 'This will restart your server. Continue?')"
                                    wire:loading.attr="disabled"
                                    style="padding: 10px 16px; background: rgba(0, 183, 255, 0.15); color: #00b7ff; border: 1px solid rgba(0, 183, 255, 0.3); border-radius: 10px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button wire:click="confirmAction('poweroff', {{ $server['virtualizor_status']['vps_id'] }}, 'Power Off Server', 'This is a hard shutdown and may cause data loss. Continue?')"
                                    wire:loading.attr="disabled"
                                    style="padding: 10px 16px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                <i class="fas fa-power-off"></i>
                            </button>
                        @endif
                        <button wire:click="selectServer({{ $server['id'] }}, {{ $server['virtualizor_status']['vps_id'] }})"
                                style="margin-left: auto; padding: 10px 20px; background: rgba(255, 255, 255, 0.05); color: #8b9bb4; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i> Details
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div style="grid-column: 1 / -1; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 60px 40px; text-align: center;">
                <i class="fas fa-server" style="font-size: 4rem; color: rgba(139, 155, 180, 0.3); margin-bottom: 20px;"></i>
                <h3 style="font-size: 1.5rem; font-weight: 600; color: #fff; margin-bottom: 8px;">No Active Servers</h3>
                <p style="color: #8b9bb4; font-size: 0.9rem;">You don't have any active VPS servers at the moment.</p>
            </div>
        @endforelse
    </div>

    {{-- Confirmation Modal --}}
    @if ($showConfirmation)
        <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);">
            <div style="width: 100%; max-width: 400px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 28px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-exclamation-triangle" style="color: #eab308; font-size: 1.5rem;"></i>
                    <h3 style="font-size: 1.25rem; font-weight: 700; color: #fff;">{{ $confirmationTitle }}</h3>
                </div>
                <p style="color: #8b9bb4; margin-bottom: 24px; font-size: 0.9rem; line-height: 1.5;">{{ $confirmationMessage }}</p>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button wire:click="cancelAction" style="padding: 12px 24px; background: rgba(255, 255, 255, 0.05); color: #8b9bb4; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        Cancel
                    </button>
                    <button wire:click="executeConfirmedAction" style="padding: 12px 24px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        <i class="fas fa-check" style="margin-right: 6px;"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Server Details Modal --}}
    @if ($selectedServer)
        <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); padding: 20px;">
            <div style="max-height: 90vh; width: 100%; max-width: 700px; overflow-y: auto; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 32px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <div>
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: #fff; margin-bottom: 4px;">{{ $selectedServer['service']['name'] }}</h2>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: {{ $selectedServer['server']['state'] === 'running' ? 'rgba(34, 197, 94, 0.15)' : 'rgba(239, 68, 68, 0.15)' }}; color: {{ $selectedServer['server']['state'] === 'running' ? '#22c55e' : '#ef4444' }}; border: 1px solid {{ $selectedServer['server']['state'] === 'running' ? 'rgba(34, 197, 94, 0.3)' : 'rgba(239, 68, 68, 0.3)' }}; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                            <i class="fas fa-circle" style="font-size: 0.4rem;"></i> {{ ucfirst($selectedServer['server']['state']) }}
                        </span>
                    </div>
                    <button wire:click="closeServerDetails" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #8b9bb4; cursor: pointer; transition: all 0.2s;">
                        <i class="fas fa-times" style="font-size: 1.2rem;"></i>
                    </button>
                </div>

                {{-- Server Info Grid --}}
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
                    <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 16px; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="font-size: 0.75rem; color: #8b9bb4; margin-bottom: 6px; text-transform: uppercase;">Hostname</div>
                        <div style="font-size: 0.9rem; color: #fff; font-weight: 500;">{{ $selectedServer['server']['hostname'] }}</div>
                    </div>
                    <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 16px; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="font-size: 0.75rem; color: #8b9bb4; margin-bottom: 6px; text-transform: uppercase;">Operating System</div>
                        <div style="font-size: 0.9rem; color: #fff; font-weight: 500;">{{ $selectedServer['server']['os_name'] }}</div>
                    </div>
                    <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 16px; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="font-size: 0.75rem; color: #8b9bb4; margin-bottom: 6px; text-transform: uppercase;">IP Address</div>
                        <div style="font-size: 0.9rem; color: #fff; font-weight: 500; font-family: monospace;">{{ $selectedServer['server']['network']['ip'] ?? 'N/A' }}</div>
                    </div>
                    <div style="background: rgba(0, 0, 0, 0.2); border-radius: 12px; padding: 16px; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="font-size: 0.75rem; color: #8b9bb4; margin-bottom: 6px; text-transform: uppercase;">Uptime</div>
                        <div style="font-size: 0.9rem; color: #fff; font-weight: 500;">{{ $selectedServer['server']['uptime'] ?? 'N/A' }}</div>
                    </div>
                </div>

                {{-- Resource Usage Details --}}
                <div style="margin-bottom: 24px;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 16px;"><i class="fas fa-chart-bar" style="margin-right: 8px; color: #00b7ff;"></i>Resource Usage</h4>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        @foreach (['cpu', 'ram', 'disk', 'bandwidth'] as $resource)
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-size: 0.85rem; color: #8b9bb4; text-transform: uppercase;">{{ $resource }}</span>
                                    @if ($resource === 'cpu')
                                        <span style="font-size: 0.85rem; color: #fff; font-weight: 600;">{{ $selectedServer['server']['resources'][$resource]['usage_percent'] }}% <span style="color: #8b9bb4; font-weight: 400;">({{ $selectedServer['server']['resources'][$resource]['cores'] }} cores)</span></span>
                                    @elseif ($resource === 'bandwidth')
                                        <span style="font-size: 0.85rem; color: #fff; font-weight: 600;">{{ $selectedServer['server']['resources'][$resource]['used'] }} / {{ $selectedServer['server']['resources'][$resource]['total'] }}</span>
                                    @else
                                        <span style="font-size: 0.85rem; color: #fff; font-weight: 600;">{{ $selectedServer['server']['resources'][$resource]['usage_percent'] }}%</span>
                                    @endif
                                </div>
                                @if ($resource !== 'cpu')
                                    @php
                                        $percent = $selectedServer['server']['resources'][$resource]['usage_percent'];
                                        $color = $percent > 80 ? '#ef4444' : ($percent > 60 ? '#eab308' : '#00b7ff');
                                    @endphp
                                    <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                        <div style="height: 100%; background: {{ $color }}; border-radius: 4px; transition: width 0.5s ease; width: {{ min($percent, 100) }}%;"></div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Bandwidth History --}}
                @if ($selectedServer['bandwidth_history'])
                    <div style="margin-bottom: 24px;">
                        <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 16px;"><i class="fas fa-network-wired" style="margin-right: 8px; color: #8b5cf6;"></i>Bandwidth (30 Days)</h4>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                            <div style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                                <div style="font-size: 0.75rem; color: #8b5cf6; margin-bottom: 8px; text-transform: uppercase;">Inbound</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">{{ $selectedServer['bandwidth_history']['total_in'] }}</div>
                            </div>
                            <div style="background: rgba(0, 183, 255, 0.1); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                                <div style="font-size: 0.75rem; color: #00b7ff; margin-bottom: 8px; text-transform: uppercase;">Outbound</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">{{ $selectedServer['bandwidth_history']['total_out'] }}</div>
                            </div>
                            <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                                <div style="font-size: 0.75rem; color: #22c55e; margin-bottom: 8px; text-transform: uppercase;">Total</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">{{ $selectedServer['bandwidth_history']['total'] }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Domains & DNS Section --}}
                @if (!empty($selectedServer['proxmox_vm']))
                    <div style="margin-bottom: 24px; padding: 20px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px;">
                        <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 16px;">
                            <i class="fas fa-globe" style="margin-right: 8px; color: #8b5cf6;"></i>Domains & DNS
                        </h4>
                        
                        {{-- Hostname --}}
                        @if ($selectedServer['proxmox_vm']['hostname'])
                        <div style="margin-bottom: 16px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                            <div style="font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; margin-bottom: 4px;">
                                <i class="fas fa-server" style="margin-right: 4px;"></i>Hostname
                            </div>
                            <div style="font-size: 1rem; color: #fff; font-weight: 500; font-family: monospace;">
                                {{ $selectedServer['proxmox_vm']['hostname'] }}
                            </div>
                        </div>
                        @endif
                        
                        {{-- DNS Records Info --}}
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 0.8rem; color: #8b9bb4; margin-bottom: 12px;">
                                <i class="fas fa-info-circle" style="margin-right: 4px; color: #00b7ff;"></i>
                                Configure these DNS records at your domain registrar:
                            </div>
                            
                            <div style="background: rgba(0, 0, 0, 0.3); border-radius: 8px; padding: 16px; font-family: monospace; font-size: 0.85rem;">
                                {{-- A Record --}}
                                <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <div style="color: #22c55e; margin-bottom: 4px;">A Record:</div>
                                    <div style="color: #fff; display: flex; justify-content: space-between;">
                                        <span>@</span>
                                        <span style="color: #8b9bb4;">→</span>
                                        <span>{{ $selectedServer['proxmox_vm']['ip_address'] ?? $selectedServer['server']['network']['ip'] ?? 'YOUR_VPS_IP' }}</span>
                                    </div>
                                    @if ($selectedServer['proxmox_vm']['hostname'])
                                    <div style="color: #fff; display: flex; justify-content: space-between; margin-top: 4px;">
                                        <span>{{ explode('.', $selectedServer['proxmox_vm']['hostname'])[0] ?? 'www' }}</span>
                                        <span style="color: #8b9bb4;">→</span>
                                        <span>{{ $selectedServer['proxmox_vm']['ip_address'] ?? $selectedServer['server']['network']['ip'] ?? 'YOUR_VPS_IP' }}</span>
                                    </div>
                                    @endif
                                </div>
                                
                                {{-- Nameservers --}}
                                <div>
                                    <div style="color: #8b5cf6; margin-bottom: 4px;">Nameservers (NS):</div>
                                    @foreach ($selectedServer['proxmox_vm']['nameservers'] ?? ['ns1.believoo.com', 'ns2.believoo.com'] as $ns)
                                        <div style="color: #fff;">{{ $ns }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        {{-- Panel Access --}}
                        @if ($selectedServer['proxmox_vm']['panel_login_url'])
                        <div style="padding: 12px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 8px;">
                            <div style="font-size: 0.75rem; color: #f59e0b; text-transform: uppercase; margin-bottom: 8px;">
                                <i class="fas fa-desktop" style="margin-right: 4px;"></i>Panel Access
                                @if ($selectedServer['proxmox_vm']['panel_status'] === 'installing')
                                    <span style="margin-left: 8px; padding: 2px 6px; background: rgba(245, 158, 11, 0.3); color: #f59e0b; border-radius: 4px; font-size: 0.65rem;">Installing...</span>
                                @elseif ($selectedServer['proxmox_vm']['panel_status'] === 'installed')
                                    <span style="margin-left: 8px; padding: 2px 6px; background: rgba(34, 197, 94, 0.3); color: #22c55e; border-radius: 4px; font-size: 0.65rem;">Ready</span>
                                @endif
                            </div>
                            <a href="{{ $selectedServer['proxmox_vm']['panel_login_url'] }}" target="_blank" 
                               style="font-size: 0.9rem; color: #00b7ff; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                                {{ $selectedServer['proxmox_vm']['panel_login_url'] }}
                                <i class="fas fa-external-link-alt" style="font-size: 0.75rem;"></i>
                            </a>
                            @if ($selectedServer['proxmox_vm']['panel_username'])
                            <div style="margin-top: 8px; font-size: 0.8rem; color: #8b9bb4;">
                                Login: <span style="color: #fff; font-family: monospace;">{{ $selectedServer['proxmox_vm']['panel_username'] }}</span>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                @endif

                {{-- Quick Actions --}}
                <div style="display: flex; gap: 12px; flex-wrap: wrap; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.08);">
                    @if ($selectedServer['actions']['can_start'])
                        <button wire:click="executeAction('start', {{ $selectedServer['server']['vps_id'] }})"
                                style="padding: 12px 24px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-play"></i> Start
                        </button>
                    @endif
                    @if ($selectedServer['actions']['can_stop'])
                        <button wire:click="executeAction('stop', {{ $selectedServer['server']['vps_id'] }})"
                                style="padding: 12px 24px; background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-stop"></i> Stop
                        </button>
                    @endif
                    @if ($selectedServer['actions']['can_restart'])
                        <button wire:click="executeAction('restart', {{ $selectedServer['server']['vps_id'] }})"
                                style="padding: 12px 24px; background: rgba(0, 183, 255, 0.15); color: #00b7ff; border: 1px solid rgba(0, 183, 255, 0.3); border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-sync-alt"></i> Restart
                        </button>
                    @endif
                    <button wire:click="refreshBandwidth({{ $selectedServer['server']['vps_id'] }})"
                            style="margin-left: auto; padding: 12px 24px; background: rgba(255, 255, 255, 0.05); color: #8b9bb4; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-redo"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
