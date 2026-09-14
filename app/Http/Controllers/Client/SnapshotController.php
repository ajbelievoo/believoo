<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ProxmoxVm;
use App\Models\VpsSnapshot;
use App\Services\ProxmoxApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SnapshotController extends Controller
{
    /**
     * Show snapshot management page
     */
    public function index()
    {
        $snapshots = VpsSnapshot::where('user_id', Auth::id())
            ->with('vm')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        $vms = ProxmoxVm::where('user_id', Auth::id())
            ->where('status', 'running')
            ->get();
            
        return view('client.snapshots.index', compact('snapshots', 'vms'));
    }
    
    /**
     * Create 1-click snapshot
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'vm_id' => 'required|exists:proxmox_vms,id',
            'name' => 'nullable|string|max:100',
        ]);
        
        $vm = ProxmoxVm::where('id', $validated['vm_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        try {
            $proxmox = new ProxmoxApiService();
            
            // Generate snapshot name
            $snapshotName = $validated['name'] ?? 'snap-' . now()->format('Y-m-d-H-i-s');
            
            // Create snapshot via Proxmox API
            $result = $proxmox->createSnapshot($vm->vmid, $vm->node, $snapshotName);
            
            if ($result['success'] ?? false) {
                // Save to database
                $snapshot = VpsSnapshot::create([
                    'user_id' => Auth::id(),
                    'proxmox_vm_id' => $vm->id,
                    'name' => $snapshotName,
                    'proxmox_snapshot_id' => $result['data'] ?? $snapshotName,
                    'type' => 'manual',
                    'status' => 'completed',
                    'size_bytes' => 0, // Will be updated later
                    'description' => 'Manual snapshot created via 1-click',
                ]);
                
                Log::info('Snapshot created successfully', [
                    'user_id' => Auth::id(),
                    'vm_id' => $vm->id,
                    'snapshot_id' => $snapshot->id,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Snapshot created successfully!',
                    'snapshot' => $snapshot,
                ]);
            }
            
            throw new \Exception($result['message'] ?? 'Failed to create snapshot');
            
        } catch (\Exception $e) {
            Log::error('Snapshot creation failed', [
                'user_id' => Auth::id(),
                'vm_id' => $vm->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create snapshot: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Quick snapshot from dashboard (1-click)
     */
    public function quickSnapshot(Request $request)
    {
        $validated = $request->validate([
            'vm_id' => 'required|exists:proxmox_vms,id',
        ]);
        
        $vm = ProxmoxVm::where('id', $validated['vm_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        // Auto-generate name
        $snapshotName = 'quick-snap-' . now()->format('Y-m-d-H-i');
        
        $request->merge(['name' => $snapshotName]);
        return $this->create($request);
    }
    
    /**
     * Restore snapshot
     */
    public function restore($id)
    {
        $snapshot = VpsSnapshot::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        try {
            $proxmox = new ProxmoxApiService();
            $vm = $snapshot->vm;
            
            $result = $proxmox->restoreSnapshot($vm->vmid, $vm->node, $snapshot->proxmox_snapshot_id);
            
            if ($result['success'] ?? false) {
                $snapshot->update(['status' => 'restored']);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Snapshot restored successfully! VM will restart.',
                ]);
            }
            
            throw new \Exception($result['message'] ?? 'Failed to restore snapshot');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete snapshot
     */
    public function delete($id)
    {
        $snapshot = VpsSnapshot::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        try {
            $proxmox = new ProxmoxApiService();
            $vm = $snapshot->vm;
            
            $result = $proxmox->deleteSnapshot($vm->vmid, $vm->node, $snapshot->proxmox_snapshot_id);
            
            if ($result['success'] ?? false) {
                $snapshot->update(['status' => 'deleted']);
                $snapshot->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Snapshot deleted successfully!',
                ]);
            }
            
            throw new \Exception($result['message'] ?? 'Failed to delete snapshot');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete: ' . $e->getMessage(),
            ], 500);
        }
    }
}
