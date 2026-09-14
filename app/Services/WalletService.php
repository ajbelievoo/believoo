<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService
{
    public function credit(User $user, float $amount, string $source = 'manual', ?string $description = null, array $metadata = [], ?int $createdBy = null, ?Order $order = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Wallet credit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $source, $description, $metadata, $createdBy, $order) {
            $lockedUser = User::lockForUpdate()->findOrFail($user->id);
            $balanceBefore = (float) ($lockedUser->wallet_balance ?? 0);
            $balanceAfter = round($balanceBefore + $amount, 2);

            $lockedUser->update(['wallet_balance' => $balanceAfter]);

            return WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'order_id' => $order?->id,
                'created_by' => $createdBy,
                'type' => 'credit',
                'source' => $source,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'INR',
                'reference' => $metadata['reference'] ?? null,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    public function debit(User $user, float $amount, string $source = 'service_payment', ?string $description = null, array $metadata = [], ?int $createdBy = null, ?Order $order = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Wallet debit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $source, $description, $metadata, $createdBy, $order) {
            $lockedUser = User::lockForUpdate()->findOrFail($user->id);
            $balanceBefore = (float) ($lockedUser->wallet_balance ?? 0);

            if ($balanceBefore < $amount) {
                throw new InvalidArgumentException('Insufficient wallet balance.');
            }

            $balanceAfter = round($balanceBefore - $amount, 2);
            $lockedUser->update(['wallet_balance' => $balanceAfter]);

            return WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'order_id' => $order?->id,
                'created_by' => $createdBy,
                'type' => 'debit',
                'source' => $source,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'INR',
                'reference' => $metadata['reference'] ?? null,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    public function canDebit(User $user, float $amount): bool
    {
        return (float) ($user->wallet_balance ?? 0) >= $amount;
    }
}
