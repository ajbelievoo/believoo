<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    /**
     * Show the 2FA challenge view.
     */
    public function challenge(): View
    {
        if (!session()->has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the 2FA code and complete login.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('two_factor_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user || !$user->twoFactorEnabled()) {
            return redirect()->route('login');
        }

        $code = $request->input('code');

        if ($user->verifyTwoFactorCode($code)) {
            // valid TOTP
        } elseif ($user->verifyTwoFactorRecoveryCode($code)) {
            $user->save();
        } else {
            return back()->withErrors(['code' => 'The provided two-factor code or recovery code was invalid.']);
        }

        Auth::login($user, session('two_factor_remember', false));

        $request->session()->regenerate();
        $request->session()->remove('two_factor_user_id');
        $request->session()->remove('two_factor_remember');

        if ($request->is('admin/*') || $request->is('admin')) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended(route('client.dashboard', absolute: false));
    }

    /**
     * Show the 2FA setup view (admin only).
     */
    public function setup(): View
    {
        $user = Auth::user();
        if (!$user->two_factor_secret) {
            $user->generateTwoFactorSecret();
            $user->save();
        }

        $qrSvg = $user->twoFactorQrCodeSvg();
        $secret = $user->two_factor_secret;

        return view('auth.two-factor-setup', [
            'qrSvg' => $qrSvg,
            'secret' => $secret,
        ]);
    }

    /**
     * Confirm and enable 2FA.
     */
    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if (!$user->verifyTwoFactorCode($request->input('code'))) {
            return back()->withErrors(['code' => 'The verification code was invalid. Please scan the QR code again.']);
        }

        $user->two_factor_confirmed_at = now();
        $codes = $user->generateRecoveryCodes();
        $user->save();

        return redirect()->route('two-factor.recovery')->with('recovery_codes', $codes);
    }

    /**
     * Show recovery codes.
     */
    public function recovery(): View
    {
        $user = Auth::user();
        $codes = session('recovery_codes') ?? $user->twoFactorRecoveryCodes();

        if (empty($codes)) {
            return redirect()->route('two-factor.setup');
        }

        return view('auth.two-factor-recovery', [
            'codes' => $codes,
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $codes = $user->generateRecoveryCodes();
        $user->save();

        return redirect()->route('two-factor.recovery')->with('recovery_codes', $codes);
    }

    /**
     * Disable 2FA.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (!Auth::guard('web')->validate(['email' => $user->email, 'password' => $request->input('password')])) {
            return back()->withErrors(['password' => 'The provided password was incorrect.']);
        }

        $user->disableTwoFactor();
        $user->save();

        return redirect()->back()->with('status', 'Two-factor authentication has been disabled.');
    }
}
