<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoProvider;
use App\Models\User;
use App\Models\UserSsoLink;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use OneLogin\Saml2\Auth as SamlAuth;

class SsoController extends Controller
{
    public function redirect(Request $request, string $slug)
    {
        $provider = SsoProvider::active()->where('slug', $slug)->firstOrFail();

        session(['sso_state' => $state = Str::random(40)]);

        if (in_array($provider->type, ['oidc', 'oauth'], true)) {
            $config = $provider->config ?? [];
            $driver = $provider->type === 'oidc' ? ($config['driver'] ?? 'google') : ($config['driver'] ?? 'google');

            $clientConfig = array_merge(
                config("services.{$driver}", []),
                [
                    'client_id' => $config['client_id'] ?? '',
                    'client_secret' => $config['client_secret'] ?? '',
                    'redirect' => route('sso.callback', $provider->slug),
                ]
            );

            config(["services.{$provider->slug}" => $clientConfig]);

            return Socialite::buildProvider(
                \Laravel\Socialite\Two\GoogleProvider::class,
                $clientConfig
            )->with(['state' => $state, 'nonce' => Str::random(32)])->redirect();
        }

        if ($provider->type === 'saml') {
            $auth = new SamlAuth($provider->samlSettings());
            return redirect($auth->login(null, [], false, false, true));
        }

        return redirect()->route('login')->withErrors(['email' => 'Unsupported SSO type.']);
    }

    public function callback(Request $request, string $slug)
    {
        $provider = SsoProvider::active()->where('slug', $slug)->firstOrFail();

        try {
            if (in_array($provider->type, ['oidc', 'oauth'], true)) {
                $config = $provider->config ?? [];
                $clientConfig = [
                    'client_id' => $config['client_id'] ?? '',
                    'client_secret' => $config['client_secret'] ?? '',
                    'redirect' => route('sso.callback', $provider->slug),
                ];

                $socialUser = Socialite::buildProvider(
                    \Laravel\Socialite\Two\GoogleProvider::class,
                    $clientConfig
                )->stateless()->user();

                $email = $socialUser->getEmail();
                $externalId = $socialUser->getId();
                $attributes = [
                    'name' => $socialUser->getName(),
                    'avatar' => $socialUser->getAvatar(),
                ];
            } elseif ($provider->type === 'saml') {
                $auth = new SamlAuth($provider->samlSettings());
                $auth->processResponse();

                if ($auth->getErrors()) {
                    Log::warning('SAML response error', ['provider' => $provider->slug, 'errors' => $auth->getErrors()]);
                    return redirect()->route('login')->withErrors(['email' => 'SAML authentication failed.']);
                }

                $email = $auth->getNameId();
                $attributes = $auth->getAttributes();
                $externalId = $email;
            } else {
                return redirect()->route('login')->withErrors(['email' => 'Unsupported SSO type.']);
            }
        } catch (\Exception $e) {
            Log::error('SSO callback error', ['provider' => $provider->slug, 'error' => $e->getMessage()]);
            return redirect()->route('login')->withErrors(['email' => 'SSO authentication failed.']);
        }

        if (!$email) {
            return redirect()->route('login')->withErrors(['email' => 'SSO provider did not return an email.']);
        }

        return $this->resolveUser($provider, $externalId, $email, $attributes);
    }

    protected function resolveUser(SsoProvider $provider, string $externalId, string $email, array $attributes)
    {
        $link = UserSsoLink::where('sso_provider_id', $provider->id)
            ->where('external_id', $externalId)
            ->first();

        if ($link) {
            $user = $link->user;
        } else {
            $user = User::where('email', $email)->first();

            if (!$user && $provider->auto_provision) {
                $user = new User([
                    'name' => $attributes['name'] ?? ($attributes[0] ?? explode('@', $email)[0]),
                    'email' => $email,
                    'password' => bcrypt(Str::random(64)),
                    'is_admin' => false,
                ]);
                $user->email_verified_at = now();
                $user->save();
            }

            if (!$user) {
                return redirect()->route('login')->withErrors(['email' => 'No matching account.']);
            }

            UserSsoLink::create([
                'user_id' => $user->id,
                'sso_provider_id' => $provider->id,
                'external_id' => $externalId,
                'attributes' => $attributes,
                'last_used_at' => now(),
            ]);
        }

        if ($link) {
            $link->update(['last_used_at' => now()]);
        }

        Auth::login($user);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function metadata(string $slug)
    {
        $provider = SsoProvider::active()->where('slug', $slug)->firstOrFail();
        $settings = $provider->samlSettings();

        $entityId = e($settings['sp']['entityId']);
        $acsUrl = e($settings['sp']['assertionConsumerService']['url']);
        $sloUrl = e($settings['sp']['singleLogoutService']['url']);
        $cert = e($settings['sp']['x509cert'] ?? '');

        $certBlock = $cert ? '<KeyDescriptor use="signing"><ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#"><ds:X509Data><ds:X509Certificate>' . $cert . '</ds:X509Certificate></ds:X509Data></ds:KeyInfo></KeyDescriptor>' : '';

        $metadata = <<<XML
<?xml version="1.0"?>
<EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" entityID="{$entityId}">
  <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    {$certBlock}
    <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="{$sloUrl}"/>
    <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="{$acsUrl}" index="1" isDefault="true"/>
    <NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</NameIDFormat>
  </SPSSODescriptor>
</EntityDescriptor>
XML;

        return response($metadata, 200, ['Content-Type' => 'application/xml']);
    }
}
