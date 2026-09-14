<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LicenseOrder;
use App\Models\VpsLicense;
use App\Services\LicenseCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseApiController extends Controller
{
    protected LicenseCheckoutService $licenseService;

    public function __construct(LicenseCheckoutService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * Get available license types
     * GET /api/v1/licenses/types
     */
    public function types(): JsonResponse
    {
        $types = $this->licenseService->getAvailableLicenses();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Get user's licenses
     * GET /api/v1/licenses
     */
    public function index(Request $request): JsonResponse
    {
        $licenses = VpsLicense::where('user_id', $request->user()->id)
            ->with('proxmoxVm')
            ->get()
            ->map(fn($license) => [
                'id' => $license->id,
                'type' => $license->type,
                'license_key' => $license->license_key ? decrypt($license->license_key) : null,
                'status' => $license->status,
                'server' => $license->proxmoxVm?->hostname,
                'server_ip' => $license->proxmoxVm?->ip_address,
                'activated_at' => $license->activated_at,
                'expires_at' => $license->expires_at,
                'days_remaining' => $license->expires_at ? $license->expires_at->diffInDays(now()) : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $licenses,
        ]);
    }

    /**
     * Create license order
     * POST /api/v1/licenses/order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_type' => 'required|string',
            'vm_id' => 'required|exists:proxmox_vms,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $vm = \App\Models\ProxmoxVm::where('id', $validated['vm_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $order = $this->licenseService->createOrder(
            $request->user(),
            $vm,
            $validated['license_type'],
            $validated['billing_cycle']
        );

        return response()->json([
            'success' => true,
            'message' => 'License order created',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $order->amount,
                'currency' => $order->currency,
                'payment_status' => $order->payment_status,
                'payment_url' => route('payment.license', $order), // Payment gateway URL
            ],
        ], 201);
    }

    /**
     * Get order status
     * GET /api/v1/licenses/orders/{orderNumber}
     */
    public function orderStatus(string $orderNumber): JsonResponse
    {
        $order = LicenseOrder::where('order_number', $orderNumber)
            ->with('license')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'license_type' => $order->license_type,
                'amount' => $order->amount,
                'payment_status' => $order->payment_status,
                'activation_status' => $order->activation_status,
                'paid_at' => $order->paid_at,
                'activated_at' => $order->activated_at,
                'license' => $order->license ? [
                    'id' => $order->license->id,
                    'key' => decrypt($order->license->license_key),
                    'expires_at' => $order->license->expires_at,
                ] : null,
            ],
        ]);
    }

    /**
     * Activate license (after payment callback)
     * POST /api/v1/licenses/{id}/activate
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $license = VpsLicense::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Trigger activation
        \App\Jobs\InstallLicenseJob::dispatch($license);

        $license->update(['status' => 'activating']);

        return response()->json([
            'success' => true,
            'message' => 'License activation initiated',
            'data' => [
                'license_id' => $license->id,
                'status' => 'activating',
            ],
        ]);
    }

    /**
     * Get license details
     * GET /api/v1/licenses/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $license = VpsLicense::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('proxmoxVm')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $license->id,
                'type' => $license->type,
                'license_key' => decrypt($license->license_key),
                'activation_code' => $license->activation_code,
                'status' => $license->status,
                'server' => [
                    'id' => $license->proxmoxVm?->id,
                    'hostname' => $license->proxmoxVm?->hostname,
                    'ip' => $license->proxmoxVm?->ip_address,
                ],
                'billing' => [
                    'price' => $license->selling_price,
                    'cycle' => $license->billing_cycle,
                ],
                'dates' => [
                    'activated_at' => $license->activated_at,
                    'expires_at' => $license->expires_at,
                    'days_remaining' => $license->expires_at ? max(0, $license->expires_at->diffInDays(now())) : null,
                ],
            ],
        ]);
    }
}
