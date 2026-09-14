# Believoo Project Notes

## PHP Version

- The web server runs PHP 8.2 (FPM at `/www/server/php/82`).
- The default `php` CLI on this machine is PHP 8.1.2, which is too old for the Laravel 11 dependencies.
- **Always use `/usr/bin/php82` for artisan and composer commands.**

Examples:

```bash
/usr/bin/php82 artisan migrate
/usr/bin/php82 artisan route:list
/usr/bin/php82 /usr/local/bin/composer require some/package
```

## OVH Reseller Integration

- OVH PHP SDK: `ovh/ovh` (installed).
- Configuration: `config/ovh.php` and `Admin > Site Settings > OVH Reseller`.
- Required OVH credentials: Application Key, Application Secret, Consumer Key, Endpoint, Subsidiary.
- Product sync: `ovh:sync-products --type=vps` (also scheduled daily at 03:00).
- Balance check: `ovh:check-balance --threshold=50` (also scheduled every 6 hours).
- Test credentials: `ovh:test-credentials`.
- OVH-linked VPS plans use the `ovh_plan_code` field. Orders for those plans are placed via OVH API after customer payment.
- Non-OVH plans continue to be provisioned on the local Proxmox node.

## Mail Server (believoo.com email hosting)

- Full mail stack already installed: **Postfix + Dovecot**, managed by the aaPanel **"Mail Server" plugin** (`/www/server/panel/plugin/mail_sys`).
- Mailbox/domain data lives in SQLite: `/www/vmail/postfixadmin.db` (tables: `domain`, `mailbox`, `alias`, etc.). Maildirs at `/www/vmail/<domain>/<user>/`.
- Passwords in `mailbox.password` are MD5-crypt (`$1$...`); `mailbox.password_encode` is hex(base64(plaintext)).
- **Create mailboxes/domains**: aaPanel UI → "Mail Server" plugin (port `42006`, admin path in `/www/server/panel/data/admin_path.pl`).
- **Webmail (Roundcube)**:
  - `https://mail.believoo.com` → serves `/www/wwwroot/cloud.believoo.com` (shared Roundcube install; also reachable as `cloud.believoo.com`).
  - `https://mail.hitune.in` → separate Roundcube copy at `/www/wwwroot/mail.hitune.in`.
  - Roundcube connects to `localhost` IMAP (143) / SMTP (25); users log in with their full email address.
- **Existing believoo.com mailboxes**: `admin@`, `info@`, `support@`, `aj@`, `no-reply@`, `rohitakrock@believoo.com`.
- **DNS**: `mail.believoo.com` A → `139.99.43.203` (direct, not Cloudflare-proxied); `believoo.com` MX → `mail.believoo.com`.
- **SSL**: web cert issued via `acme.sh` (ZeroSSL, `/root/.acme.sh`), installed to `/www/server/panel/vhost/cert/mail.believoo.com/` with auto-renewal + nginx reload hook. Mail-protocol certs (Postfix `vmail_ssl.map`, Dovecot `local_name` SNI) live under `/www/server/panel/plugin/mail_sys/cert/`.
- **nginx**: vhost at `/www/server/panel/vhost/nginx/mail.believoo.com.conf` (created manually — it does not appear in the aaPanel site list).
- **Branding/customization**: `product_name`/`skin_logo` set in `config/config.inc.php`; brand images in `images/` AND `skins/elastic/images/` (Roundcube resolves `skin_logo` paths skin-relative). Custom login layout in `skins/elastic/templates/login.html` (preloader + split-screen card); custom styles in `skins/elastic/styles/believoo-custom.css` (included via `templates/includes/layout.html`). Note: elastic's `ui.js` hides login labels and turns them into input placeholders + prepends `.input-group` icons — the CSS accounts for this.
- **Admin panels**: this app has TWO admin areas — a custom panel at `believoo.com/admin` (routes in `routes/admin.php`, controllers in `app/Http/Controllers/Admin/`, views in `resources/views/admin/`, sidebar in `resources/views/layouts/admin.blade.php`) and a Filament panel at `believoo.com/panel` (`app/Filament/`). Admin-facing features should go in the `/admin` custom panel — that's what the team uses.
- **Admin panel mailbox management**: "Email → Email Accounts" menu in `/admin` sidebar → `Admin\MailboxController` + `resources/views/admin/mailboxes/` (also a Filament `MailboxResource` exists at `/panel`). Models `App\Models\Mailbox`/`MailDomain` use the `mail` DB connection (SQLite `/www/vmail/postfixadmin.db`, `www` group has rw). Creation/update logic in `App\Services\MailboxService` — password is `crypt()` MD5-CRYPT (`$1$`), `password_encode` = `bin2hex(base64(pw))`, `maildir` = `username/`, quota in bytes comes from `mailbox.quota` via dovecot user_query. Dovecot auto-creates the Maildir + folders on first login — no root needed. Deleting a mailbox only removes the DB row (maildir stays on disk). IMPORTANT: after adding Filament resources, run `sudo -u www php82 artisan filament:clear-cached-components && optimize:clear` — the panel component cache at `bootstrap/cache/filament/panels/admin.php` is stale otherwise.

## Laravel Transactional Email (app emails)

- Mail actually sends via **SMTP**: `mail.believoo.com:587` (STARTTLS), auth user `noreply@believoo.com`. `.env` `MAIL_MAILER=smtp`. Runtime config is **overridden from the `settings` DB table** in `AppServiceProvider::boot()` (keys: `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_encryption`, `mail_from_address`, `mail_from_name`). Admin UI: Site Settings.
- **Branded theme**: `resources/views/vendor/mail/html/themes/believoo.css` (dark navy `#0a0a1a` header + logo, cyan/blue `#00b7ff` buttons). Enabled via `config/mail.php` → `markdown.theme = believoo`. ALL `MailMessage`-based notifications get it automatically.
- Header/footer: `resources/views/vendor/mail/html/header.blade.php` (uses `dark_logo`/`logo` setting, auto-swaps `.webp`→`.png`) and `message.blade.php` footer.
- **Welcome email**: `Registered` event listener in `AppServiceProvider::boot()` and `RegisteredUserController` send `App\Mail\Welcome` (`emails.welcome-html` view).
- **Verify + Reset emails**: branded via `VerifyEmail::toMailUsing` / `ResetPassword::toMailUsing` in `AppServiceProvider::boot()`. `User` implements `MustVerifyEmail`, so verification fires automatically on register.
- **OTP**: `App\Notifications\OtpNotification` — `$user->notify(new OtpNotification($code, $expiryMins, $purpose))`.
- **Newsletter**: `NewsletterSubscriber` model + `newsletter_subscribers` table; footer form POSTs to `route('newsletter.subscribe')`; unsubscribe via signed token link `newsletter.unsubscribe`; confirmation mail = `App\Mail\NewsletterSubscribed`.
- Queue worker (`queue:work`) runs as `www` — required for `ShouldQueue` notifications.

## Mail TLS Certificate (updated)
- Postfix/Dovecot now serve a proper `mail.believoo.com` certificate.
- Combined chains created in `/www/server/panel/plugin/mail_sys/cert/believoo.com/combined.pem` and `hitune.in/combined.pem`.
- Postfix SNI map `/etc/postfix/vmail_ssl.map` uses base64-encoded chain content per domain; Dovecot `local_name` SNI uses `fullchain.pem`/`privkey.pem`.

## Branded Transactional Emails (updated)
- All system emails now use the branded Believoo theme/logo.
- Markdown mail theme: `resources/views/vendor/mail/html/themes/believoo.css`.
- Custom Mailables updated: `LowBalanceWarning`, `ServiceSuspendedAlert`, `WalletDebitReceipt`, `WalletTopupSuccess`.
- `WelcomeEmail`/`NewsletterSubscribed`/`newsletter-update-html` already used `emails.layout`.
