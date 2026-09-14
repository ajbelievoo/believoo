<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SEOSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'meta_title',
                'value' => 'Believoo - Next-Gen Software & Infrastructure Agency',
                'type' => 'text'
            ],
            [
                'key' => 'meta_description',
                'value' => 'Believoo architects elite digital ecosystems, high-performance VPS, SEO dominance, and blockchain solutions for global enterprises.',
                'type' => 'textarea'
            ],
            [
                'key' => 'meta_keywords',
                'value' => 'software agency, infrastructure, vps, seo, blockchain, app development, laravel, believoo',
                'type' => 'textarea'
            ],
            [
                'key' => 'favicon',
                'value' => '',
                'type' => 'image'
            ],
        ];

        foreach ($settings as $setting) {
            // Use firstOrCreate to avoid overwriting existing values
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
