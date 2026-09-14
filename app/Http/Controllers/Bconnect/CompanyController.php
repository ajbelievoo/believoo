<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller {
    public function index(Request $r) {
        return view('bconnect.company', ['company' => Company::findOrFail($r->input('bconnect_company_id'))]);
    }

    public function update(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $canBrand = \App\Services\BconnectPlanService::canUseBranding($company->id);

        if (! $canBrand) {
            if ($r->filled('domain') && $r->input('domain') !== $company->domain) {
                return back()->with('error', 'Custom domain requires Pro/Enterprise plan.');
            }
            if ($r->filled('logo') && $r->input('logo') !== $company->logo) {
                return back()->with('error', 'Custom logo requires Pro/Enterprise plan.');
            }
            if ($r->has('branding')) {
                $oldColor = $company->branding['primary_color'] ?? '#06b6d4';
                $newColor = $r->input('branding.primary_color', $oldColor);
                if ($newColor !== $oldColor) {
                    return back()->with('error', 'Custom brand color requires Pro/Enterprise plan.');
                }
            }
        }

        $company->update($r->validate([
            'name' => 'required',
            'description' => 'nullable',
            'domain' => 'nullable|unique:bconnect_companies,domain,' . $company->id,
            'logo' => 'nullable|url',
        ]));
        if ($r->has('branding')) {
            $company->update(['branding' => $r->input('branding')]);
        }
        return back()->with('success', 'Company updated');
    }

    public function applyDomain(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        if (!$company->domain) return back()->with('error', 'No domain set');
        $exit = 0;
        $output = [];
        exec("cd /www/wwwroot/believoo && /usr/bin/php82 artisan bconnect:domain {$company->id} {$company->domain} 2>&1", $output, $exit);
        return back()->with($exit === 0 ? 'success' : 'error', implode("\n", $output));
    }

    public function setup() { return view('bconnect.company-setup'); }

    public function store(Request $r) {
        $r->validate(['company_name' => 'required']);
        $company = Company::create([
            'name' => $r->company_name,
            'slug' => uniqid('co-'),
            'plan' => 'free',
        ]);
        Member::updateOrCreate(
            ['user_id' => Auth::id(), 'company_id' => $company->id],
            ['role' => 'company_admin', 'is_active' => true]
        );
        session(['bconnect_company_id' => $company->id, 'bconnect_role' => 'company_admin']);
        return redirect()->route('bconnect.dashboard')->with('success', 'Company created');
    }
}
