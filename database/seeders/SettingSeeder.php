<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'site_logo', 'value' => 'BELIEVOO', 'type' => 'text', 'group' => 'General'],
            ['key' => 'footer_text', 'value' => 'Empowering the next generation of digital infrastructure.', 'type' => 'textarea', 'group' => 'General'],
            ['key' => 'favicon', 'value' => null, 'type' => 'image', 'group' => 'General'],
            
            // SEO Settings
            ['key' => 'meta_title', 'value' => 'Believoo', 'type' => 'text', 'group' => 'SEO'],
            ['key' => 'meta_description', 'value' => 'Believoo is a next-gen digital infrastructure provider.', 'type' => 'textarea', 'group' => 'SEO'],
            ['key' => 'meta_keywords', 'value' => 'tech, infrastructure, cloud', 'type' => 'text', 'group' => 'SEO'],

            // Contact Settings
            ['key' => 'contact_phone', 'value' => '+1 (555) 000-0000', 'type' => 'text', 'group' => 'Contact'],
            ['key' => 'contact_email', 'value' => 'hello@believoo.com', 'type' => 'text', 'group' => 'Contact'],
            ['key' => 'office_location', 'value' => '123 Tech Lane, Silicon Valley, CA', 'type' => 'textarea', 'group' => 'Contact'],
            
            // Social Media
            ['key' => 'facebook_url', 'value' => 'https://facebook.com/believoo', 'type' => 'text', 'group' => 'Social Media'],
            ['key' => 'twitter_url', 'value' => 'https://twitter.com/believoo', 'type' => 'text', 'group' => 'Social Media'],
            ['key' => 'instagram_url', 'value' => 'https://instagram.com/believoo', 'type' => 'text', 'group' => 'Social Media'],
            ['key' => 'linkedin_url', 'value' => 'https://linkedin.com/company/believoo', 'type' => 'text', 'group' => 'Social Media'],
            
            // Mail Settings
            ['key' => 'mail_host', 'value' => 'smtp.mailtrap.io', 'type' => 'text', 'group' => 'Mail'],
            ['key' => 'mail_port', 'value' => '2525', 'type' => 'text', 'group' => 'Mail'],
            ['key' => 'mail_username', 'value' => '', 'type' => 'text', 'group' => 'Mail'],
            ['key' => 'mail_password', 'value' => '', 'type' => 'text', 'group' => 'Mail'],
            ['key' => 'mail_encryption', 'value' => 'tls', 'type' => 'text', 'group' => 'Mail'],
            ['key' => 'mail_from_address', 'value' => 'hello@believoo.com', 'type' => 'text', 'group' => 'Mail'],
            ['key' => 'mail_from_name', 'value' => 'Believoo Support', 'type' => 'text', 'group' => 'Mail'],
            
            // Google Social Login
            ['key' => 'google_client_id', 'value' => '', 'type' => 'text', 'group' => 'Google Social'],
            ['key' => 'google_client_secret', 'value' => '', 'type' => 'text', 'group' => 'Google Social'],
            ['key' => 'google_redirect_url', 'value' => 'https://believoo.com/auth/google/callback', 'type' => 'text', 'group' => 'Google Social'],
            
            // Authentication Settings
            ['key' => 'enable_registration', 'value' => '1', 'type' => 'text', 'group' => 'Authentication'],
            ['key' => 'enable_email_verification', 'value' => '0', 'type' => 'text', 'group' => 'Authentication'],
            ['key' => 'enable_social_login', 'value' => '1', 'type' => 'text', 'group' => 'Authentication'],
            
            // Payment Settings - Razorpay
            ['key' => 'razorpay_enabled', 'value' => '0', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'razorpay_key_id', 'value' => '', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'razorpay_key_secret', 'value' => '', 'type' => 'textarea', 'group' => 'Payment'],
            
            // Payment Settings - Cashfree
            ['key' => 'cashfree_enabled', 'value' => '0', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'cashfree_app_id', 'value' => '', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'cashfree_secret_key', 'value' => '', 'type' => 'textarea', 'group' => 'Payment'],
            ['key' => 'cashfree_mode', 'value' => 'sandbox', 'type' => 'text', 'group' => 'Payment'],
            
            // Payment Settings - PayPal
            ['key' => 'paypal_enabled', 'value' => '0', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'paypal_client_id', 'value' => '', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'paypal_client_secret', 'value' => '', 'type' => 'textarea', 'group' => 'Payment'],
            ['key' => 'paypal_mode', 'value' => 'sandbox', 'type' => 'text', 'group' => 'Payment'],
            
            // Payment Settings - Stripe
            ['key' => 'stripe_enabled', 'value' => '0', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'stripe_key', 'value' => '', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'stripe_secret', 'value' => '', 'type' => 'textarea', 'group' => 'Payment'],
            ['key' => 'stripe_webhook_secret', 'value' => '', 'type' => 'textarea', 'group' => 'Payment'],
            
            // Payment Settings - PayU
            ['key' => 'payu_enabled', 'value' => '0', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'payu_key', 'value' => '', 'type' => 'text', 'group' => 'Payment'],
            ['key' => 'payu_salt', 'value' => '', 'type' => 'textarea', 'group' => 'Payment'],
            ['key' => 'payu_mode', 'value' => 'sandbox', 'type' => 'text', 'group' => 'Payment'],
        ];

        foreach ($settings as $setting) {
            // Use firstOrCreate to avoid overwriting existing values
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group']
                ]
            );
        }
    }
}
