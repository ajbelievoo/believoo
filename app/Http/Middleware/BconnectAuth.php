<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Bconnect\Member;

class BconnectAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('bconnect.login');
        }

        $user = Auth::user();
        $companyId = session('bconnect_company_id');

        if (!$companyId) {
            $member = Member::where('user_id', $user->id)->where('is_active', true)->first();
            if ($member) {
                session(['bconnect_company_id' => $member->company_id, 'bconnect_role' => $member->role]);
                $companyId = $member->company_id;
            }
        }

        if (!$companyId) {
            return redirect()->route('bconnect.company.setup')->with('error', 'Please create or join a company first.');
        }

        $member = Member::where('user_id', $user->id)->where('company_id', $companyId)->where('is_active', true)->first();
        if (!$member) {
            return redirect()->route('bconnect.login')->with('error', 'You do not have access to this company.');
        }

        $request->merge([
            'bconnect_company_id' => $companyId,
            'bconnect_role' => $member->role,
            'bconnect_member' => $member,
        ]);

        view()->share('bconnectCompany', $member->company);
        view()->share('bconnectRole', $member->role);
        view()->share('bconnectMember', $member);

        return $next($request);
    }
}
