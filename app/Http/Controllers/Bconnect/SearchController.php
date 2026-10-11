<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller {
    // Global search — powers the Ctrl+K palette.
    public function index(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $q = trim($r->input('q', ''));
        $out = ['tickets' => [], 'projects' => [], 'members' => [], 'meetings' => [], 'files' => []];
        if (strlen($q) < 2) return response()->json($out);
        $like = '%' . $q . '%';

        $out['tickets'] = DB::table('bconnect_tickets')->where('company_id', $companyId)
            ->where(fn($w) => $w->where('title', 'like', $like)->orWhere('id', $q))
            ->select('id', 'title', 'status')->latest()->limit(6)->get();
        $out['projects'] = DB::table('bconnect_projects')->where('company_id', $companyId)
            ->where('name', 'like', $like)->select('id', 'name', 'status')->limit(5)->get();
        $out['members'] = DB::table('bconnect_members')
            ->join('users', 'users.id', '=', 'bconnect_members.user_id')
            ->where('bconnect_members.company_id', $companyId)
            ->where(fn($w) => $w->where('users.name', 'like', $like)->orWhere('users.email', 'like', $like))
            ->select('bconnect_members.id', 'users.name', 'bconnect_members.role')->limit(5)->get();
        $out['meetings'] = DB::table('bconnect_meetings')->where('company_id', $companyId)
            ->where(fn($w) => $w->where('title', 'like', $like)->orWhere('room_id', 'like', $like))
            ->select('id', 'title', 'room_id')->latest()->limit(5)->get();
        $out['files'] = DB::table('bconnect_files')->where('company_id', $companyId)
            ->where('name', 'like', $like)->select('id', 'name', 'size')->latest()->limit(5)->get();

        return response()->json($out);
    }
}
