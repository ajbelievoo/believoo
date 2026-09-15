<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\User;
use App\Services\ChurnPredictionService;
use Illuminate\Http\Request;

class ChurnRiskController extends Controller
{
    public function users(Request $request)
    {
        $limit = min((int) $request->input('limit', 50), 200);
        $service = new ChurnPredictionService();

        $risks = collect($service->topUserRisks($limit))
            ->where('risk', '!=', 'low')
            ->values();

        return response()->json(['success' => true, 'data' => $risks]);
    }

    public function companies(Request $request)
    {
        $limit = min((int) $request->input('limit', 50), 200);
        $service = new ChurnPredictionService();

        $risks = collect($service->topBconnectRisks($limit))
            ->where('risk', '!=', 'low')
            ->values();

        return response()->json(['success' => true, 'data' => $risks]);
    }

    public function showUser(Request $request, User $user)
    {
        $service = new ChurnPredictionService();
        return response()->json([
            'success' => true,
            'data' => $service->scoreUser($user),
        ]);
    }

    public function showCompany(Request $request, Company $company)
    {
        $service = new ChurnPredictionService();
        return response()->json([
            'success' => true,
            'data' => $service->scoreBconnectCompany($company),
        ]);
    }
}
