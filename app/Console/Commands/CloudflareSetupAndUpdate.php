<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CloudflareSetupAndUpdate extends Command
{
    protected $signature = 'cloudflare:setup-and-update {token} {zone}';
    protected $description = 'Save Cloudflare token, update mail DNS records, and test them';

    public function handle()
    {
        $token = $this->argument('token');
        $zone = $this->argument('zone');

        \App\Models\Setting::updateOrCreate(
            ['key' => 'cloudflare_api_token'],
            ['value' => $token]
        );
        \App\Models\Setting::updateOrCreate(
            ['key' => 'cloudflare_zone_id'],
            ['value' => $zone]
        );

        $this->info('Saved token and zone ID.');

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ];

        $records = [
            [
                'type' => 'TXT',
                'name' => 'believoo.com',
                'content' => 'v=spf1 a:cloud.believoo.com a:mail.believoo.com ip4:139.99.43.203 mx -all',
                'ttl' => 300,
            ],
            [
                'type' => 'TXT',
                'name' => '_dmarc.believoo.com',
                'content' => 'v=DMARC1; p=none; rua=mailto:admin@believoo.com; ruf=mailto:admin@believoo.com; adkim=r; aspf=r; pct=100;',
                'ttl' => 300,
            ],
        ];

        foreach ($records as $record) {
            $response = Http::withHeaders($headers)
                ->get("https://api.cloudflare.com/client/v4/zones/{$zone}/dns_records", [
                    'type' => $record['type'],
                    'name' => $record['name'],
                ]);

            if (!$response->successful()) {
                $this->error('Failed to list ' . $record['name'] . ': ' . $response->body());
                continue;
            }

            $existing = collect($response->json('result'))->first();

            if ($existing) {
                $update = Http::withHeaders($headers)
                    ->put("https://api.cloudflare.com/client/v4/zones/{$zone}/dns_records/{$existing['id']}", $record);
                $this->info($update->successful() ? 'Updated ' . $record['name'] : 'Update failed: ' . $update->body());
            } else {
                $create = Http::withHeaders($headers)
                    ->post("https://api.cloudflare.com/client/v4/zones/{$zone}/dns_records", $record);
                $this->info($create->successful() ? 'Created ' . $record['name'] : 'Create failed: ' . $create->body());
            }
        }

        $this->info('Done. DNS propagation can take a few minutes.');
        return 0;
    }
}
