<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\RemoteSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RemoteAdminController extends Controller {
    // Fleet dashboard — all registered agents with live state
    public function fleet(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $devices = DB::table('bconnect_agent_devices')
            ->where(function ($q) use ($companyId) { $q->where('company_id', $companyId)->orWhereNull('company_id'); })
            ->latest('last_seen_at')->get()->map(function ($d) {
                $d->online = $d->last_seen_at && \Illuminate\Support\Carbon::parse($d->last_seen_at)->gt(now()->subMinutes(3));
                $d->session = DB::table('bconnect_remote_sessions')->where('device_id', $d->device_id)->where('host_kind', 'agent')->latest('id')->first(['session_code', 'status']);
                return $d;
            });
        $versions = $devices->groupBy('version')->map->count();
        return view('bconnect.admin.fleet', compact('devices', 'versions'));
    }

    // Live session monitor
    public function monitor(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $live = RemoteSession::whereIn('status', ['connecting', 'active', 'waiting'])
            ->where(fn($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->latest()->get();
        return view('bconnect.admin.monitor', compact('live'));
    }

    // Force-end any session
    public function forceEnd(Request $r, $id) {
        $companyId = $r->input('bconnect_company_id');
        $s = RemoteSession::where('id', $id)->where(fn($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))->firstOrFail();
        $s->update(['status' => 'ended', 'ended_at' => now()]);
        // queue an 'end' signal so live pollers tear down instantly
        $key = 'rsig.' . $s->session_code;
        foreach (['h', 'v'] as $role) {
            $q = \Illuminate\Support\Facades\Cache::get("{$key}.{$role}", []);
            $q[] = ['t' => 'end', 'n' => 'admin-' . uniqid(), 'at' => now()->toIso8601String()];
            \Illuminate\Support\Facades\Cache::put("{$key}.{$role}", $q, 600);
        }
        return back()->with('success', 'Session ' . $s->session_code . ' ended');
    }

    // Per-company remote policies (file transfer, unattended, etc.)
    public function policies(Request $r) {
        $company = \App\Models\Bconnect\Company::findOrFail($r->input('bconnect_company_id'));
        return view('bconnect.admin.policies', compact('company'));
    }

    public function savePolicies(Request $r) {
        $company = \App\Models\Bconnect\Company::findOrFail($r->input('bconnect_company_id'));
        $company->remote_policies = [
            'file_transfer' => $r->boolean('file_transfer'),
            'clipboard' => $r->boolean('clipboard'),
            'unattended' => $r->boolean('unattended'),
            'browser_host' => $r->boolean('browser_host'),
            'session_recording' => $r->boolean('session_recording'),
        ];
        $company->save();
        return back()->with('success', 'Remote policies saved');
    }

    // Server health — queue driver, ws reachability, disk, DB
    public function health(Request $r) {
        $h = [];
        try { DB::selectOne('SELECT 1'); $h['db'] = ['ok' => true, 'detail' => DB::connection()->getDatabaseName()]; }
        catch (\Throwable $e) { $h['db'] = ['ok' => false, 'detail' => $e->getMessage()]; }

        try { \Illuminate\Support\Facades\Cache::put('hc', 1, 5); $h['cache'] = ['ok' => \Illuminate\Support\Facades\Cache::get('hc') === 1, 'detail' => config('cache.default')]; }
        catch (\Throwable $e) { $h['cache'] = ['ok' => false, 'detail' => $e->getMessage()]; }

        $free = @disk_free_space(base_path());
        $total = @disk_total_space(base_path());
        $h['disk'] = ['ok' => $free && $total && $free / $total > 0.05, 'detail' => sprintf('%s free of %s', $free ? round($free / 1e9, 1) . 'GB' : '?', $total ? round($total / 1e9, 1) . 'GB' : '?')];

        $h['ws'] = ['ok' => !empty(env('REVERB_APP_KEY')), 'detail' => 'Reverb ' . (env('REVERB_APP_KEY') ? 'configured' : 'NOT configured')];
        $h['queue'] = ['ok' => true, 'detail' => config('queue.default') . ' driver'];
        $h['agents'] = ['ok' => true, 'detail' => DB::table('bconnect_agent_devices')->count() . ' devices registered'];

        return view('bconnect.admin.health', ['h' => $h]);
    }

    // Audit log → CSV
    public function auditExport(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $rows = DB::table('bconnect_audit_logs')->where('company_id', $companyId)->latest()->limit(10000)->get();
        $csv = fopen('php://temp', 'w');
        fputcsv($csv, ['id', 'member_id', 'action', 'details', 'ip', 'created_at']);
        foreach ($rows as $row) {
            fputcsv($csv, [$row->id, $row->member_id ?? '', $row->action ?? '', substr($row->details ?? '', 0, 500), $row->ip_address ?? '', $row->created_at]);
        }
        rewind($csv);
        return response(stream_get_contents($csv), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-' . now()->format('Ymd') . '.csv"',
        ]);
    }
}
