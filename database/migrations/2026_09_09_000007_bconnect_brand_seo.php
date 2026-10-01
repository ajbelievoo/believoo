<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $defaults = [
            ['key' => 'bconnect_name', 'value' => 'Bmydesk'],
            ['key' => 'bconnect_brand_color', 'value' => '#e11d48'],
            ['key' => 'bconnect_brand_color_dark', 'value' => '#be123c'],
            ['key' => 'bconnect_brand_color_light', 'value' => '#fb7185'],
            ['key' => 'bconnect_logo', 'value' => ''],
            ['key' => 'bconnect_favicon', 'value' => ''],
            ['key' => 'bconnect_title', 'value' => 'Bmydesk — Unified IT Workspace for Teams & Clients'],
            ['key' => 'bconnect_description', 'value' => 'Bmydesk by Believoo: video calls, remote desktop, bug tracking, AI summaries, billing and team collaboration in one workspace.'],
            ['key' => 'bconnect_keywords', 'value' => 'Bmydesk, IT workspace, video conferencing, remote desktop, bug tracking, AI summaries, team collaboration'],
            ['key' => 'bconnect_og_image', 'value' => ''],
            ['key' => 'bconnect_google_analytics', 'value' => ''],
            ['key' => 'bconnect_google_site_verification', 'value' => ''],
            ['key' => 'bconnect_footer_text', 'value' => '&copy; 2026 Bmydesk — a Believoo Private Limited brand. All rights reserved.'],
        ];
        foreach ($defaults as $d) {
            DB::table('settings')->insertOrIgnore(['key' => $d['key'], 'value' => $d['value']]);
        }
    }
    public function down(): void {
        DB::table('settings')->whereIn('key', array_column($defaults, 'key'))->delete();
    }
};
