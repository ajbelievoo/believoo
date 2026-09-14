<?php

namespace App\Http\Controllers;

use App\Models\UserHosting;
use App\Models\Service;
use App\Models\Order;
use App\Services\HostingProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpgradeController extends Controller
{
    /**
     * Show upgrade options for a hosting
     */
    public function showUpgradeOptions(UserHosting $hosting)
    {
        // Ensure user owns this hosting
        if ($hosting->user_id !== Auth::id()) {
            abort(403);
        }

        // Get available services for upgrade
        $currentService = $hosting->service;
        
        // Get hosting services excluding current one
        $availableServices = Service::where('is_active', true)
            ->where('id', '!=', $currentService?->id)
            ->where(function($query) {
                $query->where('category', 'like', '%hosting%')
                      ->orWhere('category', 'like', '%vps%')
                      ->orWhere('category', 'like', '%server%');
            })
            ->get();

        return view('upgrade.index', compact('hosting', 'currentService', 'availableServices'));
    }

    /**
     * Process upgrade to new plan
     */
    public function upgrade(Request $request, UserHosting $hosting)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'tier_name' => 'nullable|string',
            'billing_months' => 'required|integer|min:1',
        ]);

        // Ensure user owns this hosting
        if ($hosting->user_id !== Auth::id()) {
            abort(403);
        }

        $newService = Service::findOrFail($request->service_id);
        $user = Auth::user();

        // Calculate price for the new plan
        $price = $newService->getPriceForCycle($request->billing_months, $request->tier_name);

        // Create upgrade order
        $order = Order::create([
            'user_id' => $user->id,
            'service_id' => $newService->id,
            'service_name' => $newService->title,
            'tier_name' => $request->tier_name,
            'billing_months' => $request->billing_months,
            'amount' => $price,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => null,
            'notes' => 'Upgrade from ' . $hosting->plan_name . ' to ' . $newService->title,
        ]);

        // Store upgrade info in session
        session([
            'upgrade_hosting_id' => $hosting->id,
            'upgrade_order_id' => $order->id,
        ]);

        // Redirect to checkout with order
        return redirect()->route('checkout', [
            'service' => $newService->slug,
            'tier' => $request->tier_name,
            'order_id' => $order->id,
        ]);
    }

    /**
     * Process upgrade after payment (called from PaymentController)
     */
    public function processUpgrade(Order $order)
    {
        $hostingId = session('upgrade_hosting_id');
        
        if (!$hostingId) {
            return null;
        }

        $hosting = UserHosting::find($hostingId);
        
        if (!$hosting || $hosting->user_id !== $order->user_id) {
            return null;
        }

        $newService = $order->service;

        // Update hosting with new plan details
        $hosting->update([
            'service_id' => $newService->id,
            'plan_name' => $order->tier_name ?: $newService->title,
            'hosting_type' => $this->getHostingTypeFromService($newService),
            'price' => $order->amount,
            'billing_cycle' => $this->getBillingCycleFromMonths($order->billing_months),
            'expiry_date' => now()->addMonths($order->billing_months),
            'admin_notes' => ($hosting->admin_notes ?? '') . "\n\nUpgraded on " . now()->format('d M Y') . " from order #" . $order->order_number,
        ]);

        // Clear session
        session()->forget(['upgrade_hosting_id', 'upgrade_order_id']);

        return $hosting;
    }

    /**
     * Get hosting type from service category
     */
    private function getHostingTypeFromService(Service $service): string
    {
        $category = strtolower($service->category ?? '');
        
        return match($category) {
            'vps' => 'vps',
            'dedicated' => 'dedicated',
            'cloud' => 'cloud',
            'shared' => 'shared',
            default => 'shared',
        };
    }

    /**
     * Get billing cycle from months
     */
    private function getBillingCycleFromMonths(int $months): string
    {
        return match($months) {
            1 => 'monthly',
            3 => 'quarterly',
            6 => 'half_yearly',
            12 => 'yearly',
            default => 'monthly',
        };
    }
}
