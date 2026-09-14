<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ProxmoxVm;
use App\Models\VpsLicense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LicenseController extends Controller
{
    /**
     * Available license types with pricing
     */
    const LICENSE_TYPES = [
        'aapanel' => [
            'name' => 'aaPanel Professional',
            'description' => 'Lightweight control panel with PHP, MySQL, Nginx',
            'icon' => '🎛️',
            'price_monthly' => 199,
            'price_yearly' => 1990,
            'features' => ['Unlimited Sites', 'Free SSL', 'One-click Deploy', 'Resource Monitor'],
        ],
        'cpanel' => [
            'name' => 'cPanel Solo',
            'description' => 'Industry standard control panel for single account',
            'icon' => '🌐',
            'price_monthly' => 1499,
            'price_yearly' => 14990,
            'features' => ['1 Account', 'Softaculous', 'Email Hosting', 'DNS Management'],
        ],
        'cpanel_plus' => [
            'name' => 'cPanel Admin',
            'description' => 'For small businesses with up to 5 accounts',
            'icon' => '🌐',
            'price_monthly' => 2499,
            'price_yearly' => 24990,
            'features' => ['5 Accounts', 'Softaculous', 'Email Hosting', 'WHM Access'],
        ],
        'cpanel_pro' => [
            'name' => 'cPanel Pro',
            'description' => 'For growing businesses with up to 30 accounts',
            'icon' => '🌐',
            'price_monthly' => 3499,
            'price_yearly' => 34990,
            'features' => ['30 Accounts', 'Softaculous', 'Priority Support', 'WHM Access'],
        ],
        'plesk' => [
            'name' => 'Plesk Web Admin',
            'description' => 'Complete web hosting platform',
            'icon' => '🔧',
            'price_monthly' => 1299,
            'price_yearly' => 12990,
            'features' => ['10 Domains', 'WordPress Toolkit', 'Email Security', 'Git Integration'],
        ],
        'plesk_hosting' => [
            'name' => 'Plesk Web Host',
            'description' => 'For hosting providers',
            'icon' => '🔧',
            'price_monthly' => 2299,
            'price_yearly' => 22990,
            'features' => ['Unlimited Domains', 'Reseller Management', 'Security Core', 'SEO Toolkit'],
        ],
        'imunify' => [
            'name' => 'Imunify360',
            'description' => 'Advanced security suite for Linux servers',
            'icon' => '🛡️',
            'price_monthly' => 599,
            'price_yearly' => 5990,
            'features' => ['Malware Scan', 'WAF', 'IDS/IPS', 'Auto-Cleanup'],
        ],
        'kernelcare' => [
            'name' => 'KernelCare',
            'description' => 'Live kernel patching without reboots',
            'icon' => '⚡',
            'price_monthly' => 299,
            'price_yearly' => 2990,
            'features' => ['No Reboot', 'Auto-Patch', 'Security Fixes', 'Uptime Protection'],
        ],
    ];
    
    /**
     * Show license store
     */
    public function index()
    {
        $licenses = VpsLicense::where('user_id', Auth::id())
            ->with('vm')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $vms = ProxmoxVm::where('user_id', Auth::id())
            ->where('status', 'running')
            ->get();
            
        $availableLicenses = self::LICENSE_TYPES;
        
        return view('client.licenses.index', compact('licenses', 'vms', 'availableLicenses'));
    }
    
    /**
     * Purchase license
     */
    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'license_type' => 'required|in:' . implode(',', array_keys(self::LICENSE_TYPES)),
            'vm_id' => 'required|exists:proxmox_vms,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);
        
        $vm = ProxmoxVm::where('id', $validated['vm_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $licenseType = self::LICENSE_TYPES[$validated['license_type']];
        $price = $validated['billing_cycle'] === 'yearly' 
            ? $licenseType['price_yearly'] 
            : $licenseType['price_monthly'];
        
        try {
            // Generate license key
            $licenseKey = $this->generateLicenseKey($validated['license_type']);
            
            $license = VpsLicense::create([
                'user_id' => Auth::id(),
                'proxmox_vm_id' => $vm->id,
                'license_type' => $validated['license_type'],
                'license_key' => $licenseKey,
                'status' => 'active',
                'price_paid' => $price,
                'billing_cycle' => $validated['billing_cycle'],
                'expires_at' => $validated['billing_cycle'] === 'yearly' 
                    ? now()->addYear() 
                    : now()->addMonth(),
                'features' => $licenseType['features'],
            ]);
            
            // Auto-install on VPS (via SSH or API)
            $this->installLicense($license, $vm);
            
            Log::info('License purchased', [
                'user_id' => Auth::id(),
                'license_id' => $license->id,
                'type' => $validated['license_type'],
                'vm_id' => $vm->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $licenseType['name'] . ' license activated!',
                'license' => $license,
            ]);
            
        } catch (\Exception $e) {
            Log::error('License purchase failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Purchase failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Activate license on VPS
     */
    public function activate($id)
    {
        $license = VpsLicense::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        try {
            $vm = $license->vm;
            $this->installLicense($license, $vm);
            
            $license->update(['status' => 'active']);
            
            return response()->json([
                'success' => true,
                'message' => 'License activated on VPS!',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Activation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Generate license key
     */
    private function generateLicenseKey(string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));
        
        return "BEL-{$prefix}-{$timestamp}-{$random}";
    }
    
    /**
     * Install license on VPS by writing the license metadata to /etc/believoo/license.json.
     * For supported control panels (cPanel, Plesk, aaPanel) it also attempts to run
     * the panel-specific installer/activator if the panel is installed.
     */
    private function installLicense(VpsLicense $license, ProxmoxVm $vm): void
    {
        if (!$vm->ip_address) {
            Log::warning('License install skipped: VM has no IP', [
                'license_id' => $license->id,
                'vm_id' => $vm->id,
            ]);
            return;
        }

        // Resolve root password from the VM or its linked hosting
        $password = $vm->panel_password;

        if (!$password) {
            $hosting = \App\Models\UserHosting::where('vps_id', $vm->vmid)
                ->where('user_id', $license->user_id)
                ->first();
            $password = $hosting?->root_password;
        }

        if (!$password) {
            Log::warning('License install skipped: no root password available', [
                'license_id' => $license->id,
                'vm_id' => $vm->id,
            ]);
            return;
        }

        $licenseType = $license->type ?? $license->license_type ?? 'none';

        $licenseJson = json_encode([
            'type' => $licenseType,
            'key' => $license->license_key,
            'activated_at' => now()->toIso8601String(),
            'expires_at' => $license->expires_at?->toIso8601String(),
            'provider' => 'Believoo',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $panelCommand = match ($licenseType) {
            'aapanel' => 'bash /www/server/panel/script/lic_pro.sh ' . escapeshellarg($license->license_key) . ' 2>/dev/null; ',
            'cpanel', 'cpanel_plus', 'cpanel_pro' => '/usr/local/cpanel/cpkeyclt --force 2>/dev/null; ',
            'plesk', 'plesk_hosting' => 'plesk bin license --install ' . escapeshellarg($license->license_key) . ' 2>/dev/null; ',
            default => '',
        };

        $remoteCommand = $panelCommand . 'mkdir -p /etc/believoo && echo ' . escapeshellarg($licenseJson) . ' > /etc/believoo/license.json';

        $passFile = tempnam(sys_get_temp_dir(), 'lic_');
        file_put_contents($passFile, $password);
        chmod($passFile, 0600);

        try {
            $cmd = sprintf(
                'sshpass -f %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p 22 root@%s %s 2>&1',
                escapeshellarg($passFile),
                escapeshellarg($vm->ip_address),
                escapeshellarg($remoteCommand)
            );

            $output = shell_exec($cmd);

            $license->update([
                'status' => 'active',
                'activated_at' => now(),
                'last_verified_at' => now(),
            ]);

            Log::info('License installed on VPS', [
                'license_id' => $license->id,
                'vm_id' => $vm->id,
                'ip' => $vm->ip_address,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('License install failed', [
                'license_id' => $license->id,
                'vm_id' => $vm->id,
                'ip' => $vm->ip_address,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if (file_exists($passFile) && is_file($passFile)) {
                unlink($passFile);
            }
        }
    }
}
