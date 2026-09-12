<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementLog;
use Illuminate\Support\Facades\Http;

class MailDeliverabilityController extends Controller
{
    protected string $domain = 'believoo.com';
    protected string $mailHost = 'mail.believoo.com';
    protected string $ip = '139.99.43.203';

    public function index()
    {
        $checks = $this->runChecks();
        $score = $this->calculateScore($checks);
        $recommendations = $this->recommendations($checks);
        $recentLog = $this->recentLogLines();
        $recentBounces = AnnouncementLog::where('level', 'error')->latest()->take(50)->get();

        return view('admin.mail.deliverability', compact('checks', 'score', 'recommendations', 'recentLog', 'recentBounces'));
    }

    protected function runChecks(): array
    {
        $spf = $this->hasTxtRecord($this->domain, 'v=spf1');
        $dmarc = $this->hasTxtRecord('_dmarc.' . $this->domain, 'v=DMARC1');
        $dkim = $this->hasTxtRecord('default._domainkey.' . $this->domain, 'v=DKIM1') || $this->hasTxtRecord('mail._domainkey.' . $this->domain, 'v=DKIM1');
        $mx = $this->getMxRecords();
        $ptr = $this->getPtrRecord();

        $rbls = [
            'zen.spamhaus.org',
            'bl.spamcop.net',
            'b.barracudacentral.org',
            'dnsbl.sorbs.net',
            'bl.spamcannibal.org',
            'dnsbl-1.uceprotect.net',
        ];

        $blacklist = $this->checkBlacklists($this->ip, $rbls);

        $mailServerUp = $this->portCheck($this->mailHost, 587);

        $certExpiry = $this->certExpiryDays();

        return [
            'domain' => $this->domain,
            'ip' => $this->ip,
            'mail_host' => $this->mailHost,
            'spf' => $spf,
            'dmarc' => $dmarc,
            'dkim' => $dkim,
            'mx' => $mx,
            'mx_count' => count($mx),
            'ptr' => $ptr,
            'ptr_matches' => $ptr && (str_contains(strtolower($ptr), strtolower($this->mailHost)) || str_contains(strtolower($ptr), strtolower($this->domain))),
            'blacklist' => $blacklist,
            'blacklist_status' => count($blacklist['listed'] ?? []) === 0 ? 'clean' : 'listed',
            'mail_server_up' => $mailServerUp,
            'cert_expiry_days' => $certExpiry,
            'rbls_checked' => $rbls,
        ];
    }

    protected function hasTxtRecord(string $name, string $needle): bool
    {
        $records = dns_get_record($name, DNS_TXT);
        if ($records === false || empty($records)) {
            return false;
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? '';
            if (str_contains(strtolower($txt), strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    protected function getMxRecords(): array
    {
        $records = dns_get_record($this->domain, DNS_MX);
        if ($records === false || empty($records)) {
            return [];
        }

        return collect($records)
            ->sortBy('pri')
            ->map(fn ($r) => $r['target'] ?? $r['host'] ?? 'unknown')
            ->filter()
            ->values()
            ->toArray();
    }

    protected function getPtrRecord(): ?string
    {
        $octets = explode('.', $this->ip);
        $reverse = implode('.', array_reverse($octets)) . '.in-addr.arpa';
        $records = dns_get_record($reverse, DNS_PTR);

        if (empty($records)) {
            return null;
        }

        return $records[0]['target'] ?? null;
    }

    protected function checkBlacklists(string $ip, array $rbls): array
    {
        $octets = explode('.', $ip);
        $reverse = implode('.', array_reverse($octets));
        $listed = [];

        foreach ($rbls as $rbl) {
            $lookup = $reverse . '.' . $rbl;
            if (checkdnsrr($lookup, 'A')) {
                $listed[] = $rbl;
            }
        }

        return [
            'listed' => $listed,
            'total' => count($rbls),
        ];
    }

    protected function portCheck(string $host, int $port): bool
    {
        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, 3);
            if ($connection) {
                fclose($connection);
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return false;
    }

    protected function certExpiryDays(): ?int
    {
        $certFile = '/www/server/panel/vhost/cert/' . $this->mailHost . '/fullchain.pem';

        if (! is_readable($certFile)) {
            return null;
        }

        $cert = file_get_contents($certFile);
        if (! $cert) {
            return null;
        }

        $parsed = openssl_x509_parse($cert);
        if (empty($parsed['validTo_time_t'])) {
            return null;
        }

        return now()->diffInDays(
            \Carbon\Carbon::createFromTimestamp($parsed['validTo_time_t']),
            false
        );
    }

    protected function recentLogLines(): array
    {
        $files = ['/var/log/mail.log', '/var/log/mail/mail.log'];
        $lines = [];

        foreach ($files as $file) {
            if (is_readable($file)) {
                $content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($content) {
                    $lines = array_merge($lines, array_slice($content, -100));
                }
            }
        }

        if (empty($lines)) {
            return [];
        }

        // Filter to lines that indicate bounces, rejects, deferred or spam-related status
        return collect($lines)
            ->filter(fn ($line) => preg_match('/(bounced|deferred|rejected|spam|550|551|552|553|554|421|450|451|452)/i', $line))
            ->reverse()
            ->take(20)
            ->map(fn ($line) => htmlspecialchars(substr($line, -200)))
            ->values()
            ->toArray();
    }

    protected function calculateScore(array $checks): int
    {
        $score = 0;

        if ($checks['spf']) $score += 15;
        if ($checks['dmarc']) $score += 15;
        if ($checks['dkim']) $score += 15;
        if ($checks['mx_count'] > 0) $score += 10;
        if ($checks['ptr_matches']) $score += 10;
        if ($checks['blacklist_status'] === 'clean') $score += 15;
        if ($checks['mail_server_up']) $score += 10;
        if ($checks['cert_expiry_days'] !== null && $checks['cert_expiry_days'] > 7) $score += 10;

        return min(100, $score);
    }

    protected function recommendations(array $checks): array
    {
        $recs = [];

        if (! $checks['spf']) {
            $recs[] = 'Add an SPF TXT record for ' . $this->domain . ' authorising ' . $this->mailHost . '.';
        }

        if (! $checks['dmarc']) {
            $recs[] = 'Add a DMARC TXT record for _dmarc.' . $this->domain . ' (e.g. v=DMARC1; p=quarantine; rua=mailto:admin@' . $this->domain . ').';
        }

        if (! $checks['dkim']) {
            $recs[] = 'Configure a DKIM selector and publish its public key in a TXT record for _domainkey.' . $this->domain . '.';
        }

        if (! $checks['ptr_matches']) {
            $recs[] = 'Set the reverse DNS (PTR) for ' . $this->ip . ' to ' . $this->mailHost . '.';
        }

        if ($checks['blacklist_status'] === 'listed') {
            $recs[] = 'Your IP appears on the following blocklists: ' . implode(', ', $checks['blacklist']['listed']) . '. Request delisting after fixing the cause.';
        }

        if ($checks['cert_expiry_days'] !== null && $checks['cert_expiry_days'] <= 14) {
            $recs[] = 'TLS certificate for ' . $this->mailHost . ' expires in ' . $checks['cert_expiry_days'] . ' days. Renew soon.';
        }

        if (empty($recs)) {
            $recs[] = 'All core deliverability checks passed. Keep monitoring bounce and spam complaint rates.';
        }

        return $recs;
    }
}
