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

    public static function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return hexdec(substr($hex, 0, 2)) . ',' . hexdec(substr($hex, 2, 2)) . ',' . hexdec(substr($hex, 4, 2));
    }

    public static function brandData() {
        $mainFavicon = Storage::disk('public')->url(Setting::where('key', 'favicon')->value('value') ?? 'settings/AQUWGGIF61UIUqrRmRT9PYamLaP1qb1RtJr1JuSK.webp');
        $og = Storage::disk('public')->url(Setting::where('key', 'og_image')->value('value') ?? 'settings/og-image.png');
        $name = self::setting('bconnect_name', 'Bmydesk');
        $brandColor = self::setting('bconnect_brand_color', '#7c3aed');
        return [
            'name' => $name,
            'logo' => self::assetUrl('bconnect_logo', 'https://bc.believoo.com/images/bconnect-logo.png'),
            'favicon' => self::assetUrl('bconnect_favicon', $mainFavicon),
            'og_image' => self::assetUrl('bconnect_og_image', $og),
            'title' => self::setting('bconnect_title', $name . ' — Unified IT Workspace for Teams & Clients'),
            'description' => self::setting('bconnect_description', $name . ' by Believoo: video calls, remote desktop, bug tracking, AI summaries, billing and team collaboration.'),
            'keywords' => self::setting('bconnect_keywords', $name . ', IT workspace, video conferencing, remote desktop, bug tracking, AI summaries, team collaboration'),
            'footer_text' => self::setting('bconnect_footer_text', '&copy; ' . date('Y') . ' ' . $name . ' — a Believoo Private Limited brand. All rights reserved.'),
            'google_analytics' => self::setting('bconnect_google_analytics', ''),
            'google_site_verification' => self::setting('bconnect_google_site_verification', ''),
            'brand_color' => $brandColor,
            'brand_color_dark' => self::setting('bconnect_brand_color_dark', '#6d28d9'),
            'brand_color_light' => self::setting('bconnect_brand_color_light', '#a78bfa'),
            'brand_rgb' => self::hexToRgb($brandColor),
            'brand_rgb_light' => self::hexToRgb(self::setting('bconnect_brand_color_light', '#a78bfa')),
        ];
    }
}
