<?php

namespace App\Livewire;

use App\Models\StreamingProject;
use App\Models\StreamingSubscription;
use App\Models\StreamingUsageLog;
use App\Models\UserHosting;
use App\Services\StreamingKeyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StreamingManagement extends Component
{
    // ── State ────────────────────────────────────────────────────────
    public ?StreamingSubscription $subscription = null;
    public array $projects = [];
    public string $deliveryMethod = 'cloud_hosted';
    public ?UserHosting $linkedVps = null;

    // New project form
    public bool   $showNewProjectForm = false;
    public string $newProjectName     = '';
    public string $newProjectRegion   = 'global';
    public string $newProjectError    = '';

    // Regenerate keys modal
    public bool $showRegenerateModal   = false;
    public ?int $regenerateProjectId   = null;

    // Delete project modal
    public bool $showDeleteModal   = false;
    public ?int $deleteProjectId   = null;

    // Token generator (per project, keyed by project ID)
    public array $generatedTokens = [];

    // Metrics (refreshed by polling)
    public array $projectMetrics = [];

    // Chart data (7-day, per project)
    public array $chartData   = [];
    public array $chartMetric = [];

    // ── Lifecycle ────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadSubscription();
        $this->loadProjects();
        $this->loadMetrics();
        
        // Detect delivery method and VPS linkage
        if ($this->subscription) {
            $this->deliveryMethod = $this->subscription->delivery_method ?? 'cloud_hosted';
            $this->linkedVps = $this->subscription->hosting;
        }
    }

    // ── Data Loading ─────────────────────────────────────────────────

    private function loadSubscription(): void
    {
        $this->subscription = StreamingSubscription::where('user_id', Auth::id())
            ->where('status', 'active')
            ->with(['plan', 'hosting'])
            ->first();
    }

    /**
     * Get streaming endpoints based on delivery method
     */
    public function getStreamingEndpoints(): array
    {
        if (!$this->subscription) {
            return [];
        }

        if ($this->deliveryMethod === 'vps_embedded' && $this->linkedVps) {
            // VPS-Embedded: Use client's VPS IP
            $vpsIp = $this->linkedVps->ip_address;
            return [
                'rtmp_url' => "rtmp://{$vpsIp}:1935/live",
                'webrtc_url' => "wss://{$vpsIp}:8080/webrtc",
                'hls_url' => "http://{$vpsIp}:8080/hls",
                'api_url' => "http://{$vpsIp}:8080/api",
                'host_type' => 'VPS-Embedded',
                'host_info' => "Your VPS ({$vpsIp})",
            ];
        } else {
            // Cloud-Hosted: Use BelieVoo master cluster
            $config = $this->subscription->streaming_config ?? [];
            $clusterEndpoints = $config['cluster_endpoints'] ?? [];
            
            return [
                'rtmp_url' => $clusterEndpoints['rtmp_endpoint'] ?? 'rtmp://stream.believoo.com/live',
                'webrtc_url' => $clusterEndpoints['webrtc_endpoint'] ?? 'wss://stream.believoo.com/webrtc',
                'hls_url' => $clusterEndpoints['hls_endpoint'] ?? 'https://stream.believoo.com/hls',
                'api_url' => $clusterEndpoints['api_endpoint'] ?? 'https://api.stream.believoo.com',
                'host_type' => 'Cloud-Hosted',
                'host_info' => 'BelieVoo Streaming Cluster',
            ];
        }
    }

    /**
     * Get streaming configuration display info
     */
    public function getStreamingConfig(): array
    {
        if (!$this->subscription) {
            return [];
        }

        $endpoints = $this->getStreamingEndpoints();
        $plan = $this->subscription->plan;
        
        return [
            'plan_name' => $plan->name,
            'delivery_method' => $this->deliveryMethod,
            'host_type' => $endpoints['host_type'],
            'host_info' => $endpoints['host_info'],
            'endpoints' => [
                'rtmp' => $endpoints['rtmp_url'],
                'webrtc' => $endpoints['webrtc_url'],
                'hls' => $endpoints['hls_url'],
                'api' => $endpoints['api_url'],
            ],
            'limits' => [
                'max_viewers' => $plan->max_viewers,
                'max_bitrate' => $plan->max_bitrate,
                'max_resolution' => $plan->max_resolution,
                'bandwidth_gb' => $plan->bandwidth_gb,
                'storage_gb' => $plan->storage_gb,
            ],
            'features' => [
                'rtmp_support' => $plan->rtmp_support,
                'webrtc_support' => $plan->webrtc_support,
                'hls_support' => $plan->hls_support,
                'dash_support' => $plan->dash_support,
                'recording_enabled' => $plan->recording_enabled,
                'transcoding_enabled' => $plan->transcoding_enabled,
                'adaptive_bitrate' => $plan->adaptive_bitrate,
                'low_latency' => $plan->low_latency,
            ],
        ];
    }

    private function loadProjects(): void
    {
        if (!$this->subscription) {
            $this->projects = [];
            return;
        }

        $this->projects = StreamingProject::where('user_id', Auth::id())
            ->where('streaming_subscription_id', $this->subscription->id)
            ->get()
            ->toArray();
    }

    public function loadMetrics(): void
    {
        foreach ($this->projects as $project) {
            $p = StreamingProject::find($project['id']);
            if (!$p) continue;
            $this->projectMetrics[$project['id']] = [
                'bandwidth_gb' => $p->currentPeriodBandwidthGb(),
                'percentage'   => $p->bandwidthPercentage(),
                'is_active'    => $p->status === 'active',
            ];
        }
    }

    // ── Chart Data ───────────────────────────────────────────────────

    public function loadChartData(int $projectId, string $metric = 'bandwidth_used_gb'): void
    {
        // Ownership guard
        StreamingProject::where('id', $projectId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->chartMetric[$projectId] = $metric;

        $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->toDateString());
        $logs = StreamingUsageLog::where('streaming_project_id', $projectId)
            ->whereDate('recorded_at', '>=', now()->subDays(6))
            ->selectRaw("DATE(recorded_at) as day, SUM({$metric}) as total")
            ->groupBy('day')
            ->pluck('total', 'day');

        $this->chartData[$projectId] = [
            'labels' => $days->values()->toArray(),
            'values' => $days->map(fn($d) => (float) ($logs[$d] ?? 0))->values()->toArray(),
        ];

        $this->dispatch('chartDataUpdated', projectId: $projectId, data: $this->chartData[$projectId]);
    }

    // ── New Project ──────────────────────────────────────────────────

    public function createProject(): void
    {
        $this->newProjectError = '';

        if (empty(trim($this->newProjectName))) {
            $this->newProjectError = 'Project name is required.';
            return;
        }
        if (strlen($this->newProjectName) > 100) {
            $this->newProjectError = 'Project name must not exceed 100 characters.';
            return;
        }
        if (!preg_match('/^[a-zA-Z0-9 _-]+$/', $this->newProjectName)) {
            $this->newProjectError = 'Only letters, numbers, spaces, hyphens, and underscores allowed.';
            return;
        }

        $plan  = $this->subscription?->plan;
        $limit = $plan?->max_projects ?? 5;
        $count = StreamingProject::where('user_id', Auth::id())
            ->where('streaming_subscription_id', $this->subscription->id)
            ->count();

        if ($count >= $limit) {
            $this->newProjectError = "You have reached the project limit ({$limit}) for your plan.";
            return;
        }

        app(StreamingKeyService::class)->createProjectForSubscription(
            $this->subscription,
            trim($this->newProjectName),
            $this->newProjectRegion
        );

        $this->showNewProjectForm = false;
        $this->newProjectName     = '';
        $this->newProjectRegion   = 'global';
        $this->loadProjects();
        $this->loadMetrics();
    }

    // ── Regenerate Keys ──────────────────────────────────────────────

    public function confirmRegenerate(int $projectId): void
    {
        $this->regenerateProjectId = $projectId;
        $this->showRegenerateModal = true;
    }

    public function executeRegenerate(): void
    {
        $project = StreamingProject::where('id', $this->regenerateProjectId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        try {
            app(StreamingKeyService::class)->regenerateKeys($project);
            $this->showRegenerateModal  = false;
            $this->regenerateProjectId  = null;
            $this->loadProjects();
            $this->dispatch('notify', type: 'success', message: 'Keys regenerated successfully.');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Regeneration failed. Please try again.');
        }
    }

    // ── Delete Project ───────────────────────────────────────────────

    public function confirmDelete(int $projectId): void
    {
        $this->deleteProjectId = $projectId;
        $this->showDeleteModal = true;
    }

    public function executeDelete(): void
    {
        StreamingProject::where('id', $this->deleteProjectId)
            ->where('user_id', Auth::id())
            ->firstOrFail()
            ->delete(); // SoftDeletes sets deleted_at

        $this->showDeleteModal = false;
        $this->deleteProjectId = null;
        $this->loadProjects();
        $this->loadMetrics();
    }

    // ── Token Generator ──────────────────────────────────────────────

    public function generateToken(int $projectId): void
    {
        $project = StreamingProject::where('id', $projectId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $result = app(StreamingKeyService::class)->generateStreamToken(
            $project->app_id,
            $project->getDecryptedCertificate()
        );

        // Token is NOT stored in DB or logs
        $this->generatedTokens[$projectId] = $result;
    }

    // ── Render ───────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.streaming-management', [
            'streamingConfig' => $this->getStreamingConfig(),
            'endpoints' => $this->getStreamingEndpoints(),
        ])->layout('layouts.app', ['dataTheme' => 'midnight-onyx']);
    }
}
