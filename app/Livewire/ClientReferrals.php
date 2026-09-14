<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClientReferrals extends Component
{
    public function render()
    {
        $user = Auth::user();
        $user->generateReferralCode();

        $referrals = $user->referralsMade()->with('referred')->latest()->get();

        $stats = [
            'total' => $user->total_referrals ?? 0,
            'successful' => $user->successful_referrals ?? 0,
            'balance' => $user->referral_discount_balance ?? 0,
        ];

        return view('livewire.client-referrals', [
            'user' => $user,
            'referralLink' => $user->referral_link,
            'referrals' => $referrals,
            'stats' => $stats,
        ])->layout('components.layouts.believoo', ['title' => 'Referrals']);
    }
}
