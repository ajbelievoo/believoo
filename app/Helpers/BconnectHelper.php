<?php
namespace App\Helpers;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class BconnectHelper {
    public static function setting($key, $default = '') {
        $v = Setting::where('key', $key)->value('value');
        return $v ?: $default;
    }

    public static function assetUrl($key, $fallback) {
        $v = self::setting($key);
        if (str_starts_with($v, 'http')) return $v;
        if ($v) return Storage::disk('public')->url($v);
        return $fallback;
    }

    public static function brandData() {
        $mainLogo = Storage::disk('public')->url(Setting::where('key', 'logo')->value('value') ?? 'settings/AQUWGGIF61UIUqrRmRT9PYamLaP1qb1RtJr1JuSK.webp');
        $mainFavicon = Storage::disk('public')->url(Setting::where('key', 'favicon')->value('value') ?? 'settings/AQUWGGIF61UIUqrRmRT9PYamLaP1qb1RtJr1JuSK.webp');
        $og = Storage::disk('public')->url(Setting::where('key', 'og_image')->value('value') ?? 'settings/og-image.png');
        return [
            'logo' => self::assetUrl('bconnect_logo', 'https://bc.believoo.com/images/bconnect-logo.png'),
            'favicon' => self::assetUrl('bconnect_favicon', $mainFavicon),
            'og_image' => self::assetUrl('bconnect_og_image', $og),
            'title' => self::setting('bconnect_title', 'B-CONNECT — Unified IT Workspace for Teams & Clients'),
            'description' => self::setting('bconnect_description', 'B-CONNECT by Believoo: video calls, remote desktop, bug tracking, AI summaries, billing and team collaboration.'),
            'keywords' => self::setting('bconnect_keywords', 'B-CONNECT, IT workspace, video conferencing, remote desktop, bug tracking, AI summaries, team collaboration'),
            'footer_text' => self::setting('bconnect_footer_text', '&copy; 2026 Believoo. All rights reserved.'),
            'google_analytics' => self::setting('bconnect_google_analytics', ''),
            'google_site_verification' => self::setting('bconnect_google_site_verification', ''),
        ];
    }
}
