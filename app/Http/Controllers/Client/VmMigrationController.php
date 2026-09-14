<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VmMigration;
use App\Models\ProxmoxVm;
use App\Models\ProxmoxNode;
use App\Services\ProxmoxApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

class VmMigrationController extends Controller
{
    protected ProxmoxApiService $proxmox;
    
    public function __construct(ProxmoxApiService $proxmox)
    {
        $this->proxmox = $proxmox;
    }
    
    /**
     * Get available cluster nodes for migration
     */
    public function getAvailableNodes(int $vmId)
    {
        try {
            // Verify user owns this VM
            $vm = ProxmoxVm::where('id', $vmId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            
            // Get Proxmox service for this VM's node
            $proxmoxNode = ProxmoxNode::where('name', $vm->node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $vm->node);
            } else {
                $proxmox = $this->proxmox;
            }
            
            // Fetch all cluster nodes
            $nodes = $proxmox->listClusterNodes();
            
            // Filter out current node and nodes not available for migration
            $availableNodes = array_filter($nodes, function($node) use ($vm) {
                return $node['name'] !== $vm->node && ($node['available_for_migration'] ?? false);
            });
            
            return response()->json([
                'success' => true,
                'current_node' => $vm->node,
                'vmid' => $vm->vmid,
                'nodes' => array_values($availableNodes),
                'migration_info' => [
                    'downtime' => 'Zero (Live Migration)',
                    'ip_address' => 'Same (No Change)',
                    'estimated_time' => '1-5 minutes',
                    'data_safety' => '100% Safe - No Data Loss',
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch cluster nodes', [
                'vm_id' => $vmId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available nodes: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Start VM migration to target node
     */
    public function startMigration(Request $request, int $vmId)
    {
        $validated = $request->validate([
            'target_node' => 'required|string',
        ]);
        
        try {
            // Verify user owns this VM
            $vm = ProxmoxVm::where('id', $vmId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            
            // Check if migration is already in progress
            $existingMigration = VmMigration::where('proxmox_vm_id', $vmId)
                ->whereIn('status', ['pending', 'migrating'])
                ->first();
                
            if ($existingMigration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Migration already in progress for this VM',
                    'migration_id' => $existingMigration->id,
                ], 409);
            }
            
            // Get Proxmox service for this VM's node
            $proxmoxNode = ProxmoxNode::where('name', $vm->node)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $vm->node);
            } else {
                $proxmox = $this->proxmox;
            }
            
            // Start the migration
            $targetNode = $validated['target_node'];
            $upid = $proxmox->migrateVm((int) $vm->vmid, $targetNode, true);
            
            if (!$upid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start migration. Please check if VM is running and target node is available.',
                ], 500);
            }
            
            // Create migration record
            $migration = VmMigration::create([
                'user_id' => Auth::id(),
                'proxmox_vm_id' => $vm->id,
                'source_node' => $vm->node,
                'target_node' => $targetNode,
                'vmid' => $vm->vmid,
                'status' => 'migrating',
                'progress_percent' => 0,
                'status_message' => 'Migration started...',
                'proxmox_upid' => $upid,
                'task_status' => 'running',
                'is_live_migration' => true,
                'migration_type' => 'online',
                'started_at' => now(),
            ]);
            
            // Broadcast migration started event
            broadcast(new \App\Events\VmMigrationStarted($migration))->toOthers();
            
            Log::info('VM migration started', [
                'migration_id' => $migration->id,
                'vm_id' => $vm->id,
                'vmid' => $vm->vmid,
                'source' => $vm->node,
                'target' => $targetNode,
                'upid' => $upid,
            ]);
            
            return response()->json([
                'success' => true,
                'migration_id' => $migration->id,
                'upid' => $upid,
                'message' => 'Migration started successfully!',
                'status' => 'migrating',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to start VM migration', [
                'vm_id' => $vmId,
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
     * Get migration progress
     */
    public function getProgress(int $migrationId)
    {
        try {
            $migration = VmMigration::where('id', $migrationId)
                ->where('user_id', Auth::id())
                ->with('proxmoxVm')
                ->firstOrFail();
            
            // If still migrating, fetch latest progress from Proxmox
            if ($migration->status === 'migrating' && $migration->proxmox_upid) {
                $proxmoxNode = ProxmoxNode::where('name', $migration->source_node)->first();
                
                if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                    $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                    $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $migration->source_node);
                } else {
                    $proxmox = $this->proxmox;
                }
                
                $progressData = $proxmox->getMigrationProgress($migration->source_node, $migration->proxmox_upid);
                
                if ($progressData['success']) {
                    // Update migration record
                    $migration->update([
                        'progress_percent' => $progressData['progress'],
                        'status' => $progressData['status'] === 'completed' ? 'completed' : ($progressData['status'] === 'failed' ? 'failed' : 'migrating'),
                        'status_message' => $progressData['message'],
                        'task_status' => $progressData['status'],
                        'completed_at' => $progressData['status'] === 'completed' ? now() : null,
                        'duration_seconds' => $progressData['duration'],
                    ]);
                    
                    // If completed, update VM node
                    if ($progressData['status'] === 'completed') {
                        $migration->proxmoxVm->update([
                            'node' => $migration->target_node,
                        ]);
                        
                        // Broadcast completion
                        broadcast(new \App\Events\VmMigrationCompleted($migration))->toOthers();
                    }
                    
                    // Broadcast progress update
                    if ($migration->status === 'migrating') {
                        broadcast(new \App\Events\VmMigrationProgress($migration))->toOthers();
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'migration' => [
                    'id' => $migration->id,
                    'status' => $migration->status,
                    'progress' => $migration->progress_percent,
                    'status_message' => $migration->status_message,
                    'source_node' => $migration->source_node,
                    'target_node' => $migration->target_node,
                    'started_at' => $migration->started_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $migration->completed_at?->format('Y-m-d H:i:s'),
                    'duration_seconds' => $migration->duration_seconds,
                    'is_live_migration' => $migration->is_live_migration,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get migration progress', [
                'migration_id' => $migrationId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get progress: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * List user's VM migrations
     */
    public function index()
    {
        try {
            $migrations = VmMigration::where('user_id', Auth::id())
                ->with('proxmoxVm')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
                
            return response()->json([
                'success' => true,
                'migrations' => $migrations,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch migrations: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get active migration for a VM
     */
    public function getActiveMigration(int $vmId)
    {
        try {
            $vm = ProxmoxVm::where('id', $vmId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            
            $activeMigration = VmMigration::where('proxmox_vm_id', $vmId)
                ->whereIn('status', ['pending', 'migrating'])
                ->first();
                
            if (!$activeMigration) {
                return response()->json([
                    'success' => true,
                    'has_active_migration' => false,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'has_active_migration' => true,
                'migration' => [
                    'id' => $activeMigration->id,
                    'status' => $activeMigration->status,
                    'progress' => $activeMigration->progress_percent,
                    'status_message' => $activeMigration->status_message,
                    'target_node' => $activeMigration->target_node,
                    'started_at' => $activeMigration->started_at?->format('Y-m-d H:i:s'),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check active migration: ' . $e->getMessage(),
            ], 500);
        }
    }
}
