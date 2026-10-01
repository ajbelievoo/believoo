<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SupportPortalController extends Controller
{
    public function home()
    {
        $products = [
            ['key' => 'believoo', 'name' => 'Believoo', 'icon' => 'fa-briefcase', 'url' => 'https://believoo.com', 'desc' => 'Hosting, domains & digital services'],
            ['key' => 'ghc', 'name' => 'GHC Cloud', 'icon' => 'fa-cloud', 'url' => 'https://ghc.believoo.com', 'desc' => 'VPS, dedicated & cloud servers'],
            ['key' => 'bconnect', 'name' => 'Bmydesk', 'icon' => 'fa-comments', 'url' => 'https://bc.believoo.com', 'desc' => 'Team collaboration & meetings'],
            ['key' => 'mail', 'name' => 'Webmail', 'icon' => 'fa-envelope', 'url' => 'https://mail.believoo.com', 'desc' => 'Email access for your domain'],
        ];

        $stats = $this->serviceStatus();
        $overall = collect($stats)->contains(fn ($s) => $s['status'] === 'down') ? 'degraded' : 'operational';

        return view('support.home', compact('products', 'stats', 'overall'));
    }

    public function kbIndex(Request $request)
    {
        $query = KnowledgeArticle::where('is_active', true);

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('keywords', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $articles = $query->latest()->paginate(12);
        $categories = KnowledgeArticle::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('support.kb', compact('articles', 'categories'));
    }

    public function kbShow(KnowledgeArticle $article)
    {
        if (!$article->is_active) {
            abort(404);
        }

        $article->increment('used_count');

        $related = KnowledgeArticle::where('is_active', true)
            ->where('id', '!=', $article->id)
            ->where(function ($q) use ($article) {
                $q->where('category', $article->category)
                  ->orWhere('keywords', 'like', '%' . ($article->keywords ?? '') . '%');
            })
            ->limit(4)
            ->get();

        return view('support.kb-show', compact('article', 'related'));
    }

    public function status()
    {
        $services = $this->serviceStatus();
        $overall = collect($services)->contains(fn ($s) => $s['status'] === 'down') ? 'degraded' : 'operational';
        $incidents = \App\Models\Setting::where('key', 'like', 'status_incident_%')->pluck('value', 'key')->toArray();

        return view('support.status', compact('services', 'overall', 'incidents'));
    }

    public function tickets()
    {
        $tickets = Auth::check()
            ? Ticket::where('email', Auth::user()->email)->orderBy('created_at', 'desc')->get()
            : collect();

        return view('support.tickets', compact('tickets'));
    }

    public function contact()
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        return view('support.contact', compact('settings'));
    }

    protected function serviceStatus(): array
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $list = [
            ['key' => 'believoo', 'name' => 'Believoo Website', 'url' => 'https://believoo.com'],
            ['key' => 'ghc', 'name' => 'GHC Cloud', 'url' => 'https://ghc.believoo.com'],
            ['key' => 'bconnect', 'name' => 'Bmydesk', 'url' => 'https://bc.believoo.com'],
            ['key' => 'webmail', 'name' => 'Webmail', 'url' => 'https://mail.believoo.com'],
            ['key' => 'mail-server', 'name' => 'Mail Server (SMTP/IMAP)', 'url' => 'mail.believoo.com:587'],
            ['key' => 'dns', 'name' => 'DNS & Domains', 'url' => 'https://believoo.com'],
        ];

        $services = [];
        foreach ($list as $service) {
            $override = $settings['status_' . $service['key']] ?? null;
            $status = $override ? strtolower($override) : $this->checkService($service['url']);

            $services[] = array_merge($service, [
                'status' => $status,
                'badge' => $this->statusBadge($status),
            ]);
        }

        return $services;
    }

    protected function checkService(string $url): string
    {
        try {
            if (str_contains($url, '://')) {
                $response = Http::timeout(5)->get($url);
                return $response->successful() ? 'operational' : 'degraded';
            }

            [$host, $port] = array_pad(explode(':', $url), 2, 80);
            $connection = @fsockopen($host, (int) $port, $errno, $errstr, 3);
            if ($connection) {
                fclose($connection);
                return 'operational';
            }
            return 'degraded';
        } catch (\Throwable $e) {
            return 'degraded';
        }
    }

    protected function statusBadge(string $status): array
    {
        return match ($status) {
            'operational' => ['Operational', 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'],
            'degraded' => ['Degraded', 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20'],
            'down' => ['Down', 'bg-red-500/10 text-red-400 border-red-500/20'],
            'maintenance' => ['Maintenance', 'bg-blue-500/10 text-blue-400 border-blue-500/20'],
            default => ['Unknown', 'bg-gray-500/10 text-gray-400 border-gray-500/20'],
        };
    }
}
