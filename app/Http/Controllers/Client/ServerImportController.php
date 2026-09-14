<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ServerImport;
use App\Models\UserHosting;
use App\Jobs\ServerImportJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServerImportController extends Controller
{
    /**
     * List user's server imports
     */
    public function index(Request $request): JsonResponse
    {
        $imports = ServerImport::forUser(Auth::id())
            ->with('userHosting')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'imports' => $imports,
        ]);
    }

    /**
     * Get active import for a hosting
     */
    public function getActiveImport(int $hostingId): JsonResponse
    {
        $userHosting = UserHosting::where('id', $hostingId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$userHosting) {
            return response()->json([
                'success' => false,
                'message' => 'Hosting not found',
            ], 404);
        }

        $activeImport = ServerImport::where('user_hosting_id', $hostingId)
            ->active()
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'has_active_import' => $activeImport !== null,
            'import' => $activeImport,
        ]);
    }

    /**
     * Start a new server import
     */
    public function startImport(Request $request, int $hostingId): JsonResponse
    {
        try {
            // Validate request with sync direction
            $validated = $request->validate([
                'source_ip' => 'required|ip',
                'source_root_password' => 'required|string|min:1',
                'source_port' => 'nullable|integer|min:1|max:65535',
                'sync_direction' => 'required|in:pull,push', // pull=import, push=export
                'sync_paths' => 'nullable|array', // Optional specific paths to sync
            ]);

            // Verify hosting belongs to user
            $userHosting = UserHosting::where('id', $hostingId)
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->first();

            if (!$userHosting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active hosting not found',
                ], 404);
            }

            // Check for existing active import
            $existingImport = ServerImport::where('user_hosting_id', $hostingId)
                ->active()
                ->first();

            if ($existingImport) {
                return response()->json([
                    'success' => false,
                    'message' => 'An import is already in progress for this server',
                    'import' => $existingImport,
                ], 409);
            }

            $syncDirection = $validated['sync_direction'];
            $isPull = $syncDirection === 'pull';

            // Create sync record
            $import = ServerImport::create([
                'user_id' => Auth::id(),
                'user_hosting_id' => $hostingId,
                'sync_direction' => $syncDirection,
                'source_ip' => $validated['source_ip'],
                'source_root_password' => $validated['source_root_password'],
                'source_port' => $validated['source_port'] ?? 22,
                'status' => 'pending',
                'progress_percent' => 0,
                'status_message' => $isPull 
                    ? 'Import queued: Preparing to sync data from remote server to BelieVoo...'
                    : 'Export queued: Preparing to sync data from BelieVoo to remote server...',
                'started_at' => now(),
                'metadata' => [
                    'sync_paths' => $validated['sync_paths'] ?? ['/www', '/home', '/var/www'],
                    'transfer_type' => $isPull ? 'Import to BelieVoo' : 'Export to External',
                ],
            ]);

            // Dispatch the new ServerSyncJob
            \App\Jobs\ServerSyncJob::dispatch($import);

            // Broadcast event
            try {
                broadcast(new \App\Events\ServerImportStarted($import))->toOthers();
            } catch (\Exception $e) {
                Log::warning('ServerImport broadcast failed: ' . $e->getMessage());
            }

            $directionLabel = $isPull ? 'Import' : 'Export';
            
            return response()->json([
                'success' => true,
                'message' => "Server {$directionLabel} started successfully. SSH keys will be auto-generated and deployed for passwordless sync.",
                'import' => $import,
                'direction' => $syncDirection,
                'direction_label' => $directionLabel,
            ]);

        } catch (\Exception $e) {
            Log::error('Server import start failed', [
                'hosting_id' => $hostingId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get import progress
     */
    public function getProgress(int $importId): JsonResponse
    {
        $import = ServerImport::where('id', $importId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'import' => $import,
            'is_complete' => in_array($import->status, ['completed', 'failed']),
        ]);
    }

    /**
     * Cancel an active import
     */
    public function cancelImport(int $importId): JsonResponse
    {
        $import = ServerImport::where('id', $importId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found',
            ], 404);
        }

        if (!in_array($import->status, ['pending', 'connecting', 'syncing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel import in current status: ' . $import->status,
            ], 400);
        }

        // Update status
        $import->update([
            'status' => 'failed',
            'status_message' => 'Cancelled by user',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Import cancelled',
        ]);
    }

    /**
     * Test SSH connection to source server
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'source_ip' => 'required|ip',
                'source_root_password' => 'required|string',
                'source_port' => 'nullable|integer|min:1|max:65535',
            ]);

            $ip = $validated['source_ip'];
            $password = $validated['source_root_password'];
            $port = $validated['source_port'] ?? 22;

            // Test SSH connection
            $connection = @fsockopen($ip, $port, $errno, $errstr, 5);
            
            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot connect to {$ip}:{$port}. Error: {$errstr}",
                ]);
            }
            
            fclose($connection);

            // Test SSH authentication using key-based or password
            // Note: This is a simplified check, actual auth happens in the job
            $sshTest = $this->testSshAuth($ip, $port, $password);

            return response()->json([
                'success' => $sshTest['success'],
                'message' => $sshTest['message'],
                'details' => $sshTest['details'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test SSH authentication
     */
    private function testSshAuth(string $ip, int $port, string $password): array
    {
        try {
            // Check if sshpass is available
            $sshpassCheck = shell_exec('which sshpass 2>&1');
            $hasSshpass = !empty($sshpassCheck) && strpos($sshpassCheck, 'not found') === false;
            
            // Check if ssh command is available
            $sshCheck = shell_exec('which ssh 2>&1');
            $hasSsh = !empty($sshCheck) && strpos($sshCheck, 'not found') === false;
            
            if (!$hasSsh) {
                return [
                    'success' => false,
                    'message' => 'SSH client is not installed on this server. Please contact support.',
                    'details' => ['error' => 'ssh_not_found'],
                ];
            }
            
            // If sshpass is not available, just verify port is open and return warning
            if (!$hasSshpass) {
                // Port is already checked earlier, so just return a warning
                return [
                    'success' => true,
                    'message' => 'Port ' . $port . ' is reachable. Note: Server-side sshpass is required for full migration. It will be auto-installed when import starts.',
                    'details' => [
                        'os' => 'Will be detected during import',
                        'disk_total' => 'Will be calculated',
                        'warning' => 'sshpass_not_installed',
                    ],
                ];
            }
            
            // Use a temporary password file so the password is not exposed in `ps`
            $passFile = tempnam(sys_get_temp_dir(), 'simport_');
            file_put_contents($passFile, $password);
            chmod($passFile, 0600);

            try {
                $testCmd = sprintf(
                    'sshpass -f %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d root@%s "echo CONNECTION_OK; uname -a; df -h / | tail -1" 2>&1',
                    escapeshellarg($passFile),
                    $port,
                    escapeshellarg($ip)
                );

                $output = shell_exec($testCmd);
            } finally {
                if (file_exists($passFile)) {
                    unlink($passFile);
                }
            }

            if (strpos($output, 'CONNECTION_OK') !== false) {
                $lines = explode("\n", trim($output));
                $osInfo = $lines[1] ?? 'Unknown';
                $diskInfo = $lines[2] ?? 'Unknown';
                
                // Parse disk info
                $diskParts = preg_split('/\s+/', $diskInfo);
                $totalDisk = $diskParts[1] ?? 'Unknown';
                $usedDisk = $diskParts[2] ?? 'Unknown';
                $availableDisk = $diskParts[3] ?? 'Unknown';

                return [
                    'success' => true,
                    'message' => 'SSH connection successful',
                    'details' => [
                        'os' => $osInfo,
                        'disk_total' => $totalDisk,
                        'disk_used' => $usedDisk,
                        'disk_available' => $availableDisk,
                    ],
                ];
            }

            // Check for common errors
            if (strpos($output, 'Permission denied') !== false) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed. Please check root password.',
                ];
            }

            if (strpos($output, 'Connection timed out') !== false) {
                return [
                    'success' => false,
                    'message' => 'Connection timed out. Please check IP and firewall settings.',
                ];
            }

            return [
                'success' => false,
                'message' => 'SSH connection failed: ' . substr($output, 0, 200),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SSH test error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get import statistics
     */
    public function getStats(): JsonResponse
    {
        $userId = Auth::id();
        
        $stats = [
            'total_imports' => ServerImport::forUser($userId)->count(),
            'completed' => ServerImport::forUser($userId)->where('status', 'completed')->count(),
            'failed' => ServerImport::forUser($userId)->where('status', 'failed')->count(),
            'active' => ServerImport::forUser($userId)->active()->count(),
            'total_data_transferred' => ServerImport::forUser($userId)
                ->where('status', 'completed')
                ->sum('transferred_bytes'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
