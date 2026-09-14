<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use Illuminate\Http\Request;

class BconnectAdminController extends Controller {
    public function index() {
        $companies = Company::withCount(['projects', 'members', 'tickets', 'invoices'])->latest()->paginate(25);
        $revenue = \App\Models\Bconnect\Invoice::where('status', 'paid')->sum('amount');
        return view('admin.bconnect.index', compact('companies', 'revenue'));
    }

    public function company(Company $company) {
        return view('admin.bconnect.company', compact('company'));
    }
}
