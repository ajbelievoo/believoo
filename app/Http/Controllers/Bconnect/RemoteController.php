<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\RemoteSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RemoteController extends Controller {
    protected function ensureRemote($companyId) {
        if (!\App\Services\BconnectPlanService::canUseRemote($companyId)) {
            abort(403, 'Remote desktop is available on Pro/Enterprise plans.');
        }
    }

    public function index(Request $r) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        $sessions = RemoteSession::where('company_id', $r->input('bconnect_company_id'))->latest()->paginate(20);
        return view('bconnect.remote', compact('sessions'));
    }

    public function requestControl(Request $r) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        $r->validate(['target_id' => 'required|exists:bconnect_members,id']);
        $session = RemoteSession::create([
            'company_id' => $r->input('bconnect_company_id'),
            'requested_by' => $r->input('bconnect_member')->id,
            'target_id' => $r->target_id,
            'session_code' => strtoupper(Str::random(8)),
            'status' => 'pending',
            'permission' => $r->permission ?? 'view',
        ]);
        return redirect()->route('bconnect.remote')->with('success', 'Control request sent. Code: ' . $session->session_code);
    }

    public function respond(Request $r, RemoteSession $session) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        if ($session->target_id != $r->input('bconnect_member')->id) abort(403);
        $session->update(['status' => $r->status]);
        if ($r->status === 'active') {
            return redirect()->route('bconnect.remote.room', $session->id);
        }
        return back()->with('success', 'Response recorded');
    }

    public function room(Request $r, RemoteSession $session) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        if ($session->company_id != $r->input('bconnect_company_id')) abort(403);
        if ($session->status != 'active') return redirect()->route('bconnect.remote')->with('error', 'Session not active');
        return view('bconnect.remote-room', compact('session'));
    }
}
