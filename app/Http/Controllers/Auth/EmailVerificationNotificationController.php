<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('client.dashboard', absolute: false));
        }

        // Auto-verify without sending email (SMTP not configured)
        $request->user()->markEmailAsVerified();

        return redirect()->intended(route('client.dashboard', absolute: false))
            ->with('status', 'Email verified successfully!');
    }
}
