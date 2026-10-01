<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()
            ->view('admin.settings.index', compact('settings'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // General
            'site_name' => 'nullable|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'site_description' => 'nullable|string',

            // Announcement Bar
            'announcement_enabled' => 'nullable|boolean',
            'announcement_text' => 'nullable|string|max:500',
            'announcement_url' => 'nullable|string|max:500',
            'announcement_end_at' => 'nullable|date',

            // GHC Site (ghc.believoo.com)
            'ghc_site_name' => 'nullable|string|max:100',
            'ghc_tagline' => 'nullable|string|max:255',
            'ghc_meta_title' => 'nullable|string|max:255',
            'ghc_meta_description' => 'nullable|string',
            'ghc_meta_keywords' => 'nullable|string',
            'ghc_support_email' => 'nullable|email|max:255',
            'ghc_primary_color' => 'nullable|string|max:20',
            'ghc_hero_badge' => 'nullable|string|max:255',
            'ghc_hero_title' => 'nullable|string|max:500',
            'ghc_hero_subtitle' => 'nullable|string|max:1000',
            'ghc_announce_text' => 'nullable|string|max:500',
            'ghc_announce_url' => 'nullable|string|max:500',
            'ghc_logo' => 'nullable',
            'ghc_favicon' => 'nullable',
            'ghc_og_image' => 'nullable',

            // Contact
            'contact_email' => 'nullable|email|max:255',
            'support_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'business_city' => 'nullable|string|max:100',
            'business_state' => 'nullable|string|max:100',
            'business_postal' => 'nullable|string|max:20',
            'business_country' => 'nullable|string|max:100',
            'business_latitude' => 'nullable|numeric',
            'business_longitude' => 'nullable|numeric',
            'business_opening_hours' => 'nullable|string',
            'google_maps' => 'nullable|string',

            // Company Legal / Registration
            'company_legal_name' => 'nullable|string|max:255',
            'company_cin' => 'nullable|string|max:50',
            'company_pan' => 'nullable|string|max:20',
            'company_tan' => 'nullable|string|max:20',
            'company_incorporation_date' => 'nullable|string|max:50',
            'company_registered_office' => 'nullable|string',
            'company_address' => 'nullable|string',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_website' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:20',

            // Social
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'github' => 'nullable|url|max:255',

            // Social Login OAuth
            'google_login_enabled' => 'nullable|boolean',
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
            'facebook_login_enabled' => 'nullable|boolean',
            'facebook_client_id' => 'nullable|string|max:255',
            'facebook_client_secret' => 'nullable|string|max:255',
            'twitter_login_enabled' => 'nullable|boolean',
            'twitter_client_id' => 'nullable|string|max:255',
            'twitter_client_secret' => 'nullable|string|max:255',

            // SEO
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'google_analytics' => 'nullable|string|max:255',
            'google_site_verification' => 'nullable|string|max:255',
            'bing_site_verification' => 'nullable|string|max:255',

            // Payment - Indian Gateways
            'razorpay_key_id' => 'nullable|string',
            'razorpay_key_secret' => 'nullable|string',
            'razorpay_mode' => 'nullable|string|in:test,live',
            'razorpay_enabled' => 'nullable|boolean',
            'cashfree_app_id' => 'nullable|string',
            'cashfree_secret_key' => 'nullable|string',
            'cashfree_enabled' => 'nullable|boolean',
            'cashfree_mode' => 'nullable|in:sandbox,production',
            
            // Payment - International Gateways
            'paypal_client_id' => 'nullable|string',
            'paypal_client_secret' => 'nullable|string',
            'paypal_enabled' => 'nullable|boolean',
            'paypal_mode' => 'nullable|in:sandbox,production',
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'stripe_enabled' => 'nullable|boolean',
            
            // Payment - PayU (Both)
            'payu_key' => 'nullable|string',
            'payu_salt' => 'nullable|string',
            'payu_enabled' => 'nullable|boolean',
            'payu_mode' => 'nullable|in:sandbox,production',

            // Agora for Bmydesk
            'agora_app_id' => 'nullable|string',
            'agora_app_certificate' => 'nullable|string',
            'agora_enabled' => 'nullable|boolean',
            'agora_customer_id' => 'nullable|string',
            'agora_customer_secret' => 'nullable|string',
            'agora_recording_bucket' => 'nullable|string',
            'agora_recording_region' => 'nullable|string',
            'agora_recording_access_key' => 'nullable|string',
            'agora_recording_secret_key' => 'nullable|string',

            // Push Notifications
            'vapid_public_key' => 'nullable|string',
            'vapid_private_key' => 'nullable|string',

            // Bmydesk Brand & SEO
            'bconnect_name' => 'nullable|string|max:100',
            'bconnect_brand_color' => 'nullable|string|max:20',
            'bconnect_logo' => 'nullable',
            'bconnect_favicon' => 'nullable',
            'bconnect_title' => 'nullable|string',
            'bconnect_description' => 'nullable|string',
            'bconnect_keywords' => 'nullable|string',
            'bconnect_og_image' => 'nullable',
            'bconnect_google_analytics' => 'nullable|string',
            'bconnect_google_site_verification' => 'nullable|string',
            'bconnect_footer_text' => 'nullable|string',
            
            // Payment - General
            'currency' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:5',
            'gst_rate' => 'nullable|numeric|min:0|max:100',

            // Email
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'mail_from_name' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',

            // Appearance
            'primary_color' => 'nullable|string|max:255',
            'default_theme' => 'nullable|in:dark,light,auto',
            'theme_toggle_enabled' => 'nullable|boolean',
            'custom_css' => 'nullable|string',

            // Maintenance
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string',

            // Security
            'force_2fa' => 'nullable|boolean',
            'password_strength' => 'nullable|boolean',
            'session_timeout' => 'nullable|integer',
            'max_login_attempts' => 'nullable|integer',
            'allowed_ips' => 'nullable|string',
            'blocked_ips' => 'nullable|string',

            // Notifications
            'notify_new_order' => 'nullable|boolean',
            'notify_new_ticket' => 'nullable|boolean',
            'notify_new_user' => 'nullable|boolean',
            'notify_payment' => 'nullable|boolean',
            'notify_invoice' => 'nullable|boolean',
            'notify_agreement' => 'nullable|boolean',
            'sms_provider' => 'nullable|in:twilio,msg91,none',
            'sms_api_key' => 'nullable|string',
            'sms_new_order' => 'nullable|boolean',
            'sms_new_ticket' => 'nullable|boolean',

            // Backup
            'auto_backup' => 'nullable|boolean',
            'backup_frequency' => 'nullable|in:daily,weekly,monthly',
            'backup_storage' => 'nullable|in:local,s3,dropbox',
            'backup_retention' => 'nullable|integer',

            // AI Chat Assistant
            'ai_enabled' => 'nullable|boolean',
            'ai_model' => 'nullable|in:openai,gemini,claude,divine,local',
            'ai_api_key' => 'nullable|string',
            'ai_openai_model' => 'nullable|string|max:50',
            'ai_openai_api_key' => 'nullable|string',
            'ai_gemini_model' => 'nullable|string|max:50',
            'ai_gemini_api_key' => 'nullable|string',
            'ai_claude_model' => 'nullable|string|max:50',
            'ai_claude_api_key' => 'nullable|string',
            'ai_divine_base_url' => 'nullable|url|max:255',
            'ai_divine_model' => 'nullable|string|max:50',
            'ai_divine_api_key' => 'nullable|string',
            'ai_system_prompt' => 'nullable|string',
            'ai_fallback_message' => 'nullable|string',
            'ai_name' => 'nullable|string|max:50',
            'ai_tone' => 'nullable|in:friendly,professional,casual,warm',

            // External integrations
            'telegram_bot_token' => 'nullable|string|max:255',
            'meta_verify_token' => 'nullable|string|max:255',
            'meta_page_access_token' => 'nullable|string',
            'whatsapp_number' => 'nullable|string|max:20',
            'inbound_email_secret' => 'nullable|string|max:255',
            'twilio_sid' => 'nullable|string|max:255',
            'twilio_token' => 'nullable|string',
            'twilio_from' => 'nullable|string|max:20',
            'sms_enabled' => 'nullable|boolean',
            'slack_webhook_url' => 'nullable|url|max:500',
            'discord_webhook_url' => 'nullable|url|max:500',
            'google_calendar_id' => 'nullable|string|max:255',
            'whatsapp_api_token' => 'nullable|string',
            'whatsapp_phone_id' => 'nullable|string|max:50',
            'whatsapp_verify_token' => 'nullable|string|max:100',

            // DNS / Cloudflare
            'cloudflare_api_token' => 'nullable|string',
            'cloudflare_zone_id' => 'nullable|string|max:255',

            // Server Management
            'whmcs_base_url' => 'nullable|url|max:255',
            'whmcs_api_identifier' => 'nullable|string|max:255',
            'whmcs_api_secret' => 'nullable|string|max:255',
            'virtualizor_base_url' => 'nullable|url|max:255',
            'virtualizor_port' => 'nullable|integer|min:1|max:65535',
            'virtualizor_api_key' => 'nullable|string|max:255',
            'virtualizor_api_pass' => 'nullable|string|max:255',
            'server_dashboard_auto_refresh' => 'nullable|boolean',
            'server_dashboard_refresh_interval' => 'nullable|integer|min:10|max:300',
            'whmcs_cache_ttl' => 'nullable|integer|min:60',
            'virtualizor_cache_ttl' => 'nullable|integer|min:10',

            // Proxmox VE
            'proxmox_api_url' => 'nullable|url|max:255',
            'proxmox_token_id' => 'nullable|string|max:255',
            'proxmox_token_secret' => 'nullable|string|max:255',
            'proxmox_node' => 'nullable|string|max:100',
            'proxmox_bridge' => 'nullable|string|max:50',
            'proxmox_storage' => 'nullable|string|max:100',
            'proxmox_verify_ssl' => 'nullable|boolean',
        ]);

        // Handle file uploads
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'logo'], ['value' => $logoPath]);
        }

        if ($request->hasFile('dark_logo')) {
            $darkLogoPath = $request->file('dark_logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'dark_logo'], ['value' => $darkLogoPath]);
        }

        if ($request->hasFile('favicon')) {
            $faviconPath = $request->file('favicon')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'favicon'], ['value' => $faviconPath]);
        }

        foreach (['ghc_logo', 'ghc_favicon', 'ghc_og_image'] as $f) {
            if ($request->hasFile($f)) {
                $path = $request->file($f)->store('settings', 'public');
                Setting::updateOrCreate(['key' => $f], ['value' => $path]);
            }
        }

        // Bmydesk brand file uploads
        foreach (['bconnect_logo', 'bconnect_favicon', 'bconnect_og_image'] as $f) {
            if ($request->hasFile($f)) {
                $path = $request->file($f)->store('settings', 'public');
                Setting::updateOrCreate(['key' => $f], ['value' => $path]);
            }
        }

        // Handle checkbox fields (enabled/disabled states)
        $checkboxFields = [
            'razorpay_enabled',
            'cashfree_enabled',
            'paypal_enabled',
            'stripe_enabled',
            'payu_enabled',
            'enable_registration',
            'enable_email_verification',
            'enable_social_login',
            'google_login_enabled',
            'facebook_login_enabled',
            'twitter_login_enabled',
            'maintenance_mode',
            'force_2fa',
            'password_strength',
            'theme_toggle_enabled',
            'auto_backup',
            'notify_new_order',
            'notify_new_ticket',
            'notify_new_user',
            'notify_payment',
            'notify_invoice',
            'notify_agreement',
            'sms_new_order',
            'sms_new_ticket',
            'server_dashboard_auto_refresh',
            'proxmox_verify_ssl',
            'ai_enabled',
            'announcement_enabled',
        ];

        foreach ($checkboxFields as $field) {
            // If checked, value will be 1; if unchecked, it's not in request, so set to 0
            $value = $request->has($field) ? '1' : '0';
            Setting::updateOrCreate(
                ['key' => $field],
                ['value' => $value]
            );
        }

        // Save text settings (skip checkboxes and file fields already handled above)
        $fileFields = ['logo', 'dark_logo', 'favicon', 'ghc_logo', 'ghc_favicon', 'ghc_og_image', 'bconnect_logo', 'bconnect_favicon', 'bconnect_og_image'];
        foreach ($validated as $key => $value) {
            if ($value !== null && !in_array($key, $checkboxFields) && !in_array($key, $fileFields)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        // Combine Proxmox token ID and secret into full API token
        $tokenId = $request->input('proxmox_token_id');
        $tokenSecret = $request->input('proxmox_token_secret');
        if ($tokenId && $tokenSecret) {
            $apiToken = $tokenId . '=' . $tokenSecret;
            Setting::updateOrCreate(
                ['key' => 'proxmox_api_token'],
                ['value' => $apiToken]
            );
        }

        // Keep the Cloudflare domain provider config in sync with the settings value
        if ($request->filled('cloudflare_api_token')) {
            $provider = \App\Models\DomainProvider::where('code', 'cloudflare')->first();
            if ($provider) {
                $metadata = $provider->metadata ?? [];
                $metadata['api_token'] = $request->input('cloudflare_api_token');
                $metadata['api_key'] = $request->input('cloudflare_api_token');
                $provider->metadata = $metadata;
                $provider->api_key = $request->input('cloudflare_api_token');
                $provider->save();
            }
        }

        // Clear cache to reflect changes
        cache()->forget('site_settings');

        return redirect()->back()
            ->with('success', 'All settings saved successfully');
    }
}
