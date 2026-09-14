<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller {
    public function index(Request $r) {
        $logs = AuditLog::where('company_id', $r->input('bconnect_company_id'))->latest()->paginate(50);
        return view('bconnect.audit', compact('logs'));
    }
}
