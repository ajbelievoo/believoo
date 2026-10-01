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
- Laravel routes OVH calls through the GHC Python backend (`/api/admin/ovh/proxy`) because PHP's cURL/OpenSSL stack is too old for OVH's TLS 1.3 endpoints.
- OVH order checkout (`/order/cart/{cartId}/checkout`) returns `You are not allowed` when the account has no registered payment method and/or zero balance. Adding a payment method and/or prepaid balance in the OVH manager is required before real provisioning can succeed.
- Order delivery status is polled from `/me/order/{orderId}/status` (the status is not included in the main order object).
- The VPS service name is extracted from an order detail line whose `domain` matches `^vps-[a-z0-9]+\.vps\.ovh\.[a-z]+$`. Option/backup lines append `-linux`, `-autobackup`, etc. and are ignored.
- Unified OVH catalog products are stored in `ovh_products` and managed from the custom admin panel at `/admin/ovh-products`.
- `OvhApiService` can now place orders for any synced category (VPS, dedicated, web hosting, public cloud, domains) via `orderProduct()`; `OvhProvisioningService` and `PollOvhServiceDelivery` handle category-specific configuration and delivery polling.
- License, IP add-on, and CDN products are **not exposed** by OVH's public catalog API for this account (`/order/catalog/public/{license,ip,cdn}` all return `Got an invalid (or empty) URL`). These are server add-ons, not standalone catalog categories, so they are not synced or presented as sellable products.

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

## Operations

- Daily DB backups run via `/etc/cron.d/believoo-ecosystem`.
- Believoo MySQL backup: `/www/wwwroot/ops/backup-believoo.sh`.
- Hourly health checks: `/www/wwwroot/ops/health-check.sh`.

## B-Connect AI meeting notes and transcription (Phase 5.4)

- New tables: `bconnect_meeting_transcripts`, `bconnect_meeting_notes`; `bconnect_meetings` extended with `audio_path`, `transcript_status`, `notes_status`.
- Models: `MeetingTranscript`, `MeetingNote` related to `Meeting`.
- Services: `MeetingAiService` (Gemini summary/key points/action items/decisions) and `MeetingTranscriptionService` (OpenAI Whisper audio upload).
- Browser SpeechRecognition captions are persisted segment-by-segment to `bconnect_meeting_transcripts`.
- Audio file upload at `POST /meetings/{room}/audio` transcribes via Whisper and stores word-level segments.
- End-of-meeting `POST /meetings/{room}/end` now stores the final transcript and triggers `MeetingAiService` for structured notes.
- Notes view at `/meetings/{room}/notes` shows transcript and AI notes.
- Requires `ai_gemini_api_key` (notes) and `ai_openai_api_key` (Whisper) in Settings; falls back gracefully if missing.

## PHP/cURL TLS for modern payment APIs (PayU / Razorpay)

- The PHP 8.2 build links libcurl against OpenSSL 1.1.1o (`/usr/local/openssl111`). Some endpoints (Razorpay `api.razorpay.com`) fail TLS handshake from PHP unless libcurl uses the system OpenSSL 3.x library.
- Fix: `env[LD_LIBRARY_PATH] = /usr/lib/x86_64-linux-gnu` is set in `/www/server/php/82/etc/php-fpm.conf` and `LD_LIBRARY_PATH=/usr/lib/x86_64-linux-gnu` is exported in `/etc/cron.d/believoo-ecosystem` and `/etc/profile.d/believoo_php_openssl.sh` so FPM, cron, and shell `php82` calls use system OpenSSL for cURL.
- After editing `php-fpm.conf`, restart with `sudo systemctl restart php-fpm-82`.
- Payment credentials live in `/www/wwwroot/.payment.env` and are loaded into each platform's settings by the appropriate loader command (Believoo `payment:load-credentials`, Music `scripts/load_payment_env.php`, GHC restart, ZonixPanel manual sync).

## hitune.in brand site & rv.hitune.in (added 2026-09-22)

- `hitune.in` now serves a static brand/landing site from `/www/wwwroot/hitunesite/` (index + privacy-policy/terms/copyright/refund pages, Tailwind CDN, dark navy `#0a0a1a` + `#00b7ff`/`#8b5cf6` accents, panda logo copied from `/www/wwwroot/music/logo.png`). vhost: `/www/server/panel/vhost/nginx/hitune.in.conf`.
- The previous hitune.in site — the **Hitune VIBE** Laravel blog (`/www/wwwroot/hitune`, DB `hitune_blog`) — moved to `rv.hitune.in` (vhost `rv.hitune.in.conf`, same webroot `/www/wwwroot/hitune/public`, `APP_URL=https://rv.hitune.in` in its `.env`; `SESSION_DOMAIN=.hitune.in` still covers it).
- `rv.hitune.in` cert issued via `/root/.acme.sh/acme.sh` (Let's Encrypt, webroot `/www/wwwroot/acme-challenge`), installed to `/www/server/panel/vhost/cert/rv.hitune.in/` with `nginx -s reload` reloadcmd. The `hitune.in` vhost has an acme-challenge fallback to the old Laravel webroot so aaPanel/LE renewals keep working.
- hitune.in DNS is on Cloudflare with a wildcard record (`rv.hitune.in` resolved before any DNS change was needed).
- SSL audit 2026-09-23: `web`, `rtc`, `live`, `withdrax`, `panel`.hitune.in certs were expired/missing (CF error 526). All re-issued via acme.sh (Let's Encrypt, shared webroot `/www/wwwroot/acme-challenge` — each vhost has `location ^~ /.well-known/acme-challenge/`). panel.hitune.in got a new `listen 443` block. Note: `/www/wwwroot/panel`, `/www/wwwroot/live`, `/www/wwwroot/hitune/withdrax` webroots do NOT exist — those vhosts 404 at app level (dead sites, not an SSL issue).

## Hitune unified login + distribution domain (2026-09-23)

- `distribution.hitune.in` now serves the **web distribution app** (`/www/wwwroot/web`, PHP 7.4 via `php-cgi-74.sock`) — same dark site as `web.hitune.in`. The old BOF `distribution.php` page is no longer reachable at that host (acme.sh renewal still uses `-w /www/wwwroot/music`; the vhost maps `/.well-known/acme-challenge/` back to that webroot).
- **Single login across Hitune Music + Distribution**: `web/includes/sso_sync.php` bridges `web.users` ↔ `musicpro._u_list` (BusyOwl user table; bcrypt-compatible `password_verify`, login requires `time_verify`).
  - `login.php`: falls back to music creds, auto-provisions local row, syncs password on mismatch.
  - `signup.php` / `google_callback.php`: mirror new accounts into `_u_list` (role_ids='2'; google users get a random hash, verified).
  - `verify_email.php`: sets `_u_list.time_verify` after web verification. `reset_password.php`: syncs new password to music.
  - MySQL: `web`@`localhost`/`127.0.0.1` granted `SELECT, INSERT` + `UPDATE(password)` on `musicpro._u_list` only. MySQL root password is in panel `default.db` config (`mysql_root` key).

## Hitune Distribution app (web.hitune.in / distribution.hitune.in) — 2026-09-28

- Custom PHP app at `/www/wwwroot/web` — see **`/www/wwwroot/web/AGENTS.md`** for full notes.
- Release workflow: `draft→submitted→in_progress→ready→live` + `rejected`/`takedown_requested`/`taken_down`; admin status changes email the artist (`includes/email_helper.php::sendReleaseStatusEmail`); per-platform `delivery_status`+`store_url` in `release_platforms`.
- Payments: Cashfree PG v3 verified server-to-server (`GET /orders/{id}` → PAID), webhook checks `x-webhook-signature`; Razorpay signature + plan resolved from payments row.
- Royalties: admin CSV import (`royalty_imports` batch table, file-hash dedupe) → `track_royalties` → user balance → `payouts` (admin approves in `admin/payouts.php`; `withdrawal_requests` is legacy/unused).
- ISRC/UPC auto-assign gated by `settings.auto_assign_codes` (`includes/code_assign.php`), runs on ready/live.
- Debug/setup files moved out of docroot → `/www/wwwroot/_removed_debug/`.

## Auth & Email state (2026-09-28 audit)

- `enable_social_login` settings key now `1` — register page Google button uses it; login page uses `google_login_enabled`.
- `.env` `GOOGLE_CLIENT_ID` was empty (only settings table had it) — now set so **B-Connect** (`bc.believoo.com`) Socialite login works; `GOOGLE_REDIRECT_BCONNECT_URL=https://bc.believoo.com/auth/google/callback` added.
- Google OAuth client `759752024135-...t067bne` is shared across believoo.com, bc.believoo.com, ghc.believoo.com, market.believoo.com, distribution.hitune.in, web.hitune.in, music.hitune.in, rv.hitune.in — every domain's redirect URI + JS origin must exist in the Google Cloud console OAuth client.
- Queue worker: `believoo-queue.service` (systemd). Restart it after `.env` changes (`queue:restart` alone doesn't reload env until a job runs).

## PayU/Razorpay/Cashfree gateway review mode (2026-09-30)

- PayU rejected website verification ("LOB not supported") — site is temporarily cleaned to present a single LOB: **web hosting + IT services, INR pricing**.
- All temporary changes are marked `PAYU-REVIEW` in code — grep for it to restore after approval:
  - `resources/views/welcome.blade.php` — hero copy de-streamed, Brands + B-CONNECT sections commented out, service prices shown in ₹ (USD×rate).
  - `resources/views/components/layouts/believoo.blade.php` — B-CONNECT nav links, external footer links, streaming orders in social-proof popup hidden; hosting nav/footer repointed to on-domain routes.
  - `routes/web.php` — `/services/streaming*` now redirects to `/services` (route names kept so `route()` helpers don't break); `/vps` plan browsing moved OUT of auth group (public), configure/set-os still authed.
  - `app/Http/Controllers/SitemapController.php` — streaming + external brand URLs removed.
  - `resources/views/vps-plans/{index,category}.blade.php` — default currency INR, USD-equivalent annotation hidden.
  - DB `settings`: meta_title/meta_description/meta_keywords no longer mention "Live Streaming" or brand names.
- Checkout already charges INR (`PaymentController` converts USD→INR via `ExchangeRate::getUsdToInrRate()` × 1.18 GST).
- After approval: revert PAYU-REVIEW blocks, restore meta in Admin → Site Settings.

## BMyDesk Remote Desktop (AnyDesk-style, added 2026-10-01)

- **Code-based sessions**: host gets an 8-char code (`BMyDesk Agent` app or "Share my screen" browser host), viewer enters it at `/remote/connect`. Signaling over Reverb private channel `remote-agent.{code}` (client events). Stream is **P2P WebRTC** (no Agora for remote).
- **Agent API** (public, rate-limited): `POST /api/v1/bmydesk/agent/register` → code + `agent_token`; `GET .../status`; `POST .../end`; `POST .../broadcast-auth` (signs Reverb channel auth manually — agents have no user account).
- **TURN relay**: coturn on `139.99.43.203:3478` (UDP+TCP), `use-auth-secret` mode. `App\Services\TurnCredentialService::iceServers()` issues 24h creds (username `<expiry>:<label>`, base64 hmac-sha1). Config keys `TURN_HOST`/`TURN_SECRET` → `config/services.php` (`services.turn.*`). DNS `turn.believoo.com` A record exists (grey cloud).
- **Plan gating**: `remote` (view/connect) = all plans incl. free; `remote_control` (input injection) = pro/enterprise via `BconnectPlanService::canUseRemoteControl`.
- **Electron agent**: source in `bmydesk-agent/` (register → Reverb → WebRTC answer → DataChannel input → `@nut-tree-fork/nut-js` OS injection). NOTE: package is `@nut-tree-fork/nut-js` — `@nut-tree/nut-js` is 404.
- **Windows builds**: built on the IyolMe build server (`/opt/build/apps/bmydesk-agent`, wine32+wine64 installed; NSIS + zip targets work). `rcedit-ia32.exe` in `~/.cache/electron-builder/winCodeSign` was replaced with the x64 binary (wine64-only fix). Artifacts served from `public/downloads/` (gitignored).
- Unsigned build — Windows SmartScreen warning is expected until code signing cert is added.
