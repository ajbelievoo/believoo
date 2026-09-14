<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpPoolController extends Controller
{
    public function index()
    {
        $ips    = IpAddress::orderBy('status')->orderBy('ip_address')->get();
        $stats  = IpAddress::getPoolStats();
        $nodes  = \App\Models\ProxmoxNode::where('status', 'active')->pluck('name', 'name');

        return view('admin.ip-pool.index', compact('ips', 'stats', 'nodes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip|unique:ip_addresses,ip_address',
            'gateway'    => 'nullable|ip',
            'netmask'    => 'nullable|string',
            'node'       => 'nullable|string',
            'notes'      => 'nullable|string',
        ]);

        IpAddress::create($request->only(['ip_address', 'gateway', 'netmask', 'node', 'notes']));

        return back()->with('success', 'IP ' . $request->ip_address . ' added to pool.');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'ips'     => 'required|string',
            'gateway' => 'nullable|ip',
            'node'    => 'nullable|string',
        ]);

        $lines   = preg_split('/[\r\n,]+/', trim($request->ips));
        $added   = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $ip = trim($line);
            if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
                $skipped++;
                continue;
            }
            if (IpAddress::where('ip_address', $ip)->exists()) {
                $skipped++;
                continue;
            }
            IpAddress::create([
                'ip_address' => $ip,
                'gateway'    => $request->gateway,
                'node'       => $request->node,
            ]);
            $added++;
        }

        return back()->with('success', "Added {$added} IPs. Skipped {$skipped} (duplicates/invalid).");
    }

    public function destroy(IpAddress $ip)
    {
        if ($ip->status === 'assigned') {
            return back()->with('error', 'Cannot delete assigned IP. Release it first.');
        }
        $ip->delete();
        return back()->with('success', 'IP removed from pool.');
    }

    public function release(IpAddress $ip)
    {
        $ip->release();
        return back()->with('success', 'IP ' . $ip->ip_address . ' released back to pool.');
    }
}
