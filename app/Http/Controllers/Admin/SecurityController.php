<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use Illuminate\Http\Request;
class SecurityController extends Controller {
    public function index() { return view('admin.security.index', ['ips' => BlockedIp::latest()->paginate(25)]); }
    public function block(Request $r) {
        BlockedIp::updateOrCreate(['ip_address' => $r->ip_address], ['reason' => $r->reason]);
        return back()->with('success', 'IP blocked');
    }
    public function unblock(BlockedIp $ip) { $ip->delete(); return back()->with('success', 'IP unblocked'); }
}
