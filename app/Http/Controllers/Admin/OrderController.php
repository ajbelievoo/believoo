<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')
            ->latest()
            ->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'service']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,paid,failed,cancelled,refunded',
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $order->status;

        // If marking as paid, use markAsPaid to trigger auto-provisioning
        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            $order->markAsPaid(
                gateway: $request->input('payment_gateway', 'manual'),
                paymentId: $request->input('payment_id', 'manual-' . uniqid()),
                transactionId: $request->input('transaction_id'),
                response: ['manual' => true, 'admin_id' => auth()->id()]
            );

            Log::info('Order marked as paid by admin, hosting auto-provisioned', [
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'Order marked as PAID. Hosting is being provisioned automatically!');
        }

        // For other status changes, just update
        $order->update($validated);

        return redirect()->back()
            ->with('success', 'Order status updated to ' . strtoupper($newStatus));
    }
}
