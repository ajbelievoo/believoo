<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use Illuminate\Http\Request;

class SettingsController extends Controller {
    public function index(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        return view('bconnect.settings', compact('company'));
    }

    public function update(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $company->update($r->validate(['name' => 'required', 'plan' => 'required']));
        return back()->with('success', 'Settings saved');
    }
}
