<?php

namespace App\Livewire;

use Livewire\Component;
use App\Facades\ServerManagement;
use Illuminate\Support\Facades\Auth;

class ServerDashboard extends Component
{
    public array $servers = [];
    public array $invoices = [];
    public array $summary = [];
    public ?array $selectedServer = null;
    public bool $loading = false;
    public ?string $message = null;
    public string $messageType = 'info';

    // Server action confirmation
    public bool $showConfirmation = false;
    public string $pendingAction = '';
    public int $pendingVpsId = 0;
    public string $confirmationTitle = '';
    public string $confirmationMessage = '';

    // Billing info
    public ?array $billingInfo = null;
    public string $activeTab = 'servers'; // servers, billing

    protected $listeners = ['refreshServers' => 'loadData'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loading = true;

        // Get user's WHMCS client ID (stored in user model or settings)
        $whmcsClientId = Auth::user()->whmcs_client_id ?? 0;

        // Get VPS IDs from user's services
        $vpsIds = $this->getUserVpsIds();

        $data = ServerManagement::getDashboardData($whmcsClientId, $vpsIds);

        $this->servers = $data['services'] ?? [];
        $this->invoices = $data['invoices'] ?? [];
        $this->summary = $data['summary'] ?? [];

        // Load UserHosting VPS records (from VPS Plans purchases)
        $this->loadUserHostingVps();

        // Load billing info
        $this->loadBillingInfo();

        $this->loading = false;
    }
    
    /**
     * Load VPS hosting records from UserHosting model
     */
    protected function loadUserHostingVps(): void
    {
        $user = Auth::user();
        
        // Get all VPS type hostings for this user
        $vpsHostings = \App\Models\UserHosting::where('user_id', $user->id)
            ->whereIn('hosting_type', ['vps', 'cloud', 'kvm'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        foreach ($vpsHostings as $hosting) {
            $this->servers[] = [
                'id' => $hosting->id,
                'type' => 'vps_plan',
                'vps_id' => $hosting->id,
                'hostname' => $hosting->server_hostname ?? $hosting->primary_domain ?? 'VPS-' . $hosting->id,
                'ip_address' => $hosting->server_ip,
                'plan_name' => $hosting->plan_name,
                'status' => $hosting->status === 'active' ? 'online' : 'offline',
                'cpu_cores' => $hosting->cpu_cores,
                'ram_gb' => $hosting->ram_size,
                'disk_gb' => $hosting->storage_size,
                'os' => $hosting->os_name,
                'root_password' => $hosting->root_password,
                'control_panel' => $hosting->control_panel_url,
                'expiry_date' => $hosting->expiry_date?->format('Y-m-d'),
                'actions_available' => $hosting->status === 'active',
                'user_hosting' => $hosting->toArray(),
            ];
        }
        
        // Update summary
        $this->summary['total_services'] = count($this->servers);
    }

    public function loadBillingInfo(): void
    {
        $user = Auth::user();
        $billingService = app(\App\Services\BillingService::class);

        $this->billingInfo = [
            'next_due_date' => $user->next_due_date?->format('F d, Y'),
            'days_remaining' => $billingService->getDaysRemaining($user),
            'plan_price' => $user->plan_price,
            'billing_status' => $user->billing_status ?? 'active',
            'last_payment_date' => $user->last_payment_date?->format('F d, Y'),
            'current_plan_name' => $user->current_plan_name ?? 'Standard VPS',
            'is_overdue' => $billingService->isOverdue($user),
        ];
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function downloadInvoice(int $invoiceId): void
    {
        $this->dispatch('downloadInvoice', invoiceId: $invoiceId);
    }

    public function payNow(): void
    {
        $this->dispatch('redirectToPayment');
    }

    public function selectServer(int $serviceId, int $vpsId): void
    {
        // SECURITY CHECK: Ensure user owns this VPS
        if (!$this->userOwnsVps($vpsId)) {
            $this->message = 'Unauthorized: You do not have access to this server.';
            $this->messageType = 'error';
            return;
        }

        $this->selectedServer = ServerManagement::getServerDetails($serviceId, $vpsId);
    }

    public function closeServerDetails(): void
    {
        $this->selectedServer = null;
    }

    public function confirmAction(string $action, int $vpsId, string $title = '', string $message = ''): void
    {
        $this->pendingAction = $action;
        $this->pendingVpsId = $vpsId;
        $this->confirmationTitle = $title ?: ucfirst($action) . ' Server';
        $this->confirmationMessage = $message ?: 'Are you sure you want to ' . $action . ' this server?';
        $this->showConfirmation = true;
    }

    public function cancelAction(): void
    {
        $this->showConfirmation = false;
        $this->pendingAction = '';
        $this->pendingVpsId = 0;
    }

    public function executeConfirmedAction(): void
    {
        $this->showConfirmation = false;
        $this->executeAction($this->pendingAction, $this->pendingVpsId);
    }

    public function executeAction(string $action, int $vpsId): void
    {
        // SECURITY CHECK: Ensure user owns this VPS before any action
        if (!$this->userOwnsVps($vpsId)) {
            $this->message = 'Unauthorized: You cannot perform actions on servers you do not own.';
            $this->messageType = 'error';
            $this->loading = false;
            return;
        }

        $this->loading = true;

        $result = ServerManagement::executeServerAction($vpsId, $action);

        if ($result['success']) {
            $this->message = $result['message'];
            $this->messageType = 'success';
        } else {
            $this->message = $result['message'];
            $this->messageType = 'error';
        }

        // Refresh data after action
        $this->loadData();

        // If viewing server details, refresh that too
        if ($this->selectedServer && $this->selectedServer['server']['vps_id'] === $vpsId) {
            $this->selectedServer = ServerManagement::getServerDetails(
                $this->selectedServer['service']['id'],
                $vpsId
            );
        }

        // Clear message after 3 seconds
        $this->dispatch('clearMessage');

        $this->loading = false;
    }

    public function refreshBandwidth(int $vpsId): void
    {
        // SECURITY CHECK: Ensure user owns this VPS
        if (!$this->userOwnsVps($vpsId)) {
            return;
        }

        $bandwidth = ServerManagement::getRealtimeBandwidth($vpsId);

        if ($bandwidth && $this->selectedServer) {
            $this->selectedServer['server']['bandwidth'] = $bandwidth;
        }
    }

    /**
     * SECURITY: Check if the current user owns the specified VPS
     */
    protected function userOwnsVps(int $vpsId): bool
    {
        $user = Auth::user();

        // Get user's allowed VPS IDs
        $userVpsIds = $this->getUserVpsIds();

        // If stored as JSON, decode it
        if (is_string($userVpsIds)) {
            $userVpsIds = json_decode($userVpsIds, true) ?? [];
        }

        // Check if the VPS ID is in the user's allowed list
        return in_array($vpsId, $userVpsIds);
    }

    public function clearMessage(): void
    {
        $this->message = null;
    }

    /**
     * Get VPS IDs associated with current user
     */
    protected function getUserVpsIds(): array
    {
        $user = Auth::user();
        return $user->getVpsIdsArray();
    }

    public function render()
    {
        return view('livewire.server-dashboard-billing');
    }
}
