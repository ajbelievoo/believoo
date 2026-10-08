<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use App\Models\VpsPlan;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];

        $static = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('about'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('services.index'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('portfolio.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('vps-plans.index'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('contact'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('policy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('refund'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('acceptable-use'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('sla'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('blog.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('status'), 'priority' => '0.6', 'changefreq' => 'hourly'],
            ['loc' => route('kb.index'), 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['loc' => route('faq'), 'priority' => '0.6', 'changefreq' => 'weekly'],
        ];

        foreach ($static as $page) {
            $urls[] = $page;
        }

        foreach (Service::where('is_active', true)->orWhereNull('is_active')->cursor() as $service) {
            $urls[] = [
                'loc' => route('services.show', $service->slug),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $service->updated_at ? $service->updated_at->toAtomString() : Carbon::now()->toAtomString(),
            ];
        }

        foreach (VpsPlan::where('is_active', true)->orWhereNull('is_active')->cursor() as $plan) {
            $urls[] = [
                'loc' => route('vps-plans.show', $plan->slug),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $plan->updated_at ? $plan->updated_at->toAtomString() : Carbon::now()->toAtomString(),
            ];
        }

        foreach (Portfolio::where('is_visible', true)->orWhereNull('is_visible')->cursor() as $item) {
            $urls[] = [
                'loc' => route('portfolio.show', $item->slug),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $item->updated_at ? $item->updated_at->toAtomString() : Carbon::now()->toAtomString(),
            ];
        }

        foreach (Post::published()->cursor() as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $post->updated_at ? $post->updated_at->toAtomString() : Carbon::now()->toAtomString(),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . e($url['loc']) . '</loc>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            if (!empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            }
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
