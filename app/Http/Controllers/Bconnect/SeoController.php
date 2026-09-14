<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Helpers\BconnectHelper;

class SeoController extends Controller {
    public function robots() {
        $txt = "User-agent: *\nDisallow: /login\nDisallow: /register\nDisallow: /dashboard\nDisallow: /admin\nDisallow: /remote/*\nDisallow: /meetings/*\nAllow: /\n\nSitemap: https://bc.believoo.com/sitemap.xml\n";
        return response($txt, 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap() {
        $brand = BconnectHelper::brandData();
        $urls = [
            ['loc' => 'https://bc.believoo.com/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => 'https://bc.believoo.com/login', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => 'https://bc.believoo.com/register', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => 'https://bc.believoo.com/remote/agent', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => 'https://bc.believoo.com/support', 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => 'https://believoo.com/status', 'priority' => '0.6', 'changefreq' => 'daily'],
        ];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '<url><loc>' . e($u['loc']) . '</loc><lastmod>' . now()->toDateString() . '</lastmod><changefreq>' . $u['changefreq'] . '</changefreq><priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>';
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
