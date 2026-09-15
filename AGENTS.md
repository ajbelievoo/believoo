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

## Marketing automation (email campaigns)

- Admin UI: `/admin/campaigns` custom panel (`EmailCampaignController`, views in `resources/views/admin/campaigns/`).
- Models: `EmailCampaign` + `CampaignRecipient` with morph-to recipient (`User` or `NewsletterSubscriber`).
- Segments: `all`, `clients`, `active`, `newsletter`.
- Delivery is via queued `SendCampaignEmails` job using the existing SMTP/branded `emails.layout` template.
- Tracking: open pixel `/campaign/pixel/{token}` and link redirects `/campaign/click/{token}?url=...` update recipient status and counters.
- Unsubscribe: newsletter subscribers get their signed unsubscribe link; the footer always includes the main site link.
- Test mode: set `test_email` on a campaign to send to only that address (safe for testing before real dispatch).
- Public tracking routes are in `routes/web.php` and do not require authentication.
- Queue worker (`queue:work`) runs as `www` and will pick up campaign dispatch jobs.

## Public REST API & API keys

- Routes live in `routes/api.php` under `/api/*` (auto `api/` prefix).
- Authentication: `Authorization: Bearer <token>` or `X-API-Key: <token>`.
- Custom stateless guard `api` using `App\Guards\ApiKeyGuard` and `App\Http\Middleware\ApiKeyAuth`.
- Tokens are stored hashed (`key_hash`); only the prefix (`key_prefix`) is displayed in the UI.
- Scopes: `user:read`, `servers:read`, `servers:control`, `server-imports:read`, `server-imports:write`, `licenses:read`, `licenses:write`, `tickets:read`, `tickets:write`, `currencies:read`, `currencies:write`, `streaming:read`, `audio-mixer:read`, `audio-mixer:write`, `*`.
- Per-key rate limiting via `RateLimiter` (cache store), default 60 req/min, configurable per key.
- IP allow-listing supports single IPs and IPv4 CIDR notation.
- Admin UI at `/admin/api-keys` for create, regenerate, edit, revoke and view usage.
- Interactive docs at `/api/docs` (Swagger UI) and OpenAPI JSON at `/api/docs/openapi.json`.
- Legacy `auth:sanctum` references removed; no Sanctum dependency required.

## B-Connect (Phase 4 enhancements)

- Kanban board at `/kanban` and `/projects/{project}/kanban` with drag-and-drop status columns (`open`, `in_progress`, `testing`, `resolved`, `closed`), inline reordering via `POST /kanban/reorder`.
- Sprint planning at `/sprints` and `/projects/{project}/sprints`; sprint progress tracked from linked tickets.
- Time tracking at `/time-tracking` and `/projects/{project}/time-tracking` with live start/stop timer, manual entries, billable hours, and `billed_amount` auto-calculation.
- New columns on `bconnect_tickets`: `position`, `sprint_id`, `parent_id`, `start_date`, `due_date`, `estimated_hours`.
- New tables: `bconnect_time_entries`, `bconnect_sprints`.
- REST API endpoints under `/api/v1/bconnect` with `bconnect:read` and `bconnect:write` scopes.

## B-Connect AI meeting notes and transcription (Phase 5.4)

- New tables: `bconnect_meeting_transcripts`, `bconnect_meeting_notes`; `bconnect_meetings` extended with `audio_path`, `transcript_status`, `notes_status`.
- Models: `MeetingTranscript`, `MeetingNote` related to `Meeting`.
- Services: `MeetingAiService` (Gemini summary/key points/action items/decisions) and `MeetingTranscriptionService` (OpenAI Whisper audio upload).
- Browser SpeechRecognition captions are persisted segment-by-segment to `bconnect_meeting_transcripts`.
- Audio file upload at `POST /meetings/{room}/audio` transcribes via Whisper and stores word-level segments.
- End-of-meeting `POST /meetings/{room}/end` now stores the final transcript and triggers `MeetingAiService` for structured notes.
- Notes view at `/meetings/{room}/notes` shows transcript and AI notes.
- Requires `ai_gemini_api_key` (notes) and `ai_openai_api_key` (Whisper) in Settings; falls back gracefully if missing.
