<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Models\Ticket;
use App\Models\Project;
use App\Models\ProjectAsset;
use App\Models\UserHosting;
use App\Models\UserDomain;
use App\Models\DnsRecord;
use App\Models\Service;
use App\Models\Order;
use App\Models\Agreement;
use App\Models\AgreementHistory;
use App\Models\AgreementRequest;
use App\Models\Portfolio;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Livewire\WithFileUploads;

class ClientDashboard extends Component
{
    use WithFileUploads;

    // -----------------------------------------------------------------------
    // Protected VM guard — VMID 100 on ns548195 must never be touched
    // -----------------------------------------------------------------------
    private const PROTECTED_VMS = [
        ['vmid' => 100, 'node' => 'ns548195'],
    ];

    // -----------------------------------------------------------------------
    // Action message / audit properties (Tasks 1.12 & 1.13)
    // -----------------------------------------------------------------------
    public string $actionMessage = '';
    public string $actionMessageType = 'info';
    public string $pendingConfirmAction = '';

    // -----------------------------------------------------------------------
    // VM Status & Power Control properties (Phase 2)
    // -----------------------------------------------------------------------
    public string $vmStatus = 'unknown';
    public bool $isActionInProgress = false;

    // Confirmation modal (generalised)
    public bool $showConfirmModal = false;
    public string $confirmModalTitle = '';
    public string $confirmModalMessage = '';

    // -----------------------------------------------------------------------
    // Console properties (Task 3.1)
    // -----------------------------------------------------------------------
    public ?string $consoleUrl = null;
    public bool $showConsole = false;

    // -----------------------------------------------------------------------
    // Firewall properties (Task 3.4)
    // -----------------------------------------------------------------------
    public bool $firewallEnabled = false;
    public bool $firewallLoading = false;
    public ?bool $firewallPreviousState = null;

    // -----------------------------------------------------------------------
    // DNS Manager properties (Phase 4 — Tasks 4.1 & 4.3)
    // -----------------------------------------------------------------------
    public array  $dnsARecords        = [];
    public ?array $dnsPtrRecord       = null;
    public bool   $showDnsForm        = false;
    public string $dnsFormType        = 'A';   // A, AAAA, CNAME, MX, TXT, NS, SRV, CAA
    public string $dnsFormName        = '';
    public string $dnsFormValue       = '';
    public string $dnsFormPriority    = '';
    public int    $dnsFormTtl         = 3600;
    public ?int   $editingDnsRecordId = null;
    public string $dnsFormError       = '';
    public string $ptrHostname        = '';
    public bool   $showPtrForm        = false;
    // Keep old dnsFormData for backward compat
    public array  $dnsFormData        = ['hostname' => '', 'ip' => ''];

    // -----------------------------------------------------------------------
    // Nameserver Manager properties
    // -----------------------------------------------------------------------
    public bool   $showNsModal        = false;
    public string $ns1               = '';
    public string $ns2               = '';
    public string $ns3               = '';
    public string $ns4               = '';
    public string $nsError           = '';

    // -----------------------------------------------------------------------
    // Domain name editor (editable primary_domain on DNS Manager)
    // -----------------------------------------------------------------------
    public bool   $editingDomain     = false;
    public string $editDomainValue    = '';
    public string $editDomainError   = '';

    // -----------------------------------------------------------------------
    // Reinstall OS properties (Phase 4 — Task 4.5)
    // -----------------------------------------------------------------------
    public string $selectedOsTemplate = '';
    public bool   $showReinstallModal = false;
    public bool   $showReinstallConfirmModal = false;

    // -----------------------------------------------------------------------
    // Password Reset properties (Phase 4 — Task 4.6)
    // -----------------------------------------------------------------------
    public bool   $showPasswordResetModal = false;

    // -----------------------------------------------------------------------
    // Analytics Engine properties (Luxury Overhaul — Task 6)
    // -----------------------------------------------------------------------
    public array $cpuHistory    = [];   // max 60 floats (CPU % per poll)
    public array $ramHistory    = [];   // max 60 floats (RAM % per poll)
    public array $netinHistory  = [];   // max 60 floats (Mbps inbound per poll)
    public array $netoutHistory = [];   // max 60 floats (Mbps outbound per poll)

    public float $cpuCurrent    = 0.0;  // latest CPU percentage
    public float $ramCurrentGb  = 0.0;  // latest RAM used in GB
    public float $ramTotalGb    = 0.0;  // total RAM in GB
    public float $netinMbps     = 0.0;  // latest inbound bandwidth in Mbps
    public float $netoutMbps    = 0.0;  // latest outbound bandwidth in Mbps

    public bool  $dataUnavailable = false; // true when serverDetails is missing expected keys

    /**
     * Boot — runs on every Livewire request to restore selectedHosting from ID
     */
    public function boot(): void
    {
        if ($this->selectedHostingId && !$this->selectedHosting) {
            $hosting = UserHosting::find($this->selectedHostingId);
            if ($hosting && $hosting->user_id === Auth::id()) {
                $this->selectedHosting = $hosting;
            }
        }
    }

    public $activeTab = 'dashboard'; // dashboard, projects, tickets, vault, hosting, agreements, streaming
    public $isTicketModalOpen = false;
    public $isUploadModalOpen = false;
    public $selectedTicket = null;
    public $ticketReplyMessage = '';
    public $lastUnreadCount = 0;
    public $selectedHostingId = null; // Store ID only for Livewire serialization
    public $selectedHosting = null;
    public $serverDetails = null; // Real-time data from Virtualizor API
    public $serverActions = []; // Available actions (start/stop/restart)
    public $activeHostingTab = 'home'; // home, dns, backup, monitoring, disks, databases, migration, import, terminal
    public $showActionModal = false;
    public $pendingAction = '';
    public $pendingActionTitle = '';
    public $pendingActionMessage = '';
    public $serverMessage = null;
    public $serverMessageType = 'info';
    public $isLoadingServer = false;
    public $newHostname = ''; // For hostname change
    public $newDomain = ''; // For adding secondary DNS domain
    public $showDomainModal = false;
    public ?int $selectedAgreementId = null;
    public $signatureData = '';
    public $isAgreementRequestModalOpen = false;
    public $showProjectTracking = false;

    // -----------------------------------------------------------------------
    // Streaming Management properties
    // -----------------------------------------------------------------------
    public $streamingApiKeys = null;
    public $streamingSubscription = null;
    public $selectedStreamingKey = null;
    public $generatedStreamToken = null;
    public $showStreamingRegenerateConfirm = false;
    public $revealedCertificate = null;

    // VM Migration UI State
    public $showMigrationModal = false;
    public $migrationNodes = [];
    public $selectedTargetNode = null;
    public $activeMigration = null;
    public $migrationProgress = 0;
    public $migrationStatus = '';
    public $migrationMessage = '';
    public $isLoadingMigration = false;
    public $proxmoxVmId = null; // Store the Proxmox VM ID for migration

    // Server Sync UI State (Bidirectional)
    public $serverImportSourceIp = '';
    public $serverImportSourcePassword = '';
    public $serverImportSourcePort = 22;
    public $activeServerImport = null;
    public $serverImportProgress = 0;
    public $serverImportStatus = '';
    public $serverImportMessage = '';
    public $serverImportSyncLog = ''; // Live log of current file
    public $serverImportTransferSpeed = '';
    public $serverImportEta = '';
    public $isLoadingServerImport = false;
    public $serverImportConnectionTested = false;
    public $serverImportConnectionResult = null;
    public $serverImportSyncDirection = 'pull'; // 'pull' = Import, 'push' = Export
    public $serverImportActiveTab = 'import'; // 'import' or 'export'

    // Agreement Request fields
    public $projectName;
    public $projectDescription;
    public $requirements;
    public $budgetRange;
    public $timelineExpectation;
    public $preferredTechnology;

    // Ticket fields
    public $subject;
    public $message;
    public $priority = 'medium';
    public $ticketAttachment;
    public $replyAttachment;

    // Vault fields
    public $assetFile;
    public $assetName;
    public $selectedProjectId;

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->lastUnreadCount = Auth::user()->unreadNotifications->count();

        // Handle direct ticket view from notification
        if (request()->has('ticket')) {
            $ticketId = request()->query('ticket');
            $this->activeTab = 'tickets';
            try {
                $this->viewTicket($ticketId);
            } catch (\Exception $e) {
                // Ignore if ticket doesn't exist or no access
            }
        }
        
        // Handle direct hosting view from URL
        if (request()->has('hosting')) {
            $hostingId = request()->query('hosting');
            $this->activeTab = 'hosting';
            $this->viewHosting($hostingId);
        }
        
        // Handle tab from URL
        if (request()->has('tab')) {
            $this->activeTab = request()->query('tab');
        }

        // Initialise VM status, firewall state, and DNS records if a hosting is already selected
        if ($this->selectedHosting) {
            $this->refreshVmStatus();
            $this->loadFirewallState();
            $this->loadDnsRecords();
            $this->loadPtrRecord();
        }
    }

    public function viewTicket($ticketId)
    {
        // Try searching by UID first, then by primary key
        $this->selectedTicket = Ticket::with('messages')
            ->where('ticket_id', $ticketId)
            ->first();
            
        if (!$this->selectedTicket && is_numeric($ticketId)) {
            $this->selectedTicket = Ticket::with('messages')->find($ticketId);
        }

        if (!$this->selectedTicket) {
            return;
        }
        
        // Ensure user has access
        if ($this->selectedTicket->email !== Auth::user()->email) {
            $this->selectedTicket = null;
            abort(403);
        }
    }

    public function closeTicketView()
    {
        $this->selectedTicket = null;
    }

    public function getListeners()
    {
        $listeners = [];
        if ($this->selectedTicket) {
            $listeners["echo:ticket.{$this->selectedTicket->id},TicketMessageSent"] = 'onTicketMessageReceived';
        }
        return $listeners;
    }

    public function onTicketMessageReceived($event)
    {
        if ($this->selectedTicket && $event['ticket_id'] == $this->selectedTicket->id) {
            $this->viewTicket($this->selectedTicket->id);
            $this->dispatch('play-notification-sound');
        }
    }

    public function sendReply()
    {
        if (!$this->selectedTicket) return;

        $this->validate([
            'ticketReplyMessage' => 'required_without:replyAttachment|min:2',
            'replyAttachment' => 'nullable|file|max:10240', // 10MB
        ]);

        $attachmentPath = null;
        if ($this->replyAttachment) {
            $attachmentPath = $this->replyAttachment->store('ticket-attachments', 'public');
        }

        $msg = $this->selectedTicket->messages()->create([
            'sender_type' => 'user',
            'sender_name' => Auth::user()->name,
            'message' => $this->ticketReplyMessage ?? '',
            'attachment' => $attachmentPath,
        ]);

        try {
            broadcast(new \App\Events\TicketMessageSent($msg))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Ticket broadcast from dashboard failed: ' . $e->getMessage());
        }

        $this->ticketReplyMessage = '';
        $this->replyAttachment = null;
        $this->viewTicket($this->selectedTicket->id); // Refresh ticket data
        
        // Notify Admin
        $admin = \App\Models\User::where('email', 'admin@believoo.com')->first() ?? \App\Models\User::first();
        if ($admin) {
            try {
                $admin->notify(new \App\Notifications\AdminTicketReplyNotification($this->selectedTicket, $msg));
            } catch (\Exception $e) {
                \Log::error('Admin email notification failed: ' . $e->getMessage());
            }

            $ticketUrl = \App\Filament\Resources\TicketResource::getUrl('view', ['record' => $this->selectedTicket]);
            
            \Filament\Notifications\Notification::make()
                ->title('New Reply on Ticket')
                ->body("From: " . Auth::user()->name . " - " . $this->selectedTicket->ticket_id)
                ->icon('heroicon-o-chat-bubble-left')
                ->color('info')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url($ticketUrl),
                ])
                ->sendToDatabase($admin)
                ->broadcast($admin);
            
            $this->dispatch('notification-sent');
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->dispatch('tab-changed', tab: $tab);
        if ($tab !== 'tickets') {
            $this->selectedTicket = null;
        }
        if ($tab !== 'hosting') {
            $this->selectedHosting = null;
        }
        if ($tab !== 'agreements') {
            $this->selectedAgreementId = null;
        }
        if ($tab === 'streaming') {
            $this->loadStreamingData();
        }
    }

    /**
     * Load streaming API keys and usage data
     */
    public function loadStreamingData(): void
    {
        $this->streamingApiKeys = \App\Models\StreamingApiKey::forUser(Auth::id())
            ->with('plan')
            ->whereIn('status', ['active', 'suspended'])
            ->get();

        $this->streamingSubscription = \App\Models\StreamingSubscription::where('user_id', Auth::id())
            ->where('status', 'active')
            ->with(['plan', 'hosting'])
            ->first();
    }

    /**
     * Refresh streaming metrics in real-time (called by wire:poll)
     */
    public function refreshStreamingMetrics(): void
    {
        $collector = app(\App\Services\StreamingMetricsCollector::class);

        foreach ($this->streamingApiKeys ?? [] as $apiKey) {
            try {
                $metrics = $collector->collectForKey($apiKey);
                if ($metrics) {
                    // Refresh the model to get updated values
                    $apiKey->refresh();
                }
            } catch (\Exception $e) {
                // Silently fail - stream may be offline
            }
        }

        // Reload to get fresh data
        $this->loadStreamingData();
    }

    /**
     * Regenerate streaming API keys
     */
    public function regenerateStreamingKeys(int $apiKeyId): void
    {
        $apiKey = \App\Models\StreamingApiKey::forUser(Auth::id())->find($apiKeyId);
        if (!$apiKey) {
            $this->dispatch('notify', ['message' => 'API key not found', 'type' => 'error']);
            return;
        }

        try {
            $service = app(\App\Services\StreamingApiService::class);
            $service->regenerateKeys($apiKey);
            $this->loadStreamingData();
            $this->dispatch('notify', ['message' => 'API keys regenerated successfully', 'type' => 'success']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Failed to regenerate keys: ' . $e->getMessage(), 'type' => 'error']);
        }
    }

    /**
     * Reveal certificate for display (temporary, not stored)
     */
    public function revealCertificate(int $apiKeyId): void
    {
        $apiKey = \App\Models\StreamingApiKey::forUser(Auth::id())->find($apiKeyId);
        if ($apiKey) {
            $this->revealedCertificate = $apiKey->app_certificate;
        }
    }

    /**
     * Generate a stream token for testing
     */
    public function generateStreamToken(int $apiKeyId, string $channel, int $uid, int $expirySeconds = 3600): void
    {
        $apiKey = \App\Models\StreamingApiKey::forUser(Auth::id())->find($apiKeyId);
        if (!$apiKey) {
            $this->generatedStreamToken = null;
            return;
        }

        $this->generatedStreamToken = \App\Models\StreamingApiKey::generateStreamToken(
            $apiKey->app_id,
            $apiKey->app_certificate,
            $channel,
            $uid,
            $expirySeconds
        );
    }

    public function viewHosting($hostingId)
    {
        $hosting = UserHosting::with('service')->find($hostingId);

        if (!$hosting || $hosting->user_id !== Auth::id()) {
            $this->dispatch('notify', ['message' => 'Hosting not found or access denied', 'type' => 'error']);
            return;
        }
        
        $this->selectedHostingId = $hosting->id;
        $this->selectedHosting = $hosting;
        $this->activeTab = 'hosting';
        $this->dispatch('tab-changed', tab: 'hosting');
        $this->activeHostingTab = 'home';
        
        // Ensure a UserDomain exists for the hosting's primary domain
        $this->resolveOrCreateDomain();

        // Load real-time server details from Virtualizor API
        $this->loadServerDetails();

        // Load DNS records for the DNS Manager tab
        $this->loadDnsRecords();
        $this->loadPtrRecord();
    }

    /**
     * Load real-time server details from Proxmox API.
     * Results are cached for 30 seconds to prevent every button click
     * from making a slow Proxmox API call.
     */
    public function loadServerDetails(): void
    {
        if (!$this->selectedHosting) {
            return;
        }

        $this->isLoadingServer = true;
        $cacheKey = 'server_details_' . $this->selectedHosting->id;

        // Return cached data immediately if available (avoids Proxmox round-trip on every click)
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) {
            $this->serverDetails = $cached['details'];
            $this->serverActions = $cached['actions'];
            $this->isLoadingServer = false;
            return;
        }
        
        try {
            $vpsId = $this->selectedHosting->vps_id ?? null;
            
            if ($vpsId) {
                // Get Proxmox VM record to find node
                $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $vpsId)->first();
                $node = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
                
                // Build Proxmox API service
                $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
                
                if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                    $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                    $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
                } else {
                    $proxmox = app(\App\Services\ProxmoxApiService::class);
                }
                
                $vmStatus = $proxmox->getVmStatus((int) $vpsId);
                
                if ($vmStatus) {
                    $state = $vmStatus['status'] ?? 'unknown';
                    $cpuPercent = round(($vmStatus['cpu'] ?? 0) * 100, 1);
                    $memUsed = $vmStatus['mem'] ?? 0;
                    $memTotal = $vmStatus['maxmem'] ?? 0;
                    $memPercent = $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0;
                    $diskUsed = $vmStatus['disk'] ?? 0;
                    $diskTotal = $vmStatus['maxdisk'] ?? 0;
                    $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;
                    
                    // Guest agent info — only fetch if running, skip on button clicks
                    $guestInfo = null;
                    if ($state === 'running') {
                        try {
                            $guestInfo = $proxmox->getGuestAgentInfo((int) $vpsId);
                        } catch (\Exception $ge) {
                            // Guest agent not available — normal for new VMs
                        }
                    }

                    // Extract real IP from guest agent if available
                    $realIp = null;
                    if ($guestInfo && !empty($guestInfo['network'])) {
                        foreach ($guestInfo['network'] as $iface) {
                            if (($iface['name'] ?? '') === 'lo') continue;
                            foreach ($iface['ip-addresses'] ?? [] as $ipEntry) {
                                if (($ipEntry['ip-address-type'] ?? '') === 'ipv4' && ($ipEntry['ip-address'] ?? '') !== '127.0.0.1') {
                                    $realIp = $ipEntry['ip-address'];
                                    break 2;
                                }
                            }
                        }
                    }

                    // Update hosting with real IP if found
                    if ($realIp && $realIp !== $this->selectedHosting->server_ip) {
                        $this->selectedHosting->update(['server_ip' => $realIp]);
                        $this->selectedHosting->refresh();
                        \App\Models\ProxmoxVm::where('vmid', $vpsId)->update(['ip_address' => $realIp]);
                    }

                    $details = [
                        'state'        => $state,
                        'hostname'     => $guestInfo['hostname'] ?? ($proxmoxVm?->hostname ?? $this->selectedHosting->server_hostname),
                        'ip'           => $realIp ?? ($proxmoxVm?->ip_address ?? $this->selectedHosting->server_ip),
                        'os_name'      => $guestInfo['os']['pretty-name'] ?? $guestInfo['os']['name'] ?? $this->selectedHosting->os_name,
                        'uptime'       => $vmStatus['uptime'] ?? 0,
                        'cpu_percent'  => $cpuPercent,
                        'mem_percent'  => $memPercent,
                        'mem_used_gb'  => round($memUsed / 1024 / 1024 / 1024, 2),
                        'mem_total_gb' => round($memTotal / 1024 / 1024 / 1024, 2),
                        'disk_percent' => $diskPercent,
                        'disk_used_gb' => round($diskUsed / 1024 / 1024 / 1024, 2),
                        'disk_total_gb'=> round($diskTotal / 1024 / 1024 / 1024, 2),
                        'netin'        => $vmStatus['netin'] ?? 0,
                        'netout'       => $vmStatus['netout'] ?? 0,
                        'node'         => $node,
                        'vmid'         => $vpsId,
                        'location'     => $proxmoxNode?->display_name ?? $this->selectedHosting->datacenter_location ?? 'Singapore',
                        'guest_agent'  => !empty($guestInfo),
                        'network_interfaces' => $guestInfo['network'] ?? [],
                    ];
                    
                    $actions = [
                        'can_start'    => $state === 'stopped',
                        'can_stop'     => $state === 'running',
                        'can_restart'  => $state === 'running',
                        'can_power_off'=> true,
                        'can_rebuild'  => true,
                    ];
                } else {
                    // VM not found on Proxmox
                    $details = [
                        'state'    => 'unknown',
                        'hostname' => $this->selectedHosting->server_hostname,
                        'ip'       => $this->selectedHosting->server_ip,
                        'os_name'  => $this->selectedHosting->os_name,
                        'location' => $this->selectedHosting->datacenter_location ?? 'Singapore',
                    ];
                    $actions = [
                        'can_start'    => true,
                        'can_stop'     => false,
                        'can_restart'  => false,
                        'can_power_off'=> false,
                        'can_rebuild'  => true,
                    ];
                }
            } else {
                // No VPS ID linked - use database info only
                $details = [
                    'hostname' => $this->selectedHosting->server_hostname,
                    'ip'       => $this->selectedHosting->server_ip,
                    'state'    => $this->selectedHosting->status === 'active' ? 'running' : 'stopped',
                    'os_name'  => $this->selectedHosting->os_name,
                    'location' => $this->selectedHosting->datacenter_location ?? 'Singapore',
                ];
                $actions = [
                    'can_start'    => $this->selectedHosting->status !== 'active',
                    'can_stop'     => $this->selectedHosting->status === 'active',
                    'can_restart'  => $this->selectedHosting->status === 'active',
                    'can_power_off'=> false,
                    'can_rebuild'  => false,
                ];
            }

            $this->serverDetails = $details;
            $this->serverActions = $actions;

            // Cache for 30 seconds — buttons will be instant, refresh button forces a new fetch
            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'details' => $details,
                'actions' => $actions,
            ], 30);

        } catch (\Exception $e) {
            \Log::error('Failed to load server details', [
                'hosting_id' => $this->selectedHosting->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->serverDetails = [
                'hostname' => $this->selectedHosting->server_hostname,
                'ip'       => $this->selectedHosting->server_ip,
                'state'    => 'unknown',
                'os_name'  => $this->selectedHosting->os_name,
                'location' => $this->selectedHosting->datacenter_location ?? 'Singapore',
            ];
            $this->serverActions = [];
        }
        
        $this->isLoadingServer = false;
    }

    /**
     * Switch hosting detail tabs
     */
    public function setHostingTab(string $tab): void
    {
        $validTabs = [
            'home', 'dns_manager', 'dns', 'backup', 'monitoring',
            'disks', 'databases', 'migration', 'import', 'terminal',
        ];
        $this->activeHostingTab = in_array($tab, $validTabs) ? $tab : 'home';

        // Load active server import when switching to import tab (lightweight DB query)
        if ($tab === 'import') {
            $this->loadActiveServerImport();
        }

        // Bust server details cache when switching to monitoring tab so fresh data loads
        if ($tab === 'monitoring' && $this->selectedHosting) {
            \Illuminate\Support\Facades\Cache::forget('server_details_' . $this->selectedHosting->id);
            $this->loadServerDetails();
        }
    }

    /**
     * Remove a domain from the hosting record
     */
    public function removeDomain(string $domain): void
    {
        if (!$this->selectedHosting) return;
        if ($this->selectedHosting->primary_domain === $domain) {
            $this->selectedHosting->update(['primary_domain' => null]);
        } else {
            $notes = $this->selectedHosting->admin_notes ?? '';
            preg_match('/extra_domains:\[(.*?)\]/', $notes, $matches);
            $domains = $matches[1] ? array_filter(explode(',', $matches[1])) : [];
            $domains = array_values(array_diff($domains, [$domain]));
            $domainsStr = 'extra_domains:[' . implode(',', $domains) . ']';
            $cleanNotes = preg_replace('/extra_domains:\[.*?\]/', '', $notes);
            $this->selectedHosting->update(['admin_notes' => trim($cleanNotes) . ' ' . $domainsStr]);
        }
        $this->selectedHosting->refresh();
        $this->serverMessage = "Domain '{$domain}' removed.";
        $this->serverMessageType = 'info';
        $this->dispatch('clearServerMessage');
    }

    /**
     * Toggle automated backup on/off
     */
    public function toggleAutomatedBackup(): void
    {
        if (!$this->selectedHosting) return;
        $newState = !$this->selectedHosting->automated_backup;
        $this->selectedHosting->update([
            'automated_backup' => $newState,
            'backup_status' => $newState ? 'enabled' : 'disabled',
        ]);
        $this->selectedHosting->refresh();
        $this->serverMessage = $newState ? 'Automated backup enabled.' : 'Automated backup disabled.';
        $this->serverMessageType = $newState ? 'success' : 'info';
        $this->dispatch('clearServerMessage');
    }

    /**
     * Toggle streaming recording on/off for selected hosting
     */
    public function toggleRecording(): void
    {
        if (!$this->selectedHosting) return;
        $newState = !$this->selectedHosting->recording_enabled;
        $this->selectedHosting->update([
            'recording_enabled' => $newState,
        ]);
        $this->selectedHosting->refresh();
        $this->serverMessage = $newState ? 'Stream recording enabled. All streams will be recorded.' : 'Stream recording disabled.';
        $this->serverMessageType = $newState ? 'success' : 'info';
        $this->dispatch('clearServerMessage');
    }

    /**
     * Toggle streaming recording for a specific hosting by ID
     * (used in streaming dashboard)
     */
    public function toggleRecordingForHosting(int $hostingId): void
    {
        $hosting = \App\Models\UserHosting::where('id', $hostingId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$hosting) {
            $this->serverMessage = 'Hosting not found.';
            $this->serverMessageType = 'error';
            $this->dispatch('clearServerMessage');
            return;
        }

        $newState = !$hosting->recording_enabled;
        $hosting->update([
            'recording_enabled' => $newState,
        ]);

        $this->serverMessage = $newState
            ? 'Stream recording enabled for ' . ($hosting->server_hostname ?? $hosting->plan_name) . '.'
            : 'Stream recording disabled for ' . ($hosting->server_hostname ?? $hosting->plan_name) . '.';
        $this->serverMessageType = $newState ? 'success' : 'info';
        $this->dispatch('clearServerMessage');
    }

    /**
     * Create a manual snapshot of the VPS
     */
    public function createSnapshot(): void
    {
        if (!$this->selectedHosting) return;
        
        $vpsId = $this->selectedHosting->vps_id ?? null;
        if (!$vpsId) {
            $this->serverMessage = 'Server ID not found. Please contact support.';
            $this->serverMessageType = 'error';
            return;
        }

        $this->isLoadingServer = true;
        
        try {
            $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $vpsId)->first();
            if (!$proxmoxVm) {
                $this->serverMessage = 'VM not found for snapshot creation.';
                $this->serverMessageType = 'error';
                $this->isLoadingServer = false;
                return;
            }
            
            $node = $proxmoxVm->node ?? config('proxmox.node', 'ns548195');
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
            } else {
                $proxmox = app(\App\Services\ProxmoxApiService::class);
            }
            
            $snapshotName = 'manual-' . now()->format('Y-m-d-H-i-s');
            $result = $proxmox->createSnapshot((int) $vpsId, $node, $snapshotName);
            
            if ($result['success'] ?? false) {
                \App\Models\VpsSnapshot::create([
                    'user_id' => Auth::id(),
                    'proxmox_vm_id' => $proxmoxVm->id,
                    'name' => 'Manual Snapshot ' . now()->format('M d, Y H:i'),
                    'proxmox_snapshot_id' => $snapshotName,
                    'type' => 'manual',
                    'status' => 'completed',
                    'size_bytes' => 0,
                    'description' => 'Manual snapshot created from dashboard',
                    'snapshotted_at' => now(),
                ]);
                
                $this->serverMessage = 'Snapshot created successfully!';
                $this->serverMessageType = 'success';
            } else {
                $this->serverMessage = 'Failed to create snapshot: ' . ($result['message'] ?? 'Unknown error');
                $this->serverMessageType = 'error';
            }
        } catch (\Exception $e) {
            \Log::error('Snapshot creation failed', [
                'user_id' => Auth::id(),
                'hosting_id' => $this->selectedHosting->id,
                'error' => $e->getMessage(),
            ]);
            $this->serverMessage = 'Failed to create snapshot: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }
        
        $this->isLoadingServer = false;
        $this->dispatch('clearServerMessage');
    }

    /**
     * Restore a snapshot
     */
    public function restoreSnapshot(int $snapshotId): void
    {
        $snapshot = \App\Models\VpsSnapshot::where('id', $snapshotId)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$snapshot) {
            $this->serverMessage = 'Snapshot not found.';
            $this->serverMessageType = 'error';
            return;
        }

        $this->isLoadingServer = true;
        
        try {
            $vm = $snapshot->vm;
            if (!$vm) {
                $this->serverMessage = 'VM not found for snapshot restore.';
                $this->serverMessageType = 'error';
                $this->isLoadingServer = false;
                return;
            }
            
            $node = $vm->node ?? config('proxmox.node', 'ns548195');
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
            } else {
                $proxmox = app(\App\Services\ProxmoxApiService::class);
            }
            
            $result = $proxmox->restoreSnapshot((int) $vm->vmid, $node, $snapshot->proxmox_snapshot_id);
            
            if ($result['success'] ?? false) {
                $snapshot->update(['status' => 'restored']);
                $this->serverMessage = 'Snapshot restored successfully! VM will restart.';
                $this->serverMessageType = 'success';
            } else {
                $this->serverMessage = 'Failed to restore snapshot: ' . ($result['message'] ?? 'Unknown error');
                $this->serverMessageType = 'error';
            }
        } catch (\Exception $e) {
            \Log::error('Snapshot restore failed', [
                'snapshot_id' => $snapshotId,
                'error' => $e->getMessage(),
            ]);
            $this->serverMessage = 'Failed to restore snapshot: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }
        
        $this->isLoadingServer = false;
        $this->dispatch('clearServerMessage');
    }

    /**
     * Delete a snapshot
     */
    public function deleteSnapshot(int $snapshotId): void
    {
        $snapshot = \App\Models\VpsSnapshot::where('id', $snapshotId)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$snapshot) {
            $this->serverMessage = 'Snapshot not found.';
            $this->serverMessageType = 'error';
            return;
        }

        $this->isLoadingServer = true;
        
        try {
            $vm = $snapshot->vm;
            if (!$vm) {
                $this->serverMessage = 'VM not found for snapshot deletion.';
                $this->serverMessageType = 'error';
                $this->isLoadingServer = false;
                return;
            }
            
            $node = $vm->node ?? config('proxmox.node', 'ns548195');
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
            } else {
                $proxmox = app(\App\Services\ProxmoxApiService::class);
            }
            
            $result = $proxmox->deleteSnapshot((int) $vm->vmid, $node, $snapshot->proxmox_snapshot_id);
            
            if ($result['success'] ?? false) {
                $snapshot->update(['status' => 'deleted']);
                $snapshot->delete();
                $this->serverMessage = 'Snapshot deleted successfully!';
                $this->serverMessageType = 'success';
            } else {
                $this->serverMessage = 'Failed to delete snapshot: ' . ($result['message'] ?? 'Unknown error');
                $this->serverMessageType = 'error';
            }
        } catch (\Exception $e) {
            \Log::error('Snapshot delete failed', [
                'snapshot_id' => $snapshotId,
                'error' => $e->getMessage(),
            ]);
            $this->serverMessage = 'Failed to delete snapshot: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }
        
        $this->isLoadingServer = false;
        $this->dispatch('clearServerMessage');
    }

    /**
     * Set backup retention period (1, 7, or 30 days)
     */
    public function setBackupRetention(int $days): void
    {
        if (!$this->selectedHosting) return;
        
        if (!in_array($days, [1, 7, 30])) {
            $this->serverMessage = 'Invalid retention period. Choose 1, 7, or 30 days.';
            $this->serverMessageType = 'error';
            return;
        }
        
        $this->selectedHosting->update([
            'backup_retention_days' => $days,
        ]);
        $this->selectedHosting->refresh();
        
        $this->serverMessage = "Backup retention set to {$days} day" . ($days > 1 ? 's' : '') . '.';
        $this->serverMessageType = 'success';
        $this->dispatch('clearServerMessage');
    }

    /**
     * Get the next higher VPS plan above the current one
     */
    public function getNextUpgradePlan(): ?\App\Models\VpsPlan
    {
        if (!$this->selectedHosting) return null;
        $allPlans = \App\Models\VpsPlan::where('is_active', true)->orderBy('sort_order')->get();
        $currentPlan = $allPlans->firstWhere('name', $this->selectedHosting->plan_name);
        if (!$currentPlan) return null;
        return $allPlans->first(fn($p) => $p->sort_order > $currentPlan->sort_order);
    }

    /**
     * Get the Virtualizor VNC console URL for the current VPS
     */
    public function getConsoleUrl(): ?string
    {
        if (!$this->selectedHosting) return null;
        $vpsId = $this->selectedHosting->virtualizor_vps_id ?? $this->selectedHosting->vps_id ?? null;
        if (!$vpsId) return null;
        $virtualizor = app(\App\Services\VirtualizorApiService::class);
        if (!$virtualizor->isConfigured()) return null;
        $baseUrl = rtrim(config('server-management.virtualizor.base_url', ''), '/');
        return "{$baseUrl}/vnc.php?vmid={$vpsId}";
    }

    /**
     * Confirm server action (show modal)
     */
    public function confirmServerAction(string $action, string $title = '', string $message = ''): void
    {
        $this->pendingAction = $action;
        $this->pendingActionTitle = $title ?: ucfirst($action) . ' Server';
        $this->pendingActionMessage = $message ?: 'Are you sure you want to ' . $action . ' this server?';
        $this->showActionModal = true;
    }

    /**
     * Cancel pending action
     */
    public function cancelServerAction(): void
    {
        $this->showActionModal = false;
        $this->pendingAction = '';
        $this->pendingActionTitle = '';
        $this->pendingActionMessage = '';
    }

    /**
     * Execute confirmed server action
     */
    public function executeServerAction(): void
    {
        $this->showActionModal = false;
        
        if (!$this->pendingAction || !$this->selectedHosting) {
            return;
        }

        $this->isLoadingServer = true;
        
        try {
            $vpsId = $this->selectedHosting->vps_id ?? null;
            
            if (!$vpsId) {
                $this->serverMessage = 'Server ID not found. Please contact support.';
                $this->serverMessageType = 'error';
                $this->isLoadingServer = false;
                return;
            }

            // Use Proxmox API for power actions
            $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $vpsId)->first();
            $node = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
            
            // Build Proxmox API service for this VM's node
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
            } else {
                $proxmox = app(\App\Services\ProxmoxApiService::class);
            }
            
            $success = match (true) {
                $this->pendingAction === 'start'    => $proxmox->startVm((int) $vpsId),
                $this->pendingAction === 'stop'     => $proxmox->shutdownVm((int) $vpsId),
                $this->pendingAction === 'restart'  => $proxmox->rebootVm((int) $vpsId),
                $this->pendingAction === 'poweroff' => $proxmox->stopVm((int) $vpsId),
                str_starts_with($this->pendingAction, 'migrate_') => $this->migrateVmToNode(
                    (int) $vpsId, substr($this->pendingAction, 8), $proxmox, $proxmoxVm
                ),
                default => false,
            };

            if ($this->pendingAction === 'rebuild') {
                $this->rebuildVm((int) $vpsId, $proxmox, $proxmoxVm);
                $this->pendingAction = '';
                $this->isLoadingServer = false;
                return;
            }

            if ($this->pendingAction === 'reinstall') {
                $this->rebuildVm((int) $vpsId, $proxmox, $proxmoxVm);
                $this->pendingAction = '';
                $this->isLoadingServer = false;
                return;
            }

            if ($this->pendingAction === 'reset_password') {
                $this->resetRootPassword((int) $vpsId, $proxmox, $proxmoxVm);
                $this->pendingAction = '';
                $this->isLoadingServer = false;
                return;
            }

            if ($this->pendingAction === 'rescue') {
                // Rescue mode: stop VM, change boot order to rescue ISO, start
                $stopped = $proxmox->stopVm((int) $vpsId);
                if ($stopped) {
                    sleep(3);
                    $proxmox->updateVmConfig((int) $vpsId, ['boot' => 'order=ide2;scsi0;net0']);
                    $proxmox->startVm((int) $vpsId);
                    $this->serverMessage = 'Server rebooted into rescue mode. Connect via VNC to access.';
                    $this->serverMessageType = 'success';
                } else {
                    $this->serverMessage = 'Failed to enter rescue mode.';
                    $this->serverMessageType = 'error';
                }
                $this->pendingAction = '';
                $this->isLoadingServer = false;
                $this->loadServerDetails();
                return;
            }

            if ($this->pendingAction === 'terminate') {
                $deleted = $proxmox->deleteVm((int) $vpsId, true);
                if ($deleted) {
                    if ($proxmoxVm) $proxmoxVm->delete();
                    $this->selectedHosting->update(['status' => 'cancelled', 'vps_id' => null]);
                    $this->serverMessage = 'Server terminated successfully.';
                    $this->serverMessageType = 'success';
                    $this->closeHostingView();
                } else {
                    $this->serverMessage = 'Failed to terminate server.';
                    $this->serverMessageType = 'error';
                }
                $this->pendingAction = '';
                $this->isLoadingServer = false;
                return;
            }
            
            if ($success) {
                $actionLabels = [
                    'start'    => 'started',
                    'stop'     => 'stopped',
                    'restart'  => 'restarted',
                    'poweroff' => 'powered off',
                ];
                $this->serverMessage = 'Server ' . ($actionLabels[$this->pendingAction] ?? $this->pendingAction) . ' successfully.';
                $this->serverMessageType = 'success';
                
                // Send notification to user
                $notifEvent = match($this->pendingAction) {
                    'start'    => 'vm_started',
                    'stop'     => 'vm_stopped',
                    'restart'  => 'vm_restarted',
                    'poweroff' => 'vm_stopped',
                    default    => null,
                };
                if ($notifEvent) {
                    Auth::user()->notify(new \App\Notifications\VpsServerNotification($notifEvent, [
                        'hostname' => $this->selectedHosting->server_hostname ?? $this->selectedHosting->plan_name,
                        'vmid'     => $vpsId,
                    ]));
                }
                
                // Update hosting status in DB
                if ($this->pendingAction === 'start') {
                    $this->selectedHosting->update(['status' => 'active']);
                } elseif (in_array($this->pendingAction, ['stop', 'poweroff'])) {
                    $this->selectedHosting->update(['status' => 'suspended']);
                }
            } else {
                $this->serverMessage = 'Action failed. Please try again or contact support.';
                $this->serverMessageType = 'error';
            }
            
            // Bust cache so next poll/refresh gets fresh data (no sleep/blocking call)
            \Illuminate\Support\Facades\Cache::forget('server_details_' . $this->selectedHosting->id);
            \Illuminate\Support\Facades\Cache::forget('vm_status_' . ($this->selectedHosting->vps_id ?? 0));
            
        } catch (\Exception $e) {
            \Log::error('Server action failed', [
                'action' => $this->pendingAction,
                'hosting_id' => $this->selectedHosting->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->serverMessage = 'Action failed: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }
        
        $this->pendingAction = '';
        $this->isLoadingServer = false;
        
        // Auto-clear message after 5 seconds
        $this->dispatch('clearServerMessage');
    }

    /**
     * Rebuild VM - delete existing and recreate from template
     */
    protected function rebuildVm(int $vmid, \App\Services\ProxmoxApiService $proxmox, ?\App\Models\ProxmoxVm $proxmoxVm): void
    {
        try {
            // Stop VM first if running
            $status = $proxmox->getVmStatus($vmid);
            if (($status['status'] ?? '') === 'running') {
                $proxmox->stopVm($vmid);
                sleep(3);
            }
            
            // Delete existing VM
            $deleted = $proxmox->deleteVm($vmid, true);
            if (!$deleted) {
                $this->serverMessage = 'Rebuild failed: Could not delete existing VM.';
                $this->serverMessageType = 'error';
                return;
            }
            
            sleep(2);
            
            // Recreate VM with same specs
            $config = [
                'name'    => $proxmoxVm?->name ?? 'vps-' . $vmid,
                'cpu'     => $proxmoxVm?->cpu_cores ?? $this->selectedHosting->cpu_cores ?? 1,
                'memory'  => $proxmoxVm?->memory_mb ?? 1024,
                'disk'    => $proxmoxVm?->disk_gb ?? 20,
                'iso'     => $proxmoxVm?->iso ?? 'ubuntu-22.04-live-server-amd64.iso',
                'storage' => $proxmoxVm?->storage ?? 'local',
                'vmid'    => $vmid, // Reuse same VMID
            ];
            
            $result = $proxmox->createAndStartVm($config);
            
            if ($result) {
                // Update ProxmoxVm record
                if ($proxmoxVm) {
                    $proxmoxVm->update([
                        'status'     => 'running',
                        'started_at' => now(),
                    ]);
                }
                
                $this->selectedHosting->update(['status' => 'active']);
                $this->serverMessage = 'Server rebuilt successfully! It will be ready in a few minutes.';
                $this->serverMessageType = 'success';
                
                // Notify user
                $user = \App\Models\User::find($this->selectedHosting->user_id);
                if ($user) {
                    $user->notify(new \App\Notifications\VpsServerNotification('vm_rebuilt', [
                        'hostname' => $this->selectedHosting->server_hostname ?? $this->selectedHosting->plan_name,
                        'vmid'     => $vmid,
                    ]));
                }
            } else {
                $this->serverMessage = 'Rebuild failed: Could not create new VM.';
                $this->serverMessageType = 'error';
            }
            
            // Bust cache — user can click Refresh to get updated state
            \Illuminate\Support\Facades\Cache::forget('server_details_' . $this->selectedHosting->id);
            \Illuminate\Support\Facades\Cache::forget('vm_status_' . $vmid);
            
        } catch (\Exception $e) {
            \Log::error('VM rebuild failed', ['vmid' => $vmid, 'error' => $e->getMessage()]);
            $this->serverMessage = 'Rebuild failed: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }
    }

    /**
     * Reset root password via Proxmox cloud-init and notify user via email
     */
    protected function resetRootPassword(int $vmid, \App\Services\ProxmoxApiService $proxmox, ?\App\Models\ProxmoxVm $proxmoxVm): void
    {
        try {
            // Generate new secure password
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
            $newPassword = '';
            for ($i = 0; $i < 16; $i++) {
                $newPassword .= $chars[random_int(0, strlen($chars) - 1)];
            }

            // Set new password via Proxmox cloud-init config
            $updated = $proxmox->updateVmConfig($vmid, [
                'cipassword' => $newPassword,
            ]);

            // Always save to DB regardless of Proxmox response
            $this->selectedHosting->update(['root_password' => $newPassword]);
            if ($proxmoxVm) {
                $proxmoxVm->update(['panel_password' => $newPassword]);
            }

            // Send email to user with new password
            $user = \App\Models\User::find($this->selectedHosting->user_id);
            if ($user && $user->email) {
                try {
                    \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($user, $newPassword, $vmid) {
                        $message->to($user->email)
                            ->subject('🔑 Your VPS Root Password Has Been Reset')
                            ->html("
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f172a; color: #fff; padding: 40px; border-radius: 16px;'>
                                    <h2 style='color: #00b7ff; margin-bottom: 20px;'>Root Password Reset</h2>
                                    <p style='color: #94a3b8;'>Your VPS root password has been reset successfully.</p>
                                    <div style='background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; margin: 20px 0;'>
                                        <p style='color: #94a3b8; font-size: 12px; margin-bottom: 8px;'>VM ID</p>
                                        <p style='color: #00b7ff; font-family: monospace; font-size: 18px; font-weight: bold;'>{$vmid}</p>
                                        <p style='color: #94a3b8; font-size: 12px; margin-top: 16px; margin-bottom: 8px;'>New Root Password</p>
                                        <p style='color: #22c55e; font-family: monospace; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>{$newPassword}</p>
                                    </div>
                                    <p style='color: #ef4444; font-size: 12px;'>⚠️ Please save this password securely. For security, change it after logging in.</p>
                                    <p style='color: #64748b; font-size: 11px; margin-top: 20px;'>Team BelieVoo</p>
                                </div>
                            ");
                    });
                } catch (\Exception $mailEx) {
                    \Log::warning('Failed to send password reset email', ['error' => $mailEx->getMessage()]);
                }
            }

            $this->serverMessage = '✅ New root password generated and sent to your email: ' . ($user->email ?? 'on file') . '. Also visible in Connection Details below.';
            $this->serverMessageType = 'success';
            
            // Send notification
            if ($user) {
                $user->notify(new \App\Notifications\VpsServerNotification('password_reset', [
                    'hostname' => $this->selectedHosting->server_hostname ?? $this->selectedHosting->plan_name,
                    'vmid'     => $vmid,
                ]));
            }
            
            // Refresh hosting to show new password in Connection Details
            $this->selectedHosting->refresh();

        } catch (\Exception $e) {
            \Log::error('Reset password failed', ['vmid' => $vmid, 'error' => $e->getMessage()]);
            $this->serverMessage = 'Password reset failed: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }
    }

    /**
     * Migrate VM to a different Proxmox node
     * Uses new UPID-based tracking system
     */
    protected function migrateVmToNode(int $vmid, string $targetNode, \App\Services\ProxmoxApiService $proxmox, ?\App\Models\ProxmoxVm $proxmoxVm): bool
    {
        try {
            // Use the new API that returns UPID for tracking
            $upid = $proxmox->migrateVm($vmid, $targetNode, true);
            
            if ($upid) {
                // Create migration record for tracking
                $migration = \App\Models\VmMigration::create([
                    'user_id' => Auth::id(),
                    'proxmox_vm_id' => $proxmoxVm?->id,
                    'source_node' => $proxmoxVm?->node ?? config('proxmox.node'),
                    'target_node' => $targetNode,
                    'vmid' => $vmid,
                    'status' => 'migrating',
                    'progress_percent' => 0,
                    'status_message' => 'Migration started...',
                    'proxmox_upid' => $upid,
                    'task_status' => 'running',
                    'is_live_migration' => true,
                    'migration_type' => 'online',
                    'started_at' => now(),
                ]);
                
                // Set active migration for UI
                $this->activeMigration = $migration;
                $this->migrationProgress = 0;
                $this->migrationStatus = 'migrating';
                $this->migrationMessage = 'Migration started...';
                
                // Broadcast event
                try {
                    broadcast(new \App\Events\VmMigrationStarted($migration))->toOthers();
                } catch (\Exception $e) {
                    \Log::warning('Migration broadcast failed: ' . $e->getMessage());
                }
                
                $this->serverMessage = "Live migration to {$targetNode} initiated! Your website will remain online during migration.";
                $this->serverMessageType = 'success';
                return true;
            }
            
            $this->serverMessage = "Migration to {$targetNode} failed. Check node capacity or VM status.";
            $this->serverMessageType = 'error';
            return false;
            
        } catch (\Exception $e) {
            \Log::error('VM migration failed', ['vmid' => $vmid, 'target' => $targetNode, 'error' => $e->getMessage()]);
            $this->serverMessage = 'Migration failed: ' . $e->getMessage();
            $this->serverMessageType = 'error';
            return false;
        }
    }
    
    /**
     * Open migration modal and load available nodes
     */
    public function openMigrationModal(): void
    {
        if (!$this->selectedHosting) {
            $this->serverMessage = 'No server selected';
            $this->serverMessageType = 'error';
            return;
        }
        
        $this->isLoadingMigration = true;
        $this->showMigrationModal = true;
        $this->migrationNodes = [];
        $this->selectedTargetNode = null;
        
        try {
            $vpsId = $this->selectedHosting->vps_id ?? null;
            if (!$vpsId) {
                $this->serverMessage = 'VPS ID not found';
                $this->serverMessageType = 'error';
                $this->isLoadingMigration = false;
                return;
            }
            
            // Get Proxmox VM record
            $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $vpsId)->first();
            if ($proxmoxVm) {
                $this->proxmoxVmId = $proxmoxVm->id;
            }
            
            // Check for existing active migration
            $activeMigration = \App\Models\VmMigration::where('proxmox_vm_id', $proxmoxVm?->id)
                ->whereIn('status', ['pending', 'migrating'])
                ->first();
                
            if ($activeMigration) {
                $this->activeMigration = $activeMigration;
                $this->migrationProgress = $activeMigration->progress_percent;
                $this->migrationStatus = $activeMigration->status;
                $this->migrationMessage = $activeMigration->status_message;
            }
            
            // Build Proxmox API service
            $node = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
            } else {
                $proxmox = app(\App\Services\ProxmoxApiService::class);
            }
            
            // Fetch available nodes
            $nodes = $proxmox->listClusterNodes();
            
            // Filter out current node and unavailable nodes
            $this->migrationNodes = array_values(array_filter($nodes, function($n) use ($node) {
                return $n['name'] !== $node && ($n['available_for_migration'] ?? false);
            }));
            
        } catch (\Exception $e) {
            \Log::error('Failed to load migration nodes', ['error' => $e->getMessage()]);
            $this->serverMessage = 'Failed to load available nodes: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }
        
        $this->isLoadingMigration = false;
    }
    
    /**
     * Close migration modal
     */
    public function closeMigrationModal(): void
    {
        $this->showMigrationModal = false;
        $this->migrationNodes = [];
        $this->selectedTargetNode = null;
    }
    
    /**
     * Start VM migration from modal
     */
    public function startVmMigration(): void
    {
        if (!$this->selectedTargetNode || !$this->selectedHosting) {
            $this->serverMessage = 'Please select a target node';
            $this->serverMessageType = 'error';
            return;
        }
        
        $this->isLoadingMigration = true;
        
        try {
            $vpsId = $this->selectedHosting->vps_id ?? null;
            if (!$vpsId) {
                $this->serverMessage = 'VPS ID not found';
                $this->serverMessageType = 'error';
                $this->isLoadingMigration = false;
                return;
            }
            
            // Get Proxmox VM
            $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $vpsId)->first();
            $node = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
            
            // Build Proxmox API service
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
            } else {
                $proxmox = app(\App\Services\ProxmoxApiService::class);
            }
            
            // Start migration
            $success = $this->migrateVmToNode((int) $vpsId, $this->selectedTargetNode, $proxmox, $proxmoxVm);
            
            if ($success) {
                $this->closeMigrationModal();
                $this->dispatch('migration-started');
            }
            
        } catch (\Exception $e) {
            \Log::error('Migration start failed', ['error' => $e->getMessage()]);
            $this->serverMessage = 'Migration failed: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }
        
        $this->isLoadingMigration = false;
    }
    
    /**
     * Refresh migration progress (called by polling)
     */
    public function refreshMigrationProgress(): void
    {
        if (!$this->activeMigration) {
            return;
        }
        
        try {
            $migration = \App\Models\VmMigration::find($this->activeMigration['id'] ?? $this->activeMigration?->id);
            if (!$migration) {
                return;
            }
            
            // Fetch progress from Proxmox
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $migration->source_node)->first();
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $migration->source_node);
            } else {
                $proxmox = app(\App\Services\ProxmoxApiService::class);
            }
            
            $progressData = $proxmox->getMigrationProgress($migration->source_node, $migration->proxmox_upid);
            
            if ($progressData['success']) {
                $migration->update([
                    'progress_percent' => $progressData['progress'],
                    'status' => $progressData['status'] === 'completed' ? 'completed' : ($progressData['status'] === 'failed' ? 'failed' : 'migrating'),
                    'status_message' => $progressData['message'],
                    'task_status' => $progressData['status'],
                    'completed_at' => $progressData['status'] === 'completed' ? now() : null,
                    'duration_seconds' => $progressData['duration'],
                ]);
                
                // Update VM node on completion
                if ($progressData['status'] === 'completed' && $migration->proxmoxVm) {
                    $migration->proxmoxVm->update(['node' => $migration->target_node]);
                }
                
                // Update UI
                $this->activeMigration = $migration->fresh();
                $this->migrationProgress = $progressData['progress'];
                $this->migrationStatus = $progressData['status'];
                $this->migrationMessage = $progressData['message'];
                
                // Broadcast progress
                if ($progressData['status'] === 'migrating') {
                    try {
                        broadcast(new \App\Events\VmMigrationProgress($migration))->toOthers();
                    } catch (\Exception $e) {
                        // Silent fail for broadcast
                    }
                } elseif ($progressData['status'] === 'completed') {
                    try {
                        broadcast(new \App\Events\VmMigrationCompleted($migration))->toOthers();
                    } catch (\Exception $e) {
                        // Silent fail for broadcast
                    }
                    $this->serverMessage = 'Migration completed successfully!';
                    $this->serverMessageType = 'success';
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to refresh migration progress', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ================= SERVER IMPORT METHODS =================
     */

    /**
     * Start server import from external server
     */
    public function startServerImport(): void
    {
        if (!$this->selectedHosting) {
            $this->serverMessage = 'No server selected';
            $this->serverMessageType = 'error';
            return;
        }

        // Validate inputs
        if (empty($this->serverImportSourceIp) || empty($this->serverImportSourcePassword)) {
            $this->serverMessage = 'Please enter source server IP and password';
            $this->serverMessageType = 'error';
            return;
        }

        $this->isLoadingServerImport = true;

        try {
            // Check for existing active import
            $existingImport = \App\Models\ServerImport::where('user_hosting_id', $this->selectedHosting->id)
                ->active()
                ->first();

            if ($existingImport) {
                $this->activeServerImport = $existingImport->toArray();
                $this->serverImportProgress = $existingImport->progress_percent;
                $this->serverImportStatus = $existingImport->status;
                $this->serverImportMessage = $existingImport->status_message;
                $this->serverMessage = 'An import is already in progress';
                $this->serverMessageType = 'warning';
                $this->isLoadingServerImport = false;
                return;
            }

            $isPull = $this->serverImportSyncDirection === 'pull';
            $directionLabel = $isPull ? 'Import' : 'Export';

            // Create sync record
            $import = \App\Models\ServerImport::create([
                'user_id' => Auth::id(),
                'user_hosting_id' => $this->selectedHosting->id,
                'sync_direction' => $this->serverImportSyncDirection,
                'source_ip' => $this->serverImportSourceIp,
                'source_root_password' => $this->serverImportSourcePassword,
                'source_port' => $this->serverImportSourcePort,
                'status' => 'pending',
                'progress_percent' => 0,
                'status_message' => $isPull 
                    ? 'Import queued: Preparing to sync data from remote server to BelieVoo...'
                    : 'Export queued: Preparing to sync data from BelieVoo to remote server...',
                'started_at' => now(),
            ]);

            // Set active import
            $this->activeServerImport = $import->toArray();
            $this->serverImportProgress = 0;
            $this->serverImportStatus = 'pending';
            $this->serverImportMessage = $isPull 
                ? 'Import queued: Preparing to sync data from remote server to BelieVoo...'
                : 'Export queued: Preparing to sync data from BelieVoo to remote server...';

            // Dispatch the new ServerSyncJob
            \App\Jobs\ServerSyncJob::dispatch($import);

            // Broadcast event
            try {
                broadcast(new \App\Events\ServerImportStarted($import))->toOthers();
            } catch (\Exception $e) {
                \Log::warning('ServerImport broadcast failed: ' . $e->getMessage());
            }

            // Dispatch browser event
            $this->dispatch('import-started');

            $this->serverMessage = 'Server import started! Data will be synced from ' . $this->serverImportSourceIp;
            $this->serverMessageType = 'success';

        } catch (\Exception $e) {
            \Log::error('Server import start failed', ['error' => $e->getMessage()]);
            $this->serverMessage = 'Failed to start import: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }

        $this->isLoadingServerImport = false;
    }

    /**
     * Cancel active server import
     */
    public function cancelServerImport(): void
    {
        if (!$this->activeServerImport) {
            return;
        }

        try {
            $import = \App\Models\ServerImport::find($this->activeServerImport['id'] ?? null);
            
            if ($import && in_array($import->status, ['pending', 'connecting', 'syncing'])) {
                $import->update([
                    'status' => 'failed',
                    'status_message' => 'Cancelled by user',
                    'completed_at' => now(),
                ]);

                $this->activeServerImport = $import->fresh()->toArray();
                $this->serverImportStatus = 'failed';
                $this->serverImportMessage = 'Import cancelled by user';

                $this->serverMessage = 'Import cancelled';
                $this->serverMessageType = 'info';
            }
        } catch (\Exception $e) {
            \Log::error('Failed to cancel import', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Refresh server import progress
     */
    public function refreshServerImportProgress(): void
    {
        if (!$this->activeServerImport) {
            return;
        }

        try {
            $import = \App\Models\ServerImport::find($this->activeServerImport['id'] ?? null);
            
            if (!$import) {
                return;
            }

            // Update UI state with live progress details
            $this->activeServerImport = $import->toArray();
            $this->serverImportProgress = $import->progress_percent;
            $this->serverImportStatus = $import->status;
            $this->serverImportMessage = $import->status_message;
            $this->serverImportSyncLog = $import->sync_log;
            $this->serverImportTransferSpeed = $import->transfer_speed;
            $this->serverImportEta = $import->eta_seconds ? $this->formatEta($import->eta_seconds) : '';

            // Handle completion
            if ($import->status === 'completed') {
                $this->dispatch('import-completed');
                $directionLabel = $import->isPull() ? 'Import' : 'Export';
                $this->serverMessage = "Server {$directionLabel} completed successfully!";
                $this->serverMessageType = 'success';
                
                // Refresh server details
                $this->loadServerDetails();
            } elseif ($import->status === 'failed') {
                $this->dispatch('import-failed');
                $directionLabel = $import->isPull() ? 'Import' : 'Export';
                $this->serverMessage = "{$directionLabel} failed: " . $import->error_log;
                $this->serverMessageType = 'error';
            }

        } catch (\Exception $e) {
            \Log::error('Failed to refresh import progress', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Load active server import for current hosting
     */
    public function loadActiveServerImport(): void
    {
        if (!$this->selectedHosting) {
            return;
        }

        try {
            $import = \App\Models\ServerImport::where('user_hosting_id', $this->selectedHosting->id)
                ->active()
                ->latest()
                ->first();

            if ($import) {
                $this->activeServerImport = $import->toArray();
                $this->serverImportProgress = $import->progress_percent;
                $this->serverImportStatus = $import->status;
                $this->serverImportMessage = $import->status_message;
                $this->serverImportSyncLog = $import->sync_log;
                $this->serverImportTransferSpeed = $import->transfer_speed;
                $this->serverImportSyncDirection = $import->sync_direction;
                $this->serverImportActiveTab = $import->isPull() ? 'import' : 'export';
            } else {
                // Check for completed import
                $completedImport = \App\Models\ServerImport::where('user_hosting_id', $this->selectedHosting->id)
                    ->where('status', 'completed')
                    ->latest()
                    ->first();
                
                if ($completedImport && $completedImport->completed_at->diffInHours(now()) < 24) {
                    $this->activeServerImport = $completedImport->toArray();
                    $this->serverImportProgress = 100;
                    $this->serverImportStatus = 'completed';
                    $this->serverImportMessage = $completedImport->isPull() ? 'Import completed' : 'Export completed';
                    $this->serverImportSyncLog = $completedImport->sync_log;
                    $this->serverImportSyncDirection = $completedImport->sync_direction;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to load active import', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Format ETA seconds to readable string
     */
    private function formatEta(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
        } else {
            return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        }
    }

    /**
     * Clear server action message
     */
    public function clearServerMessage(): void
    {
        $this->serverMessage = null;
    }

    /**
     * Refresh server status — busts the cache so a fresh Proxmox call is made.
     */
    public function refreshServerStatus(): void
    {
        if ($this->selectedHosting) {
            \Illuminate\Support\Facades\Cache::forget('server_details_' . $this->selectedHosting->id);
        }
        $this->loadServerDetails();
        $this->serverMessage = 'Server status refreshed';
        $this->serverMessageType = 'info';
        $this->dispatch('clearServerMessage');
    }

    /**
     * Open domain add modal
     */
    public function openDomainModal(): void
    {
        $this->newDomain = '';
        $this->showDomainModal = true;
    }

    /**
     * Close domain modal
     */
    public function closeDomainModal(): void
    {
        $this->showDomainModal = false;
        $this->newDomain = '';
    }

    /**
     * Add a domain to the hosting (Secondary DNS)
     */
    public function addDomain(): void
    {
        $this->validate([
            'newDomain' => 'required|string|regex:/^[a-zA-Z0-9][a-zA-Z0-9\-\.]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/',
        ], [
            'newDomain.required' => 'Please enter a domain name.',
            'newDomain.regex' => 'Please enter a valid domain name (e.g., example.com).',
        ]);

        if (!$this->selectedHosting) return;

        // Save domain as primary_domain if not set, otherwise append to admin_notes
        if (empty($this->selectedHosting->primary_domain)) {
            $this->selectedHosting->update(['primary_domain' => $this->newDomain]);
        } else {
            // Store additional domains in admin_notes as JSON
            $existingNotes = $this->selectedHosting->admin_notes ?? '';
            $domains = [];
            if (str_contains($existingNotes, 'extra_domains:')) {
                preg_match('/extra_domains:\[(.*?)\]/', $existingNotes, $matches);
                $domains = $matches[1] ? explode(',', $matches[1]) : [];
            }
            $domains[] = $this->newDomain;
            $domainsStr = 'extra_domains:[' . implode(',', array_unique($domains)) . ']';
            $cleanNotes = preg_replace('/extra_domains:\[.*?\]/', '', $existingNotes);
            $this->selectedHosting->update(['admin_notes' => trim($cleanNotes) . ' ' . $domainsStr]);
        }

        $this->serverMessage = "Domain '{$this->newDomain}' added successfully!";
        $this->serverMessageType = 'success';
        $this->closeDomainModal();
        $this->selectedHosting->refresh();
        $this->dispatch('clearServerMessage');
    }

    /**
     * Get all domains for the current hosting
     */
    public function getHostingDomains(): array
    {
        if (!$this->selectedHosting) return [];

        $domains = [];
        if ($this->selectedHosting->primary_domain) {
            $domains[] = $this->selectedHosting->primary_domain;
        }

        // Parse extra domains from admin_notes
        $notes = $this->selectedHosting->admin_notes ?? '';
        if (str_contains($notes, 'extra_domains:')) {
            preg_match('/extra_domains:\[(.*?)\]/', $notes, $matches);
            if (!empty($matches[1])) {
                $extra = explode(',', $matches[1]);
                $domains = array_merge($domains, array_filter($extra));
            }
        }

        return array_unique($domains);
    }

    /**
     * Change server hostname
     */
    public function changeHostname(): void
    {
        if (empty($this->newHostname)) {
            $this->serverMessage = 'Please enter a valid hostname';
            $this->serverMessageType = 'error';
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9](\.[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])*$/', $this->newHostname)) {
            $this->serverMessage = 'Invalid hostname format';
            $this->serverMessageType = 'error';
            return;
        }

        try {
            $this->isLoadingServer = true;
            
            $vpsId = $this->selectedHosting->vps_id ?? null;
            
            if (!$vpsId) {
                $this->serverMessage = 'VPS ID not found';
                $this->serverMessageType = 'error';
                $this->isLoadingServer = false;
                return;
            }

            // Update hostname via Proxmox VM config
            $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $vpsId)->first();
            $node = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = \App\Services\ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
            } else {
                $proxmox = app(\App\Services\ProxmoxApiService::class);
            }

            $updated = $proxmox->updateVmConfig((int) $vpsId, ['name' => $this->newHostname]);

            if ($updated) {
                // Update DB records
                if ($proxmoxVm) {
                    $proxmoxVm->update(['hostname' => $this->newHostname]);
                }
                $this->selectedHosting->update(['server_hostname' => $this->newHostname]);
                $this->selectedHosting->refresh();
                
                $this->serverMessage = 'Hostname changed to: ' . $this->newHostname;
                $this->serverMessageType = 'success';
                $this->newHostname = '';
                $this->loadServerDetails();
            } else {
                $this->serverMessage = 'Failed to change hostname. Check Proxmox API.';
                $this->serverMessageType = 'error';
            }
        } catch (\Exception $e) {
            $this->serverMessage = 'Error: ' . $e->getMessage();
            $this->serverMessageType = 'error';
        }

        $this->isLoadingServer = false;
        $this->dispatch('clearServerMessage');
    }

    public function closeHostingView()
    {
        $this->selectedHostingId = null;
        $this->selectedHosting = null;
        $this->serverDetails = null;
        $this->serverActions = [];
        $this->activeHostingTab = 'home';
        $this->serverMessage = null;
        $this->newHostname = '';
        $this->newDomain = '';
        $this->showDomainModal = false;
        
        // Reset migration state
        $this->showMigrationModal = false;
        $this->migrationNodes = [];
        $this->selectedTargetNode = null;
        $this->activeMigration = null;
        $this->migrationProgress = 0;
        $this->migrationStatus = '';
        $this->migrationMessage = '';
        $this->isLoadingMigration = false;
        $this->proxmoxVmId = null;
        
        // Reset server import state
        $this->serverImportSourceIp = '';
        $this->serverImportSourcePassword = '';
        $this->serverImportSourcePort = 22;
        $this->activeServerImport = null;
        $this->serverImportProgress = 0;
        $this->serverImportStatus = '';
        $this->serverImportMessage = '';
        $this->serverImportSyncLog = '';
        $this->serverImportTransferSpeed = '';
        $this->serverImportEta = '';
        $this->isLoadingServerImport = false;
        $this->serverImportConnectionTested = false;
        $this->serverImportConnectionResult = null;
        $this->serverImportSyncDirection = 'pull';
        $this->serverImportActiveTab = 'import';
    }

    public function openTicketModal()
    {
        $this->isTicketModalOpen = true;
    }

    public function closeTicketModal()
    {
        $this->isTicketModalOpen = false;
        $this->reset(['subject', 'message', 'priority']);
    }

    public function openUploadModal()
    {
        $this->isUploadModalOpen = true;
    }

    public function closeUploadModal()
    {
        $this->isUploadModalOpen = false;
        $this->reset(['assetFile', 'assetName', 'selectedProjectId']);
    }

    public function markNotificationsAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function openNotification(string $id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if (!$notification) {
            return;
        }
        $notification->markAsRead();
        $this->redirect(\App\Support\NotificationLink::url($notification));
    }

    public function createTicket()
    {
        $this->validate([
            'subject' => 'required|min:5',
            'message' => 'nullable|required_without:ticketAttachment|min:10',
            'priority' => 'required|in:low,medium,high',
            'ticketAttachment' => 'nullable|file|max:10240', // 10MB
        ]);

        $attachmentPath = null;
        if ($this->ticketAttachment) {
            $attachmentPath = $this->ticketAttachment->store('ticket-attachments', 'public');
        }

        $ticket = Ticket::create([
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'status' => 'in_progress', // Changed from 'open' to 'in_progress'
        ]);

        // User's initial message
        $msg = $ticket->messages()->create([
            'sender_type' => 'user',
            'sender_name' => Auth::user()->name,
            'message' => $this->message ?? '',
            'attachment' => $attachmentPath,
        ]);

        // Auto-Response Message (Template 2)
        $autoMsg = $ticket->messages()->create([
            'sender_type' => 'admin',
            'sender_name' => 'Team Believoo',
            'message' => "Hello,

Thanks for contacting Believoo! This is an automated confirmation that we’ve received your message.

Ticket ID: {$ticket->ticket_id}
Status: In Progress

One of our specialists will review your requirements and respond within 24 hours. In the meantime, feel free to explore our portfolio to see our next-gen work.

Excellence is on its way.

Team Believoo",
        ]);

        try {
            broadcast(new \App\Events\TicketMessageSent($msg))->toOthers();
            broadcast(new \App\Events\TicketMessageSent($autoMsg))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Ticket broadcast from dashboard failed: ' . $e->getMessage());
        }

        // Notify Client via Email & Database
        try {
            Auth::user()->notify(new \App\Notifications\TicketCreatedNotification($ticket));
        } catch (\Exception $e) {
            \Log::error('Client notification failed on dashboard: ' . $e->getMessage());
        }

        // Notify Admin
        $admin = \App\Models\User::where('email', 'admin@believoo.com')->first() ?? \App\Models\User::first();
        if ($admin) {
            $ticketUrl = \App\Filament\Resources\TicketResource::getUrl('view', ['record' => $ticket]);
            
            \Filament\Notifications\Notification::make()
                ->title('New Ticket Created')
                ->body("From: " . Auth::user()->name . " - " . $ticket->ticket_id)
                ->icon('heroicon-o-ticket')
                ->color('success')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url($ticketUrl),
                ])
                ->sendToDatabase($admin)
                ->broadcast($admin);
        }

        $this->closeTicketModal();
        session()->flash('ticket_success', 'Ticket created successfully! Our team will get back to you soon.');
    }

    public function uploadAsset()
    {
        $this->validate([
            'assetFile' => 'required|file|max:10240', // 10MB max
            'assetName' => 'required|min:3',
            'selectedProjectId' => 'required|exists:projects,id',
        ]);

        $path = $this->assetFile->store('project-assets/' . $this->selectedProjectId, 'public');

        ProjectAsset::create([
            'project_id' => $this->selectedProjectId,
            'name' => $this->assetName,
            'file_path' => $path,
            'type' => 'asset',
            'uploaded_by' => Auth::id(),
        ]);

        $this->closeUploadModal();
        session()->flash('vault_success', 'File uploaded to vault successfully!');
    }

    // Agreement Request Methods
    public function openAgreementRequestModal()
    {
        $this->isAgreementRequestModalOpen = true;
    }

    public function closeAgreementRequestModal()
    {
        $this->isAgreementRequestModalOpen = false;
        $this->reset(['projectName', 'projectDescription', 'requirements', 'budgetRange', 'timelineExpectation', 'preferredTechnology']);
    }

    public function createAgreementRequest()
    {
        $this->validate([
            'projectName' => 'required|min:3|max:255',
            'projectDescription' => 'required|min:20',
            'requirements' => 'nullable|min:10',
            'budgetRange' => 'nullable|string|max:100',
            'timelineExpectation' => 'nullable|string|max:100',
            'preferredTechnology' => 'nullable|string|max:100',
        ]);

        $request = AgreementRequest::create([
            'client_id' => Auth::id(),
            'project_name' => $this->projectName,
            'project_description' => $this->projectDescription,
            'requirements' => $this->requirements,
            'budget_range' => $this->budgetRange,
            'timeline_expectation' => $this->timelineExpectation,
            'preferred_technology' => $this->preferredTechnology,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        // Notify admins
        $admins = \App\Models\User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\AgreementRequestSubmittedNotification($request));
        }

        $this->closeAgreementRequestModal();
        session()->flash('request_success', 'Your project request has been submitted successfully! We will review it and create an agreement for you.');
        
        // Switch to agreements tab to show the request
        $this->activeTab = 'agreements';
    }

    public function downloadAsset($assetId)
    {
        $asset = ProjectAsset::findOrFail($assetId);
        
        // Ensure user has access (either uploader or project owner)
        if ($asset->uploaded_by !== Auth::id() && $asset->project->user_id !== Auth::id()) {
            abort(403);
        }

        return Storage::disk('public')->download($asset->file_path, $asset->name);
    }

    // Agreement Methods
    public function viewAgreement($agreementId)
    {
        $agreement = Agreement::with(['workItems', 'milestones', 'histories', 'tasks'])
            ->findOrFail($agreementId);

        if ($agreement->client_id !== Auth::id()) {
            abort(403);
        }

        $this->selectedAgreementId = (int) $agreement->id;

        // Update status to viewed if it was just sent
        if ($agreement->status === 'sent') {
            $agreement->update(['status' => 'viewed']);

            AgreementHistory::create([
                'agreement_id' => $agreement->id,
                'user_id' => Auth::id(),
                'action' => 'viewed',
                'description' => 'Client viewed the agreement',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    public function closeAgreementView()
    {
        $this->selectedAgreementId = null;
        $this->signatureData = '';
    }

    public function signAgreement()
    {
        if (!$this->selectedAgreementId) return;

        $selectedAgreement = Agreement::with(['workItems', 'milestones'])->findOrFail($this->selectedAgreementId);

        if ($selectedAgreement->client_id !== Auth::id()) abort(403);

        $this->validate([
            'signatureData' => 'required|min:2',
        ]);

        // Generate unique certificate ID
        $certificateId = 'CERT-' . strtoupper(uniqid()) . '-' . date('YmdHis');

        $selectedAgreement->update([
            'status' => 'signed',
            'client_signed_at' => now(),
            'client_signature_data' => $this->signatureData,
            'client_signature_ip' => request()->ip(),
            'client_signature_user_agent' => request()->userAgent(),
            'client_signature_certificate_id' => $certificateId,
        ]);

        AgreementHistory::create([
            'agreement_id' => $selectedAgreement->id,
            'user_id' => Auth::id(),
            'action' => 'signed',
            'description' => 'Client signed the agreement (Certificate: ' . $certificateId . ')',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Notify admin
        $admin = \App\Models\User::where('email', 'admin@believoo.com')->first() ?? \App\Models\User::first();
        if ($admin) {
            $admin->notify(new \App\Notifications\AgreementSignedNotification($selectedAgreement, 'client'));
        }

        session()->flash('agreement_success', 'Agreement signed successfully! We will proceed with your project.');
        $this->signatureData = '';
    }

    public function downloadAgreement()
    {
        if (!$this->selectedAgreementId) return;

        $selectedAgreement = Agreement::findOrFail($this->selectedAgreementId);
        if ($selectedAgreement->client_id !== Auth::id()) abort(403);

        return redirect()->route('client.agreement.download', $selectedAgreement);
    }

    // Project Tracking Methods
    public function viewProjectTracking($agreementId)
    {
        $agreement = Agreement::with(['tasks', 'milestones', 'workItems'])
            ->findOrFail($agreementId);

        if ($agreement->client_id !== Auth::id()) {
            abort(403);
        }

        $this->selectedAgreementId = (int) $agreement->id;
        $this->showProjectTracking = true;
    }

    public function closeProjectTracking()
    {
        $this->showProjectTracking = false;
    }

    /**
     * Get secure console URL using masked proxy (hides Proxmox host details)
     * Returns console.believoo.com URL instead of raw Proxmox IP
     */
    public function getSecureConsoleUrl(): ?string
    {
        if (!$this->selectedHosting) return null;
        
        $vpsId = $this->selectedHosting->vps_id ?? null;
        if (!$vpsId) return null;

        try {
            // Use new ConsoleProxyService for secure, masked console
            $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $vpsId)->first();
            $node = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
            
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxyService = \App\Services\ConsoleProxyService::forNode(
                    $proxmoxUrl,
                    $proxmoxNode->getDecryptedApiToken(),
                    $node
                );
            } else {
                $proxyService = app(\App\Services\ConsoleProxyService::class);
            }

            $session = $proxyService->createSecureConsoleSession((int) $vpsId, Auth::id());

            if ($session) {
                Log::info('Secure console session created', [
                    'user_id' => Auth::id(),
                    'hosting_id' => $this->selectedHosting->id,
                    'console_domain' => $session['domain'],
                ]);
                return $session['console_url'];
            }

            // Fallback to legacy method
            return $this->getConsoleUrl();

        } catch (\Exception $e) {
            \Log::warning('Secure console failed, using fallback', ['error' => $e->getMessage()]);
            return $this->getConsoleUrl();
        }
    }

    /**
     * Check if domain is using BelieVoo nameservers
     */
    public function checkNameserverStatus($domain): array
    {
        $domainNs = $domain->nameservers ?? [];
        $believooNs = DnsRecord::BELIEVOO_NS;

        $hasBelievooNs = false;
        foreach ($believooNs as $ns) {
            foreach ($domainNs as $domainNsItem) {
                if (str_contains(strtolower($domainNsItem), strtolower($ns))) {
                    $hasBelievooNs = true;
                    break 2;
                }
            }
        }

        return [
            'current_ns' => $domainNs,
            'recommended_ns' => $believooNs,
            'is_using_believoo' => $hasBelievooNs,
            'needs_update' => !$hasBelievooNs && $domain->use_believoo_dns,
        ];
    }

    // -----------------------------------------------------------------------
    // Phase 2 — Proxmox service resolver (Task 2.1)
    // -----------------------------------------------------------------------

    /**
     * Resolves the correct ProxmoxApiService instance for the current selectedHosting.
     * Looks up the ProxmoxNode by name (from ProxmoxVm record) and builds a per-node
     * service via forNode(). Falls back to the app-level singleton when no token is found.
     */
    private function resolveProxmoxService(): \App\Services\ProxmoxApiService
    {
        $proxmoxNode = null;

        if ($this->selectedHosting) {
            $vpsId = $this->selectedHosting->vps_id ?? null;

            if ($vpsId) {
                // Resolve node name via ProxmoxVm record
                $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $vpsId)->first();
                $nodeName  = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
                $proxmoxNode = \App\Models\ProxmoxNode::where('name', $nodeName)->first();
            }
        }

        if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
            $baseUrl = 'https://' . $proxmoxNode->hostname . ':' . ($proxmoxNode->port ?? 8006);
            return \App\Services\ProxmoxApiService::forNode(
                $baseUrl,
                $proxmoxNode->getDecryptedApiToken(),
                $proxmoxNode->name
            );
        }

        // Fallback to app-level service
        $this->actionMessage     = 'Limited connectivity — using default Proxmox connection.';
        $this->actionMessageType = 'info';
        return app(\App\Services\ProxmoxApiService::class);
    }

    // -----------------------------------------------------------------------
    // Phase 2 — VM status polling (Task 2.3)
    // -----------------------------------------------------------------------

    /**
     * Fetches the current VM status from the Proxmox API and maps it to
     * 'running', 'stopped', or 'unknown'. Called on mount and by wire:poll.
     * Cached for 15 seconds so button clicks don't trigger Proxmox calls.
     */
    public function refreshVmStatus(): void
    {
        if (!$this->selectedHosting) {
            $this->vmStatus = 'unknown';
            return;
        }

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        if (!$vmid) {
            $this->vmStatus = 'unknown';
            return;
        }

        // Return cached status immediately — avoids Proxmox round-trip on every click
        $cacheKey = 'vm_status_' . $vmid;
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached !== null) {
            $this->vmStatus = $cached;
            return;
        }

        try {
            $proxmox = $this->resolveProxmoxService();
            $status  = $proxmox->getVmStatus($vmid);

            if (is_array($status) && isset($status['status'])) {
                $raw = $status['status'];
            } elseif (is_string($status)) {
                $raw = $status;
            } else {
                $raw = null;
            }

            $this->vmStatus = match ($raw) {
                'running' => 'running',
                'stopped' => 'stopped',
                default   => 'unknown',
            };
        } catch (\Exception $e) {
            $this->vmStatus = 'unknown';
        }

        // Cache for 15 seconds
        \Illuminate\Support\Facades\Cache::put($cacheKey, $this->vmStatus, 15);
    }

    // -----------------------------------------------------------------------
    // Phase 2 — Power actions (Tasks 2.6 & 2.7)
    // -----------------------------------------------------------------------

    /**
     * Start the VM immediately (no confirmation required).
     */
    public function startServer(): void
    {
        if (!$this->selectedHosting) return;

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        $node = $this->selectedHosting->node ?? '';

        if ($this->isProtectedVm($vmid, $node)) return;

        $this->isActionInProgress = true;
        try {
            $proxmox = $this->resolveProxmoxService();
            $success = $proxmox->startVm($vmid);

            if ($success) {
                $this->actionMessage     = 'Server is starting up.';
                $this->actionMessageType = 'success';
                $this->auditLog('start', ['vmid' => $vmid, 'node' => $node, 'outcome' => 'success']);
            } else {
                $this->actionMessage     = 'Failed to start the server. Please try again.';
                $this->actionMessageType = 'error';
                $this->auditLog('start', ['vmid' => $vmid, 'node' => $node, 'outcome' => 'failure']);
            }
        } catch (\Exception $e) {
            $this->actionMessage     = 'An error occurred while starting the server.';
            $this->actionMessageType = 'error';
            $this->auditLog('start', ['vmid' => $vmid, 'node' => $node, 'outcome' => 'failure']);
        } finally {
            $this->isActionInProgress = false;
            // Bust caches so status badge reflects the new state
            \Illuminate\Support\Facades\Cache::forget('vm_status_' . $vmid);
            \Illuminate\Support\Facades\Cache::forget('server_details_' . ($this->selectedHosting->id ?? 0));
            $this->refreshVmStatus();
        }
        $this->dispatch('log-event',
            action: 'server_start',
            description: 'Server start command sent for ' . ($this->selectedHosting->server_hostname ?? 'VPS'),
            outcome: ($success ?? false) ? 'success' : 'failure'
        );
    }

    /**
     * Show confirmation modal for graceful shutdown.
     */
    public function confirmShutdown(): void
    {
        $this->pendingConfirmAction = 'shutdown';
        $this->confirmModalTitle    = 'Shutdown Server';
        $this->confirmModalMessage  = 'Are you sure you want to gracefully shut down this server?';
        $this->showConfirmModal     = true;
    }

    /**
     * Show confirmation modal for force stop.
     */
    public function confirmStop(): void
    {
        $this->pendingConfirmAction = 'stop';
        $this->confirmModalTitle    = 'Force Stop Server';
        $this->confirmModalMessage  = 'Warning: Force stopping the server may cause data loss. Are you sure?';
        $this->showConfirmModal     = true;
    }

    /**
     * Show confirmation modal for reboot.
     */
    public function confirmReboot(): void
    {
        $this->pendingConfirmAction = 'reboot';
        $this->confirmModalTitle    = 'Reboot Server';
        $this->confirmModalMessage  = 'Are you sure you want to reboot this server?';
        $this->showConfirmModal     = true;
    }

    /**
     * Dismiss the confirmation modal without executing any action.
     */
    public function cancelConfirmAction(): void
    {
        $this->showConfirmModal     = false;
        $this->pendingConfirmAction = '';
    }

    /**
     * Execute the action that was confirmed via the modal.
     */
    public function executeConfirmedAction(): void
    {
        if (!$this->selectedHosting) return;

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        $node = $this->selectedHosting->node ?? '';

        if ($this->isProtectedVm($vmid, $node)) {
            $this->showConfirmModal = false;
            return;
        }

        // Handle firewall_off confirmation (Task 3.4)
        if ($this->pendingConfirmAction === 'firewall_off') {
            $this->showConfirmModal = false;
            $this->pendingConfirmAction = '';
            $this->applyFirewallToggle($vmid, false);
            return;
        }

        // Handle DNS A record deletion (Task 4.1)
        if (str_starts_with($this->pendingConfirmAction, 'dns_delete_')) {
            $recordId = (int) str_replace('dns_delete_', '', $this->pendingConfirmAction);
            $this->showConfirmModal     = false;
            $this->pendingConfirmAction = '';
            $this->executeDnsARecordDelete($recordId);
            return;
        }

        $this->showConfirmModal   = false;
        $this->isActionInProgress = true;

        try {
            $proxmox = $this->resolveProxmoxService();
            $action  = $this->pendingConfirmAction;

            $success = match ($action) {
                'shutdown' => $proxmox->shutdownVm($vmid),
                'stop'     => $proxmox->stopVm($vmid),
                'reboot'   => $proxmox->rebootVm($vmid),
                default    => false,
            };

            if ($success) {
                $this->actionMessage     = ucfirst($action) . ' command sent successfully.';
                $this->actionMessageType = 'success';
                $this->auditLog($action, ['vmid' => $vmid, 'node' => $node, 'outcome' => 'success']);
            } else {
                $this->actionMessage     = 'Failed to execute ' . $action . '. Please try again.';
                $this->actionMessageType = 'error';
                $this->auditLog($action, ['vmid' => $vmid, 'node' => $node, 'outcome' => 'failure']);
            }
            $this->dispatch('log-event',
                action: 'server_' . $action,
                description: ucfirst($action) . ' command sent for ' . ($this->selectedHosting->server_hostname ?? 'VPS'),
                outcome: $success ? 'success' : 'failure'
            );
        } catch (\Exception $e) {
            $this->actionMessage     = 'An error occurred. Please try again.';
            $this->actionMessageType = 'error';
            $this->auditLog($this->pendingConfirmAction, ['vmid' => $vmid, 'node' => $node, 'outcome' => 'failure']);
        } finally {
            $this->isActionInProgress = false;
            $this->pendingConfirmAction = '';
            // Bust caches so status badge reflects the new state
            \Illuminate\Support\Facades\Cache::forget('vm_status_' . $vmid);
            \Illuminate\Support\Facades\Cache::forget('server_details_' . ($this->selectedHosting->id ?? 0));
            $this->refreshVmStatus();
        }
    }

    // -----------------------------------------------------------------------
    // Console methods (Task 3.1)
    // -----------------------------------------------------------------------

    /**
     * Open the noVNC console iframe for the current VM.
     * Guards against stopped VMs and delegates URL generation to ProxmoxApiService.
     */
    public function openConsole(): void
    {
        if (!$this->selectedHosting) return;

        if ($this->vmStatus === 'stopped') {
            $this->actionMessage     = 'Start the VPS to open the console.';
            $this->actionMessageType = 'error';
            return;
        }

        $vmid    = (int) ($this->selectedHosting->vps_id ?? 0);
        $proxmox = $this->resolveProxmoxService();
        $url     = $proxmox->getConsoleUrl($vmid, \Illuminate\Support\Facades\Auth::id());

        if ($url) {
            $this->consoleUrl   = $url;
            $this->showConsole  = true;
        } else {
            $this->actionMessage     = 'Console unavailable — please try again.';
            $this->actionMessageType = 'error';
        }
    }

    /**
     * Close the noVNC console iframe.
     */
    public function closeConsole(): void
    {
        $this->showConsole = false;
        $this->consoleUrl  = null;
    }

    // -----------------------------------------------------------------------
    // Firewall methods (Task 3.4)
    // -----------------------------------------------------------------------

    /**
     * Load the current firewall state from the Proxmox API.
     * Called from mount() when a hosting is selected.
     */
    public function loadFirewallState(): void
    {
        if (!$this->selectedHosting) return;

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        if (!$vmid) return;

        try {
            $proxmox = $this->resolveProxmoxService();
            $options = $proxmox->getFirewallOptions($vmid);
            $this->firewallEnabled = (bool) ($options['enable'] ?? false);
        } catch (\Exception $e) {
            // Leave as default false on error
        }
    }

    /**
     * Toggle the firewall on or off.
     * Disabling shows a confirmation modal first; enabling applies immediately.
     */
    public function toggleFirewall(bool $enable): void
    {
        if (!$this->selectedHosting) return;

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        $node = $this->selectedHosting->node ?? '';

        if ($this->isProtectedVm($vmid, $node)) return;

        // If disabling, show confirmation modal first
        if (!$enable) {
            $this->firewallPreviousState  = $this->firewallEnabled;
            $this->pendingConfirmAction   = 'firewall_off';
            $this->confirmModalTitle      = 'Disable Firewall';
            $this->confirmModalMessage    = 'Warning: Disabling the firewall will expose your VM to the internet. Are you sure?';
            $this->showConfirmModal       = true;
            return;
        }

        $this->applyFirewallToggle($vmid, $enable);
    }

    /**
     * Apply the firewall toggle after confirmation (or directly when enabling).
     */
    public function applyFirewallToggle(int $vmid, bool $enable): void
    {
        $this->firewallPreviousState = $this->firewallEnabled;
        $this->firewallLoading       = true;

        try {
            $proxmox = $this->resolveProxmoxService();
            $success = $proxmox->setFirewallOptions($vmid, ['enable' => (int) $enable]);

            if ($success) {
                $this->firewallEnabled   = $enable;
                $this->actionMessage     = 'Firewall ' . ($enable ? 'enabled' : 'disabled') . ' successfully.';
                $this->actionMessageType = 'success';
            } else {
                $this->firewallEnabled   = $this->firewallPreviousState ?? $this->firewallEnabled;
                $this->actionMessage     = 'Failed to update firewall settings. Please try again.';
                $this->actionMessageType = 'error';
            }
            $this->dispatch('log-event',
                action: $enable ? 'firewall_enable' : 'firewall_disable',
                description: 'Firewall ' . ($enable ? 'enabled' : 'disabled') . ' for ' . ($this->selectedHosting->server_hostname ?? 'VPS'),
                outcome: $success ? 'success' : 'failure'
            );
        } catch (\Exception $e) {
            $this->firewallEnabled   = $this->firewallPreviousState ?? $this->firewallEnabled;
            $this->actionMessage     = 'An error occurred while updating firewall settings.';
            $this->actionMessageType = 'error';
        } finally {
            $this->firewallLoading = false;
        }
    }

    /**
     * Allow SSH (port 22/tcp) through the VM firewall.
     * Useful when the Proxmox VM firewall is enabled and SSH times out.
     */
    public function allowSshFirewall(): void
    {
        if (!$this->selectedHosting) {
            $this->serverMessage = 'No hosting selected.';
            $this->serverMessageType = 'error';
            return;
        }

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        if (!$vmid) {
            $this->serverMessage = 'No VM linked to this hosting.';
            $this->serverMessageType = 'error';
            return;
        }

        try {
            $proxmox = $this->resolveProxmoxService();

            // Check if an allow-SSH rule already exists
            $rules = $proxmox->getFirewallRules($vmid);
            $hasSshRule = false;
            if (is_array($rules)) {
                foreach ($rules as $rule) {
                    if (($rule['dport'] ?? '') === '22' && ($rule['action'] ?? '') === 'ACCEPT') {
                        $hasSshRule = true;
                        break;
                    }
                }
            }

            if ($hasSshRule) {
                $this->serverMessage = 'SSH (port 22) is already allowed through the firewall.';
                $this->serverMessageType = 'success';
                $this->dispatch('clear-action-message');
                return;
            }

            $success = $proxmox->addFirewallRule($vmid, [
                'type'    => 'in',
                'action'  => 'ACCEPT',
                'proto'   => 'tcp',
                'dport'   => '22',
                'comment' => 'Allow SSH access',
                'enable'  => 1,
            ]);

            if ($success) {
                $this->serverMessage = 'SSH (port 22) allowed through firewall. Try connecting now.';
                $this->serverMessageType = 'success';
            } else {
                $this->serverMessage = 'Failed to add SSH firewall rule. Check Proxmox API permissions.';
                $this->serverMessageType = 'error';
            }
            $this->dispatch('clear-action-message');
        } catch (\Exception $e) {
            $this->serverMessage = 'Error allowing SSH: ' . $e->getMessage();
            $this->serverMessageType = 'error';
            $this->dispatch('clear-action-message');
        }
    }

    // -----------------------------------------------------------------------
    // Phase 4 — DNS A Record CRUD (Task 4.1)
    // -----------------------------------------------------------------------

    /**
     * Load all A records for the current hosting's primary domain.
     */
    public function loadDnsRecords(): void
    {
        if (!$this->selectedHosting) {
            $this->dnsARecords = [];
            return;
        }

        try {
            $domain = $this->resolveOrCreateDomain();

            if ($domain) {
                $this->dnsARecords = \App\Models\DnsRecord::byDomain($domain->id)
                    ->byUser(Auth::id())
                    ->byType('A')
                    ->active()
                    ->orderBy('name')
                    ->get()
                    ->toArray();
            } else {
                $this->dnsARecords = [];
            }
        } catch (\Exception $e) {
            $this->dnsARecords = [];
        }
    }

    /**
     * Resolve the UserDomain for the current hosting, creating one if necessary.
     */
    private function resolveOrCreateDomain(): ?\App\Models\UserDomain
    {
        if (!$this->selectedHosting) {
            return null;
        }

        if ($this->selectedHosting->domain) {
            return $this->selectedHosting->domain;
        }

        $domainName = strtolower(trim($this->selectedHosting->primary_domain ?? ''));
        if (!$domainName) {
            return null;
        }

        $userId = Auth::id();

        $domain = \App\Models\UserDomain::where('user_id', $userId)
            ->where('domain_name', $domainName)
            ->first();

        if (!$domain) {
            $parts = explode('.', $domainName);
            $tld   = count($parts) >= 2 ? implode('.', array_slice($parts, -2)) : $domainName;
            $sld   = count($parts) >= 2 ? $parts[0] : $domainName;

            $domain = \App\Models\UserDomain::create([
                'user_id'          => $userId,
                'domain_name'      => $domainName,
                'tld'              => $tld,
                'sld'              => $sld,
                'status'           => 'active',
                'use_believoo_dns' => true,
                'nameservers'      => \App\Models\DnsRecord::BELIEVOO_NS,
            ]);

            $serverIp = $this->selectedHosting->server_ip;
            if ($serverIp) {
                \App\Models\DnsRecord::createDefaultWebRecords($userId, $domain->id, $serverIp);
                \Illuminate\Support\Facades\Artisan::call('dns:sync-zones', [
                    'domain' => $domainName,
                ]);
            }
        }

        $this->selectedHosting->load('domain');

        return $domain;
    }

    /**
     * Show the blank "Add A Record" form.
     */
    public function showAddARecordForm(): void
    {
        $this->editingDnsRecordId = null;
        $this->dnsFormData        = ['hostname' => '', 'ip' => ''];
        $this->dnsFormError       = '';
        $this->showDnsForm        = true;
    }

    /**
     * Populate the form with an existing A record for editing.
     */
    public function showEditARecordForm(int $recordId): void
    {
        $record = \App\Models\DnsRecord::where('id', $recordId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$record) {
            $this->actionMessage     = 'DNS record not found.';
            $this->actionMessageType = 'error';
            return;
        }

        $this->editingDnsRecordId = $recordId;
        $this->dnsFormData        = ['hostname' => $record->name, 'ip' => $record->value];
        $this->dnsFormError       = '';
        $this->showDnsForm        = true;
    }

    /**
     * Validate and save (create or update) an A record.
     * Validates hostname and IP before calling the model layer.
     */
    public function saveDnsARecord(): void
    {
        $this->dnsFormError = '';

        // Validate hostname
        $hostname = trim($this->dnsFormData['hostname'] ?? '');
        if ($hostname === '' || !preg_match('/^[a-zA-Z0-9@]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $hostname)) {
            $this->dnsFormError = 'Hostname is invalid. Use letters, digits, hyphens, or "@" for root.';
            return;
        }

        // Validate IP address
        $ip = trim($this->dnsFormData['ip'] ?? '');
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->dnsFormError = 'IP address must be a valid IPv4 address.';
            return;
        }

        $userId = Auth::id();

        // Resolve domain
        $domain = $this->resolveOrCreateDomain();

        if (!$domain) {
            $this->dnsFormError = 'No domain found for this hosting account.';
            return;
        }

        try {
            if ($this->editingDnsRecordId) {
                // Update existing record
                $record = \App\Models\DnsRecord::where('id', $this->editingDnsRecordId)
                    ->where('user_id', $userId)
                    ->firstOrFail();

                $record->update(['name' => $hostname, 'value' => $ip]);

                $this->actionMessage     = 'A record updated successfully.';
                $this->actionMessageType = 'success';
                $this->auditLog('dns_a_record_save', [
                    'record_id'   => $record->id,
                    'record_type' => 'A',
                    'hostname'    => $hostname,
                    'ip'          => $ip,
                    'outcome'     => 'success',
                ]);
            } else {
                // Create new record
                $record = \App\Models\DnsRecord::create([
                    'user_domain_id' => $domain->id,
                    'user_id'        => $userId,
                    'record_type'    => 'A',
                    'name'           => $hostname,
                    'value'          => $ip,
                    'ttl'            => 3600,
                    'is_active'      => true,
                    'source'         => 'manual',
                ]);

                $this->actionMessage     = 'A record created successfully.';
                $this->actionMessageType = 'success';
                $this->auditLog('dns_a_record_save', [
                    'record_id'   => $record->id,
                    'record_type' => 'A',
                    'hostname'    => $hostname,
                    'ip'          => $ip,
                    'outcome'     => 'success',
                ]);
            }

            $this->showDnsForm        = false;
            $this->editingDnsRecordId = null;
            $this->dnsFormData        = ['hostname' => '', 'ip' => ''];
            $this->loadDnsRecords();

        } catch (\Exception $e) {
            $this->dnsFormError = 'Failed to save DNS record. Please try again.';
            $this->auditLog('dns_a_record_save', [
                'record_type' => 'A',
                'hostname'    => $hostname,
                'ip'          => $ip,
                'outcome'     => 'failure',
            ]);
        }
    }

    /**
     * Delete an A record after confirmation.
     * Uses the generalised confirm modal.
     */
    public function deleteDnsARecord(int $recordId): void
    {
        // Verify ownership before showing modal
        $record = \App\Models\DnsRecord::where('id', $recordId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$record) {
            $this->actionMessage     = 'DNS record not found.';
            $this->actionMessageType = 'error';
            return;
        }

        // Store record ID in pendingConfirmAction for executeConfirmedAction
        $this->pendingConfirmAction = 'dns_delete_' . $recordId;
        $this->confirmModalTitle    = 'Delete A Record';
        $this->confirmModalMessage  = 'Are you sure you want to delete the A record for "' . $record->name . '"? This cannot be undone.';
        $this->showConfirmModal     = true;
    }

    /**
     * Execute the actual DNS record deletion (called from executeConfirmedAction).
     */
    private function executeDnsARecordDelete(int $recordId): void
    {
        try {
            $record = \App\Models\DnsRecord::where('id', $recordId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $hostname = $record->name;
            $ip       = $record->value;
            $record->delete();

            $this->actionMessage     = 'A record deleted successfully.';
            $this->actionMessageType = 'success';
            $this->auditLog('dns_a_record_delete', [
                'record_id'   => $recordId,
                'record_type' => 'A',
                'hostname'    => $hostname,
                'ip'          => $ip,
                'outcome'     => 'success',
            ]);
            $this->loadDnsRecords();

        } catch (\Exception $e) {
            $this->actionMessage     = 'Failed to delete DNS record. Please try again.';
            $this->actionMessageType = 'error';
        }
    }

    // -----------------------------------------------------------------------
    // Phase 4 — Universal DNS Record CRUD (all types)
    // -----------------------------------------------------------------------

    /**
     * Open the add form for any record type.
     */
    public function openDnsAddForm(string $type = 'A'): void
    {
        $this->editingDnsRecordId = null;
        $this->dnsFormType        = strtoupper($type);
        $this->dnsFormName        = '';
        $this->dnsFormValue       = '';
        $this->dnsFormPriority    = '';
        $this->dnsFormTtl         = 3600;
        $this->dnsFormError       = '';
        $this->showDnsForm        = true;
    }

    /**
     * Open the edit form for any record type.
     */
    public function openDnsEditForm(int $recordId): void
    {
        $record = \App\Models\DnsRecord::where('id', $recordId)
            ->where('user_id', Auth::id())
            ->first();
        if (!$record) { $this->dnsFormError = 'Record not found.'; return; }

        $this->editingDnsRecordId = $recordId;
        $this->dnsFormType        = $record->record_type;
        $this->dnsFormName        = $record->name;
        $this->dnsFormValue       = $record->value;
        $this->dnsFormPriority    = (string) ($record->priority ?? '');
        $this->dnsFormTtl         = $record->ttl ?? 3600;
        $this->dnsFormError       = '';
        $this->showDnsForm        = true;
    }

    /**
     * Save (create or update) any DNS record type.
     */
    public function saveDnsRecord(): void
    {
        $this->dnsFormError = '';
        $type  = strtoupper(trim($this->dnsFormType));
        $name  = trim($this->dnsFormName);
        $value = trim($this->dnsFormValue);

        if ($name === '') { $this->dnsFormError = 'Hostname is required.'; return; }
        if ($value === '') { $this->dnsFormError = 'Value is required.'; return; }
        if ($type === 'A' && !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->dnsFormError = 'Invalid IPv4 address.'; return;
        }
        if ($type === 'AAAA' && !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->dnsFormError = 'Invalid IPv6 address.'; return;
        }

        $userId = Auth::id();
        $domain = $this->resolveOrCreateDomain();

        if (!$domain) { $this->dnsFormError = 'No domain found for this hosting account.'; return; }

        try {
            $data = [
                'record_type' => $type,
                'name'        => $name,
                'value'       => $value,
                'ttl'         => (int) $this->dnsFormTtl ?: 3600,
                'priority'    => $this->dnsFormPriority !== '' ? (int) $this->dnsFormPriority : null,
                'is_active'   => true,
            ];

            $isUpdate = (bool) $this->editingDnsRecordId;

            if ($this->editingDnsRecordId) {
                $record = \App\Models\DnsRecord::where('id', $this->editingDnsRecordId)
                    ->where('user_id', $userId)->firstOrFail();
                $record->update($data);
                $this->actionMessage = ucfirst(strtolower($type)) . ' record updated.';
            } else {
                \App\Models\DnsRecord::create(array_merge($data, [
                    'user_domain_id' => $domain->id,
                    'user_id'        => $userId,
                    'source'         => 'manual',
                ]));
                $this->actionMessage = ucfirst(strtolower($type)) . ' record created.';
            }

            $this->actionMessageType  = 'success';
            $this->showDnsForm        = false;
            $this->editingDnsRecordId = null;
            $this->dnsFormName        = '';
            $this->dnsFormValue       = '';
            $this->dnsFormPriority    = '';
            $this->loadDnsRecords();
            $this->dispatch('log-event',
                action: $isUpdate ? 'dns_record_update' : 'dns_record_add',
                description: ($isUpdate ? 'Updated' : 'Created') . ' ' . $type . ' record: ' . $name,
                outcome: 'success'
            );

        } catch (\Exception $e) {
            $this->dnsFormError = 'Failed to save: ' . $e->getMessage();
        }
    }

    /**
     * Delete any DNS record.
     */
    public function deleteDnsRecord(int $recordId): void
    {
        $record = \App\Models\DnsRecord::where('id', $recordId)
            ->where('user_id', Auth::id())->first();
        if (!$record) { $this->actionMessage = 'Record not found.'; $this->actionMessageType = 'error'; return; }
        $record->delete();
        $this->actionMessage     = 'DNS record deleted.';
        $this->actionMessageType = 'success';
        $this->loadDnsRecords();
        $this->dispatch('log-event',
            action: 'dns_record_delete',
            description: 'Deleted DNS record ID ' . $recordId,
            outcome: 'success'
        );
    }

    // -----------------------------------------------------------------------
    // Nameserver Management
    // -----------------------------------------------------------------------

    public function openNsModal(): void
    {
        $this->showNsModal = true;
        $this->nsError = '';
        $domain = $this->resolveOrCreateDomain();
        if ($domain) {
            $ns = is_array($domain->nameservers) ? $domain->nameservers : [];
            $this->ns1 = $ns[0] ?? '';
            $this->ns2 = $ns[1] ?? '';
            $this->ns3 = $ns[2] ?? '';
            $this->ns4 = $ns[3] ?? '';
        }
    }

    public function closeNsModal(): void
    {
        $this->showNsModal = false;
        $this->ns1 = '';
        $this->ns2 = '';
        $this->ns3 = '';
        $this->ns4 = '';
        $this->nsError = '';
    }

    public function saveNameservers(): void
    {
        $this->nsError = '';
        if (!$this->selectedHosting) {
            $this->nsError = 'No hosting selected.';
            return;
        }

        $domain = $this->resolveOrCreateDomain();
        if (!$domain) {
            $this->nsError = 'No domain linked to this hosting.';
            return;
        }

        $nameservers = [];
        foreach ([$this->ns1, $this->ns2, $this->ns3, $this->ns4] as $ns) {
            $ns = trim($ns);
            if ($ns !== '') {
                $nameservers[] = $ns;
            }
        }

        if (count($nameservers) < 2) {
            $this->nsError = 'At least 2 nameservers are required.';
            return;
        }

        try {
            $domain->update(['nameservers' => $nameservers]);
            $this->showNsModal = false;
            $this->actionMessage = 'Nameservers updated successfully. DNS propagation may take 24-48 hours.';
            $this->actionMessageType = 'success';
            $this->dispatch('clear-action-message');
        } catch (\Exception $e) {
            $this->nsError = 'Failed to update nameservers: ' . $e->getMessage();
        }
    }

    public function resetToBelievooNs(): void
    {
        $this->ns1 = 'ns1.believoo.com';
        $this->ns2 = 'ns2.believoo.com';
        $this->ns3 = '';
        $this->ns4 = '';
    }

    public function startEditDomain(): void
    {
        $this->editingDomain = true;
        $this->editDomainValue = $this->selectedHosting?->primary_domain ?? '';
        $this->editDomainError = '';
    }

    public function cancelEditDomain(): void
    {
        $this->editingDomain = false;
        $this->editDomainValue = '';
        $this->editDomainError = '';
    }

    public function saveDomainName(): void
    {
        $this->editDomainError = '';
        if (!$this->selectedHosting) {
            $this->editDomainError = 'No hosting selected.';
            return;
        }

        $domain = strtolower(trim($this->editDomainValue));
        if ($domain === '') {
            $this->editDomainError = 'Domain name cannot be empty.';
            return;
        }

        if (!preg_match('/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)*$/i', $domain)) {
            $this->editDomainError = 'Invalid domain name format.';
            return;
        }

        try {
            $this->selectedHosting->update(['primary_domain' => $domain]);
            $this->selectedHosting->refresh();
            $this->resolveOrCreateDomain();
            $this->editingDomain = false;
            $this->editDomainValue = '';
            $this->actionMessage = 'Domain name updated successfully.';
            $this->actionMessageType = 'success';
            $this->dispatch('clear-action-message');
        } catch (\Exception $e) {
            $this->editDomainError = 'Failed to update domain: ' . $e->getMessage();
        }
    }

    // -----------------------------------------------------------------------
    // Phase 4 — PTR Record Management (Task 4.3)
    // -----------------------------------------------------------------------

    /**
     * Load the current PTR record for the hosting's server IP.
     */
    public function loadPtrRecord(): void
    {
        if (!$this->selectedHosting) {
            $this->dnsPtrRecord = null;
            return;
        }

        $serverIp = $this->selectedHosting->server_ip ?? null;
        if (!$serverIp) {
            $this->dnsPtrRecord = null;
            return;
        }

        try {
            $userId = Auth::id();
            $ptr = \App\Models\DnsRecord::where('user_id', $userId)
                ->where('record_type', 'PTR')
                ->where('value', $serverIp)
                ->active()
                ->first();

            $this->dnsPtrRecord = $ptr ? $ptr->toArray() : null;
        } catch (\Exception $e) {
            $this->dnsPtrRecord = null;
        }
    }

    /**
     * Show the PTR record form, pre-populated with the client's primary domain.
     */
    public function showSetPtrForm(): void
    {
        $primaryDomain = $this->selectedHosting->primary_domain
            ?? $this->selectedHosting->server_hostname
            ?? '';

        $this->ptrHostname = $primaryDomain;
        $this->dnsFormError = '';
        $this->showPtrForm  = true;
    }

    /**
     * Validate and save the PTR record.
     * Validates FQDN pattern before persisting.
     */
    public function savePtrRecord(): void
    {
        $this->dnsFormError = '';

        $hostname = trim($this->ptrHostname);

        // Validate FQDN
        if ($hostname === '' || !preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $hostname)) {
            $this->dnsFormError = 'Please enter a valid fully-qualified domain name (e.g. server.example.com).';
            return;
        }

        $serverIp = $this->selectedHosting->server_ip ?? null;
        if (!$serverIp) {
            $this->dnsFormError = 'No server IP assigned to this hosting.';
            return;
        }

        $userId = Auth::id();

        // Resolve domain
        $domain = $this->resolveOrCreateDomain();

        if (!$domain) {
            $this->dnsFormError = 'No domain found for this hosting account.';
            return;
        }

        try {
            // Upsert: delete existing PTR for this IP, then create new one
            \App\Models\DnsRecord::where('user_id', $userId)
                ->where('record_type', 'PTR')
                ->where('value', $serverIp)
                ->delete();

            $record = \App\Models\DnsRecord::create([
                'user_domain_id' => $domain->id,
                'user_id'        => $userId,
                'record_type'    => 'PTR',
                'name'           => $hostname,
                'value'          => $serverIp,
                'ttl'            => 3600,
                'is_active'      => true,
                'source'         => 'manual',
            ]);

            $this->actionMessage     = 'Reverse DNS (PTR) record saved. Propagation may take up to 24 hours.';
            $this->actionMessageType = 'success';
            $this->showPtrForm       = false;
            $this->auditLog('dns_ptr_record_save', [
                'record_id'   => $record->id,
                'record_type' => 'PTR',
                'hostname'    => $hostname,
                'ip'          => $serverIp,
                'outcome'     => 'success',
            ]);
            $this->loadPtrRecord();
            $this->dispatch('log-event',
                action: 'dns_ptr_update',
                description: 'PTR record updated: ' . $hostname . ' → ' . $serverIp,
                outcome: 'success'
            );

        } catch (\Exception $e) {
            $this->dnsFormError = 'Failed to save PTR record. Please try again.';
            $this->auditLog('dns_ptr_record_save', [
                'record_type' => 'PTR',
                'hostname'    => $hostname,
                'ip'          => $serverIp,
                'outcome'     => 'failure',
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // Phase 4 — OS Reinstall (Task 4.5)
    // -----------------------------------------------------------------------

    /**
     * Show the OS selector modal (first step of reinstall).
     * Calls isProtectedVm() before showing anything.
     */
    public function showReinstallOptions(): void
    {
        if (!$this->selectedHosting) return;

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        $node = $this->selectedHosting->node ?? '';

        if ($this->isProtectedVm($vmid, $node)) return;

        $this->selectedOsTemplate      = 'ubuntu-22.04';
        $this->showReinstallModal       = true;
        $this->showReinstallConfirmModal = false;
    }

    /**
     * Close the OS selector and open the data-loss warning modal (second step).
     */
    public function confirmReinstall(): void
    {
        if (empty($this->selectedOsTemplate)) {
            $this->actionMessage     = 'Please select an operating system.';
            $this->actionMessageType = 'error';
            return;
        }

        $this->showReinstallModal        = false;
        $this->showReinstallConfirmModal = true;
    }

    /**
     * Execute the OS reinstall after both confirmations.
     * Calls isProtectedVm() again as a server-side guard.
     * Sends email with new credentials; never displays password in UI.
     */
    public function executeReinstall(): void
    {
        if (!$this->selectedHosting) return;

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        $node = $this->selectedHosting->node ?? '';

        // Server-side guard — always re-check
        if ($this->isProtectedVm($vmid, $node)) {
            $this->showReinstallConfirmModal = false;
            return;
        }

        $this->showReinstallConfirmModal = false;
        $this->isActionInProgress        = true;

        try {
            $proxmox = $this->resolveProxmoxService();

            // Resolve CloudInitService for this node
            $proxmoxVm   = \App\Models\ProxmoxVm::where('vmid', $vmid)->first();
            $nodeName    = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $nodeName)->first();

            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $baseUrl = 'https://' . $proxmoxNode->hostname . ':' . ($proxmoxNode->port ?? 8006);
                $cloudInit = \App\Services\CloudInitService::forNode(
                    $baseUrl,
                    $proxmoxNode->getDecryptedApiToken(),
                    $nodeName
                );
            } else {
                $cloudInit = app(\App\Services\CloudInitService::class);
            }

            // Generate a new secure password for the reinstalled OS
            $newPassword = $this->generateSecurePassword();

            $osTemplateMap = [
                'ubuntu-22.04'        => 'ubuntu-22.04-server-cloudimg-amd64.img',
                'debian-12'           => 'debian-12-genericcloud-amd64.qcow2',
                'windows-server-2022' => 'windows-server-2022-datacenter.iso',
            ];

            $config = [
                'os_template' => $osTemplateMap[$this->selectedOsTemplate] ?? $this->selectedOsTemplate,
                'password'    => $newPassword,
                'hostname'    => $this->selectedHosting->server_hostname ?? 'vps-' . $vmid,
                'ip'          => $this->selectedHosting->server_ip ?? '',
                'gateway'     => $this->selectedHosting->gateway ?? $this->selectedHosting->gateway_ip ?? '',
            ];

            $result = $cloudInit->rebuildWithCloudInit($vmid, $config);

            if ($result['success'] ?? false) {
                // Send email with new credentials — password goes to email ONLY
                $user = \App\Models\User::find($this->selectedHosting->user_id);
                if ($user && $user->email) {
                    try {
                        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($user, $vmid, $newPassword) {
                            $osLabel = match ($this->selectedOsTemplate) {
                                'ubuntu-22.04'        => 'Ubuntu 22.04',
                                'debian-12'           => 'Debian 12',
                                'windows-server-2022' => 'Windows Server 2022',
                                default               => $this->selectedOsTemplate,
                            };
                            $message->to($user->email)
                                ->subject('🖥️ Your VPS Has Been Reinstalled — ' . $osLabel)
                                ->html("
                                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f172a; color: #fff; padding: 40px; border-radius: 16px;'>
                                        <h2 style='color: #00b7ff; margin-bottom: 20px;'>VPS Reinstalled Successfully</h2>
                                        <p style='color: #94a3b8;'>Your VPS (VM ID: {$vmid}) has been reinstalled with <strong style='color:#fff;'>{$osLabel}</strong>.</p>
                                        <div style='background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; margin: 20px 0;'>
                                            <p style='color: #94a3b8; font-size: 12px; margin-bottom: 8px;'>Root Password</p>
                                            <p style='color: #22c55e; font-family: monospace; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>{$newPassword}</p>
                                        </div>
                                        <p style='color: #ef4444; font-size: 12px;'>⚠️ Please save this password securely and change it after first login.</p>
                                        <p style='color: #64748b; font-size: 11px; margin-top: 20px;'>Team BelieVoo</p>
                                    </div>
                                ");
                        });
                    } catch (\Exception $mailEx) {
                        \Illuminate\Support\Facades\Log::warning('Reinstall email failed', ['error' => $mailEx->getMessage()]);
                    }
                }

                $this->actionMessage     = 'OS reinstalled successfully. New credentials have been sent to your email.';
                $this->actionMessageType = 'success';
                $this->auditLog('reinstall', [
                    'vmid'        => $vmid,
                    'node'        => $node,
                    'os_template' => $this->selectedOsTemplate,
                    'outcome'     => 'success',
                ]);
                \Illuminate\Support\Facades\Cache::forget('vm_status_' . $vmid);
                \Illuminate\Support\Facades\Cache::forget('server_details_' . ($this->selectedHosting->id ?? 0));
                $this->refreshVmStatus();
            } else {
                $reason = $result['error'] ?? 'Unknown error';
                $this->actionMessage     = 'Reinstall failed: ' . $reason;
                $this->actionMessageType = 'error';
                $this->auditLog('reinstall', [
                    'vmid'        => $vmid,
                    'node'        => $node,
                    'os_template' => $this->selectedOsTemplate,
                    'outcome'     => 'failure',
                    'error'       => $reason,
                ]);
            }
        } catch (\Exception $e) {
            $this->actionMessage     = 'Reinstall failed: ' . $e->getMessage();
            $this->actionMessageType = 'error';
            $this->auditLog('reinstall', [
                'vmid'    => $vmid,
                'node'    => $node,
                'outcome' => 'failure',
            ]);
        } finally {
            $this->isActionInProgress = false;
            // Clear password from any local variable scope — it was never assigned to a property
        }
    }

    // -----------------------------------------------------------------------
    // Phase 4 — Root Password Reset (Task 4.6)
    // -----------------------------------------------------------------------

    /**
     * Show the password reset confirmation modal.
     * Calls isProtectedVm() first.
     */
    public function confirmPasswordReset(): void
    {
        if (!$this->selectedHosting) return;

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        $node = $this->selectedHosting->node ?? '';

        if ($this->isProtectedVm($vmid, $node)) return;

        $this->showPasswordResetModal = true;
    }

    /**
     * Execute the root password reset.
     * Generates a cryptographically random password using random_int.
     * Sends the password to the user's email ONLY — never assigns it to a public property.
     */
    public function executePasswordReset(): void
    {
        if (!$this->selectedHosting) return;

        $vmid = (int) ($this->selectedHosting->vps_id ?? 0);
        $node = $this->selectedHosting->node ?? '';

        // Server-side guard — always re-check
        if ($this->isProtectedVm($vmid, $node)) {
            $this->showPasswordResetModal = false;
            return;
        }

        $this->showPasswordResetModal = false;
        $this->isActionInProgress     = true;

        try {
            // Generate cryptographically random password (≥16 chars, all complexity classes)
            // Password is stored ONLY in a local variable — never in any public property or log
            $newPassword = $this->generateSecurePassword();

            $proxmox = $this->resolveProxmoxService();
            $success = $proxmox->resetCloudInitPassword($vmid, $newPassword);

            if ($success) {
                // Send password to user's email — it must NOT appear in the UI or logs
                $user = \App\Models\User::find($this->selectedHosting->user_id);
                if ($user && $user->email) {
                    try {
                        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($user, $vmid, $newPassword) {
                            $message->to($user->email)
                                ->subject('🔑 Your VPS Root Password Has Been Reset')
                                ->html("
                                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f172a; color: #fff; padding: 40px; border-radius: 16px;'>
                                        <h2 style='color: #00b7ff; margin-bottom: 20px;'>Root Password Reset</h2>
                                        <p style='color: #94a3b8;'>Your VPS root password has been reset successfully.</p>
                                        <div style='background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; margin: 20px 0;'>
                                            <p style='color: #94a3b8; font-size: 12px; margin-bottom: 8px;'>VM ID</p>
                                            <p style='color: #00b7ff; font-family: monospace; font-size: 18px; font-weight: bold;'>{$vmid}</p>
                                            <p style='color: #94a3b8; font-size: 12px; margin-top: 16px; margin-bottom: 8px;'>New Root Password</p>
                                            <p style='color: #22c55e; font-family: monospace; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>{$newPassword}</p>
                                        </div>
                                        <p style='color: #ef4444; font-size: 12px;'>⚠️ Please save this password securely. Change it after logging in.</p>
                                        <p style='color: #64748b; font-size: 11px; margin-top: 20px;'>Team BelieVoo</p>
                                    </div>
                                ");
                        });
                    } catch (\Exception $mailEx) {
                        \Illuminate\Support\Facades\Log::warning('Password reset email failed', ['error' => $mailEx->getMessage()]);
                    }
                }

                $this->actionMessage     = 'New password sent to your email.';
                $this->actionMessageType = 'success';
                // Audit log — password is intentionally excluded from context
                $this->auditLog('password_reset', [
                    'vmid'    => $vmid,
                    'node'    => $node,
                    'outcome' => 'success',
                ]);
            } else {
                $this->actionMessage     = 'Password reset failed. Please try again.';
                $this->actionMessageType = 'error';
                $this->auditLog('password_reset', [
                    'vmid'    => $vmid,
                    'node'    => $node,
                    'outcome' => 'failure',
                ]);
            }
        } catch (\Exception $e) {
            $this->actionMessage     = 'An error occurred during password reset.';
            $this->actionMessageType = 'error';
            $this->auditLog('password_reset', [
                'vmid'    => $vmid,
                'node'    => $node,
                'outcome' => 'failure',
            ]);
        } finally {
            $this->isActionInProgress = false;
        }
    }

    /**
     * Generate a cryptographically random password of exactly 20 characters.
     * Guarantees at least one uppercase, one lowercase, one digit, and one special character.
     * Uses random_int() for cryptographic randomness.
     * This method is private — the result must NEVER be assigned to a public property.
     */
    private function generateSecurePassword(): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $special = '!@#$%^&*';
        $all     = $upper . $lower . $digits . $special;

        // Guarantee at least one of each required character class
        $password  = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Fill remaining 16 characters from the full set (total length = 20)
        for ($i = 4; $i < 20; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle to avoid predictable positions for the guaranteed characters
        $chars = str_split($password);
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }


    // -----------------------------------------------------------------------
    // Phase 2 — Proxmox service resolver (Task 2.1)
    // -----------------------------------------------------------------------

    private function isProtectedVm(int $vmid, string $node): bool
    {
        foreach (self::PROTECTED_VMS as $protected) {
            if ($protected['vmid'] === $vmid && $protected['node'] === $node) {
                $this->auditLog('protected_vm_attempt', [
                    'vmid' => $vmid, 'node' => $node,
                    'action' => $this->pendingConfirmAction ?? 'unknown',
                ], warning: true);
                $this->actionMessage     = 'This VM is protected and cannot be modified from the client panel.';
                $this->actionMessageType = 'error';
                return true;
            }
        }
        return false;
    }

    // -----------------------------------------------------------------------
    // Audit logger (Task 1.13)
    // -----------------------------------------------------------------------

    private function auditLog(string $action, array $context, bool $warning = false): void
    {
        $entry = array_merge([
            'action'    => $action,
            'user_id'   => \Illuminate\Support\Facades\Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ], $context);
        if ($warning) {
            \Illuminate\Support\Facades\Log::warning('vps_client_audit', $entry);
        } else {
            \Illuminate\Support\Facades\Log::info('vps_client_audit', $entry);
        }
    }

    /**
     * Refresh analytics data from the already-populated $serverDetails property.
     *
     * Called by wire:poll.5000ms in the analytics Blade partials.
     * Reads ONLY from $this->serverDetails — no new Proxmox API calls or DB queries.
     * Appends one data point to each history array and caps each at 60 entries.
     * Sets $dataUnavailable = true when a value is missing from $serverDetails.
     * Dispatches the 'analytics-updated' browser event with the updated arrays.
     */
    public function refreshAnalytics(): void
    {
        $this->dataUnavailable = false;

        // ── CPU ──────────────────────────────────────────────────────────────
        if (isset($this->serverDetails['cpu_percent'])) {
            $cpu = (float) $this->serverDetails['cpu_percent'];
        } else {
            // Re-use last known value; flag data as unavailable
            $cpu = $this->cpuCurrent;
            $this->dataUnavailable = true;
        }
        $this->cpuCurrent = $cpu;
        $this->cpuHistory[] = $cpu;
        if (count($this->cpuHistory) > 60) {
            array_shift($this->cpuHistory);
        }

        // ── RAM ──────────────────────────────────────────────────────────────
        if (isset($this->serverDetails['mem_used_gb'], $this->serverDetails['mem_total_gb'])) {
            $ramUsedGb  = (float) $this->serverDetails['mem_used_gb'];
            $ramTotalGb = (float) $this->serverDetails['mem_total_gb'];
            $ramPercent = $ramTotalGb > 0 ? round(($ramUsedGb / $ramTotalGb) * 100, 1) : 0.0;
        } elseif (isset($this->serverDetails['mem_percent'])) {
            // Fallback: derive from percentage only
            $ramPercent = (float) $this->serverDetails['mem_percent'];
            $ramUsedGb  = $this->ramCurrentGb;
            $ramTotalGb = $this->ramTotalGb;
        } else {
            // Re-use last known values; flag data as unavailable
            $ramPercent = $this->ramTotalGb > 0
                ? round(($this->ramCurrentGb / $this->ramTotalGb) * 100, 1)
                : 0.0;
            $ramUsedGb  = $this->ramCurrentGb;
            $ramTotalGb = $this->ramTotalGb;
            $this->dataUnavailable = true;
        }
        $this->ramCurrentGb = $ramUsedGb;
        $this->ramTotalGb   = $ramTotalGb;
        $this->ramHistory[] = $ramPercent;
        if (count($this->ramHistory) > 60) {
            array_shift($this->ramHistory);
        }

        // ── Network ──────────────────────────────────────────────────────────
        // Proxmox returns netin/netout in bytes/s; convert to Mbps (÷ 125000)
        if (isset($this->serverDetails['netin'])) {
            $netinMbps = round((float) $this->serverDetails['netin'] / 125000, 3);
        } else {
            $netinMbps = $this->netinMbps;
            $this->dataUnavailable = true;
        }

        if (isset($this->serverDetails['netout'])) {
            $netoutMbps = round((float) $this->serverDetails['netout'] / 125000, 3);
        } else {
            $netoutMbps = $this->netoutMbps;
            $this->dataUnavailable = true;
        }

        $this->netinMbps  = $netinMbps;
        $this->netoutMbps = $netoutMbps;

        $this->netinHistory[] = $netinMbps;
        if (count($this->netinHistory) > 60) {
            array_shift($this->netinHistory);
        }

        $this->netoutHistory[] = $netoutMbps;
        if (count($this->netoutHistory) > 60) {
            array_shift($this->netoutHistory);
        }

        // ── Dispatch browser event for Chart.js listeners ────────────────────
        $this->dispatch('analytics-updated', [
            'cpu'     => $this->cpuHistory,
            'ram'     => $this->ramHistory,
            'netin'   => $this->netinHistory,
            'netout'  => $this->netoutHistory,
            'current' => [
                'cpu'        => $this->cpuCurrent,
                'ramUsedGb'  => $this->ramCurrentGb,
                'ramTotalGb' => $this->ramTotalGb,
                'netinMbps'  => $this->netinMbps,
                'netoutMbps' => $this->netoutMbps,
            ],
            'dataUnavailable' => $this->dataUnavailable,
        ]);
    }

    public function render()
    {
        $user = Auth::user();

        // Always load lightweight collections for dashboard stats
        $projects      = Project::where('user_id', $user->id)->get();
        $tickets       = \App\Models\Ticket::where('email', $user->email)->latest()->get();
        $hostings      = UserHosting::where('user_id', $user->id)->latest()->get();
        $agreements    = \App\Models\Agreement::where('client_id', $user->id)->latest()->get();

        $kanban        = ['Planning' => collect(), 'Developing' => collect(), 'Testing' => collect(), 'Delivered' => collect()];
        $leads         = collect();
        $vaultAssets   = collect();
        $domains       = collect();
        $agreementRequests = collect();
        $availableHostings = collect();
        $portfolioItems    = collect();
        $testimonials      = collect();
        $hasProvisioning   = false;

        if ($this->activeTab === 'projects') {
            $projects = Project::where('user_id', $user->id)->with('assets')->get();
            $kanban = [
                'Planning'   => $projects->where('status', 'Planning'),
                'Developing' => $projects->where('status', 'Developing'),
                'Testing'    => $projects->where('status', 'Testing'),
                'Delivered'  => $projects->where('status', 'Delivered'),
            ];
        }

        if ($this->activeTab === 'tickets') {
            $tickets = \App\Models\Ticket::where('email', $user->email)->latest()->get();
        }

        if ($this->activeTab === 'vault') {
            $vaultAssets = \App\Models\ProjectAsset::whereHas('project', fn($q) => $q->where('user_id', $user->id))->latest()->get();
            if ($projects->isEmpty()) {
                $projects = Project::where('user_id', $user->id)->get();
            }
        }

        if ($this->activeTab === 'hosting') {
            $hostings = UserHosting::where('user_id', $user->id)->with('service')->latest()->get();
            $hasProvisioning = $hostings->whereIn('status', ['provisioning', 'pending'])->count() > 0;
            $domains = \App\Models\UserDomain::where('user_id', $user->id)->where('status', 'active')->with('domainProvider')->latest()->get();
            $availableHostings = \App\Models\Service::where('is_active', true)
                ->where(function ($q) {
                    $q->where('category', 'like', '%hosting%')
                      ->orWhere('category', 'like', '%server%')
                      ->orWhere('slug', 'like', '%vps%');
                })->get();
        }

        if ($this->activeTab === 'agreements') {
            $agreements = \App\Models\Agreement::where('client_id', $user->id)->with(['workItems', 'milestones', 'histories'])->latest()->get();
            $agreementRequests = \App\Models\AgreementRequest::where('client_id', $user->id)->with('agreement')->latest()->get();
        }

        // Load streaming data
        if ($this->activeTab === 'streaming') {
            if ($this->streamingApiKeys === null) {
                $this->loadStreamingData();
            }
        }

        // Load wallet data
        $walletTransactionsList = collect();
        $walletTopups = collect();
        // Payment settings — always load so SDK scripts can be included regardless of active tab
        $razorpaySettings = \App\Models\Setting::where('group', 'Payment')->pluck('value', 'key')->toArray();

        if ($this->activeTab === 'wallet') {
            $walletTransactionsList = $user->walletTransactions()->latest()->limit(20)->get();
            $walletTopups = \App\Models\WalletTopup::where('user_id', $user->id)->latest()->limit(10)->get();
        }

        // Load orders data
        $ordersList = collect();
        if ($this->activeTab === 'orders') {
            $ordersList = $user->orders()->with('service')->latest()->paginate(10);
        }

        // Load selectedAgreement from ID (always, not just on agreements tab — needed for re-renders)
        $selectedAgreement = null;
        if ($this->selectedAgreementId) {
            $selectedAgreement = \App\Models\Agreement::with(['workItems', 'milestones', 'histories', 'tasks'])
                ->where('client_id', $user->id)
                ->find($this->selectedAgreementId);
        }

        // Leads and portfolio are lightweight — load always for header counters
        $leads = \App\Models\Lead::where('email', $user->email)->latest()->get();
        $portfolioItems = \App\Models\Portfolio::visible()->latest()->take(3)->get();
        $testimonials   = \App\Models\Testimonial::visible()->ordered()->take(4)->get();

        // Wallet analytics — lightweight, load always for graph
        $walletTransactions = $user->walletTransactions()->latest()->limit(30)->get();

        // Hosting renewals — load always for timeline
        $userHostings = \App\Models\UserHosting::where('user_id', $user->id)
            ->with('service')
            ->latest()
            ->get();

        return view('livewire.client-dashboard', [
            'kanban'                => $kanban,
            'projects'              => $projects,
            'leads'                 => $leads,
            'tickets'               => $tickets,
            'vaultAssets'           => $vaultAssets,
            'hostings'              => $hostings,
            'domains'               => $domains,
            'availableHostings'     => $availableHostings,
            'agreements'            => $agreements,
            'agreementRequests'     => $agreementRequests,
            'selectedAgreement'     => $selectedAgreement,
            'portfolioItems'        => $portfolioItems,
            'testimonials'          => $testimonials,
            'hasProvisioning'       => $hasProvisioning,
            'walletTransactions'    => $walletTransactions,
            'userHostings'          => $userHostings,
            'currencySymbol'        => '$',
            'streamingApiKeys'      => $this->streamingApiKeys,
            'streamingSubscription'   => $this->streamingSubscription,
            'generatedStreamToken'  => $this->generatedStreamToken,
            'walletTransactionsList'=> $walletTransactionsList,
            'walletTopups'          => $walletTopups,
            'razorpaySettings'      => $razorpaySettings,
            'ordersList'            => $ordersList,
        ])->layout('components.layouts.believoo', ['title' => 'Client Portal']);
    }
}
