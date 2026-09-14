<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateCloudflareMailDns extends Command
{
    protected $signature = 'cloudflare:update-mail-dns {--dry-run}';
    protected $description = 'Update Cloudflare SPF and DMARC records for better email deliverability';

    public function handle()
    {
        $token = \App\Models\Setting::getValue('cloudflare_api_token');
        $zoneId = \App\Models\Setting::getValue('cloudflare_zone_id');

        if (empty($token) || empty($zoneId)) {
            $this->error('cloudflare_api_token and cloudflare_zone_id settings are required.');
            return 1;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ];

        $records = [
            // SPF
            [
                'type' => 'TXT',
                'name' => 'believoo.com',
                'content' => 'v=spf1 a:cloud.believoo.com a:mail.believoo.com ip4:139.99.43.203 mx -all',
                'ttl' => 300,
            ],
            // DMARC
            [
                'type' => 'TXT',
                'name' => '_dmarc.believoo.com',
                'content' => 'v=DMARC1; p=none; rua=mailto:admin@believoo.com; ruf=mailto:admin@believoo.com; adkim=r; aspf=r; pct=100;',
                'ttl' => 300,
            ],
        ];

        foreach ($records as $record) {
            if ($this->option('dry-run')) {
                $this->info('[DRY-RUN] ' . $record['type'] . ' ' . $record['name'] . ' => ' . $record['content']);
                continue;
            }

            // Find existing record
            $response = Http::withHeaders($headers)
                ->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", [
                    'type' => $record['type'],
                    'name' => $record['name'],
                ]);

            if (!$response->successful()) {
                $this->error('Failed to list DNS records for ' . $record['name'] . ': ' . $response->body());
                Log::error('Cloudflare DNS list failed', ['record' => $record, 'response' => $response->json()]);
                continue;
            }

            $existing = collect($response->json('result'))->first();

            if ($existing) {
                $update = Http::withHeaders($headers)
                    ->put("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records/{$existing['id']}", $record);

                if ($update->successful()) {
                    $this->info('Updated ' . $record['name']);
                } else {
                    $this->error('Failed to update ' . $record['name'] . ': ' . $update->body());
                    Log::error('Cloudflare DNS update failed', ['record' => $record, 'response' => $update->json()]);
                }
            } else {
                $create = Http::withHeaders($headers)
                    ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", $record);

                if ($create->successful()) {
                    $this->info('Created ' . $record['name']);
                } else {
                    $this->error('Failed to create ' . $record['name'] . ': ' . $create->body());
                    Log::error('Cloudflare DNS create failed', ['record' => $record, 'response' => $create->json()]);
                }
            }
        }

        $this->info('Done.');
        return 0;
    }
}
