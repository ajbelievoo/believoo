<?php
namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use App\Services\BconnectSubscriptionService;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    public function index(Request $r)
    {
        $companyId = $r->input('bconnect_company_id');
        $member = $r->input('bconnect_member');
        $company = Company::findOrFail($companyId);

        $projectIds = Project::where('company_id', $companyId)->where('client_id', $member->id)->pluck('id');

        $projects = Project::whereIn('id', $projectIds)->with('client.user')->get();
        $tickets = Ticket::whereIn('project_id', $projectIds)->with('project', 'reporter.user')->latest()->limit(20)->get();
        $invoices = Invoice::where('company_id', $companyId)->where('client_id', $member->id)->latest()->paginate(20);

        $status = BconnectSubscriptionService::status($company);
        $days = BconnectSubscriptionService::daysUntilExpiry($company);

        return view('bconnect.client.dashboard', compact('company', 'member', 'projects', 'tickets', 'invoices', 'status', 'days'));
    }
}
