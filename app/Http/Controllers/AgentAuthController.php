<?php

namespace App\Http\Controllers;

use App\Models\SupportAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentAuthController extends Controller
{
    private function basePath(Request $request): string
    {
        return $request->getHost() === 'agent.believoo.com' ? '' : '/agent';
    }

    public function showLogin(Request $request)
    {
        if (session('support_agent_id')) {
            return redirect($this->basePath($request) . '/dashboard');
        }
        return view('agent.login', ['base' => $this->basePath($request)]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $agent = SupportAgent::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$agent || !$agent->password || !Hash::check($request->password, $agent->password)) {
            return back()->withErrors(['email' => 'Invalid credentials or account disabled.'])->withInput();
        }

        $agent->update(['is_online' => true, 'last_seen_at' => now()]);
        session(['support_agent_id' => $agent->id]);

        return redirect($this->basePath($request) . '/dashboard');
    }

    public function logout(Request $request)
    {
        if ($id = session('support_agent_id')) {
            SupportAgent::where('id', $id)->update(['is_online' => false]);
        }
        session()->forget('support_agent_id');
        return redirect($this->basePath($request) . '/login');
    }

    public function dashboard(Request $request)
    {
        $agent = SupportAgent::find(session('support_agent_id'));
        if (!$agent) {
            return redirect($this->basePath($request) . '/login');
        }
        return view('agent.dashboard', [
            'agent' => $agent,
            'base' => $this->basePath($request),
        ]);
    }

    public function keepAlive(Request $request)
    {
        $id = session('support_agent_id');
        $agent = $id ? SupportAgent::find($id) : null;

        if ($agent) {
            $agent->update(['last_seen_at' => now()]);
            return response()->json(['ok' => true, 'online' => true]);
        }

        return response()->json(['ok' => true, 'online' => false]);
    }
}
