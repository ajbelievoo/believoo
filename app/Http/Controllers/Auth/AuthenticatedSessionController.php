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

            Auth::login($user, $request->boolean('remember'));
        } else {
            $request->authenticate();
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
