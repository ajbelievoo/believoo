<?php

namespace App\Console\Commands;

use App\Models\Bconnect\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Email → ticket. Reads the support@believoo.com Maildir directly
 * (Postfix/Dovecot on this server) — no IMAP auth needed.
 * Processed messages move from new/ to cur/ with a .seen suffix.
 */
class BconnectMailTickets extends Command
{
    protected $signature = 'bconnect:mail-tickets';
    protected $description = 'Import support@believoo.com emails as portal tickets';

    public function handle(): int
    {
        $dir = '/www/vmail/believoo.com/support/new';
        if (!is_dir($dir)) { $this->warn('maildir missing'); return 0; }

        $company = DB::table('bconnect_companies')->orderBy('id')->first();
        if (!$company) return 0;

        $count = 0;
        foreach (glob($dir . '/*') ?: [] as $f) {
            try {
                $raw = file_get_contents($f);
                [$hdr, $body] = array_pad(preg_split("/\r?\n\r?\n/", $raw, 2), 2, '');

                $subject = $this->header($hdr, 'Subject') ?: '(no subject)';
                $subject = $this->decodeMime($subject);
                $from = $this->header($hdr, 'From');
                preg_match('/<([^>]+)>/', $from, $m);
                $email = $m[1] ?? $from;
                $name = trim(preg_replace('/<[^>]+>/', '', $from), ' "') ?: $email;

                // Skip bounces/noise
                if (stripos($email, 'mailer-daemon') !== false || stripos($subject, 'undelivered') !== false) {
                    rename($f, '/www/vmail/believoo.com/support/cur/' . basename($f) . ':2,S');
                    continue;
                }

                // Plain-text body preference
                $text = $this->bodyText($raw, $body);

                Ticket::create([
                    'company_id' => $company->id,
                    'title' => Str::limit($subject, 190, ''),
                    'description' => "From: {$name} <{$email}>\n\n" . Str::limit($text, 8000, ''),
                    'status' => 'open',
                    'priority' => 'normal',
                    'source' => 'email',
                    'public_token' => Str::random(24),
                    'reporter_name' => $name,
                    'reporter_email' => $email,
                ]);

                $cur = '/www/vmail/believoo.com/support/cur/' . basename($f) . ':2,S';
                rename($f, $cur);
                $count++;
            } catch (\Throwable $e) {
                $this->warn('skip ' . basename($f) . ': ' . $e->getMessage());
            }
        }
        $this->info("imported $count ticket(s)");
        return 0;
    }

    private function header(string $hdr, string $name): string
    {
        if (preg_match('/^' . preg_quote($name, '/') . ':\s*(.*?)(\r?\n\S|$)/ims', $hdr, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    private function decodeMime(string $s): string
    {
        if (function_exists('iconv_mime_decode')) return iconv_mime_decode($s, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $s;
        if (function_exists('mb_decode_mimeheader')) return mb_decode_mimeheader($s);
        return $s;
    }

    private function bodyText(string $raw, string $body): string
    {
        // multipart → prefer text/plain part
        if (preg_match('/boundary="?([^";]+)"?/i', $raw, $bm)) {
            $parts = explode('--' . $bm[1], $body);
            foreach ($parts as $p) {
                if (stripos($p, 'Content-Type: text/plain') !== false) {
                    $chunks = preg_split("/\r?\n\r?\n/", $p, 2);
                    $text = $chunks[1] ?? '';
                    if (stripos($p, 'base64') !== false) $text = base64_decode($text);
                    if (stripos($p, 'quoted-printable') !== false) $text = quoted_printable_decode($text);
                    return trim($text);
                }
            }
        }
        $text = $body;
        if (preg_match('/base64/i', $raw) && !preg_match('/[a-z]{5,}\s[a-z]{5,}/i', $text)) {
            $dec = base64_decode($text, true);
            if ($dec !== false) $text = $dec;
        }
        $text = strip_tags($text);
        return trim($text) ?: '(empty)';
    }
}
