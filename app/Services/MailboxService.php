<?php

namespace App\Services;

use App\Models\MailDomain;
use App\Models\Mailbox;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MailboxService
{
    /**
     * Create a new mailbox on the mail server (Dovecot/Postfix via SQLite backend).
     * The Maildir on disk is auto-created by Dovecot on first login/delivery.
     */
    public function createMailbox(
        string $localPart,
        string $domain,
        string $password,
        string $fullName = '',
        ?float $quotaGb = null,
        bool $active = true
    ): Mailbox {
        $localPart = strtolower(trim($localPart));
        $domain = strtolower(trim($domain));
        $username = $localPart . '@' . $domain;

        if (!preg_match('/^[a-z0-9._-]+$/', $localPart)) {
            throw ValidationException::withMessages([
                'local_part' => 'Only lowercase letters, numbers, dot, dash and underscore are allowed.',
            ]);
        }

        $this->assertPasswordStrength($password);

        if (Mailbox::where('username', $username)->exists()) {
            throw ValidationException::withMessages([
                'local_part' => "The email account {$username} already exists.",
            ]);
        }

        $mailDomain = MailDomain::where('domain', $domain)->where('active', 1)->first();

        if (!$mailDomain) {
            throw ValidationException::withMessages([
                'domain' => "The domain {$domain} is not configured on the mail server.",
            ]);
        }

        $currentCount = Mailbox::where('domain', $domain)->count();
        if ($mailDomain->mailboxes && $currentCount + 1 > $mailDomain->mailboxes) {
            throw ValidationException::withMessages([
                'local_part' => "Mailbox limit reached for {$domain} ({$mailDomain->mailboxes} max).",
            ]);
        }

        $now = now()->format('Y-m-d H:i:s');
        $quota = $quotaGb ? (int) ($quotaGb * 1073741824) : (int) ($mailDomain->mailbox_quota ?? 0);

        return Mailbox::create([
            'username' => $username,
            'password' => $this->cryptPassword($password),
            'password_encode' => $this->encodePassword($password),
            'full_name' => $fullName,
            'is_admin' => 0,
            'maildir' => $username . '/',
            'quota' => $quota,
            'current_usage' => 0,
            'quota_active' => 1,
            'local_part' => $localPart,
            'domain' => $domain,
            'created' => $now,
            'modified' => $now,
            'active' => $active ? 1 : 0,
        ]);
    }

    /**
     * Reset a mailbox password.
     */
    public function resetPassword(Mailbox $mailbox, string $password): void
    {
        $this->assertPasswordStrength($password);

        $mailbox->update([
            'password' => $this->cryptPassword($password),
            'password_encode' => $this->encodePassword($password),
            'modified' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete a mailbox account. The mail data directory on disk is kept
     * (owned by vmail); it can be removed manually if needed.
     */
    public function deleteMailbox(Mailbox $mailbox): void
    {
        $mailbox->delete();
    }

    /**
     * Generate a strong random password (uppercase + lowercase + digit, >= 8 chars).
     */
    public static function generatePassword(int $length = 14): string
    {
        $password = Str::random($length - 2);
        $password .= random_int(0, 9) . chr(random_int(65, 90));

        return str_shuffle($password);
    }

    /**
     * Password rule required by the mail server plugin:
     * at least 8 chars with uppercase, lowercase and a digit.
     */
    private function assertPasswordStrength(string $password): void
    {
        if (strlen($password) < 8 || !preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', $password)) {
            throw ValidationException::withMessages([
                'password' => 'Password must be at least 8 characters and include uppercase, lowercase and a number.',
            ]);
        }
    }

    /**
     * MD5-CRYPT hash — the scheme Dovecot expects (default_pass_scheme = MD5-CRYPT).
     */
    private function cryptPassword(string $password): string
    {
        $salt = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./'), 0, 8);

        return crypt($password, '$1$' . $salt . '$');
    }

    /**
     * hex(base64(plaintext)) — used by the mail admin UI to display passwords.
     */
    private function encodePassword(string $password): string
    {
        return bin2hex(base64_encode($password));
    }
}
