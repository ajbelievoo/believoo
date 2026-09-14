<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount(['orders', 'agreements', 'tickets'])
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['orders', 'agreements', 'walletTransactions' => fn ($query) => $query->latest()->limit(10)]);
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'is_admin' => 'boolean',
            'whmcs_client_id' => 'nullable|string|max:255',
            'vps_ids' => 'nullable|string',
            'next_due_date' => 'nullable|date',
            'plan_price' => 'nullable|numeric|min:0',
            'billing_status' => 'nullable|in:active,suspended,terminated',
            'last_payment_date' => 'nullable|date',
            'current_plan_name' => 'nullable|string|max:255',
        ]);

        // Convert comma-separated VPS IDs to array
        if (!empty($validated['vps_ids'])) {
            $vpsIds = array_map('trim', explode(',', $validated['vps_ids']));
            $vpsIds = array_filter($vpsIds, 'is_numeric');
            $validated['vps_ids'] = array_map('intval', $vpsIds);
        } else {
            $validated['vps_ids'] = [];
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }

    public function changePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user->password = bcrypt($request->password);
        $user->save();

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Password updated successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }

    public function adjustWallet(Request $request, User $user, WalletService $walletService)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validated['type'] === 'credit') {
            $walletService->credit(
                $user,
                (float) $validated['amount'],
                'admin_adjustment',
                $validated['description'] ?? 'Admin wallet credit',
                ['reference' => 'ADMIN-' . now()->format('YmdHis')],
                auth()->id()
            );
        } else {
            $walletService->debit(
                $user,
                (float) $validated['amount'],
                'admin_adjustment',
                $validated['description'] ?? 'Admin wallet debit',
                ['reference' => 'ADMIN-' . now()->format('YmdHis')],
                auth()->id()
            );
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Wallet balance updated successfully');
    }
}
