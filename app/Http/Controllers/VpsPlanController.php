<?php

namespace App\Http\Controllers;

use App\Models\VpsPlan;
use Illuminate\Http\Request;

class VpsPlanController extends Controller
{
    /**
     * Show all VPS categories (like OVH homepage)
     */
    public function index()
    {
        $categories = VpsPlan::getCategoriesWithCounts();
        $plansByCategory = VpsPlan::getPlansByCategory();
        
        return view('vps-plans.index', compact('categories', 'plansByCategory'));
    }

    /**
     * Show plans by category (like OVH VPS 2026 page)
     */
    public function category(string $category)
    {
        $categoryInfo = VpsPlan::CATEGORIES[$category] ?? null;
        
        if (!$categoryInfo) {
            abort(404, 'Category not found');
        }
        
        $plans = VpsPlan::byCategory($category)
            ->active()
            ->ordered()
            ->get();
        
        $allCategories = VpsPlan::getCategoriesWithCounts();
        
        return view('vps-plans.category', compact('plans', 'category', 'categoryInfo', 'allCategories'));
    }

    /**
     * Show single plan details
     */
    public function show(VpsPlan $vpsPlan)
    {
        if (!$vpsPlan->is_active) {
            abort(404);
        }
        
        $relatedPlans = VpsPlan::byCategory($vpsPlan->category)
            ->active()
            ->where('id', '!=', $vpsPlan->id)
            ->ordered()
            ->take(3)
            ->get();
        
        return view('vps-plans.show', compact('vpsPlan', 'relatedPlans'));
    }

    /**
     * Configure/Order plan - redirect to checkout with plan details
     */
    public function configure(VpsPlan $vpsPlan)
    {
        if (!$vpsPlan->isAvailable()) {
            return back()->with('error', 'This plan is currently not available');
        }
        
        // Check if user is logged in
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('info', 'Please login to order your VPS')
                ->with('redirect_after_login', route('vps-plans.configure', $vpsPlan->slug));
        }

        // Find or create the canonical VPS service used for checkout.
        $vpsService = \App\Models\Service::firstOrCreate(
            ['slug' => 'vps'],
            [
                'title' => 'VPS Hosting',
                'category' => 'vps',
                'description' => 'Virtual Private Servers powered by OVHcloud',
                'price' => 0,
                'is_active' => true,
                'billing_cycles' => \App\Models\Service::getDefaultBillingCycles(),
            ]
        );

        // Store plan details in session for checkout to pick up
        session([
            'vps_plan_id'    => $vpsPlan->id,
            'vps_plan_name'  => $vpsPlan->name,
            'vps_plan_price' => $vpsPlan->price_monthly,
            'vps_plan_specs' => [
                'cpu_cores' => $vpsPlan->cpu_cores,
                'memory_gb' => $vpsPlan->memory_gb,
                'disk_gb'   => $vpsPlan->disk_gb,
                'disk_type' => $vpsPlan->disk_type,
            ],
        ]);

        return redirect()->route('checkout', [
            'service' => $vpsService->slug,
            'tier'    => $vpsPlan->name,
        ]);
    }

    /**
     * Store selected OS in session so checkout can pass it to provisioning.
     */
    public function setOs(VpsPlan $vpsPlan, Request $request)
    {
        $os = $request->input('os', 'Ubuntu 22.04');
        session(['vps_selected_os' => $os]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'os' => $os]);
        }

        return back()->with('info', 'OS selected: ' . $os);
    }
}
