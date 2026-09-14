{{-- Streaming Management Tab - Glassmorphic UI --}}
<div class="streaming-tab-wrapper" wire:poll.10s="refreshStreamingMetrics">
@if($streamingApiKeys && $streamingApiKeys->count() > 0)
<div class="animate-in fade-in slide-in-from-bottom-4 duration-500" x-data="{ 
    activeStreamKey: {{ $streamingApiKeys->first()->id ?? 'null' }},
    showRegenerateConfirm: false,
    regenerateKeyId: null,
    copiedField: null,
    showTokenGenerator: false,
    tokenChannel: '',
    tokenUid: Math.floor(Math.random() * 100000),
    tokenExpiry: 3600,
    generatedToken: ''
}">
    
    {{-- Header Section --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-white uppercase tracking-tight flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center border border-purple-500/30">
                        <i class="fas fa-broadcast-tower text-purple-400"></i>
                    </div>
                    BelieVoo <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-pink-400 to-red-400">Live Engine</span>
                </h2>
                <p class="text-gray-400 text-sm mt-2">Manage your streaming credentials, monitor usage, and access playback URLs</p>
                
                {{-- Delivery Method Badge --}}
                @if(isset($streamingSubscription))
                    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold" 
                         style="
                             @if($streamingSubscription->delivery_method === 'vps_embedded')
                                 background: rgba(34, 197, 94, 0.2); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);
                             @else
                                 background: rgba(139, 92, 246, 0.2); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3);
                             @endif
                         ">
                        <i class="fas @if($streamingSubscription->delivery_method === 'vps_embedded') fa-server @else fa-cloud @endif"></i>
                        @if($streamingSubscription->delivery_method === 'vps_embedded')
                            VPS-Embedded
                        @else
                            Cloud-Hosted
                        @endif
                    </div>
                @endif
            </div>
            
            <div class="flex items-center gap-3">
                <button 
                    @click="showTokenGenerator = true"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-purple-500/20 to-pink-500/20 text-purple-300 border border-purple-500/30 font-bold uppercase tracking-wider text-xs hover:bg-purple-500/30 transition-all flex items-center gap-2">
                    <i class="fas fa-key"></i> Generate Token
                </button>
                <a 
                    href="/docs/streaming"
                    target="_blank"
                    class="px-4 py-2 rounded-xl bg-white/5 text-gray-300 border border-white/10 font-bold uppercase tracking-wider text-xs hover:bg-white/10 transition-all flex items-center gap-2">
                    <i class="fas fa-book"></i> API Docs
                </a>
            </div>
        </div>
    </div>

    {{-- Usage Statistics Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        @php
            $totalBandwidth = $streamingApiKeys->sum('bandwidth_used_gb');
            $totalStorage = $streamingApiKeys->sum('storage_used_gb');
            $totalMinutes = $streamingApiKeys->sum('total_stream_minutes');
            $activeStreams = $streamingApiKeys->sum('current_viewers');
        @endphp
        
        {{-- Bandwidth Card --}}
        <div class="glass-premium rounded-2xl p-5 border border-white/10 relative overflow-hidden group hover:border-purple-500/30 transition-all">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center">
                        <i class="fas fa-tachometer-alt text-purple-400"></i>
                    </div>
                    <span class="text-xs font-bold text-purple-400 uppercase tracking-wider">This Month</span>
                </div>
                @if($totalBandwidth < 1)
                    <p class="text-2xl font-black text-white">{{ number_format($totalBandwidth * 1024, 1) }} <span class="text-sm font-semibold text-gray-400">MB</span></p>
                @else
                    <p class="text-2xl font-black text-white">{{ number_format($totalBandwidth, 2) }} <span class="text-sm font-semibold text-gray-400">GB</span></p>
                @endif
                <p class="text-xs text-gray-500 mt-1">Bandwidth Used</p>
            </div>
        </div>

        {{-- Storage Card --}}
        <div class="glass-premium rounded-2xl p-5 border border-white/10 relative overflow-hidden group hover:border-pink-500/30 transition-all">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-pink-500/10 rounded-full blur-2xl group-hover:bg-pink-500/20 transition-all"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-pink-500/20 flex items-center justify-center">
                        <i class="fas fa-hdd text-pink-400"></i>
                    </div>
                    <span class="text-xs font-bold text-pink-400 uppercase tracking-wider">Storage</span>
                </div>
                @if($totalStorage < 1)
                    <p class="text-2xl font-black text-white">{{ number_format($totalStorage * 1024, 1) }} <span class="text-sm font-semibold text-gray-400">MB</span></p>
                @else
                    <p class="text-2xl font-black text-white">{{ number_format($totalStorage, 2) }} <span class="text-sm font-semibold text-gray-400">GB</span></p>
                @endif
                <p class="text-xs text-gray-500 mt-1">Recording Storage</p>
            </div>
        </div>

        {{-- Stream Time Card --}}
        <div class="glass-premium rounded-2xl p-5 border border-white/10 relative overflow-hidden group hover:border-cyan-500/30 transition-all">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-cyan-500/10 rounded-full blur-2xl group-hover:bg-cyan-500/20 transition-all"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/20 flex items-center justify-center">
                        <i class="fas fa-clock text-cyan-400"></i>
                    </div>
                    <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Total</span>
                </div>
                @if($totalMinutes < 60)
                    <p class="text-2xl font-black text-white">{{ $totalMinutes }} <span class="text-sm font-semibold text-gray-400">mins</span></p>
                @else
                    <p class="text-2xl font-black text-white">{{ floor($totalMinutes / 60) }} <span class="text-sm font-semibold text-gray-400">hrs</span></p>
                @endif
                <p class="text-xs text-gray-500 mt-1">Streaming Time</p>
            </div>
        </div>

        {{-- Active Viewers Card --}}
        <div class="glass-premium rounded-2xl p-5 border border-white/10 relative overflow-hidden group hover:border-green-500/30 transition-all">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-500/10 rounded-full blur-2xl group-hover:bg-green-500/20 transition-all"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-green-500/20 flex items-center justify-center">
                        <i class="fas fa-users text-green-400"></i>
                    </div>
                    @if($activeStreams > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-500/20 text-green-400">
                            <span class="w-1.5 h-1.5 mr-1 rounded-full bg-green-400 animate-pulse"></span>
                            LIVE
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-500/20 text-gray-400">
                            <span class="w-1.5 h-1.5 mr-1 rounded-full bg-gray-400"></span>
                            OFFLINE
                        </span>
                    @endif
                </div>
                <p class="text-2xl font-black text-white">{{ $activeStreams }}</p>
                <p class="text-xs text-gray-500 mt-1">Current Viewers</p>
            </div>
        </div>
    </div>

    {{-- Recording Settings --}}
    @php
        $streamingHostings = \App\Models\UserHosting::where('user_id', Auth::id())
            ->where('has_streaming_addon', true)
            ->get();
    @endphp
    @if($streamingHostings->count() > 0)
    <div class="mb-8">
        <h3 class="text-sm font-black uppercase tracking-wider text-gray-400 mb-4 flex items-center gap-2">
            <i class="fas fa-video"></i> Recording Settings
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($streamingHostings as $hosting)
            <div class="glass rounded-2xl p-4 border border-white/10">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-server text-purple-400 text-xs"></i>
                        <span class="text-sm font-bold text-white">{{ $hosting->server_hostname ?? $hosting->plan_name }}</span>
                    </div>
                    <button
                        wire:click="toggleRecordingForHosting({{ $hosting->id }})"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $hosting->recording_enabled ? 'bg-orange-500' : 'bg-gray-600' }}">
                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $hosting->recording_enabled ? 'translate-x-5' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                <p class="text-[10px] text-gray-500">
                    {{ $hosting->recording_enabled ? 'Recording ON - All streams saved for 30 days' : 'Recording OFF - Streams not saved' }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- API Keys Management --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Left: API Key Selector --}}
        <div class="lg:col-span-1 space-y-4">
            <h3 class="text-sm font-black uppercase tracking-wider text-gray-400 mb-4 flex items-center gap-2">
                <i class="fas fa-layer-group"></i> Your Stream Projects
            </h3>
            
            @foreach($streamingApiKeys as $apiKey)
            <div 
                @click="activeStreamKey = {{ $apiKey->id }}"
                class="cursor-pointer rounded-2xl p-4 border transition-all"
                :class="activeStreamKey === {{ $apiKey->id }} ? 'bg-gradient-to-r from-purple-500/20 to-pink-500/20 border-purple-500/50' : 'bg-white/5 border-white/10 hover:border-white/30'">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="font-bold text-white">{{ $apiKey->plan->name ?? 'Streaming Plan' }}</p>
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ substr($apiKey->app_id, 0, 20) }}...</p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase {{ $apiKey->getStatusBadgeClass() }}">
                        {{ $apiKey->status }}
                    </span>
                </div>
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Viewers</span>
                        <span class="text-white font-mono">{{ $apiKey->current_viewers }} / {{ $apiKey->plan?->max_viewers ?? '-' }}</span>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-1.5">
                        @php
                            $viewerPercent = $apiKey->plan && $apiKey->plan->max_viewers > 0 
                                ? min(100, ($apiKey->current_viewers / $apiKey->plan->max_viewers) * 100) 
                                : 0;
                        @endphp
                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-1.5 rounded-full transition-all" style="width: {{ $viewerPercent }}%"></div>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Bandwidth</span>
                        <span class="text-white font-mono">{{ number_format($apiKey->bandwidth_used_gb, 1) }} GB</span>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-1.5">
                        @php
                            $bwPercent = $apiKey->plan && $apiKey->plan->bandwidth_gb > 0 
                                ? min(100, ($apiKey->bandwidth_used_gb / $apiKey->plan->bandwidth_gb) * 100) 
                                : 0;
                        @endphp
                        <div class="bg-gradient-to-r from-cyan-500 to-blue-500 h-1.5 rounded-full transition-all" style="width: {{ $bwPercent }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Right: API Key Details --}}
        @foreach($streamingApiKeys as $apiKey)
        <div 
            x-show="activeStreamKey === {{ $apiKey->id }}"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
            class="lg:col-span-2 space-y-6">
            
            {{-- API Credentials Card --}}
            <div class="glass-premium rounded-2xl p-6 border border-purple-500/20">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-black text-white flex items-center gap-2">
                        <i class="fas fa-shield-alt text-purple-400"></i> API Credentials
                    </h3>
                    <div class="flex gap-2">
                        <button 
                            @click="regenerateKeyId = {{ $apiKey->id }}; showRegenerateConfirm = true"
                            class="px-3 py-1.5 rounded-lg bg-white/5 text-gray-400 hover:text-white hover:bg-white/10 transition-all text-xs font-bold uppercase">
                            <i class="fas fa-sync-alt mr-1"></i> Regenerate
                        </button>
                    </div>
                </div>

                {{-- App ID --}}
                <div class="mb-4">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">App ID</label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-black/30 rounded-xl px-4 py-3 font-mono text-sm text-white border border-white/10">
                            {{ $apiKey->app_id }}
                        </div>
                        <button 
                            @click="navigator.clipboard.writeText('{{ $apiKey->app_id }}'); copiedField = 'app_id_{{ $apiKey->id }}'; setTimeout(() => copiedField = null, 2000)"
                            class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                            <i class="fas" :class="copiedField === 'app_id_{{ $apiKey->id }}' ? 'fa-check text-green-400' : 'fa-copy'"></i>
                        </button>
                    </div>
                </div>

                {{-- App Certificate --}}
                <div class="mb-4" x-data="{ certValue{{ $apiKey->id }}: null }">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">App Certificate</label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-black/30 rounded-xl px-4 py-3 font-mono text-sm border border-white/10 overflow-hidden text-gray-400">
                            <span x-show="!certValue{{ $apiKey->id }}">••••••••••••••••••••••••••••••••••••••••••••••••••</span>
                            <span x-show="certValue{{ $apiKey->id }}" x-text="certValue{{ $apiKey->id }}" x-cloak class="text-white"></span>
                        </div>
                        <button
                            @click="
                                if (!certValue{{ $apiKey->id }}) {
                                    fetch('{{ route('api.streaming.credential', ['apiKey' => $apiKey, 'field' => 'app_certificate']) }}')
                                        .then(r => r.json())
                                        .then(data => {
                                            if (data.success && data.value) {
                                                certValue{{ $apiKey->id }} = data.value;
                                            } else {
                                                alert('Could not fetch certificate.');
                                            }
                                        });
                                } else {
                                    certValue{{ $apiKey->id }} = null;
                                }
                            "
                            class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all"
                            title="Toggle visibility">
                            <i class="fas" :class="certValue{{ $apiKey->id }} ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                        <button
                            @click="
                                fetch('{{ route('api.streaming.credential', ['apiKey' => $apiKey, 'field' => 'app_certificate']) }}')
                                    .then(r => r.json())
                                    .then(data => {
                                        if (data.success && data.value) {
                                            certValue{{ $apiKey->id }} = data.value;
                                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                                navigator.clipboard.writeText(data.value).then(() => { copiedField = 'cert_{{ $apiKey->id }}'; setTimeout(() => copiedField = null, 2000); });
                                            } else {
                                                const ta = document.createElement('textarea'); ta.value = data.value; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); copiedField = 'cert_{{ $apiKey->id }}'; setTimeout(() => copiedField = null, 2000);
                                            }
                                        } else {
                                            alert('Could not fetch certificate.');
                                        }
                                    });
                            "
                            class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all"
                            title="Copy certificate">
                            <i class="fas" :class="copiedField === 'cert_{{ $apiKey->id }}' ? 'fa-check text-green-400' : 'fa-copy'"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2"><i class="fas fa-lock mr-1"></i> Fetched on demand and never embedded in the page source.</p>
                </div>

                {{-- REST API Key --}}
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">REST API Key</label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-black/30 rounded-xl px-4 py-3 font-mono text-sm text-white border border-white/10">
                            ••••••••••••••••••••••••••••••••••••••••••••••••••
                        </div>
                        <button
                            @click="
                                fetch('{{ route('api.streaming.credential', ['apiKey' => $apiKey, 'field' => 'rest_api_key']) }}')
                                    .then(r => r.json())
                                    .then(data => {
                                        if (data.success && data.value) {
                                            navigator.clipboard.writeText(data.value).then(() => { copiedField = 'rest_key_{{ $apiKey->id }}'; setTimeout(() => copiedField = null, 2000); });
                                        } else {
                                            alert('Could not fetch API key.');
                                        }
                                    });
                            "
                            class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                            <i class="fas" :class="copiedField === 'rest_key_{{ $apiKey->id }}' ? 'fa-check text-green-400' : 'fa-copy'"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stream URLs Card --}}
            <div class="glass-premium rounded-2xl p-6 border border-cyan-500/20">
                <h3 class="text-lg font-black text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-link text-cyan-400"></i> Stream URLs
                </h3>

                <div class="space-y-4">
                    {{-- Dynamic endpoints based on delivery method --}}
                    @php
                        $endpoints = [];
                        if (isset($streamingSubscription)) {
                            if ($streamingSubscription->delivery_method === 'vps_embedded' && isset($streamingSubscription->hosting)) {
                                $vpsIp = $streamingSubscription->hosting->ip_address;
                                $endpoints = [
                                    'rtmp' => "rtmp://{$vpsIp}:1935/live",
                                    'webrtc' => "wss://{$vpsIp}:8080/webrtc",
                                    'hls' => "http://{$vpsIp}:8080/hls",
                                    'api' => "http://{$vpsIp}:8080/api"
                                ];
                            } else {
                                $config = $streamingSubscription->streaming_config ?? [];
                                $clusterEndpoints = $config['cluster_endpoints'] ?? [];
                                $endpoints = [
                                    'rtmp' => $clusterEndpoints['rtmp_endpoint'] ?? 'rtmp://stream.believoo.com/live',
                                    'webrtc' => $clusterEndpoints['webrtc_endpoint'] ?? 'wss://stream.believoo.com/webrtc',
                                    'hls' => $clusterEndpoints['hls_endpoint'] ?? 'https://stream.believoo.com/hls',
                                    'api' => $clusterEndpoints['api_endpoint'] ?? 'https://api.stream.believoo.com'
                                ];
                            }
                        } else {
                            $endpoints = [
                                'rtmp' => config('streaming.ingest_base_url', 'rtmp://stream.believoo.com:1935') . '/live',
                                'webrtc' => config('streaming.webrtc_base_url', 'wss://stream.believoo.com/webrtc'),
                                'hls' => config('streaming.playback_base_url', 'https://stream.believoo.com/hls') . '/live',
                                'api' => config('streaming.api_base_url', 'https://api.stream.believoo.com')
                            ];
                        }
                    @endphp

                    @if(isset($endpoints['rtmp']))
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block flex items-center gap-2">
                            <i class="fas fa-upload text-orange-400"></i> RTMP Ingest URL
                            @if(isset($streamingSubscription) && $streamingSubscription->delivery_method === 'vps_embedded')
                                <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold" style="background: rgba(34, 197, 94, 0.2); color: #22c55e;">
                                    <i class="fas fa-server text-xs"></i> Your VPS
                                </span>
                            @endif
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-black/30 rounded-xl px-4 py-3 font-mono text-xs text-white border border-white/10 break-all">
                                {{ $endpoints['rtmp'] }}
                            </div>
                            <button 
                                @click="navigator.clipboard.writeText('{{ $endpoints['rtmp'] }}'); copiedField = 'rtmp_{{ $apiKey->id }}'; setTimeout(() => copiedField = null, 2000)"
                                class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex-shrink-0 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                                <i class="fas" :class="copiedField === 'rtmp_{{ $apiKey->id }}' ? 'fa-check text-green-400' : 'fa-copy'"></i>
                            </button>
                        </div>
                    </div>
                    @endif

                    @if(isset($endpoints['hls']))
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block flex items-center gap-2">
                            <i class="fas fa-play-circle text-green-400"></i> HLS Playback URL
                            @if(isset($streamingSubscription) && $streamingSubscription->delivery_method === 'vps_embedded')
                                <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold" style="background: rgba(34, 197, 94, 0.2); color: #22c55e;">
                                    <i class="fas fa-server text-xs"></i> Your VPS
                                </span>
                            @endif
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-black/30 rounded-xl px-4 py-3 font-mono text-xs text-white border border-white/10 break-all">
                                {{ $endpoints['hls'] }}
                            </div>
                            <button 
                                @click="navigator.clipboard.writeText('{{ $endpoints['hls'] }}'); copiedField = 'hls_{{ $apiKey->id }}'; setTimeout(() => copiedField = null, 2000)"
                                class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex-shrink-0 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                                <i class="fas" :class="copiedField === 'hls_{{ $apiKey->id }}' ? 'fa-check text-green-400' : 'fa-copy'"></i>
                            </button>
                        </div>
                    </div>
                    @endif

                    @if(isset($endpoints['webrtc']))
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block flex items-center gap-2">
                            <i class="fas fa-video text-purple-400"></i> WebRTC URL
                            @if(isset($streamingSubscription) && $streamingSubscription->delivery_method === 'vps_embedded')
                                <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold" style="background: rgba(34, 197, 94, 0.2); color: #22c55e;">
                                    <i class="fas fa-server text-xs"></i> Your VPS
                                </span>
                            @endif
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-black/30 rounded-xl px-4 py-3 font-mono text-xs text-white border border-white/10 break-all">
                                {{ $endpoints['webrtc'] }}
                            </div>
                            <button 
                                @click="navigator.clipboard.writeText('{{ $endpoints['webrtc'] }}'); copiedField = 'webrtc_{{ $apiKey->id }}'; setTimeout(() => copiedField = null, 2000)"
                                class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex-shrink-0 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                                <i class="fas" :class="copiedField === 'webrtc_{{ $apiKey->id }}' ? 'fa-check text-green-400' : 'fa-copy'"></i>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Plan Features --}}
            <div class="glass-premium rounded-2xl p-6 border border-pink-500/20">
                <h3 class="text-lg font-black text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-crown text-pink-400"></i> Plan Features
                </h3>
                @if($apiKey->plan)
                <div class="grid grid-cols-2 gap-3">
                    @foreach($apiKey->plan->getFeaturesList() as $feature)
                    <div class="flex items-center gap-2 text-sm text-gray-300">
                        <i class="fas fa-check-circle text-green-400 text-xs"></i>
                        <span>{{ $feature }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Recording History Section --}}
    @php
        $allRecordings = \App\Models\StreamRecording::forUser(Auth::id())
            ->whereIn('status', ['recording', 'completed'])
            ->with('hosting')
            ->orderBy('started_at', 'desc')
            ->limit(20)
            ->get();
    @endphp
    <div class="mt-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-lg bg-orange-500/20 flex items-center justify-center">
                <i class="fas fa-video text-orange-400 text-sm"></i>
            </div>
            <h3 class="text-lg font-black text-white uppercase tracking-widest">Recent Recordings</h3>
        </div>
        @if($allRecordings->count() > 0)
        <div class="glass rounded-2xl border border-white/10 overflow-hidden">
            <div class="divide-y divide-white/5">
                @foreach($allRecordings as $rec)
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg {{ $rec->status === 'recording' ? 'bg-red-500/20' : 'bg-green-500/20' }} flex items-center justify-center">
                            <i class="fas {{ $rec->status === 'recording' ? 'fa-circle text-red-400 animate-pulse' : 'fa-check text-green-400' }} text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">{{ $rec->recording_name }}</p>
                            <p class="text-[10px] text-gray-500">
                                @if($rec->hosting)
                                    {{ $rec->hosting->server_hostname ?? 'VPS' }} &middot;
                                @endif
                                {{ $rec->started_at->format('M d, Y H:i') }}
                                @if($rec->auto_delete_at)
                                    &middot; Auto-delete {{ $rec->auto_delete_at->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400">{{ $rec->duration_formatted }}</span>
                        <span class="text-[10px] text-gray-500">{{ $rec->file_size_mb > 0 ? number_format($rec->file_size_mb, 1).' MB' : '--' }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="glass rounded-2xl border border-white/10 p-8 text-center">
            <div class="w-12 h-12 rounded-xl bg-orange-500/10 flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-video-slash text-orange-400/50 text-lg"></i>
            </div>
            <p class="text-sm text-gray-400">No recordings yet</p>
            <p class="text-xs text-gray-500 mt-1">Start streaming to see your recordings here. Recordings are auto-deleted after 30 days.</p>
        </div>
        @endif
    </div>

    {{-- Regenerate Confirm Modal --}}
    <div 
        x-show="showRegenerateConfirm"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
        x-transition.opacity>
        <div 
            x-show="showRegenerateConfirm"
            class="bg-gray-900 border border-red-500/30 rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4"
            x-transition.scale>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Regenerate Keys?</h3>
                    <p class="text-xs text-red-400">This action cannot be undone</p>
                </div>
            </div>

            <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl mb-6">
                <ul class="text-xs text-gray-400 space-y-1.5 ml-4 list-disc">
                    <li>Your current keys will be immediately invalidated</li>
                    <li>All active streams will be interrupted</li>
                    <li>You must update your streaming software with new credentials</li>
                    <li>Existing recordings will remain accessible</li>
                </ul>
            </div>

            <div class="flex justify-end gap-3">
                <button @click="showRegenerateConfirm = false; regenerateKeyId = null" 
                        class="px-4 py-2 text-sm font-medium text-gray-400 bg-white/5 rounded-lg hover:bg-white/10 transition">
                    Cancel
                </button>
                <button 
                    wire:click="regenerateStreamingKeys(regenerateKeyId)"
                    @click="showRegenerateConfirm = false"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                    Yes, Regenerate
                </button>
            </div>
        </div>
    </div>

    {{-- Token Generator Modal --}}
    <div 
        x-show="showTokenGenerator"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
        x-transition.opacity>
        <div 
            x-show="showTokenGenerator"
            class="bg-gray-900 border border-purple-500/30 rounded-2xl shadow-2xl p-6 max-w-lg w-full mx-4"
            x-transition.scale>
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center">
                        <i class="fas fa-key text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Generate Stream Token</h3>
                        <p class="text-xs text-gray-400">Create secure tokens for stream authentication</p>
                    </div>
                </div>
                <button @click="showTokenGenerator = false" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">Channel Name</label>
                    <input 
                        type="text" 
                        x-model="tokenChannel"
                        placeholder="e.g., live-stream-1"
                        class="w-full bg-black/30 rounded-xl px-4 py-3 text-white border border-white/10 focus:border-purple-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">UID (User ID)</label>
                        <input 
                            type="number" 
                            x-model="tokenUid"
                            class="w-full bg-black/30 rounded-xl px-4 py-3 text-white border border-white/10 focus:border-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">Expiry (seconds)</label>
                        <select x-model="tokenExpiry" class="w-full bg-black/30 rounded-xl px-4 py-3 text-white border border-white/10 focus:border-purple-500 focus:outline-none">
                            <option value="3600">1 hour</option>
                            <option value="21600">6 hours</option>
                            <option value="86400">24 hours</option>
                            <option value="604800">7 days</option>
                        </select>
                    </div>
                </div>
                
                <button 
                    wire:click="generateStreamToken(activeStreamKey, tokenChannel, tokenUid, tokenExpiry)"
                    @click="generatedToken = '{{ $generatedStreamToken ?? '' }}'"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold uppercase tracking-wider text-sm hover:opacity-90 transition">
                    <i class="fas fa-magic mr-2"></i> Generate Token
                </button>

                @if($generatedStreamToken)
                <div class="mt-4 p-4 bg-black/50 rounded-xl border border-purple-500/30">
                    <label class="text-xs font-bold text-purple-400 uppercase tracking-wider mb-2 block">Generated Token</label>
                    <div class="flex items-start gap-2">
                        <div class="flex-1 font-mono text-xs text-gray-300 break-all leading-relaxed">
                            {{ $generatedStreamToken }}
                        </div>
                        <button 
                            @click="navigator.clipboard.writeText('{{ $generatedStreamToken }}')"
                            class="text-gray-400 hover:text-white">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@else
{{-- No Streaming Keys State --}}
<div class="flex flex-col items-center justify-center py-20 text-center">
    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center mb-6 border border-purple-500/30">
        <i class="fas fa-broadcast-tower text-4xl text-purple-400"></i>
    </div>
    <h3 class="text-2xl font-black text-white mb-2">No Live Streaming Access</h3>
    <p class="text-gray-400 max-w-md mb-6">Unlock the power of BelieVoo Live Engine with professional-grade streaming infrastructure.</p>
    <div class="flex flex-col sm:flex-row gap-4">
        <a href="{{ route('services.streaming') }}" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold uppercase tracking-wider text-sm hover:opacity-90 transition">
            <i class="fas fa-rocket mr-2"></i> Get Streaming API
        </a>
        <a href="/docs/streaming" target="_blank" class="px-6 py-3 rounded-xl bg-white/5 border border-white/10 text-gray-300 font-bold uppercase tracking-wider text-sm hover:bg-white/10 transition">
            <i class="fas fa-book mr-2"></i> View Docs
        </a>
    </div>
</div>
@endif
</div>
