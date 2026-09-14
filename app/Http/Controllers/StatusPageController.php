<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class StatusPageController extends Controller
{
    protected array $services = [
        'believoo' => [
            'name' => 'Believoo Website',
            'url' => 'https://believoo.com',
        ],
        'ghc' => [
            'name' => 'GHC Cloud',
            'url' => 'https://ghc.believoo.com',
        ],
        'bconnect' => [
            'name' => 'B-Connect',
            'url' => 'https://bc.believoo.com',
        ],
        'webmail' => [
            'name' => 'Webmail',
            'url' => 'https://mail.believoo.com',
        ],
        'mail-server' => [
            'name' => 'Mail Server (SMTP/IMAP)',
            'url' => 'mail.believoo.com:587',
        ],
        'dns' => [
            'name' => 'DNS & Domains',
            'url' => 'https://believoo.com',
        ],
    ];

    public function index()
    {
        if (request()->getHost() !== 'support.believoo.com') {
            return redirect('https://support.believoo.com/status');
        }

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $services = [];

        foreach ($this->services as $key => $service) {
            $statusKey = 'status_' . $key;
            $override = $settings[$statusKey] ?? null;

            if ($override) {
                $status = strtolower($override);
            } else {
                $status = $this->checkService($service['url']);
            }

            $services[] = [
                'key' => $key,
                'name' => $service['name'],
                'url' => $service['url'],
                'status' => $status,
                'badge' => $this->statusLabel($status),
            ];
        }

        $overall = collect($services)->contains(fn ($s) => $s['status'] === 'down') ? 'degraded' : 'operational';

        $incidents = \App\Models\Setting::where('key', 'like', 'status_incident_%')->pluck('value', 'key')->toArray();

        return view('status.index', compact('services', 'overall', 'incidents'));
    }

    protected function checkService(string $url): string
    {
        try {
            if (str_contains($url, '://')) {
                $response = Http::timeout(5)->get($url);
                return $response->successful() ? 'operational' : 'degraded';
            }

            // For raw host:port, try socket connection
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

    protected function statusLabel(string $status): array
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
