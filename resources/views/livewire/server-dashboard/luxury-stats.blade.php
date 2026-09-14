<div x-data="{ autoRefresh: true }" x-init="setInterval(() => { if(autoRefresh) $wire.dispatch('refreshStats') }, 5000)">
    <style>
        .luxury-stats-container {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 32px;
            position: relative;
            overflow: hidden;
        }
        .luxury-stats-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 183, 255, 0.5), transparent);
        }
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        .stats-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 20px;
        }
        .live-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #00b7ff, #7000ff);
            opacity: 0.5;
        }
        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #8b9bb4;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }
        .stat-subvalue {
            font-size: 0.85rem;
            color: #6b7280;
        }
        .chart-container {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .chart-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chart-canvas {
            width: 100%;
            height: 200px;
        }
        .power-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 24px;
        }
        .power-btn {
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .power-btn.start {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        .power-btn.restart {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .power-btn.stop {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .power-btn.snapshot {
            background: linear-gradient(135deg, #00b7ff, #0066cc);
            color: white;
        }
        .power-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .power-actions { grid-template-columns: repeat(2, 1fr); }
        }
    </style>

    <div class="luxury-stats-container">
        {{-- Header with Live Indicator --}}
        <div class="stats-header">
            <div class="stats-title">
                <i class="fas fa-server" style="color: #00b7ff;"></i>
                @if($vm)
                    {{ $vm->name ?? 'VPS-' . $vm->vmid }}
                @else
                    No Active VPS
                @endif
            </div>
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span style="font-size: 0.75rem; font-weight: 600; color: #22c55e;">LIVE</span>
            </div>
        </div>

        @if($isLoading)
            <div style="text-align: center; padding: 60px; color: #8b9bb4;">
                <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; margin-bottom: 16px; color: #00b7ff;"></i>
                <p>Loading real-time stats from Proxmox...</p>
            </div>
        @elseif(!$vm)
            <div style="text-align: center; padding: 60px; color: #8b9bb4;">
                <i class="fas fa-server" style="font-size: 3rem; margin-bottom: 16px; color: #4b5563;"></i>
                <p>No VPS found. Create one to see live stats!</p>
            </div>
        @else
            {{-- Stats Grid --}}
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">CPU Usage</div>
                    <div class="stat-value" style="color: #00b7ff;">{{ $stats['cpu_percent'] ?? 0 }}%</div>
                    <div class="stat-subvalue">{{ $stats['cpu'] ?? 0 }} cores active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Memory</div>
                    <div class="stat-value" style="color: #8b5cf6;">{{ $stats['memory_percent'] ?? 0 }}%</div>
                    <div class="stat-subvalue">{{ $stats['memory_used'] ?? '0' }} / {{ $stats['memory_total'] ?? '0' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Disk</div>
                    <div class="stat-value" style="color: #f59e0b;">{{ $stats['disk_percent'] ?? 0 }}%</div>
                    <div class="stat-subvalue">{{ $stats['disk_used'] ?? '0' }} / {{ $stats['disk_total'] ?? '0' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Uptime</div>
                    <div class="stat-value" style="color: #22c55e;">{{ $stats['uptime'] ?? '0d 0h' }}</div>
                    <div class="stat-subvalue">Status: {{ ucfirst($stats['status'] ?? 'unknown') }}</div>
                </div>
            </div>

            {{-- Area Charts --}}
            <div class="chart-container">
                <div class="chart-title">
                    <i class="fas fa-chart-area" style="color: #00b7ff;"></i>
                    Real-time Performance (Last 2 Minutes)
                </div>
                <canvas id="luxuryChart" class="chart-canvas"></canvas>
            </div>

            {{-- Quick Actions --}}
            <div class="power-actions">
                <button class="power-btn start" onclick="vmAction('start')">
                    <i class="fas fa-play"></i>
                    Start
                </button>
                <button class="power-btn restart" onclick="vmAction('restart')">
                    <i class="fas fa-redo"></i>
                    Restart
                </button>
                <button class="power-btn stop" onclick="vmAction('stop')">
                    <i class="fas fa-stop"></i>
                    Stop
                </button>
                <button class="power-btn snapshot" onclick="createSnapshot()">
                    <i class="fas fa-camera"></i>
                    Snapshot
                </button>
            </div>
        @endif
    </div>

    {{-- Chart.js for Area Charts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let chart = null;
        
        function initChart() {
            const ctx = document.getElementById('luxuryChart');
            if (!ctx) return;
            
            const chartData = @json($chartData);
            const labels = chartData.map(d => d.time);
            const cpuData = chartData.map(d => d.cpu);
            const memoryData = chartData.map(d => d.memory);
            
            if (chart) {
                chart.destroy();
            }
            
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'CPU %',
                        data: cpuData,
                        borderColor: '#00b7ff',
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                            gradient.addColorStop(0, 'rgba(0, 183, 255, 0.4)');
                            gradient.addColorStop(1, 'rgba(0, 183, 255, 0.0)');
                            return gradient;
                        },
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        borderWidth: 2,
                    }, {
                        label: 'Memory %',
                        data: memoryData,
                        borderColor: '#8b5cf6',
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                            gradient.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
                            gradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)');
                            return gradient;
                        },
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                color: '#8b9bb4',
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            min: 0,
                            max: 100,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)'
                            },
                            ticks: {
                                color: '#8b9bb4',
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 0
                    }
                }
            });
        }
        
        // Initialize chart after Livewire loads
        document.addEventListener('livewire:initialized', () => {
            setTimeout(initChart, 100);
        });
        
        // Re-init on Livewire updates
        Livewire.on('refreshStats', () => {
            setTimeout(initChart, 100);
        });
        
        function vmAction(action) {
            if (!confirm('Are you sure you want to ' + action + ' this VPS?')) return;
            
            // Dispatch to parent component
            Livewire.dispatch('vmAction', { action: action, vmId: {{ $vm->id ?? 'null' }} });
        }
        
        function createSnapshot() {
            if (!confirm('Create a snapshot backup before migration?\n\nThis will save the current state of your VPS.')) return;
            
            Livewire.dispatch('createSnapshot', { vmId: {{ $vm->id ?? 'null' }} });
        }
    </script>
</div>
