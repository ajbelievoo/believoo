<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'referred_email' => 'required|email|unique:referrals,referred_email',
            'referred_name' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        // Generate referral code if not exists
        if (!$user->referral_code) {
            $user->generateReferralCode();
        }

        // Check if this email was already referred by someone else
        $existingReferral = Referral::where('referred_email', $request->referred_email)
            ->where('status', '!=', 'rewarded')
            ->first();

        if ($existingReferral) {
            return back()->with('error', 'This email has already been referred by another user.');
        }

        // Create referral
        $referral = Referral::create([
            'referrer_id' => $user->id,
            'referred_email' => $request->referred_email,
            'referred_name' => $request->referred_name,
            'status' => 'pending',
        ]);

        // Send referral email to friend
        // This would typically use a Mailable class
        // Mail::to($request->referred_email)->send(new ReferralInvitationMail($user, $referral));

        // Send notification to user
        $user->notify(new \App\Notifications\ReferralSentNotification($referral));

        return back()->with('success', 'Referral sent successfully! Your friend will receive an invitation email.');
    }

    public function getReferralStats()
    {
        $user = Auth::user();

        return response()->json([
            'referral_code' => $user->referral_code ?? $user->generateReferralCode(),
            'referral_link' => $user->referral_link,
            'total_referrals' => $user->total_referrals,
            'successful_referrals' => $user->successful_referrals,
            'discount_balance' => $user->referral_discount_balance,
            'pending_referrals' => $user->referralsMade()->where('status', 'pending')->count(),
        ]);
    }

    public function getReferrals()
    {
        $user = Auth::user();

        $referrals = $user->referralsMade()
            ->with('referred')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($referral) {
                return [
                    'id' => $referral->id,
                    'referred_name' => $referral->referred_name,
                    'referred_email' => $referral->referred_email,
                    'status' => $referral->status,
                    'status_color' => $referral->status_color,
                    'discount_amount' => $referral->discount_amount,
                    'created_at' => $referral->created_at->format('M d, Y'),
                    'rewarded_at' => $referral->rewarded_at?->format('M d, Y'),
                ];
            });

        return response()->json($referrals);
    }
}
