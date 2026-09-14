<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BconnectAdminController extends Controller
{
    public function index()
    {
        $companies = Company::withCount(['projects', 'members', 'tickets', 'invoices'])->latest()->paginate(25);
        $revenue = Invoice::where('status', 'paid')->sum('amount');
        return view('admin.bconnect.index', compact('companies', 'revenue'));
    }

    public function company(Company $company)
    {
        $company->loadCount(['projects', 'members', 'tickets', 'invoices']);
        $invoices = $company->invoices()->latest()->limit(20)->get();
        return view('admin.bconnect.company', compact('company', 'invoices'));
    }

    public function updatePlan(Request $request, Company $company)
    {
        $data = $request->validate([
            'plan' => 'required|in:free,pro,enterprise',
            'plan_expires_at' => 'nullable|date',
        ]);

        $company->update([
            'plan' => $data['plan'],
            'plan_expires_at' => $data['plan_expires_at'] ?? null,
        ]);

        return back()->with('success', 'Plan updated to ' . ucfirst($data['plan']));
    }

    public function toggleActive(Company $company)
    {
        $company->update(['is_active' => !$company->is_active]);
        return back()->with('success', 'Company ' . ($company->is_active ? 'activated' : 'suspended'));
    }

    public function applyDomain(Company $company)
    {
        if (!$company->domain) {
            return back()->with('error', 'No custom domain set for this company.');
        }

        try {
            $exitCode = Artisan::call('bconnect:domain', [
                'company_id' => $company->id,
                'domain' => $company->domain,
            ]);

            $output = Artisan::output();

            if ($exitCode !== 0) {
                return back()->with('error', $output);
            }

            return back()->with('success', 'Domain vhost applied. Output: ' . $output);
        } catch (\Exception $e) {
            return back()->with('error', 'Domain setup failed: ' . $e->getMessage());
        }
    }

    public function notifyAdmins(Request $request, Company $company)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        $admins = Member::where('company_id', $company->id)
            ->whereIn('role', ['company_admin', 'super_admin'])
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'company_id' => $company->id,
                'member_id' => $admin->id,
                'type' => 'admin',
                'title' => $data['title'],
                'message' => $data['message'],
                'url' => route('bconnect.dashboard'),
            ]);
        }

        return back()->with('success', 'Notification sent to ' . $admins->count() . ' admin(s).');
    }
}
