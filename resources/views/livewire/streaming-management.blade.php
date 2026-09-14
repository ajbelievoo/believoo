<div wire:poll.30000ms="loadMetrics" class="pt-20 pb-10 min-h-screen" style="background:#0a0a1a;padding:2rem 1rem;">
    <style>
        .streaming-card {
            background: rgba(17, 17, 43, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 212, 255, 0.1);
            border-radius: 16px;
        }
        .streaming-btn {
            background: linear-gradient(135deg, #00d4ff 0%, #0891b2 100%);
            color: #000;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .streaming-btn:hover {
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
        }
        .streaming-text-cyan { color: #00d4ff; }
        .streaming-gradient-text {
            background: linear-gradient(135deg, #00d4ff 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>

    <div style="max-width:1200px;margin:0 auto;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 style="font-size:1.8rem;font-weight:900;color:#fff;margin:0;">
                    <span class="streaming-gradient-text">Live Stream</span> Dashboard
                </h1>
                <p style="color:#64748b;margin:0.25rem 0 0;font-size:0.9rem;">Manage your streaming projects and credentials</p>
            </div>
            @if($subscription)
            <button wire:click="$set('showNewProjectForm', true)"
                    class="streaming-btn px-5 py-2.5 rounded-xl text-sm font-bold"
                    aria-label="Create new streaming project">
                + New Project
            </button>
            @endif
        </div>

        {{-- No subscription CTA --}}
        @if(!$subscription)
        <div class="streaming-card" style="border-radius:16px;padding:3rem;text-align:center;">
            <div style="font-size:3rem;margin-bottom:1rem;">📡</div>
            <h2 style="color:#fff;font-size:1.5rem;font-weight:700;margin-bottom:0.75rem;">No Active Streaming Plan</h2>
            <p style="color:#64748b;margin-bottom:1.5rem;">Purchase a streaming plan to get your AppID, AppCertificate, and start streaming.</p>
            <a href="{{ route('services.streaming') }}"
               class="streaming-btn inline-block px-6 py-3 rounded-xl font-bold no-underline">
                View Streaming Plans →
            </a>
        </div>
        @else

        {{-- Amber Alerts (90%+ bandwidth) --}}
        @foreach($projects as $project)
            @if(($projectMetrics[$project['id']]['percentage'] ?? 0) >= 90)
                <x-streaming.amber-alert
                    :percentage="$projectMetrics[$project['id']]['percentage']"
                    :projectName="$project['name']"
                />
            @endif
        @endforeach

        {{-- Plan info bar with delivery method --}}
        <div class="streaming-card" style="border-radius:12px;padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div>
                <span style="color:#64748b;font-size:0.85rem;">Active Plan:</span>
                <span style="color:#00f5ff;font-weight:700;">{{ $subscription->plan->name ?? 'Unknown' }}</span>
                @if($endpoints['host_type'] ?? null)
                    <span style="color:#8b5cf6;font-size:0.85rem;margin-left:0.5rem;">
                        ({{ $endpoints['host_type'] }})
                    </span>
                @endif
            </div>
            <div style="margin-left:auto;display:flex;align-items:center;gap:1rem;">
                <span style="color:#64748b;font-size:0.85rem;">
                    {{ $subscription->plan->max_viewers ?? '—' }} max viewers
                </span>
                <span style="color:#64748b;font-size:0.85rem;">
                    {{ $subscription->plan->bandwidth_gb ?? '—' }}GB/mo bandwidth
                </span>
                @if($endpoints['host_info'] ?? null)
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="color:#64748b;font-size:0.85rem;">Host:</span>
                        <span style="color:#10b981;font-weight:600;font-size:0.85rem;">{{ $endpoints['host_info'] }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Endpoints Information --}}
        @if($endpoints && !empty($endpoints))
        <div class="streaming-card" style="border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
            <h3 style="color:#fff;font-size:1.1rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
                <span>🌐</span> Streaming Endpoints
            </h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;">
                <div style="background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.2);border-radius:8px;padding:1rem;">
                    <div style="color:#00d4ff;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem;">RTMP URL</div>
                    <div style="color:#fff;font-family:monospace;font-size:0.9rem;word-break:break-all;">{{ $endpoints['rtmp_url'] }}</div>
                </div>
                <div style="background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.2);border-radius:8px;padding:1rem;">
                    <div style="color:#8b5cf6;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem;">WebRTC URL</div>
                    <div style="color:#fff;font-family:monospace;font-size:0.9rem;word-break:break-all;">{{ $endpoints['webrtc_url'] }}</div>
                </div>
                <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);border-radius:8px;padding:1rem;">
                    <div style="color:#10b981;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem;">HLS URL</div>
                    <div style="color:#fff;font-family:monospace;font-size:0.9rem;word-break:break-all;">{{ $endpoints['hls_url'] }}</div>
                </div>
                <div style="background:rgba(251,146,60,0.1);border:1px solid rgba(251,146,60,0.2);border-radius:8px;padding:1rem;">
                    <div style="color:#fb923c;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem;">API URL</div>
                    <div style="color:#fff;font-family:monospace;font-size:0.9rem;word-break:break-all;">{{ $endpoints['api_url'] }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Search & Filter --}}
        <div x-data="{
            search: '',
            statusFilter: 'all',
            matches(name, appId, status) {
                const q = this.search.toLowerCase();
                const nameMatch  = name.toLowerCase().includes(q);
                const appIdMatch = appId.toLowerCase().includes(q);
                const statusMatch = this.statusFilter === 'all' || status === this.statusFilter;
                return (nameMatch || appIdMatch) && statusMatch;
            }
        }">
            <div style="display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                <input x-model="search" type="text" placeholder="Search by name or AppID..."
                       aria-label="Search streaming projects"
                       style="flex:1;min-width:200px;padding:0.6rem 1rem;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#fff;font-size:0.9rem;outline:none;" />
                <select x-model="statusFilter" aria-label="Filter by status"
                        style="padding:0.6rem 1rem;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;font-size:0.9rem;outline:none;">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            {{-- Project Cards --}}
            @forelse($projects as $project)
            <div
                x-show="matches('{{ addslashes($project['name']) }}', '{{ $project['app_id'] }}', '{{ $project['status'] }}')"
                class="streaming-card hover:border-cyan-500/30 transition-all"
                style="border-radius:16px;padding:1.5rem;margin-bottom:1.5rem;position:relative;overflow:hidden;"
            >
                {{-- Status accent line --}}
                <div style="position:absolute;top:0;left:0;right:0;height:2px;background:{{ $project['status'] === 'active' ? 'linear-gradient(90deg,#00f5ff,#7000ff)' : 'linear-gradient(90deg,#ff4444,#ff8800)' }};"></div>

                {{-- Project header --}}
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:0.75rem;">
                    <div>
                        <h3 style="color:#fff;font-size:1.1rem;font-weight:700;margin:0 0 0.25rem;">{{ $project['name'] }}</h3>
                        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                            <x-dashboard.status-pill :status="$project['status']" />
                            <span style="color:#64748b;font-size:0.8rem;">{{ $project['region'] === 'in' ? '🇮🇳 India' : '🌐 Global' }}</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <button wire:click="confirmRegenerate({{ $project['id'] }})"
                                style="padding:0.4rem 0.9rem;border-radius:8px;background:rgba(255,179,0,0.1);border:1px solid rgba(255,179,0,0.3);color:#ffb300;font-size:0.8rem;font-weight:600;cursor:pointer;"
                                aria-label="Regenerate keys for {{ $project['name'] }}">
                            🔄 Regen Keys
                        </button>
                        <button wire:click="confirmDelete({{ $project['id'] }})"
                                style="padding:0.4rem 0.9rem;border-radius:8px;background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;font-size:0.8rem;font-weight:600;cursor:pointer;"
                                aria-label="Delete project {{ $project['name'] }}">
                            🗑 Delete
                        </button>
                    </div>
                </div>

                {{-- Credentials grid --}}
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:0.75rem;margin-bottom:1.25rem;">

                    {{-- AppID --}}
                    <div style="background:rgba(0,0,0,0.3);border-radius:10px;padding:0.75rem 1rem;">
                        <div style="color:#64748b;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.35rem;">App ID</div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <code style="color:#00f5ff;font-size:0.85rem;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $project['app_id'] }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $project['app_id'] }}')"
                                    style="background:none;border:none;color:#64748b;cursor:pointer;padding:0.2rem;"
                                    aria-label="Copy App ID">📋</button>
                        </div>
                    </div>

                    {{-- AppCertificate --}}
                    @php
                        $proj = \App\Models\StreamingProject::find($project['id']);
                        $maskedCert = $proj ? $proj->getMaskedCertificate() : '••••••••••••••••';
                    @endphp
                    <div style="background:rgba(0,0,0,0.3);border-radius:10px;padding:0.75rem 1rem;">
                        <div style="color:#64748b;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.35rem;">App Certificate</div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <code style="color:#94a3b8;font-size:0.85rem;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $maskedCert }}</code>
                            <button wire:click="$dispatch('copy-cert-{{ $project['id'] }}')"
                                    style="background:none;border:none;color:#64748b;cursor:pointer;padding:0.2rem;"
                                    aria-label="Copy App Certificate">📋</button>
                        </div>
                    </div>

                    {{-- REST API Key --}}
                    @php $maskedKey = $proj ? $proj->getMaskedRestApiKey() : '••••••••••••••••'; @endphp
                    <div style="background:rgba(0,0,0,0.3);border-radius:10px;padding:0.75rem 1rem;">
                        <div style="color:#64748b;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.35rem;">REST API Key</div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <code style="color:#94a3b8;font-size:0.85rem;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $maskedKey }}</code>
                            <button style="background:none;border:none;color:#64748b;cursor:pointer;padding:0.2rem;"
                                    aria-label="Copy REST API Key">📋</button>
                        </div>
                    </div>

                    {{-- RTMP URL --}}
                    <div style="background:rgba(0,0,0,0.3);border-radius:10px;padding:0.75rem 1rem;">
                        <div style="color:#64748b;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.35rem;">RTMP Ingest URL</div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <code style="color:#7000ff;font-size:0.8rem;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $project['rtmp_url'] }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $project['rtmp_url'] }}')"
                                    style="background:none;border:none;color:#64748b;cursor:pointer;padding:0.2rem;"
                                    aria-label="Copy RTMP URL">📋</button>
                        </div>
                    </div>

                    {{-- WebRTC URL --}}
                    <div style="background:rgba(0,0,0,0.3);border-radius:10px;padding:0.75rem 1rem;">
                        <div style="color:#64748b;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.35rem;">WebRTC URL</div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <code style="color:#7000ff;font-size:0.8rem;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $project['webrtc_url'] }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $project['webrtc_url'] }}')"
                                    style="background:none;border:none;color:#64748b;cursor:pointer;padding:0.2rem;"
                                    aria-label="Copy WebRTC URL">📋</button>
                        </div>
                    </div>

                </div>

                {{-- Metrics row --}}
                @php
                    $pct = $projectMetrics[$project['id']]['percentage'] ?? 0;
                    $bwGb = $projectMetrics[$project['id']]['bandwidth_gb'] ?? 0;
                    $barColor = $pct >= 100 ? '#ff4444' : ($pct >= 80 ? '#ffb300' : '#00f5ff');
                    $planLimit = $subscription->plan->bandwidth_limit_gb ?? 1;
                @endphp
                <div style="margin-bottom:1.25rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.4rem;">
                        <span style="color:#64748b;font-size:0.8rem;">Bandwidth Used This Month</span>
                        <span style="color:{{ $barColor }};font-size:0.85rem;font-weight:700;">{{ number_format($bwGb, 2) }}GB / {{ $planLimit }}GB ({{ $pct }}%)</span>
                    </div>
                    <div style="height:6px;background:rgba(255,255,255,0.08);border-radius:999px;overflow:hidden;">
                        <div style="height:100%;width:{{ min(100,$pct) }}%;background:{{ $barColor }};border-radius:999px;box-shadow:0 0 8px {{ $barColor }};transition:width 0.5s ease;"></div>
                    </div>
                    @if($pct >= 100)
                        <p style="color:#ff4444;font-size:0.8rem;margin-top:0.4rem;">⛔ Bandwidth limit reached. Stream suspended.</p>
                    @elseif($pct >= 80)
                        <p style="color:#ffb300;font-size:0.8rem;margin-top:0.4rem;">⚠️ Approaching bandwidth limit.</p>
                    @endif
                </div>

                {{-- 7-Day Neon Chart --}}
                <div style="margin-bottom:1.25rem;"
                     x-data="{
                         chart: null,
                         metric: 'bandwidth_used_gb',
                         initChart(labels, values) {
                             if (this.chart) this.chart.destroy();
                             const canvas = this.$refs.canvas;
                             const ctx = canvas.getContext('2d');
                             const gradient = ctx.createLinearGradient(0, 0, 0, 120);
                             gradient.addColorStop(0, 'rgba(0,245,255,0.35)');
                             gradient.addColorStop(1, 'rgba(0,245,255,0.00)');
                             this.chart = new Chart(canvas, {
                                 type: 'line',
                                 data: {
                                     labels: labels,
                                     datasets: [{
                                         data: values,
                                         borderColor: '#00f5ff',
                                         backgroundColor: gradient,
                                         tension: 0.4,
                                         fill: true,
                                         pointRadius: 3,
                                         pointBackgroundColor: '#00f5ff',
                                         borderWidth: 2,
                                     }]
                                 },
                                 options: {
                                     responsive: true,
                                     plugins: { legend: { display: false } },
                                     scales: {
                                         x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 10 } } },
                                         y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 10 } }, beginAtZero: true }
                                     }
                                 }
                             });
                         }
                     }"
                     x-init="
                         $wire.loadChartData({{ $project['id'] }}, metric);
                         $wire.on('chartDataUpdated', (event) => {
                             if (event.projectId == {{ $project['id'] }}) {
                                 initChart(event.data.labels, event.data.values);
                             }
                         });
                     "
                >
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="color:#64748b;font-size:0.8rem;font-weight:600;">7-Day Usage</span>
                        <div style="display:flex;gap:0.4rem;">
                            <button @click="metric='bandwidth_used_gb'; $wire.loadChartData({{ $project['id'] }}, metric)"
                                    :style="metric==='bandwidth_used_gb' ? 'background:rgba(0,245,255,0.15);border-color:rgba(0,245,255,0.4);color:#00f5ff;' : 'background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);color:#64748b;'"
                                    style="padding:0.2rem 0.6rem;border-radius:6px;border:1px solid;font-size:0.75rem;cursor:pointer;transition:all 0.2s;">
                                Bandwidth
                            </button>
                            <button @click="metric='active_stream_minutes'; $wire.loadChartData({{ $project['id'] }}, metric)"
                                    :style="metric==='active_stream_minutes' ? 'background:rgba(0,245,255,0.15);border-color:rgba(0,245,255,0.4);color:#00f5ff;' : 'background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);color:#64748b;'"
                                    style="padding:0.2rem 0.6rem;border-radius:6px;border:1px solid;font-size:0.75rem;cursor:pointer;transition:all 0.2s;">
                                Minutes
                            </button>
                        </div>
                    </div>
                    <canvas x-ref="canvas" height="100" style="width:100%;"></canvas>
                </div>

                {{-- Token Generator --}}
                <div style="background:rgba(0,0,0,0.2);border-radius:10px;padding:1rem;border:1px solid rgba(255,255,255,0.06);">
                    <div style="color:#64748b;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.6rem;">🔑 Token Generator</div>
                    @if(isset($generatedTokens[$project['id']]))
                        @php
                            $tokenData = $generatedTokens[$project['id']];
                            $expired = time() > $tokenData['expires_at'];
                        @endphp
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                            <input type="text" readonly value="{{ $tokenData['token'] }}"
                                   style="flex:1;background:rgba(0,0,0,0.3);border:1px solid rgba(0,245,255,0.2);border-radius:8px;padding:0.4rem 0.75rem;color:#94a3b8;font-size:0.75rem;font-family:monospace;outline:none;"
                                   aria-label="Generated stream token" />
                            <button onclick="navigator.clipboard.writeText('{{ $tokenData['token'] }}')"
                                    style="background:none;border:none;color:#64748b;cursor:pointer;padding:0.2rem;"
                                    aria-label="Copy token">📋</button>
                        </div>
                        @if($expired)
                            <span style="color:#ff4444;font-size:0.75rem;">⏰ Token expired</span>
                        @else
                            <span style="color:#00f5ff;font-size:0.75rem;">✅ Valid until {{ date('H:i', $tokenData['expires_at']) }}</span>
                        @endif
                    @endif
                    <button wire:click="generateToken({{ $project['id'] }})"
                            style="margin-top:0.5rem;padding:0.4rem 1rem;border-radius:8px;background:rgba(0,245,255,0.08);border:1px solid rgba(0,245,255,0.2);color:#00f5ff;font-size:0.8rem;font-weight:600;cursor:pointer;"
                            aria-label="Generate stream token for {{ $project['name'] }}">
                        Generate Token (1hr)
                    </button>
                </div>

            </div>
            @empty
            <div class="streaming-card" style="border-radius:16px;padding:2rem;text-align:center;">
                <p style="color:#64748b;">No projects yet. Click <strong style="color:#00f5ff;">+ New Project</strong> to create one.</p>
            </div>
            @endforelse

        </div>{{-- end x-data search --}}
        @endif

        {{-- New Project Modal --}}
        @if($showNewProjectForm)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:50;display:flex;align-items:center;justify-content:center;padding:1rem;">
            <div class="streaming-card" style="border-radius:16px;padding:2rem;width:100%;max-width:480px;position:relative;">
                <h3 style="color:#fff;font-size:1.2rem;font-weight:700;margin:0 0 1.25rem;">Create New Project</h3>

                <div style="margin-bottom:1rem;">
                    <label style="color:#94a3b8;font-size:0.85rem;display:block;margin-bottom:0.4rem;">Project Name</label>
                    <input wire:model="newProjectName" type="text" placeholder="my-stream-project"
                           style="width:100%;padding:0.6rem 1rem;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#fff;font-size:0.9rem;outline:none;box-sizing:border-box;"
                           aria-label="New project name" />
                    @if($newProjectError)
                        <p style="color:#ff4444;font-size:0.8rem;margin-top:0.35rem;">{{ $newProjectError }}</p>
                    @endif
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="color:#94a3b8;font-size:0.85rem;display:block;margin-bottom:0.4rem;">Region</label>
                    <select wire:model="newProjectRegion"
                            style="width:100%;padding:0.6rem 1rem;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;font-size:0.9rem;outline:none;"
                            aria-label="Select region">
                        <option value="global">🌐 Global</option>
                        <option value="in">🇮🇳 India</option>
                    </select>
                </div>

                <div style="display:flex;gap:0.75rem;">
                    <button wire:click="createProject"
                            style="flex:1;padding:0.65rem;border-radius:10px;background:linear-gradient(135deg,rgba(0,245,255,0.2),rgba(112,0,255,0.2));border:1px solid rgba(0,245,255,0.4);color:#00f5ff;font-weight:700;cursor:pointer;">
                        Create Project
                    </button>
                    <button wire:click="$set('showNewProjectForm', false)"
                            style="padding:0.65rem 1.25rem;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#64748b;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Regenerate Keys Modal --}}
        @if($showRegenerateModal)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:50;display:flex;align-items:center;justify-content:center;padding:1rem;">
            <div class="streaming-card" style="border-radius:16px;padding:2rem;width:100%;max-width:440px;">
                <h3 style="color:#ffb300;font-size:1.1rem;font-weight:700;margin:0 0 0.75rem;">⚠️ Regenerate Keys?</h3>
                <p style="color:#94a3b8;font-size:0.9rem;margin-bottom:1.5rem;">
                    This will generate a new AppCertificate and REST API Key. <strong style="color:#fff;">Existing integrations will stop working</strong> until updated. The AppID will remain unchanged.
                </p>
                <div style="display:flex;gap:0.75rem;">
                    <button wire:click="executeRegenerate"
                            style="flex:1;padding:0.65rem;border-radius:10px;background:rgba(255,179,0,0.15);border:1px solid rgba(255,179,0,0.4);color:#ffb300;font-weight:700;cursor:pointer;">
                        Yes, Regenerate
                    </button>
                    <button wire:click="$set('showRegenerateModal', false)"
                            style="padding:0.65rem 1.25rem;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#64748b;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Delete Project Modal --}}
        @if($showDeleteModal)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:50;display:flex;align-items:center;justify-content:center;padding:1rem;">
            <div class="streaming-card" style="border-radius:16px;padding:2rem;width:100%;max-width:440px;">
                <h3 style="color:#ff4444;font-size:1.1rem;font-weight:700;margin:0 0 0.75rem;">🗑 Delete Project?</h3>
                <p style="color:#94a3b8;font-size:0.9rem;margin-bottom:1.5rem;">
                    This project will be soft-deleted. All credentials will become inactive. This action can be reversed by an administrator.
                </p>
                <div style="display:flex;gap:0.75rem;">
                    <button wire:click="executeDelete"
                            style="flex:1;padding:0.65rem;border-radius:10px;background:rgba(255,68,68,0.15);border:1px solid rgba(255,68,68,0.4);color:#ff4444;font-weight:700;cursor:pointer;">
                        Delete Project
                    </button>
                    <button wire:click="$set('showDeleteModal', false)"
                            style="padding:0.65rem 1.25rem;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#64748b;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
