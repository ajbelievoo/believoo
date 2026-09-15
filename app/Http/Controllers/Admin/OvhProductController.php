<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvhProduct;
use Illuminate\Http\Request;

class OvhProductController extends Controller
{
    public function index(Request $request)
    {
        $query = OvhProduct::query();

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('plan_code', 'like', "%{$search}%")
                  ->orWhere('invoice_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('category')->orderBy('sort_order')->orderBy('id')->paginate(50);
        $categories = OvhProduct::select('category')->distinct()->pluck('category');

        return view('admin.ovh-products.index', compact('products', 'categories'));
    }

    public function show(OvhProduct $ovhProduct)
    {
        return view('admin.ovh-products.show', compact('ovhProduct'));
    }

    public function toggleActive(OvhProduct $ovhProduct)
    {
        $ovhProduct->update(['is_active' => !$ovhProduct->is_active]);

        return back()->with('success', 'Product status updated.');
    }
}
