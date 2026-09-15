<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Mail\BconnectMemberInvite;
use App\Models\User;
use App\Models\Bconnect\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MemberController extends Controller {
    protected $roles = ['super_admin', 'company_admin', 'manager', 'developer', 'client'];

    public function index(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $members = Member::with('user')->where('company_id', $companyId)->paginate(20);
        $roles = $this->roles;
        $canAdd = \App\Services\BconnectPlanService::canAddMember($companyId);
        $memberUsage = Member::where('company_id', $companyId)->where('is_active', true)->count();
        $memberLimit = \App\Services\BconnectPlanService::check($companyId, 'members');
        return view('bconnect.members', compact('members', 'roles', 'canAdd', 'memberUsage', 'memberLimit'));
    }

    public function store(Request $r) {
        if (!\App\Services\BconnectPlanService::canAddMember($r->input('bconnect_company_id'))) {
            return back()->with('error', 'Member limit reached for your plan. Upgrade to add more.');
        }
        $data = $r->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:' . implode(',', $this->roles),
            'permissions' => 'nullable'
        ]);
        $password = Str::random(12);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($password)]);
        Member::create([
            'user_id' => $user->id,
            'company_id' => $r->input('bconnect_company_id'),
            'role' => $data['role'],
            'permissions' => $data['permissions'] ? json_decode($data['permissions'], true) : null,
            'is_active' => true,
        ]);

        try {
            Mail::to($user->email)->send(new BconnectMemberInvite($user, $password));
            $msg = 'Member invited. Temporary password sent to ' . $user->email;
        } catch (\Throwable $e) {
            \Log::warning('B-Connect member invite email failed: ' . $e->getMessage());
            $msg = 'Member created but email failed. Password: ' . $password;
        }

        return back()->with('success', $msg);
    }

    public function update(Request $r, Member $member) {
        if ($member->company_id != $r->input('bconnect_company_id')) {
            abort(403);
        }
        $member->update($r->validate([
            'role' => 'required|in:' . implode(',', $this->roles),
            'is_active' => 'sometimes|boolean'
        ]));
        return back()->with('success', 'Member updated');
    }

    public function destroy(Request $r, Member $member) {
        if ($member->company_id != $r->input('bconnect_company_id')) {
            abort(403);
        }
        $member->delete();
        return back()->with('success', 'Member removed');
    }
}
