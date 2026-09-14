<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'min:10', 'max:15', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->generateReferralCode();

        // Handle referral code
        if ($request->filled('ref')) {
            $referrer = User::where('referral_code', $request->input('ref'))->first();
            if ($referrer && $referrer->id !== $user->id) {
                $user->referred_by = $referrer->id;
                $user->save();

                \App\Models\Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                    'referral_code' => $request->input('ref'),
                    'referred_email' => $user->email,
                    'referred_name' => $user->name,
                    'status' => 'registered',
                    'registered_at' => now(),
                ]);
            }
        }

        event(new Registered($user));

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\Welcome($user));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Welcome email failed: ' . $e->getMessage());
        }

        Auth::login($user);

        return redirect(route('client.dashboard', absolute: false));
    }
}
