<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StreamingUsageLog;
use App\Models\StreamingApiKey;
use Illuminate\Http\Request;

class StreamAnalyticsController extends Controller
{
    public function index()
    {
        $streams = StreamingUsageLog::with('apiKey')
            ->latest('started_at')
            ->paginate(20);

        $activeStreams = StreamingUsageLog::whereNull('ended_at')->count();
        $totalViewers = StreamingUsageLog::whereNull('ended_at')->sum('viewer_count') ?? 0;
        $todayBandwidth = 0; // Column not available yet

        return view('admin.stream-analytics.index', compact(
            'streams', 'activeStreams', 'totalViewers', 'todayBandwidth'
        ));
    }

    public function show($id)
    {
        $stream = StreamingUsageLog::with('apiKey')->findOrFail($id);
        return view('admin.stream-analytics.show', compact('stream'));
    }
}
