<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ChurnPredictionService;
use Illuminate\Http\Request;

class ChurnRiskController extends Controller
{
    public function index(Request $request)
    {
        $service = new ChurnPredictionService();
        $tab = $request->input('tab', 'users');

        $allUserRisks = collect($service->topUserRisks(200));
        $allCompanyRisks = collect($service->topBconnectRisks(200));

        $userRisks = $tab === 'users' ? $allUserRisks->take(50) : collect([]);
        $companyRisks = $tab === 'bconnect' ? $allCompanyRisks->take(50) : collect([]);

        $counts = [
            'users_high' => $allUserRisks->where('risk', 'high')->count(),
            'users_medium' => $allUserRisks->where('risk', 'medium')->count(),
            'companies_high' => $allCompanyRisks->where('risk', 'high')->count(),
            'companies_medium' => $allCompanyRisks->where('risk', 'medium')->count(),
        ];

        return view('admin.churn-risk.index', compact('userRisks', 'companyRisks', 'tab', 'counts'));
    }
}
