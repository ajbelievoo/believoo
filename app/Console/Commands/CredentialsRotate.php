<?php

namespace App\Console\Commands;

use App\Models\DomainProvider;
use App\Models\Mailbox;
use App\Models\Setting;
use App\Services\MailboxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Minishlink\WebPush\VAPID;

class CredentialsRotate extends Command
{
    protected $signature = 'credentials:rotate
                            {--local-only : Only rotate fully local credentials (VAPID, SMTP, APP_KEY)}
                            {--app-key : Rotate the Laravel APP_KEY}';
    protected $description = 'Rotate exposed credentials for SMTP, Google OAuth, Cloudflare and VAPID';

    public function handle()
    {
        $this->info('Starting credential rotation. Values are not shown.');

        $rotated = [];
        $warnings = [];

        if ($this->option('app-key')) {
            Artisan::call('key:generate', ['--force' => true]);
            $rotated[] = 'APP_KEY (Laravel app key) was regenerated. All sessions have been invalidated.';
        }

        // VAPID (local)
        try {
            $keys = VAPID::createVapidKeys();
            Setting::updateOrCreate(['key' => 'vapid_public_key'], ['value' => $keys['publicKey']]);
            Setting::updateOrCreate(['key' => 'vapid_private_key'], ['value' => $keys['privateKey']]);
            $this->updateDotEnv('VAPID_PUBLIC_KEY', $keys['publicKey']);
            $this->updateDotEnv('VAPID_PRIVATE_KEY', $keys['privateKey']);
            $rotated[] = 'VAPID keys (public + private) for browser push were regenerated. Existing push subscriptions must be re-registered by clients.';
        } catch (\Throwable $e) {
            $warnings[] = 'VAPID rotation failed: ' . $e->getMessage();
        }

        // SMTP (local — we control the mailbox on this server)
        try {
            $newPassword = MailboxService::generatePassword(16);

            // Update the mailbox the app uses
            $mailbox = Mailbox::where('username', 'noreply@believoo.com')->first();
            if ($mailbox) {
                app(MailboxService::class)->resetPassword($mailbox, $newPassword);
            } else {
                $warnings[] = 'noreply@believoo.com mailbox was not found; only DB / .env values were rotated.';
            }

            // Update the no-reply alias mailbox as well if it exists
            $noReply = Mailbox::where('username', 'no-reply@believoo.com')->first();
            if ($noReply) {
                app(MailboxService::class)->resetPassword($noReply, $newPassword);
            }

            Setting::updateOrCreate(['key' => 'mail_password'], ['value' => $newPassword]);
            $this->updateDotEnv('MAIL_PASSWORD', $newPassword);
            $rotated[] = 'SMTP password for noreply@believoo.com was rotated in the mailbox DB, settings and .env.';
        } catch (\Throwable $e) {
            $warnings[] = 'SMTP rotation failed: ' . $e->getMessage();
        }

        if (! $this->option('local-only')) {
            // Google OAuth client secret (external — set a random placeholder)
            try {
                $newGoogleSecret = $this->generateSafeSecret(40);
                Setting::updateOrCreate(['key' => 'google_client_secret'], ['value' => $newGoogleSecret]);
                $this->updateDotEnv('GOOGLE_CLIENT_SECRET', $newGoogleSecret);
                $warnings[] = 'Google OAuth client secret was replaced with a random placeholder. You MUST update it in the Google Cloud Console and then in Site Settings.';
                $rotated[] = 'google_client_secret (placeholder rotated)';
            } catch (\Throwable $e) {
                $warnings[] = 'Google client secret rotation failed: ' . $e->getMessage();
            }

            // Cloudflare API token (external — set a random placeholder)
            try {
                $newCloudflareToken = $this->generateSafeSecret(60);
                Setting::updateOrCreate(['key' => 'cloudflare_api_token'], ['value' => $newCloudflareToken]);

                // Update the Cloudflare domain provider row directly with an encrypted metadata
                // blob using the current APP_KEY. This avoids decrypting legacy data.
                $encryptedMetadata = \Illuminate\Support\Facades\Crypt::encryptString(json_encode([
                    'api_token' => $newCloudflareToken,
                    'api_key' => $newCloudflareToken,
                    'username' => 'admin@believoo.com',
                    'account_id' => '',
                ]));

                \Illuminate\Support\Facades\DB::table('domain_providers')
                    ->where('code', 'cloudflare')
                    ->update([
                        'api_key' => $newCloudflareToken,
                        'metadata' => $encryptedMetadata,
                        'updated_at' => now(),
                    ]);

                $this->updateDotEnv('CLOUDFLARE_API_TOKEN', $newCloudflareToken);
                $warnings[] = 'Cloudflare API token was replaced with a random placeholder. You MUST generate a new token in the Cloudflare dashboard and update it in Site Settings and the Domain Provider config.';
                $rotated[] = 'cloudflare_api_token (placeholder rotated)';
            } catch (\Throwable $e) {
                $warnings[] = 'Cloudflare API token rotation failed: ' . $e->getMessage();
            }
        }

        Artisan::call('config:clear');

        $this->newLine();
        $this->info('Rotated:');
        foreach ($rotated as $r) {
            $this->info('  - ' . $r);
        }

        if (! empty($warnings)) {
            $this->newLine();
            $this->warn('Notes / manual steps:');
            foreach ($warnings as $w) {
                $this->warn('  - ' . $w);
            }
        }

        $this->newLine();
        $this->info('Credential rotation complete.');

        return Command::SUCCESS;
    }

    private function updateDotEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        if (! File::exists($path)) {
            return;
        }

        $content = File::get($path);
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $key . '=' . $this->escapeEnvValue($value), $content);
        } else {
            $content .= PHP_EOL . $key . '=' . $this->escapeEnvValue($value) . PHP_EOL;
        }

        File::put($path, $content);
    }

    private function escapeEnvValue(string $value): string
    {
        return '"' . addcslashes($value, '"\\$') . '"';
    }

    private function generateSafeSecret(int $length): string
    {
        return Str::random($length);
    }
}
