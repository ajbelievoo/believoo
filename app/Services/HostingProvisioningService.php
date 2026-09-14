<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\UserHosting;
use App\Models\Service;
use App\Models\ProxmoxVm;
use App\Models\ProxmoxNode;
use App\Models\VpsPlan;
use App\Models\User;
use App\Models\IpAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HostingProvisioningService
{
    /**
     * Provision hosting when order is paid (automatic processing)
     * NOW WITH FULL AUTOMATION: Auto-creates VM via Proxmox API
     */
    public function provisionFromOrder(Order $order): ?UserHosting
    {
        try {
            // ===== OVH RESeller ORDER =====
            // If this order is linked to an OVH-backed plan, let the OVH service handle it.
            $ovhProvisioner = new OvhProvisioningService();
            if ($ovhProvisioner->isOvhOrder($order)) {
                Log::info('Order routed to OVH provisioning', ['order_id' => $order->id]);
                return $ovhProvisioner->provisionFromOrder($order);
            }

            // ===== IDEMPOTENCY CHECK =====
            // Prevent duplicate provisioning if called multiple times (webhook + callback)
            $quantity = (int) ($order->metadata['quantity'] ?? 1);
            $existingCount = UserHosting::where('order_id', $order->id)->count();
            
            if ($existingCount >= $quantity) {
                $existing = UserHosting::where('order_id', $order->id)->first();
                Log::info('Provisioning skipped: hosting already exists for order ' . $order->id, [
                    'existing_count' => $existingCount,
                    'quantity'       => $quantity,
                ]);
                return $existing;
            }

            $service = $order->service;

            if (!$service) {
                Log::error('Cannot provision hosting: Service not found for order ' . $order->id);
                return null;
            }

            // Determine hosting type from service category
            $hostingType = $this->getHostingTypeFromService($service);

            // Get VPS specs from order metadata or service
            $specs = $this->getVpsSpecsFromOrder($order, $service);

            // Calculate dates
            $startDate = now();
            $expiryDate = $this->calculateExpiryDate($startDate, $order->billing_months ?? 1);

            // Calculate price
            $price = $order->amount;
            $billingCycle = $this->getBillingCycleFromMonths($order->billing_months ?? 1);

            // Generate root password
            $rootPassword = $this->generateSecurePassword();

            // Create user hosting with pending status initially
            $hosting = UserHosting::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'service_id' => $service->id,
                'hosting_type' => $hostingType,
                'plan_name' => $order->tier_name ?: $service->title,
                'status' => 'provisioning', // Provisioning status while VM is being created
                'price' => $price,
                'billing_cycle' => $billingCycle,
                'start_date' => $startDate,
                'expiry_date' => $expiryDate,
                'primary_domain' => null,
                'cpu_cores' => $specs['cpu_cores'] ?? null,
                'ram_size' => $specs['memory_gb'] ?? null,
                'storage_size' => $specs['disk_gb'] ?? null,
                'os_name' => $specs['os'] ?? 'Ubuntu 22.04',
                'root_password' => $rootPassword,
                'admin_notes' => 'Auto-provisioned from order #' . $order->order_number . '. VM creation in progress...',
            ]);

            // Create invoice for this order
            $this->createInvoiceFromOrder($order, $hosting);

            // Notify user: payment confirmed + provisioning started
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new \App\Notifications\VpsServerNotification('payment_success', [
                    'amount' => number_format($order->amount, 2),
                    'plan'   => $hosting->plan_name,
                ]));
                $user->notify(new \App\Notifications\VpsServerNotification('vm_provisioning', [
                    'plan' => $hosting->plan_name,
                ]));
            }

            // ========== AUTOMATIC VM CREATION ==========
            // Create VM via Proxmox API asynchronously
            $this->autoCreateVmForHosting($hosting, $order, $specs, $rootPassword);

            Log::info('Hosting provisioned with auto-VM creation for order ' . $order->id, [
                'hosting_id' => $hosting->id,
                'user_id' => $order->user_id,
                'vm_specs' => $specs,
            ]);

            return $hosting;

        } catch (\Exception $e) {
            Log::error('Failed to provision hosting for order ' . $order->id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create hosting manually from admin panel
     */
    public function createManualHosting(array $data, int $adminId): array
    {
        try {
            // Create or use existing order
            $order = null;
            if (!empty($data['create_order'])) {
                $order = Order::create([
                    'user_id' => $data['user_id'],
                    'service_id' => $data['service_id'] ?? null,
                    'service_name' => $data['service_name'] ?? $data['plan_name'],
                    'tier_name' => $data['tier_name'] ?? null,
                    'billing_months' => $data['billing_months'] ?? 1,
                    'amount' => $data['price'] ?? 0,
                    'currency' => 'INR',
                    'status' => $data['order_status'] ?? 'paid',
                    'payment_gateway' => 'admin',
                    'paid_at' => now(),
                    'notes' => 'Created manually by admin #' . $adminId,
                ]);
            }

            // Calculate dates
            $startDate = $data['start_date'] ?? now();
            $expiryDate = $data['expiry_date'] ?? $this->calculateExpiryDate(
                $startDate, 
                $data['billing_months'] ?? 1
            );

            // Create hosting
            $hosting = UserHosting::create([
                'user_id' => $data['user_id'],
                'order_id' => $order?->id,
                'service_id' => $data['service_id'] ?? null,
                'hosting_type' => $data['hosting_type'],
                'plan_name' => $data['plan_name'],
                'status' => $data['status'] ?? 'active',
                'price' => $data['price'] ?? 0,
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'start_date' => $startDate,
                'expiry_date' => $expiryDate,
                'server_ip' => $data['server_ip'] ?? null,
                'server_hostname' => $data['server_hostname'] ?? null,
                'control_panel_url' => $data['control_panel_url'] ?? null,
                'control_panel_username' => $data['control_panel_username'] ?? null,
                'control_panel_password' => $data['control_panel_password'] ?? null,
                'primary_domain' => $data['primary_domain'] ?? null,
                'cpu_cores' => $data['cpu_cores'] ?? null,
                'ram_size' => $data['ram_size'] ?? null,
                'storage_size' => $data['storage_size'] ?? null,
                'os_name' => $data['os_name'] ?? null,
                'datacenter_location' => $data['datacenter_location'] ?? null,
                'root_password' => $data['root_password'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? 'Created manually by admin #' . $adminId,
            ]);

            // Create invoice
            $invoice = $this->createManualInvoice($data, $order?->id, $hosting->id, $adminId);

            Log::info('Manual hosting created by admin', [
                'hosting_id' => $hosting->id,
                'invoice_id' => $invoice?->id,
                'admin_id' => $adminId,
            ]);

            return [
                'success' => true,
                'hosting' => $hosting,
                'order' => $order,
                'invoice' => $invoice,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create manual hosting: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create invoice from order
     */
    private function createInvoiceFromOrder(Order $order, UserHosting $hosting): ?Invoice
    {
        try {
            return Invoice::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'invoice_type' => 'hosting',
                'description' => $hosting->plan_name . ' - ' . $hosting->hosting_type . ' Hosting',
                'amount' => $order->amount,
                'tax_amount' => 0, // Calculate based on GST rules
                'total_amount' => $order->amount,
                'currency' => $order->currency,
                'status' => 'paid',
                'payment_gateway' => $order->payment_gateway,
                'payment_id' => $order->payment_id,
                'paid_at' => $order->paid_at,
                'due_date' => $order->paid_at?->addDays(7),
                'line_items' => [
                    [
                        'item' => $hosting->plan_name,
                        'description' => $hosting->hosting_type . ' Hosting - ' . $hosting->billing_cycle,
                        'quantity' => 1,
                        'price' => $order->amount,
                        'total' => $order->amount,
                    ]
                ],
                'notes' => 'Auto-generated from order #' . $order->order_number,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create invoice from order: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create manual invoice
     */
    private function createManualInvoice(array $data, ?int $orderId, int $hostingId, int $adminId): ?Invoice
    {
        try {
            $amount = $data['price'] ?? 0;
            $taxAmount = $data['tax_amount'] ?? 0;
            
            return Invoice::create([
                'user_id' => $data['user_id'],
                'order_id' => $orderId,
                'invoice_type' => $data['invoice_type'] ?? 'hosting',
                'description' => ($data['plan_name'] ?? 'Hosting Plan') . ' - Manual Allocation',
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $amount + $taxAmount,
                'currency' => 'INR',
                'status' => $data['payment_status'] ?? 'pending',
                'payment_gateway' => 'admin',
                'paid_at' => ($data['payment_status'] ?? 'pending') === 'paid' ? now() : null,
                'due_date' => $data['due_date'] ?? now()->addDays(7),
                'line_items' => [
                    [
                        'item' => $data['plan_name'] ?? 'Hosting Service',
                        'description' => ($data['hosting_type'] ?? 'Shared') . ' Hosting - ' . ($data['billing_cycle'] ?? 'monthly'),
                        'quantity' => 1,
                        'price' => $amount,
                        'total' => $amount,
                    ]
                ],
                'notes' => 'Created manually by admin #' . $adminId . ' for hosting #' . $hostingId,
                'created_by' => $adminId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create manual invoice: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get hosting type from service category
     */
    private function getHostingTypeFromService(Service $service): string
    {
        $category = strtolower($service->category ?? '');
        
        return match($category) {
            'vps' => 'vps',
            'dedicated' => 'dedicated',
            'cloud' => 'cloud',
            'shared' => 'shared',
            default => 'shared',
        };
    }

    /**
     * Calculate expiry date based on billing months
     */
    private function calculateExpiryDate($startDate, int $months): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($startDate)->addMonths($months);
    }

    /**
     * Get billing cycle string from months
     */
    private function getBillingCycleFromMonths(int $months): string
    {
        return match($months) {
            1 => 'monthly',
            3 => 'quarterly',
            6 => 'half_yearly',
            12 => 'yearly',
            default => 'monthly',
        };
    }

    /**
     * Get VPS specs from order metadata or service
     */
    private function getVpsSpecsFromOrder(Order $order, Service $service): array
    {
        $metadata = $order->metadata ?? [];

        // 1. Highest priority: explicit vps_plan_id in metadata
        if (!empty($metadata['vps_plan_id'])) {
            $plan = VpsPlan::find($metadata['vps_plan_id']);
            if ($plan) {
                Log::info('Provisioning specs from metadata vps_plan_id', [
                    'order_id' => $order->id,
                    'plan_id'  => $plan->id,
                    'specs'    => ['cpu' => $plan->cpu_cores, 'ram' => $plan->memory_gb, 'disk' => $plan->disk_gb],
                ]);
                return [
                    'cpu_cores' => $plan->cpu_cores,
                    'memory_gb' => $plan->memory_gb,
                    'disk_gb' => $plan->disk_gb,
                    'os' => $metadata['os'] ?? 'Ubuntu 22.04',
                    'iso' => $metadata['iso'] ?? 'ubuntu-22.04-live-server-amd64.iso',
                ];
            }
        }

        // 2. CPU/RAM directly in metadata
        if (!empty($metadata['cpu_cores']) && !empty($metadata['memory_gb'])) {
            return [
                'cpu_cores' => $metadata['cpu_cores'],
                'memory_gb' => $metadata['memory_gb'],
                'disk_gb' => $metadata['disk_gb'] ?? 20,
                'os' => $metadata['os'] ?? 'Ubuntu 22.04',
                'iso' => $metadata['iso'] ?? 'ubuntu-22.04-live-server-amd64.iso',
            ];
        }

        // 3. Try to find matching VpsPlan by tier_name (case-insensitive, trimmed)
        $tierName = trim($order->tier_name ?? '');
        if (!empty($tierName)) {
            $plan = VpsPlan::whereRaw('LOWER(name) = LOWER(?)', [$tierName])
                ->orWhereRaw('LOWER(slug) = LOWER(?)', [$tierName])
                ->first();

            if ($plan) {
                Log::info('Provisioning specs from tier_name plan lookup', [
                    'order_id'  => $order->id,
                    'tier_name' => $tierName,
                    'specs'     => ['cpu' => $plan->cpu_cores, 'ram' => $plan->memory_gb, 'disk' => $plan->disk_gb],
                ]);
                return [
                    'cpu_cores' => $plan->cpu_cores,
                    'memory_gb' => $plan->memory_gb,
                    'disk_gb' => $plan->disk_gb,
                    'os' => 'Ubuntu 22.04',
                    'iso' => 'ubuntu-22.04-live-server-amd64.iso',
                ];
            }
        }

        // 4. Try service pricing tiers (case-insensitive tier match)
        if (!empty($service->pricing_tiers)) {
            foreach ($service->pricing_tiers as $tier) {
                $tierPlanName = trim($tier['name'] ?? '');
                if (empty($tierName) || strcasecmp($tierPlanName, $tierName) === 0) {
                    Log::info('Provisioning specs from service pricing tier', [
                        'order_id' => $order->id,
                        'tier'     => $tierPlanName,
                    ]);
                    return [
                        'cpu_cores' => $tier['cpu_cores'] ?? 1,
                        'memory_gb' => $tier['ram_gb'] ?? 1,
                        'disk_gb' => $tier['disk_gb'] ?? 20,
                        'os' => 'Ubuntu 22.04',
                        'iso' => 'ubuntu-22.04-live-server-amd64.iso',
                    ];
                }
            }
        }

        // 5. Last resort: infer from service name / tier_name if it looks like a VPS plan
        $searchSource = !empty($tierName) ? $tierName : ($service->title ?? '');
        if (stripos($searchSource, 'vps') !== false) {
            // Try a fuzzy plan search (e.g. "VPS-2" -> any plan with "2" in the name)
            preg_match('/(\d+)/', $searchSource, $m);
            if (!empty($m[1])) {
                $plan = VpsPlan::where('name', 'like', '%' . $m[1] . '%')
                    ->orWhere('slug', 'like', '%' . $m[1] . '%')
                    ->first();
                if ($plan) {
                    Log::warning('Provisioning specs from fuzzy plan fallback', [
                        'order_id'     => $order->id,
                        'tier_name'    => $tierName,
                        'search_source'=> $searchSource,
                        'matched_plan' => $plan->name,
                    ]);
                    return [
                        'cpu_cores' => $plan->cpu_cores,
                        'memory_gb' => $plan->memory_gb,
                        'disk_gb' => $plan->disk_gb,
                        'os' => 'Ubuntu 22.04',
                        'iso' => 'ubuntu-22.04-live-server-amd64.iso',
                    ];
                }
            }
        }

        // 5b. If service title itself is a known VPS plan name, lookup directly
        if (!empty($service->title)) {
            $plan = VpsPlan::whereRaw('LOWER(name) = LOWER(?)', [$service->title])
                ->orWhereRaw('LOWER(slug) = LOWER(?)', [$service->title])
                ->first();
            if ($plan) {
                Log::warning('Provisioning specs from service title plan lookup', [
                    'order_id'     => $order->id,
                    'service_title'=> $service->title,
                    'matched_plan' => $plan->name,
                ]);
                return [
                    'cpu_cores' => $plan->cpu_cores,
                    'memory_gb' => $plan->memory_gb,
                    'disk_gb' => $plan->disk_gb,
                    'os' => 'Ubuntu 22.04',
                    'iso' => 'ubuntu-22.04-live-server-amd64.iso',
                ];
            }
        }

        // 6. True default — only for non-VPS orders
        Log::error('Provisioning fell back to default 1/1/20 specs', [
            'order_id'    => $order->id,
            'tier_name'   => $tierName,
            'service'     => $service->title ?? 'unknown',
            'metadata'    => $metadata,
        ]);

        return [
            'cpu_cores' => 1,
            'memory_gb' => 1,
            'disk_gb' => 20,
            'os' => 'Ubuntu 22.04',
            'iso' => 'ubuntu-22.04-live-server-amd64.iso',
        ];
    }

    /**
     * Automatically create VM for hosting via Proxmox API
     */
    private function autoCreateVmForHosting(UserHosting $hosting, Order $order, array $specs, string $rootPassword): void
    {
        try {
            // Get available Proxmox node
            $node = ProxmoxNode::where('status', 'active')
                ->where('max_vms', '>', 0)
                ->whereRaw('current_vms < max_vms')
                ->orderByRaw('current_vms ASC')
                ->first();
            
            if (!$node) {
                // Fallback: try any active node
                $node = ProxmoxNode::where('status', 'active')->first();
            }
            
            if (!$node) {
                Log::warning('No available Proxmox node for auto-provisioning', ['hosting_id' => $hosting->id]);
                $hosting->update([
                    'status' => 'pending',
                    'admin_notes' => 'No available node for auto-provisioning. Admin intervention required.',
                ]);
                return;
            }
            
            // Prepare VM configuration
            $vmName = 'vps-' . $order->user_id . '-' . $hosting->id;

            // ===== JIT ON-DEMAND IP ACQUISITION =====
            // Purchase a fresh IPv4 from the server provider's API ONLY after client paid.
            // Zero upfront cost — no pre-purchased static pool.
            $jitIpResult = null;
            $ipRecord    = null;
            $jitProvider = null;

            if ($node->supportsJitIp()) {
                $providerService = app(ServerProviderApiService::class);
                $jitIpResult     = $providerService->acquireIp($node);

                if ($jitIpResult) {
                    $jitProvider = $node->provider_name;
                    Log::info('JIT IP acquired for VM', [
                        'hosting_id' => $hosting->id,
                        'node'       => $node->name,
                        'ip'         => $jitIpResult->ip,
                        'provider'   => $jitProvider,
                    ]);
                } else {
                    Log::warning('JIT IP acquisition failed — will fallback to DHCP', [
                        'hosting_id' => $hosting->id,
                        'node'       => $node->name,
                        'provider'   => $node->provider_name,
                    ]);
                }
            } else {
                Log::info('JIT IP not enabled for node — falling back to DHCP', [
                    'hosting_id' => $hosting->id,
                    'node'       => $node->name,
                ]);
            }

            // Build Proxmox cloud-init IP configuration
            if ($jitIpResult) {
                $ipConfig = $jitIpResult->toProxmoxIpConfig();
            } else {
                $ipConfig = 'ip=dhcp';
            }

            $vmConfig = [
                'name'      => $vmName,
                'cpu'       => $specs['cpu_cores'],
                'memory'    => ($specs['memory_gb'] ?? 1) * 1024,
                'disk'      => $specs['disk_gb'] ?? 20,
                'iso'       => $specs['iso'] ?? 'ubuntu-22.04-live-server-amd64.iso',
                'storage'   => 'local',
                'ciuser'    => 'root',
                'cipassword'=> $rootPassword,
                'ipconfig0' => $ipConfig,
            ];

            // Create Proxmox API service
            $apiToken = $node->getDecryptedApiToken();
            if (!$apiToken) {
                throw new \Exception('No API token for node: ' . $node->name);
            }

            $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;
            $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $apiToken, $node->name);

            // Create VM
            Log::info('Auto-creating VM for hosting', [
                'hosting_id' => $hosting->id,
                'node' => $node->name,
                'config' => $vmConfig,
            ]);

            $result = $proxmox->createAndStartVm($vmConfig);

            if (!$result) {
                throw new \Exception('Failed to create VM via Proxmox API');
            }

            $vmid = $result['vmid'];

            // Give VM a moment to boot before checking IP
            $ipAddress = null;
            try {
                sleep(5);
                $vmStatus = $proxmox->getVmStatus($vmid);
                $ipAddress = $this->extractIpFromStatus($vmStatus);
            } catch (\Exception $ipEx) {
                Log::warning('Could not get VM IP after creation', ['vmid' => $vmid, 'error' => $ipEx->getMessage()]);
            }

            // Persist JIT IP in ledger (or use discovered IP if JIT failed)
            $finalIp = $jitIpResult?->ip ?? $ipAddress;

            if ($jitIpResult) {
                $ipRecord = IpAddress::createFromJitResult($jitIpResult, $node->name, $jitProvider);
                $ipRecord->assignToVm($vmid, null, $hosting->id);
            }

            // Create or update ProxmoxVm record
            $proxmoxVm = ProxmoxVm::updateOrCreate(
                ['vmid' => $vmid],
                [
                    'user_id'          => $order->user_id,
                    'name'             => $vmName,
                    'hostname'         => $vmName . '.believoo.com',
                    'node'             => $node->name,
                    'cpu_cores'        => $specs['cpu_cores'],
                    'memory_mb'        => $vmConfig['memory'],
                    'disk_gb'          => $specs['disk_gb'],
                    'storage'          => 'local',
                    'iso'              => $vmConfig['iso'],
                    'ip_address'       => $finalIp,
                    'plan_name'        => $hosting->plan_name,
                    'status'           => 'running',
                    'created_at_proxmox' => now(),
                    'started_at'       => now(),
                ]
            );

            // Update hosting with server details
            $hosting->update([
                'status'             => 'active',
                'server_ip'          => $finalIp,
                'server_hostname'    => $proxmoxVm->hostname,
                'root_password'      => $rootPassword,
                'datacenter_location'=> $node->display_name,
                'vps_id'             => $vmid,
                'admin_notes'        => 'VM auto-created. VMID: ' . $vmid . ' on node: ' . $node->name . ($jitIpResult ? ' | JIT IP from ' . $jitProvider . ': ' . $jitIpResult->ip : ' | IP: DHCP'),
            ]);
            
            // Update node VM count
            $node->increment('current_vms');
            
            // Send welcome email with credentials
            $this->sendWelcomeEmail($hosting, $order, $proxmoxVm, $rootPassword);

            // Notify user: VM is ready
            $notifUser = User::find($order->user_id);
            if ($notifUser) {
                $notifUser->notify(new \App\Notifications\VpsServerNotification('vm_created', [
                    'vmid'     => $vmid,
                    'plan'     => $hosting->plan_name,
                    'hostname' => $proxmoxVm->hostname,
                    'ip'       => $finalIp ?? 'Pending',
                ]));
            }
            
            Log::info('VM auto-created successfully', [
                'hosting_id' => $hosting->id,
                'vm_id' => $proxmoxVm->id,
                'vmid' => $vmid,
                'ip' => $ipAddress,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Auto-VM creation failed: ' . $e->getMessage(), [
                'hosting_id' => $hosting->id,
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update hosting to reflect failure
            $hosting->update([
                'status' => 'pending',
                'admin_notes' => 'Auto-VM creation failed: ' . $e->getMessage() . '. Admin intervention required.',
            ]);
        }
    }

    /**
     * Extract IP address from VM status
     */
    private function extractIpFromStatus(?array $status): ?string
    {
        if (!$status) {
            return null;
        }
        
        // Try to get IP from agent info or network interfaces
        if (!empty($status['agent-info']['network-interfaces'])) {
            foreach ($status['agent-info']['network-interfaces'] as $interface) {
                if (!empty($interface['ip-addresses'])) {
                    foreach ($interface['ip-addresses'] as $ip) {
                        // Return first IPv4 address that's not localhost
                        if ($ip['ip-address-type'] === 'ipv4' && $ip['ip-address'] !== '127.0.0.1') {
                            return $ip['ip-address'];
                        }
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Generate secure root password
     */
    private function generateSecurePassword(int $length = 16): string
    {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        $all = $upper . $lower . $numbers . $special;
        
        $password = '';
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
        
        return str_shuffle($password);
    }

    /**
     * Send welcome email with VPS credentials
     */
    private function sendWelcomeEmail(UserHosting $hosting, Order $order, ProxmoxVm $vm, string $rootPassword): void
    {
        try {
            $user = User::find($order->user_id);
            
            if (!$user || !$user->email) {
                Log::warning('Cannot send welcome email: User email not found', ['user_id' => $order->user_id]);
                return;
            }
            
            $emailData = [
                'user' => $user,
                'hosting' => $hosting,
                'order' => $order,
                'vm' => $vm,
                'password' => $rootPassword, // Template uses $password
                'ipAddress' => $vm->ip_address ?? 'Pending',
                'hostname' => $vm->hostname,
                'vmid' => $vm->vmid,
                'specs' => [
                    'cpu' => $vm->cpu_cores,
                    'ram' => $vm->memoryInGb(),
                    'disk' => $vm->disk_gb,
                ],
            ];
            
            Mail::send('emails.vps.welcome', $emailData, function ($message) use ($user, $hosting) {
                $message->to($user->email)
                    ->subject('🚀 Your VPS is Ready! Access Details Inside - ' . $hosting->plan_name)
                    ->from(config('mail.from.address'), config('mail.from.name', 'BelieVoo'));
            });
            
            Log::info('Welcome email sent successfully', [
                'hosting_id' => $hosting->id,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage(), [
                'hosting_id' => $hosting->id,
                'order_id' => $order->id,
            ]);
        }
    }

}