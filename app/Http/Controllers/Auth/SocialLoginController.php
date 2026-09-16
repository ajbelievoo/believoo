<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialLoginController extends Controller
{
    public function redirectToGoogle()
    {
        // Set config from database
        $clientId = Setting::getValue('google_client_id');
        $clientSecret = Setting::getValue('google_client_secret');
        $redirectUrl = Setting::getValue('google_redirect_url') ?: config('services.google.redirect', 'https://believoo.com/auth/google/callback');

        if ($clientId) {
            Config::set('services.google.client_id', $clientId);
        }
        if ($clientSecret) {
            Config::set('services.google.client_secret', $clientSecret);
        }

        return Socialite::driver('google')
            ->redirectUrl($redirectUrl)
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            // Set config from database
            $clientId = Setting::getValue('google_client_id');
            $clientSecret = Setting::getValue('google_client_secret');
            $redirectUrl = Setting::getValue('google_redirect_url') ?: config('services.google.redirect', 'https://believoo.com/auth/google/callback');

            if ($clientId) {
                Config::set('services.google.client_id', $clientId);
            }
            if ($clientSecret) {
                Config::set('services.google.client_secret', $clientSecret);
            }

            $user = Socialite::driver('google')
                ->redirectUrl($redirectUrl)
                ->stateless()
                ->user();
            $finduser = User::where('google_id', $user->id)->orWhere('email', $user->email)->first();

            if($finduser){
                if(!$finduser->google_id) {
                    $finduser->update([
                        'google_id' => $user->id,
                        'social_type' => 'google',
                        'avatar' => $user->avatar,
                    ]);
                }
                Auth::login($finduser);

                // Redirect based on user type
                if ($finduser->isAdmin()) {
                    return redirect()->intended('/admin');
                } elseif (empty($finduser->phone)) {
                    return redirect()->route('profile.complete');
                } else {
                    return redirect()->route('client.dashboard');
                }
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'social_type' => 'google',
                    'avatar' => $user->avatar,
                    'password' => null, // Social login users don't need a password initially
                    'is_admin' => false, // Explicitly set to false - social users are clients
                    'email_verified_at' => now(), // Google already verifies emails
                ]);

                Auth::login($newUser);
                return redirect()->route('profile.complete');
            }

        } catch (Exception $e) {
            Log::error('Google login failed: ' . $e->getMessage());
            return redirect('/admin/login')->with('error', 'Google login failed: ' . $e->getMessage());
        }
    }

    // Facebook Login
    public function redirectToFacebook()
    {
        // Set config from database
        $clientId = Setting::getValue('facebook_client_id');
        $clientSecret = Setting::getValue('facebook_client_secret');

        if ($clientId) {
            Config::set('services.facebook.client_id', $clientId);
        }
        if ($clientSecret) {
            Config::set('services.facebook.client_secret', $clientSecret);
        }

        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            // Set config from database
            $clientId = Setting::getValue('facebook_client_id');
            $clientSecret = Setting::getValue('facebook_client_secret');

            if ($clientId) {
                Config::set('services.facebook.client_id', $clientId);
            }
            if ($clientSecret) {
                Config::set('services.facebook.client_secret', $clientSecret);
            }

            $user = Socialite::driver('facebook')->user();
            $finduser = User::where('facebook_id', $user->id)->orWhere('email', $user->email)->first();

            if($finduser){
                if(!$finduser->facebook_id) {
                    $finduser->update([
                        'facebook_id' => $user->id,
                        'social_type' => 'facebook',
                        'avatar' => $user->avatar,
                    ]);
                }
                Auth::login($finduser);

                if ($finduser->isAdmin()) {
                    return redirect()->intended('/admin');
                } elseif (empty($finduser->phone)) {
                    return redirect()->route('profile.complete');
                } else {
                    return redirect()->route('client.dashboard');
                }
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'facebook_id' => $user->id,
                    'social_type' => 'facebook',
                    'avatar' => $user->avatar,
                    'password' => null,
                    'is_admin' => false,
                    'email_verified_at' => now(),
                ]);

                Auth::login($newUser);
                return redirect()->route('profile.complete');
            }

        } catch (Exception $e) {
            return redirect('/admin/login')->with('error', 'Something went wrong with Facebook login.');
        }
    }

    // Twitter/X Login
    public function redirectToTwitter()
    {
        // Set config from database
        $clientId = Setting::getValue('twitter_client_id');
        $clientSecret = Setting::getValue('twitter_client_secret');

        if ($clientId) {
            Config::set('services.twitter.client_id', $clientId);
        }
        if ($clientSecret) {
            Config::set('services.twitter.client_secret', $clientSecret);
        }

        return Socialite::driver('twitter')->redirect();
    }

    public function handleTwitterCallback()
    {
        try {
            // Set config from database
            $clientId = Setting::getValue('twitter_client_id');
            $clientSecret = Setting::getValue('twitter_client_secret');

            if ($clientId) {
                Config::set('services.twitter.client_id', $clientId);
            }
            if ($clientSecret) {
                Config::set('services.twitter.client_secret', $clientSecret);
            }

            $user = Socialite::driver('twitter')->user();
            $finduser = User::where('twitter_id', $user->id)->orWhere('email', $user->email)->first();

            if($finduser){
                if(!$finduser->twitter_id) {
                    $finduser->update([
                        'twitter_id' => $user->id,
                        'social_type' => 'twitter',
                        'avatar' => $user->avatar,
                    ]);
                }
                Auth::login($finduser);

                if ($finduser->isAdmin()) {
                    return redirect()->intended('/admin');
                } elseif (empty($finduser->phone)) {
                    return redirect()->route('profile.complete');
                } else {
                    return redirect()->route('client.dashboard');
                }
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'twitter_id' => $user->id,
                    'social_type' => 'twitter',
                    'avatar' => $user->avatar,
                    'password' => null,
                    'is_admin' => false,
                    'email_verified_at' => now(),
                ]);

                Auth::login($newUser);
                return redirect()->route('profile.complete');
            }

        } catch (Exception $e) {
            return redirect('/admin/login')->with('error', 'Something went wrong with Twitter login.');
        }
    }
}
