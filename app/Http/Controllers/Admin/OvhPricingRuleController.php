<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvhPricingRule;
use Illuminate\Http\Request;

class OvhPricingRuleController extends Controller
{
    public function index()
    {
        $rules = OvhPricingRule::orderBy('priority', 'desc')->paginate(25);
        return view('admin.ovh_pricing_rules.index', compact('rules'));
    }

    public function create()
    {
        return view('admin.ovh_pricing_rules.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string|max:255',
            'plan_code' => 'required|string|max:255',
            'margin_percent' => 'nullable|numeric',
            'fixed_markup' => 'nullable|numeric',
            'min_margin_percent' => 'nullable|numeric',
            'max_margin_percent' => 'nullable|numeric',
            'currency' => 'required|string|size:3',
            'round_to' => 'required|integer|min:0|max:4',
            'priority' => 'required|integer',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after_or_equal:active_from',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        OvhPricingRule::create($data);

        return redirect()->route('admin.ovh-pricing-rules.index')->with('success', 'Rule created.');
    }

    public function edit(OvhPricingRule $rule)
    {
        return view('admin.ovh_pricing_rules.form', compact('rule'));
    }

    public function update(Request $request, OvhPricingRule $rule)
    {
        $data = $request->validate([
            'category' => 'required|string|max:255',
            'plan_code' => 'required|string|max:255',
            'margin_percent' => 'nullable|numeric',
            'fixed_markup' => 'nullable|numeric',
            'min_margin_percent' => 'nullable|numeric',
            'max_margin_percent' => 'nullable|numeric',
            'currency' => 'required|string|size:3',
            'round_to' => 'required|integer|min:0|max:4',
            'priority' => 'required|integer',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after_or_equal:active_from',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $rule->update($data);

        return redirect()->route('admin.ovh-pricing-rules.index')->with('success', 'Rule updated.');
    }

    public function destroy(OvhPricingRule $rule)
    {
        $rule->delete();
        return redirect()->route('admin.ovh-pricing-rules.index')->with('success', 'Rule deleted.');
    }
}
