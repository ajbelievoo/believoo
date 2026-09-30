<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Ticket;
use Illuminate\Http\Request;

class SupportController extends Controller {
    public function index(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $memberId = $r->input('bconnect_member')->id;
        $tickets = Ticket::with('project')
            ->where('company_id', $companyId)
            ->where('reporter_id', $memberId)
            ->where('type', 'support')
            ->latest()
            ->take(10)
            ->get();
        $projects = \App\Models\Bconnect\Project::where('company_id', $companyId)->get();
        $canCreate = \App\Services\BconnectPlanService::canCreateTicket($companyId);
        return view('bconnect.support', compact('tickets', 'projects', 'canCreate'));
    }
}
