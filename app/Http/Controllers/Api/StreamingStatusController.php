<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StreamingProject;
use Illuminate\Http\JsonResponse;

class StreamingStatusController extends Controller
{
    public function show(string $appId): JsonResponse
    {
        $project = StreamingProject::where('app_id', $appId)->first();

        if (!$project) {
            return response()->json(['status' => 'invalid_app_id'], 404);
        }

        // SECURITY: Never expose app_certificate or rest_api_key in response
        return response()->json([
            'status'  => $project->status,   // 'active' | 'suspended'
            'app_id'  => $project->app_id,
            'region'  => $project->region,
        ]);
    }
}
