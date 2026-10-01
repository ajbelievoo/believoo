<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Mail\BconnectWelcome;
use App\Models\User;
use App\Models\Setting;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Member;
use App\Notifications\BconnectResetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller {
    public function landing() { return view('bconnect.landing'); }
    public function showLogin() { return view('bconnect.login'); }

    public function login(Request $r) {
        $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (Auth::attempt($r->only('email', 'password'))) {
            $member = Member::where('user_id', Auth::id())->where('is_active', true)->first();
            if ($member) {
                session(['bconnect_company_id' => $member->company_id, 'bconnect_role' => $member->role]);
            }
            return redirect()->intended(route('bconnect.dashboard'));
        }
        return back()->with('error', 'Invalid credentials');
    }

    public function showRegister() { return view('bconnect.register'); }

    public function register(Request $r) {
        $r->validate(['name' => 'required', 'email' => 'required|email|unique:users', 'password' => 'required|min:6', 'company_name' => 'required']);
        $user = User::create(['name' => $r->name, 'email' => $r->email, 'password' => Hash::make($r->password)]);
        $company = Company::create(['name' => $r->company_name, 'slug' => uniqid('co-'), 'plan' => 'free']);
        Member::create(['user_id' => $user->id, 'company_id' => $company->id, 'role' => 'company_admin', 'is_active' => true]);
        try {
            Mail::to($user->email)->send(new BconnectWelcome($user));
        } catch (\Throwable $e) {
            \Log::warning('Bmydesk welcome email failed: ' . $e->getMessage());
        }
        Auth::login($user);
        session(['bconnect_company_id' => $company->id, 'bconnect_role' => 'company_admin']);
        return redirect()->route('bconnect.dashboard');
    }

    protected function configureGoogle(): string
    {
        $clientId = Setting::getValue('google_client_id') ?: config('services.google.client_id');
        $clientSecret = Setting::getValue('google_client_secret') ?: config('services.google.client_secret');
        $redirectUrl = Setting::getValue('google_redirect_url_bconnect') ?: config('services.google.redirect_bconnect', 'https://bc.believoo.com/auth/google/callback');

        if ($clientId) Config::set('services.google.client_id', $clientId);
        if ($clientSecret) Config::set('services.google.client_secret', $clientSecret);

        return $redirectUrl;
    }

    public function redirectToGoogle() {
        $redirectUrl = $this->configureGoogle();
        return Socialite::driver('google')->redirectUrl($redirectUrl)->stateless()->redirect();
    }

    public function handleGoogleCallback() {
        try {
            $redirectUrl = $this->configureGoogle();
            $google = Socialite::driver('google')->redirectUrl($redirectUrl)->stateless()->user();
            $user = User::where('email', $google->email)->first();
            $newUser = false;
            if (!$user) {
                $newUser = true;
                $user = User::create(['name' => $google->name, 'email' => $google->email, 'password' => Hash::make(uniqid()), 'email_verified_at' => now()]);
                $company = Company::create(['name' => $google->name . ' Workspace', 'slug' => uniqid('co-'), 'plan' => 'free']);
                Member::create(['user_id' => $user->id, 'company_id' => $company->id, 'role' => 'company_admin', 'is_active' => true]);
                try {
                    Mail::to($user->email)->send(new BconnectWelcome($user));
                } catch (\Throwable $e) {
                    \Log::warning('Bmydesk welcome email failed: ' . $e->getMessage());
                }
            }
            $member = Member::where('user_id', $user->id)->where('is_active', true)->first();
            Auth::login($user);
            if ($member) {
                session(['bconnect_company_id' => $member->company_id, 'bconnect_role' => $member->role]);
                return redirect()->route('bconnect.dashboard');
            }
            return redirect()->route('bconnect.company.setup')->with('info', 'Please create or join a company');
        } catch (\Exception $e) {
            return redirect()->route('bconnect.login')->with('error', 'Google login failed: ' . $e->getMessage());
        }
    }

    public function logout() {
        Auth::logout();
        session()->forget(['bconnect_company_id', 'bconnect_role']);
        return redirect()->route('bconnect.login');
    }

    public function showForgot() {
        return view('bconnect.forgot-password');
    }

    public function sendReset(Request $r) {
        $r->validate(['email' => 'required|email']);

        $status = Password::broker()->sendResetLink(
            ['email' => $r->email],
            function (User $user, string $token) {
                $user->notify(new BconnectResetPassword($token));
                return null;
            }
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'We have emailed your password reset link.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showReset(Request $r) {
        return view('bconnect.reset-password', ['token' => $r->token, 'email' => $r->email]);
    }

    public function reset(Request $r) {
        $r->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker()->reset(
            $r->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('bconnect.login')->with('status', 'Your password has been reset.')
            : back()->withErrors(['email' => [__($status)]]);
    }
}
