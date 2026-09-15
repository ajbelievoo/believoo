<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Handle phone login
        if ($request->filled('phone')) {
            $user = User::where('phone', $request->phone)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return back()->withErrors(['phone' => 'These credentials do not match our records.']);
            }

            if ($user->twoFactorEnabled()) {
                return $this->redirectToTwoFactorChallenge($request, $user);
            }

            Auth::login($user, $request->boolean('remember'));
        } else {
            $request->authenticate();
            $user = Auth::user();

            if ($user && $user->twoFactorEnabled()) {
                return $this->redirectToTwoFactorChallenge($request, $user);
            }
        }

        $request->session()->regenerate();

        // Check for custom redirect URL
        if ($request->filled('redirect')) {
            return redirect($request->input('redirect'));
        }

        if ($request->is('admin/*') || $request->is('admin')) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended(route('client.dashboard', absolute: false));
    }

    /**
     * Redirect the user to the two-factor challenge screen.
     */
    protected function redirectToTwoFactorChallenge(Request $request, User $user): RedirectResponse
    {
        if ($request->filled('phone')) {
            // Phone login did not create a session yet, no need to logout
        } else {
            Auth::logout();
        }

        $request->session()->put('two_factor_user_id', $user->id);
        $request->session()->put('two_factor_remember', $request->boolean('remember'));

        return redirect()->route('two-factor.challenge');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
