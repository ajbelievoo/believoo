<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserDomain;
use App\Models\DnsRecord;
use Illuminate\Support\Facades\Log;

/**
 * ProvisionDnsDomain — fully onboard a domain into BelieVoo DNS (PowerDNS).
 *
 * Creates/updates the UserDomain (use_believoo_dns = true), builds a complete
 * starter zone (NS + root/www/ns A records) and pushes it live to PowerDNS.
 *
 * Usage:
 *   php artisan dns:provision blizolive.com 139.99.43.203
 *   php artisan dns:provision blizolive.com 1.2.3.4 --user=5
 *   php artisan dns:provision blizolive.com 1.2.3.4 --ns-ip=139.99.43.203
 */
class ProvisionDnsDomain extends Command
{
    protected $signature = 'dns:provision
        {domain : Domain name to provision (e.g. blizolive.com)}
        {ip : Website/hosting IP for @ and www records}
        {--user= : Owner user ID (defaults to the first user)}
        {--ns-ip= : DNS server IP for ns1/ns2 A records (defaults to website IP)}';

    protected $description = 'Provision a domain into BelieVoo DNS: create UserDomain + default records + sync PowerDNS zone';

    public function handle(): int
    {
        $domain = strtolower(trim($this->argument('domain')));
        $ip     = trim($this->argument('ip'));
        $nsIp   = $this->option('ns-ip') ? trim($this->option('ns-ip')) : null;

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error("Invalid website IP: {$ip}");
            return self::FAILURE;
        }
        if ($nsIp !== null && !filter_var($nsIp, FILTER_VALIDATE_IP)) {
            $this->error("Invalid --ns-ip: {$nsIp}");
            return self::FAILURE;
        }

        // Resolve owner user
        $userId = $this->option('user');
        if (!$userId) {
            $userId = optional(User::orderBy('id')->first())->id;
        }
        if (!$userId) {
            $this->error('No user found. Pass --user=ID');
            return self::FAILURE;
        }

        // Derive tld / sld
        $parts = explode('.', $domain);
        $tld   = count($parts) >= 2 ? implode('.', array_slice($parts, -2)) : $domain;
        $sld   = $parts[0];

        // Create or fetch the domain record
        $userDomain = UserDomain::firstOrCreate(
            ['domain_name' => $domain],
            [
                'user_id'            => $userId,
                'domain_provider_id' => null,
                'tld'                => $tld,
                'sld'                => $sld,
                'status'             => 'active',
                'nameservers'        => DnsRecord::BELIEVOO_NS,
                'use_believoo_dns'   => true,
                'use_provider_dns'   => false,
            ]
        );

        // Ensure flags are correct (in case it pre-existed with wrong settings)
        $userDomain->update([
            'status'           => 'active',
            'use_believoo_dns' => true,
            'use_provider_dns' => false,
            'nameservers'      => DnsRecord::BELIEVOO_NS,
        ]);

        // Build the starter zone records (idempotent)
        DnsRecord::createDefaultWebRecords($userId, $userDomain->id, $ip, $nsIp);

        $this->info("✓ Provisioned {$domain} (user #{$userId}) -> {$ip}");
        Log::info('dns:provision', [
            'domain'  => $domain,
            'user_id' => $userId,
            'ip'      => $ip,
            'ns_ip'   => $nsIp ?? $ip,
        ]);

        // Push the zone to PowerDNS immediately
        $this->call('dns:sync-zones', ['domain' => $domain]);

        return self::SUCCESS;
    }
}
