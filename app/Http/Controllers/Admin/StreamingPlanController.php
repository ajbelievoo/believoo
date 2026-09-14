<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StreamingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StreamingPlanController extends Controller
{
    public function __construct()
    {
        // Middleware applied in routes file
    }

    /**
     * Display all streaming plans
     */
    public function index()
    {
        $plans = StreamingPlan::withCount(['apiKeys', 'subscriptions'])
            ->orderBy('sort_order')
            ->orderBy('delivery_method')
            ->orderBy('is_addon')
            ->get();

        return view('admin.streaming-plans.index', compact('plans'));
    }

    /**
     * Show form to create new streaming plan
     */
    public function create()
    {
        return view('admin.streaming-plans.create');
    }

    /**
     * Store new streaming plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'addon_price' => 'nullable|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,half_yearly,yearly',
            'max_viewers' => 'nullable|integer|min:1',
            'max_concurrent_viewers' => 'nullable|integer|min:1',
            'max_bitrate' => 'nullable|integer|min:100',
            'max_resolution' => 'nullable|integer|in:480,720,1080,1440,2160',
            'bandwidth_gb' => 'nullable|integer|min:1',
            'bandwidth_limit_gb' => 'nullable|integer|min:1',
            'storage_gb' => 'nullable|integer|min:1',
            'storage_limit_gb' => 'nullable|integer|min:1',
            'stream_count' => 'required|integer|min:1|max:50',
            'max_projects' => 'required|integer|min:1|max:100',
            'rtmp_support' => 'boolean',
            'webrtc_support' => 'boolean',
            'hls_support' => 'boolean',
            'dash_support' => 'boolean',
            'recording_enabled' => 'boolean',
            'transcoding_enabled' => 'boolean',
            'adaptive_bitrate' => 'boolean',
            'low_latency' => 'boolean',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_addon' => 'required|boolean',
            'delivery_method' => ['required', Rule::in(['vps_embedded', 'cloud_hosted'])],
            'sort_order' => 'nullable|integer|min:0',
            
            // AI Beauty Filter Settings
            'beauty_enabled' => 'nullable|boolean',
            'beauty_skin_smoothing' => 'nullable|numeric|min:0|max:1',
            'beauty_face_slimming' => 'nullable|numeric|min:0|max:1',
            'beauty_eye_enlargement' => 'nullable|numeric|min:0|max:1',
            'beauty_brightness' => 'nullable|numeric|min:-1|max:1',
            'beauty_preset' => 'nullable|string|in:NATURAL,GLAMOUR,PROFESSIONAL,LIVE',
        ]);

        // Handle unlimited values
        if ($request->has('unlimited_viewers')) {
            $validated['max_viewers'] = 999999;
            $validated['max_concurrent_viewers'] = 999999;
        }
        if ($request->has('unlimited_bandwidth')) {
            $validated['bandwidth_gb'] = 999999;
            $validated['bandwidth_limit_gb'] = 999999;
        }
        if ($request->has('unlimited_storage')) {
            $validated['storage_gb'] = 999999;
            $validated['storage_limit_gb'] = 999999;
        }

        // Set default addon_price for standalone plans
        if (!$validated['is_addon']) {
            $validated['addon_price'] = 0;
        }

        // Generate slug
        $validated['slug'] = Str::slug($validated['name']);

        $plan = StreamingPlan::create($validated);

        return redirect()
            ->route('admin.streaming-plans.index')
            ->with('success', 'Streaming plan created successfully!');
    }

    /**
     * Show streaming plan details
     */
    public function show(StreamingPlan $streamingPlan)
    {
        $streamingPlan->load(['apiKeys' => function($query) {
            $query->with('user')->latest();
        }, 'subscriptions' => function($query) {
            $query->with('user', 'hosting')->latest();
        }]);

        return view('admin.streaming-plans.show', compact('streamingPlan'));
    }

    /**
     * Show form to edit streaming plan
     */
    public function edit(StreamingPlan $streamingPlan)
    {
        return view('admin.streaming-plans.edit', compact('streamingPlan'));
    }

    /**
     * Update streaming plan
     */
    public function update(Request $request, StreamingPlan $streamingPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'addon_price' => 'nullable|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,half_yearly,yearly',
            'max_viewers' => 'nullable|integer|min:1',
            'max_concurrent_viewers' => 'nullable|integer|min:1',
            'max_bitrate' => 'nullable|integer|min:100',
            'max_resolution' => 'nullable|integer|in:480,720,1080,1440,2160',
            'bandwidth_gb' => 'nullable|integer|min:1',
            'bandwidth_limit_gb' => 'nullable|integer|min:1',
            'storage_gb' => 'nullable|integer|min:1',
            'storage_limit_gb' => 'nullable|integer|min:1',
            'stream_count' => 'required|integer|min:1|max:50',
            'max_projects' => 'required|integer|min:1|max:100',
            'rtmp_support' => 'boolean',
            'webrtc_support' => 'boolean',
            'hls_support' => 'boolean',
            'dash_support' => 'boolean',
            'recording_enabled' => 'boolean',
            'transcoding_enabled' => 'boolean',
            'adaptive_bitrate' => 'boolean',
            'low_latency' => 'boolean',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_addon' => 'required|boolean',
            'delivery_method' => ['required', Rule::in(['vps_embedded', 'cloud_hosted'])],
            'sort_order' => 'nullable|integer|min:0',
            
            // AI Beauty Filter Settings
            'beauty_enabled' => 'nullable|boolean',
            'beauty_skin_smoothing' => 'nullable|numeric|min:0|max:1',
            'beauty_face_slimming' => 'nullable|numeric|min:0|max:1',
            'beauty_eye_enlargement' => 'nullable|numeric|min:0|max:1',
            'beauty_brightness' => 'nullable|numeric|min:-1|max:1',
            'beauty_preset' => 'nullable|string|in:NATURAL,GLAMOUR,PROFESSIONAL,LIVE',
        ]);

        // Handle unlimited values
        if ($request->has('unlimited_viewers')) {
            $validated['max_viewers'] = 999999;
            $validated['max_concurrent_viewers'] = 999999;
        }
        if ($request->has('unlimited_bandwidth')) {
            $validated['bandwidth_gb'] = 999999;
            $validated['bandwidth_limit_gb'] = 999999;
        }
        if ($request->has('unlimited_storage')) {
            $validated['storage_gb'] = 999999;
            $validated['storage_limit_gb'] = 999999;
        }

        // Set default addon_price for standalone plans
        if (!$validated['is_addon']) {
            $validated['addon_price'] = 0;
        }

        $streamingPlan->update($validated);

        return redirect()
            ->route('admin.streaming-plans.index')
            ->with('success', 'Streaming plan updated successfully!');
    }

    /**
     * Delete streaming plan
     */
    public function destroy(StreamingPlan $streamingPlan)
    {
        // Check if plan has active subscriptions or API keys
        if ($streamingPlan->apiKeys()->active()->count() > 0) {
            return redirect()
                ->route('admin.streaming-plans.index')
                ->with('error', 'Cannot delete plan with active API keys!');
        }

        if ($streamingPlan->subscriptions()->active()->count() > 0) {
            return redirect()
                ->route('admin.streaming-plans.index')
                ->with('error', 'Cannot delete plan with active subscriptions!');
        }

        $streamingPlan->delete();

        return redirect()
            ->route('admin.streaming-plans.index')
            ->with('success', 'Streaming plan deleted successfully!');
    }

    /**
     * Toggle plan status
     */
    public function toggleStatus(StreamingPlan $streamingPlan)
    {
        $streamingPlan->update([
            'is_active' => !$streamingPlan->is_active
        ]);

        return redirect()
            ->route('admin.streaming-plans.index')
            ->with('success', 'Plan status updated successfully!');
    }

    /**
     * Duplicate a streaming plan
     */
    public function duplicate(StreamingPlan $streamingPlan)
    {
        $newPlan = $streamingPlan->replicate();
        $newPlan->name = $streamingPlan->name . ' (Copy)';
        $newPlan->slug = Str::slug($newPlan->name);
        $newPlan->is_active = false;
        $newPlan->save();

        return redirect()
            ->route('admin.streaming-plans.edit', $newPlan)
            ->with('success', 'Plan duplicated successfully!');
    }
}
