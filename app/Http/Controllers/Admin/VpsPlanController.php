<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpsPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VpsPlanController extends Controller
{
    /**
     * List all VPS plans
     */
    public function index()
    {
        $plans = VpsPlan::orderBy('category')->orderBy('sort_order')->get();
        $categories = VpsPlan::getCategoriesWithCounts();
        
        return view('admin.vps-plans.index', compact('plans', 'categories'));
    }

    /**
     * Show plans by category (public view like OVH)
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
     * Show create form
     */
    public function create()
    {
        $categories = VpsPlan::CATEGORIES;
        return view('admin.vps-plans.create', compact('categories'));
    }

    /**
     * Store new plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:vps_plans',
            'category' => 'required|string|in:' . implode(',', array_keys(VpsPlan::CATEGORIES)),
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cpu_cores' => 'required|integer|min:1',
            'memory_gb' => 'required|integer|min:1',
            'disk_gb' => 'required|integer|min:10',
            'disk_type' => 'required|string|in:SSD,NVMe',
            'bandwidth' => 'required|string|max:50',
            'price_monthly' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
            'unlimited_traffic' => 'nullable|boolean',
            'daily_backup' => 'nullable|boolean',
            'installation_free' => 'nullable|boolean',
            'is_active' => 'boolean',
            'is_sold_out' => 'boolean',
            'is_recommended' => 'boolean',
            'sort_order' => 'integer|min:0',
            'streaming_addon_price' => 'nullable|numeric|min:0',
            'ovh_plan_code' => 'nullable|string|max:100',
            'ovh_region' => 'nullable|string|max:50',
        ]);

        $validated['unlimited_traffic'] = $request->boolean('unlimited_traffic');
        $validated['daily_backup'] = $request->boolean('daily_backup');
        $validated['installation_free'] = $request->boolean('installation_free');

        try {
            // Generate features array from specs
            $features = [
                $validated['cpu_cores'] . ' vCores',
                $validated['memory_gb'] . ' GB RAM',
                $validated['disk_gb'] . ' GB ' . $validated['disk_type'],
                $validated['daily_backup'] ? 'Daily backup' : 'No daily backup',
                $validated['unlimited_traffic'] ? 'Unlimited traffic' : $validated['bandwidth'],
                $validated['installation_free'] ? 'Free installation' : 'Paid installation',
            ];

            $ovhConfig = $validated['ovh_plan_code'] ? array_merge($validated['ovh_config'] ?? [], [
                'plan_code' => $validated['ovh_plan_code'],
                'region'    => $validated['ovh_region'] ?? null,
            ]) : ($validated['ovh_config'] ?? []);

            $plan = VpsPlan::create([
                ...$validated,
                'features' => $features,
                'ovh_config' => $ovhConfig,
            ]);

            Log::info('VPS Plan created', [
                'plan_id' => $plan->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.vps-plans.index')
                ->with('success', "VPS Plan '{$plan->name}' created successfully");

        } catch (\Exception $e) {
            Log::error('Failed to create VPS plan', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create plan: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(VpsPlan $vpsPlan)
    {
        $categories = VpsPlan::CATEGORIES;
        return view('admin.vps-plans.edit', compact('vpsPlan', 'categories'));
    }

    /**
     * Update plan
     */
    public function update(Request $request, VpsPlan $vpsPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'category' => 'required|string|in:' . implode(',', array_keys(VpsPlan::CATEGORIES)),
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cpu_cores' => 'required|integer|min:1',
            'memory_gb' => 'required|integer|min:1',
            'disk_gb' => 'required|integer|min:10',
            'disk_type' => 'required|string|in:SSD,NVMe',
            'bandwidth' => 'required|string|max:50',
            'price_monthly' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
            'unlimited_traffic' => 'nullable|boolean',
            'daily_backup' => 'nullable|boolean',
            'installation_free' => 'nullable|boolean',
            'is_active' => 'boolean',
            'is_sold_out' => 'boolean',
            'is_recommended' => 'boolean',
            'sort_order' => 'integer|min:0',
            'streaming_addon_price' => 'nullable|numeric|min:0',
            'ovh_plan_code' => 'nullable|string|max:100',
            'ovh_region' => 'nullable|string|max:50',
        ]);

        $validated['unlimited_traffic'] = $request->boolean('unlimited_traffic');
        $validated['daily_backup'] = $request->boolean('daily_backup');
        $validated['installation_free'] = $request->boolean('installation_free');

        try {
            // Regenerate features if specs changed
            $features = [
                $validated['cpu_cores'] . ' vCores',
                $validated['memory_gb'] . ' GB RAM',
                $validated['disk_gb'] . ' GB ' . $validated['disk_type'],
                $validated['daily_backup'] ? 'Daily backup' : 'No daily backup',
                $validated['unlimited_traffic'] ? 'Unlimited traffic' : $validated['bandwidth'],
                $validated['installation_free'] ? 'Free installation' : 'Paid installation',
            ];

            $ovhConfig = $validated['ovh_plan_code'] ? array_merge($vpsPlan->ovh_config ?? [], [
                'plan_code' => $validated['ovh_plan_code'],
                'region'    => $validated['ovh_region'] ?? null,
            ]) : ($vpsPlan->ovh_config ?? []);

            $vpsPlan->update([
                ...$validated,
                'features' => $features,
                'ovh_config' => $ovhConfig,
            ]);

            // If this is recommended, remove recommended from others in same category
            if ($validated['is_recommended'] ?? false) {
                VpsPlan::where('category', $validated['category'])
                    ->where('id', '!=', $vpsPlan->id)
                    ->update(['is_recommended' => false]);
            }

            Log::info('VPS Plan updated', [
                'plan_id' => $vpsPlan->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.vps-plans.index')
                ->with('success', "VPS Plan '{$vpsPlan->name}' updated successfully");

        } catch (\Exception $e) {
            Log::error('Failed to update VPS plan', [
                'plan_id' => $vpsPlan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update plan: ' . $e->getMessage());
        }
    }

    /**
     * Delete plan
     */
    public function destroy(VpsPlan $vpsPlan)
    {
        try {
            $name = $vpsPlan->name;
            $vpsPlan->delete();

            Log::info('VPS Plan deleted', [
                'plan_id' => $vpsPlan->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.vps-plans.index')
                ->with('success', "VPS Plan '{$name}' deleted successfully");

        } catch (\Exception $e) {
            Log::error('Failed to delete VPS plan', [
                'plan_id' => $vpsPlan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete plan: ' . $e->getMessage());
        }
    }

    /**
     * Toggle sold out status
     */
    public function toggleSoldOut(VpsPlan $vpsPlan)
    {
        $vpsPlan->update(['is_sold_out' => !$vpsPlan->is_sold_out]);

        $status = $vpsPlan->is_sold_out ? 'marked as SOLD OUT' : 'marked as AVAILABLE';

        return back()->with('success', "{$vpsPlan->name} has been {$status}");
    }

    /**
     * Bulk update prices (2.5x calculation)
     */
    public function bulkPriceUpdate(Request $request)
    {
        $validated = $request->validate([
            'multiplier' => 'required|numeric|min:1|max:10',
            'category' => 'nullable|string',
        ]);

        $multiplier = $validated['multiplier'];

        try {
            $query = VpsPlan::query();
            
            if (!empty($validated['category'])) {
                $query->byCategory($validated['category']);
            }

            $plans = $query->get();
            $updated = 0;

            foreach ($plans as $plan) {
                if ($plan->cost_price) {
                    $newPrice = $plan->cost_price * $multiplier;
                    $plan->update(['price_monthly' => $newPrice]);
                    $updated++;
                }
            }

            return back()->with('success', "Prices updated for {$updated} plans using {$multiplier}x multiplier");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update prices: ' . $e->getMessage());
        }
    }
}
