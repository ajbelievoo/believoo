<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use App\Models\Bconnect\Meeting;
use App\Models\Bconnect\Invoice;
use Illuminate\Http\Request;

class DashboardController extends Controller {
    public function index(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $stats = [
            'projects' => Project::where('company_id', $companyId)->count(),
            'open_tickets' => Ticket::where('company_id', $companyId)->where('status', '!=', 'closed')->count(),
            'meetings' => Meeting::where('company_id', $companyId)->count(),
            'invoices' => Invoice::where('company_id', $companyId)->where('status', 'paid')->sum('amount'),
        ];
        $recentTickets = Ticket::where('company_id', $companyId)->latest()->limit(5)->get();
        return view('bconnect.dashboard', compact('stats', 'recentTickets'));
    }
}
