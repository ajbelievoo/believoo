<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserDomain;
use App\Models\DnsRecord;
use Illuminate\Support\Facades\Log;

/**
 * SyncDnsZones — syncs BelieVoo database DNS records to PowerDNS zone files.
 *
 * Usage:
 *   php artisan dns:sync-zones              # sync all domains
 *   php artisan dns:sync-zones unilive.me   # sync one domain
 */
class SyncDnsZones extends Command
{
    protected $signature   = 'dns:sync-zones {domain? : Optional domain name to sync}';
    protected $description = 'Sync DNS records from database to PowerDNS zone files';

    private string $zoneDir    = '/var/named/chroot/var/named';
    private string $zonesConf  = '/var/named/chroot/etc/named.rfc1912.zones';
    private string $ns1        = 'ns1.believoo.com.';
    private string $ns2        = 'ns2.believoo.com.';
    private string $adminEmail = 'admin.believoo.com.';

    public function handle(): int
    {
        $targetDomain = $this->argument('domain');

        $query = UserDomain::where('use_believoo_dns', true)->where('status', 'active');
        if ($targetDomain) {
            $query->where('domain_name', $targetDomain);
        }

        $domains = $query->get();

        if ($domains->isEmpty()) {
            $this->warn('No domains found to sync.');
            return 0;
        }

        foreach ($domains as $domain) {
            $this->syncDomain($domain);
        }

        // pdns_control reload is non-disruptive (no full restart needed)
        $this->info('DNS zones synced successfully.');

        return 0;
    }

    private function syncDomain(UserDomain $domain): void
    {
        $name    = $domain->domain_name;
        $records = DnsRecord::where('user_domain_id', $domain->id)
            ->where('is_active', true)
            ->orderBy('record_type')
            ->orderBy('name')
            ->get();

        $serial   = date('Ymd') . '01';
        $zoneFile = $this->zoneDir . '/' . $name . '.zone';

        // Build zone file content
        $lines   = [];
        $lines[] = '$TTL 3600';
        $lines[] = $name . '.     IN SOA  ' . $this->ns1 . '  ' . $this->adminEmail . ' (';
        $lines[] = '                        ' . $serial . '  ; serial';
        $lines[] = '                        3600        ; refresh';
        $lines[] = '                        1800        ; retry';
        $lines[] = '                        1209600     ; expire';
        $lines[] = '                        1800 )      ; minimum';
        $lines[] = '';
        $lines[] = '; Nameservers';
        $lines[] = $name . '.             86400   IN  NS      ' . $this->ns1;
        $lines[] = $name . '.             86400   IN  NS      ' . $this->ns2;
        $lines[] = '';
        $lines[] = '; DNS Records';

        foreach ($records as $rec) {
            $recName = $rec->name === '@' ? $name . '.' : $rec->name;
            $ttl     = $rec->ttl ?? 3600;
            $type    = $rec->record_type;
            $value   = $rec->value;

            // Ensure FQDN for certain record types
            if (in_array($type, ['CNAME', 'MX', 'NS']) && !str_ends_with($value, '.')) {
                $value .= '.';
            }
            // TXT records need quotes
            if ($type === 'TXT' && !str_starts_with($value, '"')) {
                $value = '"' . $value . '"';
            }
            // Skip PTR records — they go in reverse zones
            if ($type === 'PTR') continue;

            // MX/SRV needs priority
            $priority = ($rec->priority !== null) ? $rec->priority . ' ' : '';

            $lines[] = $recName . "\t" . $ttl . "\tIN\t" . $type . "\t" . $priority . $value;
        }

        $content = implode("\n", $lines) . "\n";

        // Write zone file directly (www-data has write access via group)
        $written = @file_put_contents($zoneFile, $content);
        if ($written === false) {
            // Try via sudo
            $tmpFile = tempnam(sys_get_temp_dir(), 'zone_');
            file_put_contents($tmpFile, $content);
            exec('sudo -n cp ' . escapeshellarg($tmpFile) . ' ' . escapeshellarg($zoneFile) . ' 2>&1', $out, $code);
            @unlink($tmpFile);
            if ($code !== 0) {
                $this->error("Failed to write zone file for {$name}: " . implode(' ', $out));
                return;
            }
        }

        // CRITICAL: ensure the zone file is readable by the PowerDNS (pdns) user AND
        // re-writable by the www-data group (web user). Files copied via `sudo cp`
        // from tempnam default to 0600 root:root, which PowerDNS cannot read ->
        // "Permission denied" -> SERVFAIL. Force 0664 (group write + world read).
        if (!@chmod($zoneFile, 0664)) {
            exec('sudo -n chmod 664 ' . escapeshellarg($zoneFile) . ' 2>&1');
        }
        exec('sudo -n chown root:www-data ' . escapeshellarg($zoneFile) . ' 2>&1');

        // Add to zones config if not already there
        $zonesContent = @file_get_contents($this->zonesConf) ?: '';
        $isNewZone    = !str_contains($zonesContent, '"' . $name . '"');
        if ($isNewZone) {
            $entry = "\nzone \"{$name}\" IN {\n        type master;\n        file \"{$this->zoneDir}/{$name}.zone\";\n        allow-update { none; };\n};\n";
            $appended = @file_put_contents($this->zonesConf, $entry, FILE_APPEND);
            if ($appended === false) {
                exec('sudo -n bash -c ' . escapeshellarg('printf ' . escapeshellarg($entry) . ' >> ' . $this->zonesConf) . ' 2>&1');
            }
            $this->info("Added {$name} to zones config.");
        }

        // Reload PowerDNS. `reload` re-reads CHANGED zones (non-disruptive), but a
        // brand-new zone added to the config is only picked up by `rediscover`.
        if ($isNewZone) {
            exec('sudo -n pdns_control rediscover 2>&1', $rediscoverOut);
        }
        exec('sudo -n pdns_control reload 2>&1', $pdnsOut, $pdnsCode);

        $this->info("✓ Synced {$name} — " . $records->count() . ' records');
        Log::info('dns:sync-zones', ['domain' => $name, 'records' => $records->count()]);
    }
}
