<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ServerMigration;
use App\Models\ProxmoxVm;
use App\Services\ServerMigrationService;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MigrationController extends Controller
{
    protected ServerMigrationService $migrationService;
    protected EncryptionService $encryption;
    
    public function __construct(ServerMigrationService $migrationService, EncryptionService $encryption)
    {
        $this->migrationService = $migrationService;
        $this->encryption = $encryption;
    }
    
    /**
     * Show migration wizard
     */
    public function wizard()
    {
        $userVms = ProxmoxVm::where('user_id', Auth::id())
            ->where('status', 'running')
            ->get();
            
        return view('client.migration.wizard', compact('userVms'));
    }
    
    /**
     * Start new migration
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'source_ip' => 'required|ip',
            'source_ssh_port' => 'required|integer|min:1|max:65535',
            'source_password' => 'required|string|min:1',
            'source_root_user' => 'nullable|string|default:root',
            'proxmox_vm_id' => 'required|exists:proxmox_vms,id',
        ]);
        
        // Verify the selected VM belongs to the authenticated user
        $vm = ProxmoxVm::where('id', $validated['proxmox_vm_id'])
            ->where('user_id', Auth::id())
            ->first();
        
        if (!$vm) {
            return response()->json([
                'success' => false,
                'message' => 'Selected VM does not belong to you.',
            ], 403);
        }
        
        try {
            // Encrypt password before storage
            $encryptedPassword = $this->encryption->encrypt($validated['source_password']);
            
            $migration = ServerMigration::create([
                'user_id' => Auth::id(),
                'proxmox_vm_id' => $validated['proxmox_vm_id'],
                'source_ip' => $validated['source_ip'],
                'source_ssh_port' => $validated['source_ssh_port'] ?? 22,
                'source_password_encrypted' => $encryptedPassword,
                'source_root_user' => $validated['source_root_user'] ?? 'root',
                'status' => 'pending',
                'progress_percent' => 0,
                'status_message' => 'Migration queued...',
            ]);
            
            // Start migration in background
            $this->migrationService->startMigration($migration);
            
            return response()->json([
                'success' => true,
                'migration_id' => $migration->id,
                'message' => 'Migration started successfully',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Migration start failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start migration: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get migration status (for AJAX polling)
     */
    public function status(int $id)
    {
        $migration = ServerMigration::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        return response()->json(
            $this->migrationService->getStatus($migration)
        );
    }
    
    /**
     * API endpoint for migration script to update progress
     */
    public function updateProgress(Request $request, int $id)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
            'status' => 'required|string',
            'message' => 'required|string',
        ]);
        
        // Validate one-time migration token stored in a protected file
        $tokenFile = storage_path('app/migrations/migration_' . $id . '.token');
        $sentToken = (string) $request->header('X-Migration-Token');
        
        if (!$sentToken || !file_exists($tokenFile)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $expectedToken = (string) file_get_contents($tokenFile);
        
        if (!hash_equals($expectedToken, $sentToken)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $migration = ServerMigration::find($id);
        
        if (!$migration) {
            return response()->json(['error' => 'Migration not found'], 404);
        }
        
        $this->migrationService->updateProgress(
            $id,
            $validated['progress'],
            $validated['status'],
            $validated['message']
        );
        
        return response()->json(['success' => true]);
    }
    
    /**
     * List user's migrations
     */
    public function index()
    {
        $migrations = ServerMigration::where('user_id', Auth::id())
            ->with('proxmoxVm')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('client.migration.index', compact('migrations'));
    }
    
    /**
     * Show migration details
     */
    public function show(int $id)
    {
        $migration = ServerMigration::where('id', $id)
            ->where('user_id', Auth::id())
            ->with('proxmoxVm')
            ->firstOrFail();
            
        return view('client.migration.show', compact('migration'));
    }
}
