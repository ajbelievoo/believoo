<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AgentController extends Controller {
    public function index() { return view('bconnect.agent'); }

    // GET /remote/app-login — hand the logged-in member's token to the
    // installed app via the bmydesk:// deep link ("website login → app").
    public function appLogin(Request $r) {
        $member = $r->input('bconnect_member');
        if (!$member) return redirect()->route('bconnect.remote.agent');
        if (!$member->api_token) {
            $member->api_token = \Illuminate\Support\Str::random(48);
            $member->save();
        }
        return redirect()->away('bmydesk://auth?token=' . $member->api_token . '&name=' . urlencode($member->user->name));
    }

    public function requestBeta(Request $r) {
        $r->validate(['os' => 'required|in:windows,macos', 'message' => 'nullable']);
        $companyId = $r->input('bconnect_company_id');
        $member = $r->input('bconnect_member');
        $body = "Bmydesk desktop agent beta request\n\nCompany ID: {$companyId}\nUser: {$member->user->name} ({$member->user->email})\nOS: {$r->os}\nMessage: " . ($r->message ?: 'N/A');

        try {
            Mail::raw($body, function ($m) {
                $m->to('support@believoo.com')->subject('Bmydesk Agent Beta Request');
            });
        } catch (\Throwable $e) {
            \Log::warning('Agent beta request email failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Beta access requested. Our team will contact you shortly.');
    }
}
