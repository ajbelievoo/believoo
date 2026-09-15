<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use App\Models\Bconnect\Meeting;
use App\Models\Bconnect\Invoice;
use App\Services\BconnectPlanService;
use App\Services\BconnectSubscriptionService;
use Illuminate\Http\Request;

class DashboardController extends Controller {
    public function index(Request $r) {
        if ($r->input('bconnect_role') === 'client') {
            return redirect()->route('bconnect.client.dashboard');
        }
        $companyId = $r->input('bconnect_company_id');
        $company = Company::findOrFail($companyId);

        $stats = [
            'projects' => Project::where('company_id', $companyId)->count(),
            'open_tickets' => Ticket::where('company_id', $companyId)->where('status', '!=', 'closed')->count(),
            'meetings' => Meeting::where('company_id', $companyId)->count(),
            'invoices' => Invoice::where('company_id', $companyId)->where('status', 'paid')->sum('amount'),
        ];

        $recentTickets = Ticket::where('company_id', $companyId)->with('project')->latest()->limit(5)->get();
        $recentMeetings = Meeting::where('company_id', $companyId)->with('creator.user')->where('ended_at', null)->orWhere('ended_at', '>=', now()->subDay())->latest()->limit(5)->get();
        $pendingInvoices = Invoice::where('company_id', $companyId)->where('status', 'pending')->latest()->limit(5)->get();
        $planLimits = [
            'members' => BconnectPlanService::check($companyId, 'members'),
            'member_usage' => \App\Models\Bconnect\Member::where('company_id', $companyId)->where('is_active', true)->count(),
        ];
        $status = BconnectSubscriptionService::status($company);
        $days = BconnectSubscriptionService::daysUntilExpiry($company);

        return view('bconnect.dashboard', compact('company', 'stats', 'recentTickets', 'recentMeetings', 'pendingInvoices', 'planLimits', 'status', 'days'));
    }
}
