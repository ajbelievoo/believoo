<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use PDO;

class BrandPanelsController extends Controller
{
    /** Shared License Hub SQLite (read-only; WAL-safe alongside the node app). */
    private function licDb(): PDO
    {
        $pdo = new PDO('sqlite:/www/wwwroot/iolicense/data/masgame.db', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA query_only = ON');
        $pdo->exec('PRAGMA busy_timeout = 3000');
        return $pdo;
    }

    private function probe(string $url, int $timeout = 4): array
    {
        try {
            $t0 = microtime(true);
            $r = Http::timeout($timeout)->get($url);
            return ['up' => $r->successful(), 'code' => $r->status(), 'ms' => (int) ((microtime(true) - $t0) * 1000)];
        } catch (\Throwable $e) {
            return ['up' => false, 'code' => 0, 'ms' => null];
        }
    }

    /* ================= LICENSE HUB (io + ar) ================= */
    public function license(\Illuminate\Http\Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        $one = function (PDO $db, string $sql) {
            return (int) $db->query($sql)->fetchColumn();
        };
        try {
            $db = $this->licDb();
            $stats = [
                'clients' => $one($db, 'SELECT COUNT(*) FROM clients'),
                'resellers' => $one($db, "SELECT COUNT(*) FROM clients WHERE is_reseller=1"),
                'licenses' => $one($db, 'SELECT COUNT(*) FROM licenses'),
                'licenses_active' => $one($db, "SELECT COUNT(*) FROM licenses WHERE status='active'"),
                'activated' => $one($db, 'SELECT COUNT(*) FROM licenses WHERE activated_at IS NOT NULL'),
                'games' => $one($db, 'SELECT COUNT(*) FROM games'),
                'revenue' => (float) $db->query('SELECT COALESCE(SUM(amount),0) FROM sales')->fetchColumn(),
                'checkins_24h' => $one($db, "SELECT COUNT(*) FROM licenses WHERE last_checkin > datetime('now','-1 day')"),
                'dev_users' => $one($db, 'SELECT COUNT(*) FROM dev_users'),
                'orders_paid' => $one($db, "SELECT COUNT(*) FROM ar_orders WHERE status='paid'"),
                'orders_pending' => $one($db, "SELECT COUNT(*) FROM ar_orders WHERE status='pending'"),
                'sdk_devices' => $one($db, 'SELECT COUNT(*) FROM ar_devices'),
                'newsletter' => $one($db, 'SELECT COUNT(*) FROM newsletter'),
                'flagged' => $one($db, "SELECT COUNT(*) FROM checkins WHERE flags<>'' AND ts > datetime('now','-7 day')"),
            ];
            $recentLicenses = $db->query('SELECT l.*, c.name client_name FROM licenses l LEFT JOIN clients c ON c.id=l.client_id ORDER BY l.id DESC LIMIT 15')->fetchAll();
            $checkins = $db->query("SELECT l.lid, l.domain, l.last_checkin, l.bound_ip, l.instance_id, c.name client_name FROM licenses l LEFT JOIN clients c ON c.id=l.client_id WHERE l.last_checkin IS NOT NULL ORDER BY l.last_checkin DESC LIMIT 12")->fetchAll();
            $sales = $db->query('SELECT s.*, c.name client_name, l.lid FROM sales s LEFT JOIN clients c ON c.id=s.client_id LEFT JOIN licenses l ON l.id=s.license_id ORDER BY s.id DESC LIMIT 10')->fetchAll();
            $clients = $db->query('SELECT * FROM clients ORDER BY id DESC LIMIT 15')->fetchAll();
            $games = $db->query('SELECT * FROM games ORDER BY id')->fetchAll();
            $arOrders = $db->query('SELECT * FROM ar_orders ORDER BY id DESC LIMIT 10')->fetchAll();
            $dbError = null;
        } catch (\Throwable $e) {
            $stats = []; $recentLicenses = $checkins = $sales = $clients = $games = $arOrders = [];
            $dbError = $e->getMessage();
        }
        $ioStatus = $this->probe('https://io.believoo.com/');
        $arStatus = $this->probe('https://ar.believoo.com/');
        return view('admin.brandpanels.license', compact('tab', 'stats', 'recentLicenses', 'checkins', 'sales', 'clients', 'games', 'arOrders', 'ioStatus', 'arStatus', 'dbError'));
    }

    /* ================= BelieRTC ================= */
    public function rtc(\Illuminate\Http\Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        $store = json_decode(@file_get_contents('/www/wwwroot/rtc/api/data/store.json'), true) ?: [];
        $projects = $store['projects'] ?? [];
        $users = $store['users'] ?? [];
        $events = array_slice(array_reverse($store['events'] ?? []), 0, 15);
        $restKeys = $store['restKeys'] ?? [];
        $alertRules = $store['alertRules'] ?? [];
        $usage = $store['usage'] ?? [];
        $apiStatus = $this->probe('http://127.0.0.1:3100/v1/health');
        $siteStatus = $this->probe('https://rtc.believoo.com/');
        $stats = [
            'projects' => count($projects),
            'projects_enabled' => count(array_filter($projects, fn($p) => $p['enabled'] ?? false)),
            'users' => count($users),
            'events' => count($store['events'] ?? []),
            'rest_keys' => count($restKeys),
            'alert_rules' => count($alertRules),
        ];
        return view('admin.brandpanels.rtc', compact('tab', 'stats', 'projects', 'users', 'events', 'restKeys', 'alertRules', 'usage', 'apiStatus', 'siteStatus'));
    }
    /* ================= BelieVoo Search — full admin ================= */
    private const SEARCH_DATA = '/opt/believoo-search/shared/data';

    private function jread(string $name): array
    {
        $f = self::SEARCH_DATA . '/' . $name . '.json';
        return is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    }

    private function jwrite(string $name, $data): void
    {
        $f = self::SEARCH_DATA . '/' . $name . '.json';
        $tmp = $f . '.tmp-' . getmypid();
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        rename($tmp, $f);
        @chmod($f, 0664);
    }


    private function slotInventory(array $leads): array
    {
        $slots = [
            ['id' => 'sponsor-top', 'name' => 'Sponsored Listing — top of results', 'placement' => 'Search results (all queries, keyword matched)', 'capacity' => 4, 'shared' => true, 'priceShared' => 4999, 'priceDedicated' => 14999],
            ['id' => 'speeddial', 'name' => 'Speed Dial tile — browser new tab', 'placement' => 'BelieVoo Browser new tab (all users)', 'capacity' => 6, 'shared' => true, 'priceShared' => 9999, 'priceDedicated' => 0],
            ['id' => 'banner-home', 'name' => 'Banner — search home page', 'placement' => 'search.believoo.com home', 'capacity' => 2, 'shared' => true, 'priceShared' => 7999, 'priceDedicated' => 19999],
            ['id' => 'banner-results', 'name' => 'Banner — results pages', 'placement' => 'All search result pages', 'capacity' => 2, 'shared' => true, 'priceShared' => 5999, 'priceDedicated' => 15999],
        ];
        foreach (($this->jread('slots')['slots'] ?? []) as $cs) {
            $found = false;
            foreach ($slots as &$sl) if ($sl['id'] === ($cs['id'] ?? '')) { $sl = array_merge($sl, $cs); $found = true; }
            unset($sl);
            if (!$found && !empty($cs['id'])) $slots[] = $cs;
        }
        $occ = $wait = [];
        foreach ($leads as $l) {
            $sid = $l['slotId'] ?? '';
            if (!$sid) continue;
            if (in_array($l['status'] ?? '', ['awaiting_payment', 'paid', 'live'], true)) $occ[$sid] = ($occ[$sid] ?? 0) + 1;
            if (($l['status'] ?? '') === 'waitlist') $wait[$sid] = ($wait[$sid] ?? 0) + 1;
        }
        return array_map(function ($sl) use ($occ, $wait) {
            $sl['used'] = $occ[$sl['id']] ?? 0;
            $sl['free'] = max(0, (int)($sl['capacity'] ?? 1) - $sl['used']);
            $sl['waitlist'] = $wait[$sl['id']] ?? 0;
            return $sl;
        }, $slots);
    }

    public function search(\Illuminate\Http\Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        $stats = $this->jread('stats');
        $popular = $this->jread('popular');
        $sponsored = $this->jread('sponsored')['ads'] ?? [];
        $pages = $this->jread('pages');
        $banner = $this->jread('banner');
        $site = $this->jread('site');
        $monetize = $this->jread('monetize');
        $speeddial = $this->jread('speeddial')['tiles'] ?? [];
        $rewards = $this->jread('rewards');
        $browser = $this->jread('browser');
        $adStats = $this->jread('ad-stats');
        $adverts = $this->jread('adverts');
        $payments = $this->jread('payments')['payments'] ?? [];
        $crashes = $this->jread('crashes');
        $sync = $this->jread('sync');
        $geo = $stats['geo'] ?? [];
        $cities = $stats['cities'] ?? [];
        $browsers = $stats['browsers'] ?? [];
        $norm = function ($map) {
            $out = [];
            foreach ((array) $map as $k => $v) {
                if (is_int($k) && is_array($v)) $out[] = ['name' => $v['country'] ?? $v['city'] ?? $v['version'] ?? $v['name'] ?? '?', 'hits' => (int)($v['hits'] ?? $v['count'] ?? $v['installs'] ?? 0)];
                elseif (is_numeric($v)) $out[] = ['name' => (string) $k, 'hits' => (int) $v];
            }
            usort($out, fn($a, $b) => $b['hits'] <=> $a['hits']);
            return $out;
        };
        $geoList = $norm($geo);
        $cityList = $norm($cities);
        $browserList = $norm($browsers);
        $slots = $this->slotInventory(is_array($adverts) ? $adverts : []);
        $freshLeads = is_array($adverts) ? count(array_filter($adverts, fn($l) => ($l['status'] ?? '') === 'new')) : 0;

        $days = $stats['days'] ?? [];
        $searches30 = 0; $suggest30 = 0;
        foreach ($days as $d) { $searches30 += $d['searches'] ?? 0; $suggest30 += $d['suggest'] ?? 0; }
        $today = $days[date('Y-m-d')] ?? [];
        $providers = [];
        if (!empty($monetize['braveKey'])) $providers[] = 'brave';
        if (!empty($monetize['googleKey'])) $providers[] = 'google';
        $providers[] = 'searxng';
        $queries = $stats['queries'] ?? [];
        arsort($queries);

        $nodes = [
            'node1' => $this->probe('http://127.0.0.1:3101/stats'),
            'node2' => $this->probe('http://127.0.0.1:3102/stats'),
        ];
        $siteStatus = $this->probe('https://search.believoo.com/');
        return view('admin.brandpanels.search', compact(
            'tab', 'stats', 'popular', 'sponsored', 'pages', 'banner', 'site', 'monetize',
            'speeddial', 'rewards', 'browser', 'adStats', 'searches30', 'suggest30',
            'today', 'providers', 'queries', 'nodes', 'siteStatus',
            'adverts', 'payments', 'crashes', 'sync', 'geoList', 'cityList', 'browserList',
            'slots', 'freshLeads'
        ));
    }

    public function searchSave(\Illuminate\Http\Request $request)
    {
        $section = $request->input('section');
        $msg = 'Saved.';
        switch ($section) {
            case 'banner':
                $b = $this->jread('banner');
                $b['active'] = $request->boolean('active');
                $b['position'] = $request->input('position', 'both');
                $b['title'] = $request->input('title', '');
                $b['url'] = $request->input('url', '');
                $this->jwrite('banner', $b);
                break;
            case 'branding':
                $s = $this->jread('site');
                foreach (['siteName','tagline','logoMode','color1','color2','footerNote','metaDesc','companyName','companyAddr','companyCin'] as $k)
                    if ($request->has($k)) $s[$k] = $request->input($k);
                $this->jwrite('site', $s);
                break;
            case 'monetize':
                $m = $this->jread('monetize');
                foreach (['amazonTag','flipkartAffid','ghcRef','adsensePubId','adsenseStyleId','geminiKey','geminiModel','googleKey','googleCx','braveKey'] as $k)
                    if ($request->filled($k)) $m[$k] = $request->input($k);
                $this->jwrite('monetize', $m);
                break;
            case 'sponsored_add':
                $doc = $this->jread('sponsored');
                $ads = $doc['ads'] ?? [];
                $ads[] = [
                    'id' => $request->input('id') ?: ('ad-' . substr(md5(uniqid()), 0, 8)),
                    'title' => $request->input('title', ''),
                    'text' => $request->input('text', ''),
                    'display' => $request->input('display', ''),
                    'url' => $request->input('url', ''),
                    'keywords' => array_values(array_filter(array_map('trim', explode(',', (string) $request->input('keywords', ''))))),
                    'intents' => [],
                    'bid' => (float) $request->input('bid', 1),
                    'active' => true,
                ];
                $doc['ads'] = $ads;
                if (empty($doc['_doc'])) $doc['_doc'] = 'BelieVoo Ads: first-party keyword-targeted sponsored listings.';
                $this->jwrite('sponsored', $doc);
                $msg = 'Ad added.';
                break;
            case 'sponsored_action':
                $doc = $this->jread('sponsored');
                $ads = $doc['ads'] ?? [];
                foreach ($ads as $i => &$a) {
                    if (($a['id'] ?? '') === $request->input('id')) {
                        if ($request->input('op') === 'delete') array_splice($ads, $i, 1);
                        else $a['active'] = !($a['active'] ?? true);
                        break;
                    }
                }
                unset($a);
                $doc['ads'] = $ads;
                $this->jwrite('sponsored', $doc);
                $msg = 'Ad updated.';
                break;
            case 'speeddial_add':
                $doc = $this->jread('speeddial');
                $tiles = $doc['tiles'] ?? [];
                $tiles[] = [
                    'title' => $request->input('title', ''),
                    'url' => $request->input('url', ''),
                    'color' => $request->input('color', '#00b7ff'),
                    'letter' => strtoupper(substr($request->input('title', 'A'), 0, 1)),
                    'sponsored' => $request->boolean('sponsored'),
                ];
                $doc['tiles'] = $tiles;
                $this->jwrite('speeddial', $doc);
                $msg = 'Tile added.';
                break;
            case 'speeddial_delete':
                $doc = $this->jread('speeddial');
                $tiles = $doc['tiles'] ?? [];
                $idx = (int) $request->input('idx', -1);
                if (isset($tiles[$idx])) array_splice($tiles, $idx, 1);
                $doc['tiles'] = $tiles;
                $this->jwrite('speeddial', $doc);
                $msg = 'Tile deleted.';
                break;
            case 'page_save':
                $pages = $this->jread('pages');
                $slug = $request->input('slug');
                if ($slug && isset($pages[$slug])) {
                    if ($request->has('title')) $pages[$slug]['title'] = $request->input('title');
                    if ($request->has('desc')) $pages[$slug]['desc'] = $request->input('desc');
                    $this->jwrite('pages', $pages);
                }
                $msg = 'Page updated.';
                break;
            case 'rewards_cfg':
                $r = $this->jread('rewards');
                $r['cfg']['claimSearches'] = (int) $request->input('claimSearches', 50);
                $r['cfg']['claimRefs'] = (int) $request->input('claimRefs', 5);
                $this->jwrite('rewards', $r);
                $msg = 'Rewards config saved.';
                break;
                        case 'lead_approve':
                $leads = $this->jread('adverts');
                $i = (int) $request->input('idx');
                if (!isset($leads[$i])) throw new \RuntimeException('Lead not found.');
                $price = (int) preg_replace('/[^\d]/', '', (string) $request->input('price', '')) ?: 4999;
                $leads[$i]['status'] = 'awaiting_payment';
                $leads[$i]['price'] = $price;
                $this->jwrite('adverts', $leads);
                $msg = 'Lead approved — awaiting payment ₹' . number_format($price) . '.'; $tab = 'sponsored';
                break;
            case 'lead_reject':
                $leads = $this->jread('adverts');
                $i = (int) $request->input('idx');
                if (!isset($leads[$i])) throw new \RuntimeException('Lead not found.');
                $leads[$i]['status'] = 'rejected';
                $leads[$i]['reason'] = (string) $request->input('reason', 'Not a fit right now');
                $this->jwrite('adverts', $leads);
                $msg = 'Lead rejected.'; $tab = 'sponsored';
                break;
            case 'lead_live':
                $leads = $this->jread('adverts');
                $i = (int) $request->input('idx');
                if (!isset($leads[$i])) throw new \RuntimeException('Lead not found.');
                $lead = $leads[$i];
                $leads[$i]['status'] = 'live';
                $this->jwrite('adverts', $leads);
                if (($lead['format'] ?? '') === 'sponsored' && !empty($lead['keywords'])) {
                    $d = $this->jread('sponsored');
                    $d['ads'] = $d['ads'] ?? [];
                    $exists = false;
                    foreach ($d['ads'] as $a) if (($a['leadRef'] ?? null) === $i) $exists = true;
                    if (!$exists) {
                        $host = preg_replace('/^www\./', '', (string) parse_url($lead['url'] ?? '', PHP_URL_HOST));
                        $d['ads'][] = [
                            'id' => 'lead-' . $i . '-' . substr(preg_replace('/[^a-z0-9]+/', '-', strtolower($lead['product'] ?? 'ad')), 0, 30),
                            'title' => $lead['product'] ?? '',
                            'text' => substr(($lead['msg'] ?? '') ?: ('Sponsored: ' . ($lead['product'] ?? '')), 0, 140),
                            'url' => ($lead['url'] ?? '') ?: ('mailto:' . ($lead['email'] ?? '')),
                            'display' => $host ?: 'partner.believoo.com',
                            'keywords' => array_slice(array_values(array_filter(array_map('trim', explode(',', (string) $lead['keywords'])))), 0, 20),
                            'intents' => [], 'bid' => 1, 'active' => true,
                            'slotId' => $lead['slotId'] ?? 'sponsor-top', 'leadRef' => $i,
                        ];
                        $this->jwrite('sponsored', $d);
                    }
                }
                $msg = 'Lead is live.'; $tab = 'sponsored';
                break;
            case 'lead_promote':
                $leads = $this->jread('adverts');
                $i = (int) $request->input('idx');
                if (!isset($leads[$i])) throw new \RuntimeException('Lead not found.');
                $leads[$i]['status'] = 'awaiting_payment';
                $this->jwrite('adverts', $leads);
                $msg = 'Waitlist lead promoted.'; $tab = 'sponsored';
                break;
            case 'lead_close':
                $leads = $this->jread('adverts');
                $i = (int) $request->input('idx');
                if (!isset($leads[$i])) throw new \RuntimeException('Lead not found.');
                $leads[$i]['status'] = 'closed';
                $this->jwrite('adverts', $leads);
                $msg = 'Lead closed.'; $tab = 'sponsored';
                break;
            case 'lead_delete':
                $leads = $this->jread('adverts');
                $i = (int) $request->input('idx');
                if (!isset($leads[$i])) throw new \RuntimeException('Lead not found.');
                array_splice($leads, $i, 1);
                $this->jwrite('adverts', array_values($leads));
                $msg = 'Lead deleted.'; $tab = 'sponsored';
                break;
            case 'browser_save':
                $b = $this->jread('browser');
                $b['announcement'] = ['active' => (bool) $request->input('annActive'), 'text' => (string) $request->input('annText', ''), 'url' => (string) $request->input('annUrl', '')];
                $b['homepage'] = (string) $request->input('homepage', '');
                $b['updateUrl'] = (string) $request->input('updateUrl', '');
                $b['features'] = ['suggestions' => (bool) $request->input('fSug'), 'speedDial' => (bool) $request->input('fSd'), 'adblock' => (bool) $request->input('fAd')];
                $b['minVersion'] = (string) $request->input('minVersion', '');
                $b['kidsMode'] = (bool) $request->input('kids');
                $bm = [];
                foreach (preg_split('/\r?\n/', (string) $request->input('managedBm', '')) as $line) {
                    if (strpos($line, '|') !== false) { [$t, $u2] = array_map('trim', explode('|', $line, 2)); if ($t && $u2) $bm[] = ['title' => $t, 'url' => $u2]; }
                }
                $b['managedBookmarks'] = $bm;
                $b['blockedHosts'] = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $request->input('blockedHosts', '')))));
                $fl = json_decode((string) $request->input('flags', ''), true);
                $b['flags'] = is_array($fl) ? $fl : [];
                $b['whatsNew'] = (string) $request->input('whatsNew', '');
                $this->jwrite('browser', $b);
                $msg = 'Browser config saved.'; $tab = 'browser';
                break;
            case 'site_controls':
                $sdata = $this->jread('site');
                $sdata['maintenance'] = (bool) $request->input('maintenance');
                $sdata['blockedWords'] = (string) $request->input('blockedWords', '');
                $this->jwrite('site', $sdata);
                $msg = 'Site controls saved.'; $tab = 'settings';
                break;
default:
                return redirect()->route('admin.brandpanels.search')->with('error', 'Unknown section.');
        }
        return redirect()->route('admin.brandpanels.search', ['tab' => $request->input('tab', 'dashboard')])->with('success', $msg);
    }
    /* ===== writable helpers (License Hub / RTC) ===== */
    private function licDbW(): PDO
    {
        $pdo = new PDO('sqlite:/www/wwwroot/iolicense/data/masgame.db', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }

    /** Ed25519-sign a license payload — identical to node lic.sign() (MG1.b64.b64). */
    private function licSign(array $payload): string
    {
        $keys = json_decode(file_get_contents('/www/wwwroot/iolicense/data/keys.json'), true);
        if (!preg_match('/-----BEGIN PRIVATE KEY-----(.*?)-----END PRIVATE KEY-----/s', $keys['priv'], $m)) {
            throw new \RuntimeException('license private key unreadable');
        }
        $der = base64_decode($m[1]);
        $kp = sodium_crypto_sign_seed_keypair(substr($der, -32));
        $body = rtrim(strtr(base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
        $sig = sodium_crypto_sign_detached($body, sodium_crypto_sign_secretkey($kp));
        return 'MG1.' . $body . '.' . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    }

    public function licenseSave(\Illuminate\Http\Request $request)
    {
        $op = $request->input('op');
        $tab = $request->input('tab', 'licenses');
        try {
            $db = $this->licDbW();
            switch ($op) {
                case 'issue':
                    $client = $db->query('SELECT * FROM clients WHERE id=' . (int) $request->input('client_id'))->fetch();
                    $game = $db->query("SELECT * FROM games WHERE slug=" . $db->quote($request->input('game_slug')) . " AND status='active'")->fetch();
                    $domain = strtolower(trim((string) $request->input('domain')));
                    if (!$client || !$domain || !$game) return redirect()->route('admin.brandpanels.license', ['tab' => 'licenses'])->with('error', 'Client/domain/game required.');
                    $lid = 'L-' . strtoupper(bin2hex(random_bytes(8)));
                    $payload = [
                        'lid' => $lid, 'iss' => 'masgame', 'client_id' => (int) $client['id'], 'client_name' => $client['name'],
                        'domain' => $domain, 'game_slug' => $game['slug'], 'reseller' => $request->boolean('reseller') ? 1 : 0,
                        'api_plan' => in_array($request->input('api_plan'), ['monthly', 'yearly']) ? $request->input('api_plan') : 'none',
                        'iat' => time(),
                        'exp' => $request->filled('expires_at') ? strtotime($request->input('expires_at')) : null,
                    ];
                    $token = $this->licSign($payload);
                    $updMode = in_array($request->input('upd_mode'), ['free', 'paid', 'none']) ? $request->input('upd_mode') : 'free';
                    $db->prepare('INSERT INTO licenses(lid,client_id,domain,game_slug,reseller,update_trigger,api_plan,api_expires,token,expires_at,ip_lock,upd_mode,upd_price,upd_url) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                        ->execute([$lid, $client['id'], $domain, $game['slug'], $payload['reseller'], $request->boolean('update_trigger') ? 1 : 0,
                            $payload['api_plan'], $request->input('api_expires') ?: null, $token, $request->input('expires_at') ?: null,
                            $request->boolean('ip_lock') ? 1 : 0, $updMode, (float) $request->input('upd_price', 0), trim((string) $request->input('upd_url', ''))]);
                    $feats = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('features', ''))), fn($f) => preg_match('/^[a-z*][a-z0-9*_-]*$/i', $f)));
                    $dlim = max(0, (int) $request->input('device_limit', 0));
                    if ($feats || $dlim) $db->prepare('UPDATE licenses SET features=?, device_limit=? WHERE lid=?')->execute([json_encode($feats), $dlim, $lid]);
                    return redirect()->route('admin.brandpanels.license', ['tab' => 'licenses'])->with('success', 'License issued: ' . $lid);
                case 'status':
                    $st = in_array($request->input('status'), ['active', 'revoked', 'expired']) ? $request->input('status') : 'active';
                    $db->prepare('UPDATE licenses SET status=? WHERE lid=?')->execute([$st, $request->input('lid')]);
                    $msg = 'License ' . $request->input('lid') . ' → ' . $st;
                    break;
                case 'unbind':
                    $db->prepare('UPDATE licenses SET instance_id=NULL, activated_at=NULL, bound_ip=NULL WHERE lid=?')->execute([$request->input('lid')]);
                    $msg = 'Machine unbound.';
                    break;
                case 'reset':
                    $db->prepare("UPDATE licenses SET domain='', instance_id=NULL, activated_at=NULL, bound_ip=NULL, reg_email='' WHERE lid=?")->execute([$request->input('lid')]);
                    $msg = 'License fully reset.';
                    break;
                case 'flags':
                    $db->prepare('UPDATE licenses SET update_trigger=?, reseller=? WHERE lid=?')->execute([$request->boolean('update_trigger') ? 1 : 0, $request->boolean('reseller') ? 1 : 0, $request->input('lid')]);
                    $msg = 'Flags saved.';
                    break;
                case 'source_unlock':
                    $db->prepare('UPDATE licenses SET source_unlock=? WHERE lid=?')->execute([$request->boolean('on') ? 1 : 0, $request->input('lid')]);
                    $msg = 'Source unlock ' . ($request->boolean('on') ? 'enabled' : 'disabled');
                    break;
                case 'client_add':
                    $db->prepare('INSERT INTO clients(name,email,company,domain,is_reseller,update_trigger,notes) VALUES(?,?,?,?,?,?,?)')
                        ->execute([$request->input('name'), $request->input('email'), $request->input('company'), $request->input('domain'),
                            $request->boolean('is_reseller') ? 1 : 0, $request->boolean('update_trigger') ? 1 : 0, $request->input('notes')]);
                    $msg = 'Client added.'; $tab = 'clients';
                    break;
                case 'client_update':
                    $db->prepare('UPDATE clients SET name=?,email=?,company=?,domain=?,is_reseller=?,update_trigger=?,status=?,notes=? WHERE id=?')
                        ->execute([$request->input('name'), $request->input('email'), $request->input('company'), $request->input('domain'),
                            $request->boolean('is_reseller') ? 1 : 0, $request->boolean('update_trigger') ? 1 : 0,
                            $request->input('status') === 'suspended' ? 'suspended' : 'active', $request->input('notes'), (int) $request->input('id')]);
                    $msg = 'Client updated.'; $tab = 'clients';
                    break;
                case 'sale_add':
                    $db->prepare("INSERT INTO sales(license_id,client_id,amount,currency,item,note,sold_at) VALUES(?,?,?,?,?,?,datetime('now'))")
                        ->execute([(int) $request->input('license_id') ?: null, (int) $request->input('client_id') ?: null,
                            (float) $request->input('amount', 0), $request->input('currency', 'INR'), $request->input('item'), $request->input('note')]);
                    $msg = 'Sale recorded.'; $tab = 'sales';
                    break;
                case 'game_update':
                    $db->prepare('UPDATE games SET name=?,description=?,demo_url=?,status=? WHERE slug=?')
                        ->execute([$request->input('name'), $request->input('description'), $request->input('demo_url'),
                            $request->input('status') === 'disabled' ? 'disabled' : 'active', $request->input('slug')]);
                    $msg = 'Game updated.'; $tab = 'games';
                    break;
                default:
                    return redirect()->route('admin.brandpanels.license')->with('error', 'Unknown op.');
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.brandpanels.license', ['tab' => $tab])->with('error', 'Save failed: ' . $e->getMessage());
        }
        return redirect()->route('admin.brandpanels.license', ['tab' => $tab])->with('success', $msg);
    }

    /* ===== RTC store writes ===== */
    private function rtcStore(): array
    {
        return json_decode((string) @file_get_contents('/www/wwwroot/rtc/api/data/store.json'), true) ?: [];
    }
    private function rtcSave(array $d): void
    {
        $f = '/www/wwwroot/rtc/api/data/store.json';
        $tmp = $f . '.tmp-' . getmypid();
        file_put_contents($tmp, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        rename($tmp, $f);
        @chmod($f, 0644);
    }

    public function rtcSaveOp(\Illuminate\Http\Request $request)
    {
        $op = $request->input('op');
        $tab = $request->input('tab', 'projects');
        try {
            $d = $this->rtcStore();
            switch ($op) {
                case 'project_add':
                    $d['projects'][] = [
                        'id' => 'app_' . bin2hex(random_bytes(6)),
                        'name' => $request->input('name'),
                        'useCase' => $request->input('useCase') ?: null,
                        'dataCenter' => $request->input('dataCenter', 'APAC'),
                        'apiKey' => 'BK' . bin2hex(random_bytes(10)),
                        'apiSecret' => bin2hex(random_bytes(20)),
                        'createdAt' => date('c'),
                        'enabled' => true,
                        'limits' => ['monthlyMinutes' => (int) $request->input('monthlyMinutes', 10000), 'maxParticipants' => (int) $request->input('maxParticipants', 10), 'recordings' => (int) $request->input('recordings', 3)],
                        'webhookUrl' => null,
                    ];
                    $msg = 'Project created.';
                    break;
                case 'project_update':
                    foreach ($d['projects'] ?? [] as &$p) {
                        if ($p['id'] === $request->input('id')) {
                            if ($request->has('enabled')) $p['enabled'] = $request->boolean('enabled');
                            if ($request->filled('name')) $p['name'] = $request->input('name');
                            if ($request->has('webhookUrl')) $p['webhookUrl'] = $request->input('webhookUrl') ?: null;
                            if ($request->filled('monthlyMinutes')) $p['limits']['monthlyMinutes'] = (int) $request->input('monthlyMinutes');
                            if ($request->filled('maxParticipants')) $p['limits']['maxParticipants'] = (int) $request->input('maxParticipants');
                        }
                    }
                    unset($p);
                    $msg = 'Project updated.';
                    break;
                case 'project_delete':
                    $d['projects'] = array_values(array_filter($d['projects'] ?? [], fn($p) => $p['id'] !== $request->input('id')));
                    $msg = 'Project deleted.';
                    break;
                case 'restkey_add':
                    $active = count(array_filter($d['restKeys'] ?? [], fn($k) => $k['enabled']));
                    if ($active >= 10) return redirect()->route('admin.brandpanels.rtc', ['tab' => 'restkeys'])->with('error', 'Max 10 active keys.');
                    $d['restKeys'][] = [
                        'id' => 'rk_' . base_convert((string) (int) (microtime(true) * 1000), 10, 36),
                        'customerId' => 'cust_' . bin2hex(random_bytes(10)),
                        'secret' => bin2hex(random_bytes(24)),
                        'enabled' => true,
                        'note' => substr((string) $request->input('note', ''), 0, 120),
                        'created' => date('c'),
                    ];
                    $msg = 'REST key created.'; $tab = 'restkeys';
                    break;
                case 'restkey_toggle':
                    foreach ($d['restKeys'] ?? [] as &$k) if ($k['id'] === $request->input('id')) $k['enabled'] = !($k['enabled'] ?? true);
                    unset($k);
                    $msg = 'Key toggled.'; $tab = 'restkeys';
                    break;
                case 'restkey_delete':
                    $d['restKeys'] = array_values(array_filter($d['restKeys'] ?? [], fn($k) => $k['id'] !== $request->input('id')));
                    $msg = 'Key deleted.'; $tab = 'restkeys';
                    break;
                default:
                    return redirect()->route('admin.brandpanels.rtc')->with('error', 'Unknown op.');
            }
            $this->rtcSave($d);
        } catch (\Throwable $e) {
            return redirect()->route('admin.brandpanels.rtc', ['tab' => $tab])->with('error', 'Save failed: ' . $e->getMessage());
        }
        return redirect()->route('admin.brandpanels.rtc', ['tab' => $tab])->with('success', $msg);
    }

    /* ================= B-AR (ar.believoo.com) ================= */
    public function ar(\Illuminate\Http\Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        $dbError = null;
        $stats = $devUsers = $orders = $devices = $plans = $assets = [];
        try {
            $db = $this->licDbW();
            $stats = [
                'dev_users'      => (int) $db->query("SELECT COUNT(*) FROM dev_users")->fetchColumn(),
                'dev_verified'   => (int) $db->query("SELECT COUNT(*) FROM dev_users WHERE email_verified=1")->fetchColumn(),
                'orders'         => (int) $db->query("SELECT COUNT(*) FROM ar_orders")->fetchColumn(),
                'orders_paid'    => (int) $db->query("SELECT COUNT(*) FROM ar_orders WHERE status='paid'")->fetchColumn(),
                'revenue_inr'    => (int) $db->query("SELECT COALESCE(SUM(amount),0) FROM ar_orders WHERE status='paid' AND currency='INR'")->fetchColumn(),
                'devices'        => (int) $db->query("SELECT COUNT(*) FROM ar_devices")->fetchColumn(),
                'plans'          => (int) $db->query("SELECT COUNT(*) FROM ar_plans WHERE status='active'")->fetchColumn(),
                'assets'         => (int) $db->query("SELECT COUNT(*) FROM ar_assets WHERE status='active'")->fetchColumn(),
            ];
            $devUsers = $db->query("SELECT * FROM dev_users ORDER BY id DESC LIMIT 100")->fetchAll();
            $orders   = $db->query("SELECT o.*, u.email AS user_email FROM ar_orders o LEFT JOIN dev_users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 100")->fetchAll();
            $devices  = $db->query("SELECT * FROM ar_devices ORDER BY last_seen DESC LIMIT 100")->fetchAll();
            $plans    = $db->query("SELECT * FROM ar_plans ORDER BY sort, id")->fetchAll();
            $assets   = $db->query("SELECT * FROM ar_assets ORDER BY sort, id LIMIT 200")->fetchAll();
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }
        $arStatus = $this->probe('https://ar.believoo.com/');
        return view('admin.brandpanels.ar', compact('tab', 'stats', 'devUsers', 'orders', 'devices', 'plans', 'assets', 'arStatus', 'dbError'));
    }

    public function arSaveOp(\Illuminate\Http\Request $request)
    {
        $op = $request->input('op');
        $tab = $request->input('tab', 'dashboard');
        $msg = 'Saved.';
        try {
            $db = $this->licDbW();
            switch ($op) {
                case 'dev_status':
                    $st = $request->input('status') === 'suspended' ? 'suspended' : 'active';
                    $db->prepare("UPDATE dev_users SET status=? WHERE id=?")->execute([$st, (int) $request->input('id')]);
                    $msg = 'Developer ' . $st . '.'; $tab = 'developers';
                    break;
                case 'dev_verify':
                    $db->prepare("UPDATE dev_users SET email_verified=1 WHERE id=?")->execute([(int) $request->input('id')]);
                    $msg = 'Email marked verified.'; $tab = 'developers';
                    break;
                case 'order_status':
                    $st = in_array($request->input('status'), ['paid', 'pending', 'failed', 'cancelled'], true) ? $request->input('status') : 'pending';
                    $paidAt = $st === 'paid' ? date('Y-m-d H:i:s') : null;
                    $db->prepare("UPDATE ar_orders SET status=?, paid_at=COALESCE(?, paid_at) WHERE id=?")->execute([$st, $paidAt, (int) $request->input('id')]);
                    $msg = 'Order marked ' . $st . '.'; $tab = 'orders';
                    break;
                case 'device_delete':
                    $db->prepare("DELETE FROM ar_devices WHERE id=?")->execute([(int) $request->input('id')]);
                    $msg = 'Device unbound.'; $tab = 'devices';
                    break;
                case 'plan_save':
                    $priceInr = (int) round(((float) $request->input('price_inr', 0)) * 100);
                    $priceUsd = (int) round(((float) $request->input('price_usd', 0)) * 100);
                    $features = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('features', '')))));
                    $perks = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('perks', '')))));
                    $db->prepare("UPDATE ar_plans SET name=?, tagline=?, price_inr=?, price_usd=?, period=?, features=?, perks=?, device_limit=?, max_apps=?, highlight=?, status=? WHERE slug=?")
                        ->execute([
                            (string) $request->input('name'), (string) $request->input('tagline', ''),
                            $priceInr, $priceUsd, in_array($request->input('period'), ['monthly','yearly','once'], true) ? $request->input('period') : 'monthly',
                            json_encode($features), json_encode($perks),
                            (int) $request->input('device_limit', 0), max(1, (int) $request->input('max_apps', 1)),
                            $request->input('highlight') ? 1 : 0,
                            $request->input('status') === 'disabled' ? 'disabled' : 'active',
                            (string) $request->input('slug'),
                        ]);
                    $msg = 'Plan updated.'; $tab = 'plans';
                    break;
                case 'plan_add':
                    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $request->input('slug')));
                    if (!$slug || !(string) $request->input('name')) return redirect()->route('admin.brandpanels.ar', ['tab' => 'plans'])->with('error', 'Slug + name required.');
                    $priceInr = (int) round(((float) $request->input('price_inr', 0)) * 100);
                    $priceUsd = (int) round(((float) $request->input('price_usd', 0)) * 100);
                    $db->prepare("INSERT INTO ar_plans (slug, name, tagline, price_inr, price_usd, period, features, perks, device_limit, max_apps, highlight, sort, status) VALUES (?,?,?,?,?,?,?,?,?,?,0,99,'active')")
                        ->execute([
                            $slug, (string) $request->input('name'), (string) $request->input('tagline', ''),
                            $priceInr, $priceUsd, in_array($request->input('period'), ['monthly','yearly','once'], true) ? $request->input('period') : 'monthly',
                            '[]', '[]', (int) $request->input('device_limit', 0), max(1, (int) $request->input('max_apps', 1)),
                        ]);
                    $msg = 'Plan added.'; $tab = 'plans';
                    break;
                case 'asset_save':
                    $db->prepare("UPDATE ar_assets SET name=?, type=?, url=?, min_plan=?, status=? WHERE asset_id=?")
                        ->execute([
                            (string) $request->input('name'), (string) $request->input('type'),
                            (string) $request->input('url'), (string) $request->input('min_plan', 'trial'),
                            $request->input('status') === 'disabled' ? 'disabled' : 'active',
                            (string) $request->input('asset_id'),
                        ]);
                    $msg = 'Asset updated.'; $tab = 'assets';
                    break;
                case 'asset_add':
                    $aid = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $request->input('asset_id')));
                    if (!$aid || !(string) $request->input('url')) return redirect()->route('admin.brandpanels.ar', ['tab' => 'assets'])->with('error', 'Asset ID + URL required.');
                    $db->prepare("INSERT INTO ar_assets (asset_id, type, name, url, min_plan, status, sort) VALUES (?,?,?,?,?,'active',99)")
                        ->execute([
                            $aid, in_array($request->input('type'), ['lut','sticker','model','background','audio'], true) ? $request->input('type') : 'sticker',
                            (string) $request->input('name'), (string) $request->input('url'),
                            (string) $request->input('min_plan', 'trial'),
                        ]);
                    $msg = 'Asset added.'; $tab = 'assets';
                    break;
                case 'asset_delete':
                    $db->prepare("DELETE FROM ar_assets WHERE asset_id=?")->execute([(string) $request->input('asset_id')]);
                    $msg = 'Asset deleted.'; $tab = 'assets';
                    break;
                default:
                    return redirect()->route('admin.brandpanels.ar')->with('error', 'Unknown op.');
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.brandpanels.ar', ['tab' => $tab])->with('error', 'Save failed: ' . $e->getMessage());
        }
        return redirect()->route('admin.brandpanels.ar', ['tab' => $tab])->with('success', $msg);
    }


    /* ================= BelieVoo AI Studio (aistudio.believoo.com) ================= */
    private function aiApi(string $method, string $path, ?array $body = null)
    {
        $key = (string) env('AISTUDIO_ADMIN_KEY', '');
        $req = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Host' => 'ai.believoo.com',
            'Accept' => 'application/json',
        ])->timeout(10)->baseUrl('http://127.0.0.1:8090');
        $resp = match (strtoupper($method)) {
            'GET'    => $req->get('/v1/admin' . $path),
            'POST'   => $req->post('/v1/admin' . $path, $body ?? []),
            'PUT'    => $req->put('/v1/admin' . $path, $body ?? []),
            'PATCH'  => $req->patch('/v1/admin' . $path, $body ?? []),
            'DELETE' => $req->delete('/v1/admin' . $path),
            default  => throw new \RuntimeException('bad method'),
        };
        $json = $resp->json();
        if (!$resp->ok()) {
            throw new \RuntimeException($json['error']['message'] ?? ('AI API HTTP ' . $resp->status()));
        }
        return is_array($json) ? $json : [];
    }

    public function aistudio(\Illuminate\Http\Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        $apiError = null;
        $status = $stats = $ai = $config = $branding = $seo = $smtp = $storage = $logs = $audit = $iprules = [];
        $keys = $webhooks = $webhookEvents = [];
        $billing = $payments = [];
        $templates = $analytics = $flood = $notify = $tiers = $backups = [];
        try {
            $status   = $this->aiApi('GET', '/status');
            $stats    = $this->aiApi('GET', '/stats');
            if ($tab === 'dashboard') { $logs = $this->aiApi('GET', '/logs'); }
            if ($tab === 'audit')     { $audit = $this->aiApi('GET', '/audit'); }
            if ($tab === 'ai')        { $ai = $this->aiApi('GET', '/ai'); }
            if ($tab === 'provider')  { $config = $this->aiApi('GET', '/config'); }
            if ($tab === 'branding')  { $branding = $this->aiApi('GET', '/branding'); $seo = $this->aiApi('GET', '/seo'); }
            if ($tab === 'mail')      { $smtp = $this->aiApi('GET', '/smtp'); }
            if ($tab === 'keys')      { $keys = $this->aiApi('GET', '/keys')['keys'] ?? []; }
            if ($tab === 'security')  { $iprules = $this->aiApi('GET', '/iprules'); $wh = $this->aiApi('GET', '/webhooks'); $webhooks = $wh['webhooks'] ?? []; $webhookEvents = $wh['events'] ?? []; $flood = $this->aiApi('GET', '/flood'); $notify = $this->aiApi('GET', '/notify'); }
            if ($tab === 'storage')   { $storage = $this->aiApi('GET', '/storage'); $backups = $this->aiApi('GET', '/backups'); }
            if ($tab === 'billing')   { $billing = $this->aiApi('GET', '/billing'); $payments = $this->aiApi('GET', '/payments'); }
            if ($tab === 'playground') { $ai = $this->aiApi('GET', '/ai'); }
            if ($tab === 'prompts')    { $templates = $this->aiApi('GET', '/templates'); $ai = $this->aiApi('GET', '/ai'); }
            if ($tab === 'analytics')  { $analytics = $this->aiApi('GET', '/analytics'); }
            if ($tab === 'keys')       { $keys = $this->aiApi('GET', '/keys')['keys'] ?? []; $tiers = $this->aiApi('GET', '/tiers')['tiers'] ?? []; }
        } catch (\Throwable $e) {
            $apiError = $e->getMessage();
        }
        $siteStatus = $this->probe('https://aistudio.believoo.com/');
        return view('admin.brandpanels.aistudio', compact(
            'tab', 'apiError', 'status', 'stats', 'ai', 'config', 'branding', 'seo', 'smtp',
            'storage', 'logs', 'audit', 'iprules', 'keys', 'webhooks', 'webhookEvents', 'siteStatus', 'billing', 'payments', 'templates', 'analytics', 'flood', 'notify', 'tiers', 'backups'
        ));
    }

    public function aistudioSave(\Illuminate\Http\Request $request)
    {
        $op = $request->input('op');
        $tab = $request->input('tab', 'dashboard');
        $msg = 'Saved.';
        try {
            switch ($op) {
                case 'ai_save':
                    $extra = [];
                    foreach (explode(',', (string) $request->input('extra_models', '')) as $m) {
                        $m = trim($m);
                        if ($m !== '') $extra[$m] = '';
                    }
                    $this->aiApi('PUT', '/ai', [
                        'system_prompt'        => (string) $request->input('system_prompt', ''),
                        'default_temperature'  => (float) $request->input('default_temperature', 0.7),
                        'default_max_tokens'   => (int) $request->input('default_max_tokens', 0),
                        'fallback_provider'    => (string) $request->input('fallback_provider', 'none'),
                        'prompt_caching'       => (bool) $request->input('prompt_caching'),
                        'image_compress'       => (bool) $request->input('image_compress'),
                        'extra_models'         => $extra,
                    ]);
                    $msg = 'AI engine settings saved.'; $tab = 'ai';
                    break;
                case 'provider_save':
                    $map = [];
                    foreach (explode(',', (string) $request->input('model_map', '')) as $pair) {
                        if (strpos($pair, '=') !== false) {
                            [$k, $v] = array_map('trim', explode('=', $pair, 2));
                            if ($k !== '') $map[$k] = $v;
                        }
                    }
                    $body = [
                        'provider' => (string) $request->input('provider', 'gemini'),
                        'external' => [
                            'base_url'      => (string) $request->input('base_url', ''),
                            'default_model' => (string) $request->input('default_model', ''),
                        ],
                        'external_model_map' => $map,
                        'flags'  => [
                            'chat'   => (bool) $request->input('flag_chat'),
                            'image'  => (bool) $request->input('flag_image'),
                            'audio'  => (bool) $request->input('flag_audio'),
                            'code'   => (bool) $request->input('flag_code'),
                            'tools'  => (bool) $request->input('flag_tools'),
                        ],
                        'limits' => [
                            'default_rpm' => (int) $request->input('default_rpm', 0),
                            'default_tpm' => (int) $request->input('default_tpm', 0),
                        ],
                    ];
                    if ((string) $request->input('api_key', '') !== '') {
                        $body['external']['api_key'] = (string) $request->input('api_key');
                    }
                    $this->aiApi('PUT', '/config', $body);
                    $msg = 'Provider config saved.'; $tab = 'provider';
                    break;
                case 'branding_save':
                    $this->aiApi('PUT', '/branding', [
                        'brand_name'     => (string) $request->input('brand_name', ''),
                        'company'        => (string) $request->input('company', ''),
                        'logo_url'       => (string) $request->input('logo_url', ''),
                        'favicon_url'    => (string) $request->input('favicon_url', ''),
                        'primary_color'  => (string) $request->input('primary_color', '#7c5cff'),
                        'accent_color'   => (string) $request->input('accent_color', '#4ade80'),
                        'default_theme'  => (string) $request->input('default_theme', 'dark'),
                        'footer_text'    => (string) $request->input('footer_text', ''),
                        'custom_css'     => (string) $request->input('custom_css', ''),
                        'custom_js'      => (string) $request->input('custom_js', ''),
                        'maintenance'    => (bool) $request->input('maintenance'),
                        'announcement'   => [
                            'on'    => (bool) $request->input('ann_on'),
                            'text'  => (string) $request->input('ann_text', ''),
                            'level' => (string) $request->input('ann_level', 'info'),
                        ],
                    ]);
                    $msg = 'Branding saved.'; $tab = 'branding';
                    break;
                case 'seo_save':
                    $this->aiApi('PUT', '/seo', [
                        'meta_title'       => (string) $request->input('meta_title', ''),
                        'meta_description' => (string) $request->input('meta_description', ''),
                        'og_title'         => (string) $request->input('og_title', ''),
                        'og_description'   => (string) $request->input('og_description', ''),
                        'og_image'         => (string) $request->input('og_image', ''),
                        'robots_txt'       => (string) $request->input('robots_txt', ''),
                        'meta_keywords'    => (string) $request->input('meta_keywords', ''),
                        'ga_id'            => (string) $request->input('ga_id', ''),
                    ]);
                    $msg = 'SEO saved.'; $tab = 'branding';
                    break;
                case 'smtp_save':
                    $body = [
                        'host'       => (string) $request->input('host', ''),
                        'port'       => (int) $request->input('port', 587),
                        'username'   => (string) $request->input('username', ''),
                        'encryption' => (string) $request->input('encryption', 'starttls'),
                        'from_name'  => (string) $request->input('from_name', ''),
                        'from_email' => (string) $request->input('from_email', ''),
                    ];
                    if ((string) $request->input('password', '') !== '') {
                        $body['password'] = (string) $request->input('password');
                    }
                    $this->aiApi('PUT', '/smtp', $body);
                    $msg = 'SMTP saved.'; $tab = 'mail';
                    break;
                case 'smtp_test':
                    $this->aiApi('POST', '/smtp/test', ['to' => (string) $request->input('to')]);
                    $msg = 'Test email sent to ' . $request->input('to') . '.'; $tab = 'mail';
                    break;
                case 'key_create':
                    $scopes = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('scopes', '')))));
                    $res = $this->aiApi('POST', '/keys', [
                        'name'   => (string) $request->input('name', 'key'),
                        'rpm'    => (int) $request->input('rpm', 0),
                        'tpm'    => (int) $request->input('tpm', 0),
                        'scopes' => $scopes,
                        'admin'  => (bool) $request->input('admin'),
                    ]);
                    $newKey = $res['key'] ?? $res['api_key'] ?? null;
                    $msg = 'Key created.' . ($newKey ? ' API key: ' . $newKey . ' — copy now, shown once!' : '');
                    $tab = 'keys';
                    break;
                case 'key_toggle':
                    $this->aiApi('PATCH', '/keys/' . (int) $request->input('id'), ['enabled' => (bool) $request->input('enabled')]);
                    $msg = 'Key updated.'; $tab = 'keys';
                    break;
                case 'key_admin':
                    $this->aiApi('PATCH', '/keys/' . (int) $request->input('id'), ['admin' => (bool) $request->input('admin')]);
                    $msg = 'Key admin flag updated.'; $tab = 'keys';
                    break;
                case 'key_delete':
                    $this->aiApi('DELETE', '/keys/' . (int) $request->input('id'));
                    $msg = 'Key deleted.'; $tab = 'keys';
                    break;
                case 'iprules_save':
                    $bl = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $request->input('blacklist', '')))));
                    $wl = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $request->input('whitelist', '')))));
                    $this->aiApi('PUT', '/iprules', ['blacklist' => $bl, 'whitelist' => $wl]);
                    $msg = 'IP rules saved.'; $tab = 'security';
                    break;
                case 'webhook_add':
                    $ev = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('events', '')))));
                    $this->aiApi('POST', '/webhooks', ['url' => (string) $request->input('url'), 'events' => $ev]);
                    $msg = 'Webhook added.'; $tab = 'security';
                    break;
                case 'webhook_delete':
                    $this->aiApi('DELETE', '/webhooks/' . (int) $request->input('id'));
                    $msg = 'Webhook deleted.'; $tab = 'security';
                    break;
                case 'billing_save':
                    $providers = [];
                    foreach (['razorpay','cashfree','stripe','paypal'] as $p) {
                        $pc = [
                            'enabled' => (bool) $request->input($p . '_enabled'),
                            'mode'    => (string) $request->input($p . '_mode', 'live'),
                            'key_id'  => (string) $request->input($p . '_key_id', ''),
                        ];
                        foreach (['secret','webhook_secret','webhook_id'] as $sk) {
                            $val = (string) $request->input($p . '_' . $sk, '');
                            if ($val !== '') $pc[$sk] = $val;
                        }
                        $providers[$p] = $pc;
                    }
                    $plans = json_decode((string) $request->input('plans', '[]'), true);
                    $this->aiApi('PUT', '/billing', [
                        'default_provider' => (string) $request->input('default_provider', 'razorpay'),
                        'currency'         => strtoupper((string) $request->input('currency', 'INR')),
                        'providers'        => $providers,
                        'plans'            => is_array($plans) ? $plans : [],
                    ]);
                    $msg = 'Billing settings saved.'; $tab = 'billing';
                    break;
                case 'asset_upload':
                    $file = $request->file('file');
                    if (!$file) { throw new \RuntimeException('choose a file to upload'); }
                    $akey = (string) env('AISTUDIO_ADMIN_KEY', '');
                    $resp = \Illuminate\Support\Facades\Http::withHeaders([
                            'Authorization' => 'Bearer ' . $akey,
                        ])->timeout(30)
                        ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                        ->post('http://127.0.0.1:8090/v1/admin/upload', ['kind' => (string) $request->input('kind', 'logo')]);
                    $j = $resp->json() ?: [];
                    if (!$resp->ok() || empty($j['url'])) {
                        throw new \RuntimeException($j['error']['message'] ?? 'upload failed');
                    }
                    $msg = ucfirst((string) $request->input('kind', 'asset')) . ' uploaded: ' . $j['url'] . ' — copy into the Branding field above.';
                    $tab = 'branding';
                    break;
                case 'playground_run':
                    $res = $this->aiApi('POST', '/playground', [
                        'provider'    => ($p = (string) $request->input('pg_provider', '')) !== '' ? $p : null,
                        'model'       => (string) $request->input('pg_model', ''),
                        'temperature' => $request->input('pg_temp') !== null && $request->input('pg_temp') !== '' ? (float) $request->input('pg_temp') : null,
                        'max_tokens'  => $request->input('pg_max_tokens') !== null && $request->input('pg_max_tokens') !== '' ? (int) $request->input('pg_max_tokens') : null,
                        'system'      => ($s = (string) $request->input('pg_system', '')) !== '' ? $s : null,
                        'messages'    => [['role' => 'user', 'content' => (string) $request->input('pg_message', '')]],
                    ]);
                    $tab = 'playground';
                    return redirect()->route('admin.brandpanels.aistudio', ['tab' => 'playground'])
                        ->with('success', 'Response in ' . ($res['latency_ms'] ?? '?') . 'ms via ' . ($res['provider_used'] ?? '?') . ' / ' . ($res['model_used'] ?? '?'))
                        ->with('pg', $res);
                case 'ai_adv_save':
                    $this->aiApi('PUT', '/ai', [
                        'max_ctx'      => (int) $request->input('max_ctx', 0),
                        'stop'         => array_values(array_filter(array_map('trim', explode(',', (string) $request->input('stop', ''))))),
                        'banned_words' => array_values(array_filter(array_map('trim', explode(',', (string) $request->input('banned_words', ''))))),
                        'cost'         => json_decode((string) $request->input('cost', '{}'), true) ?: [],
                    ]);
                    $msg = 'Advanced AI settings saved.'; $tab = 'prompts';
                    break;
                case 'template_add':
                    $tpls = array_values(array_filter(
                        $this->aiApi('GET', '/templates')['templates'] ?? [],
                        fn($t) => empty($t['builtin'])));
                    $name = (string) $request->input('tname', '');
                    $tpls[] = [
                        'id' => strtolower(preg_replace('/\W+/', '-', $name)),
                        'name' => $name,
                        'builtin' => false,
                        'prompt' => (string) $request->input('tprompt', ''),
                    ];
                    $this->aiApi('PUT', '/templates', ['templates' => $tpls]);
                    $msg = 'Template added.'; $tab = 'prompts';
                    break;
                case 'template_delete':
                    $tpls = array_values(array_filter(
                        $this->aiApi('GET', '/templates')['templates'] ?? [],
                        fn($t) => empty($t['builtin']) && ($t['id'] ?? '') !== (string) $request->input('id')));
                    $this->aiApi('PUT', '/templates', ['templates' => $tpls]);
                    $msg = 'Template deleted.'; $tab = 'prompts';
                    break;
                case 'template_apply':
                    $res = $this->aiApi('POST', '/templates/apply', ['id' => (string) $request->input('id')]);
                    $msg = 'Template applied as global system prompt.'; $tab = 'prompts';
                    break;
                case 'tiers_save':
                    $this->aiApi('PUT', '/tiers', ['tiers' => [
                        'free'       => ['rpm' => (int) $request->input('free_rpm', 60),   'tpm' => (int) $request->input('free_tpm', 60000)],
                        'pro'        => ['rpm' => (int) $request->input('pro_rpm', 300),   'tpm' => (int) $request->input('pro_tpm', 500000)],
                        'enterprise' => ['rpm' => (int) $request->input('ent_rpm', 1200),  'tpm' => (int) $request->input('ent_tpm', 4000000)],
                    ]]);
                    $msg = 'Tier limits saved.'; $tab = 'keys';
                    break;
                case 'key_tier':
                    $this->aiApi('POST', '/keys/' . (int) $request->input('id') . '/tier', ['tier' => (string) $request->input('tier')]);
                    $msg = 'Tier assigned — rpm/tpm copied to key.'; $tab = 'keys';
                    break;
                case 'flood_save':
                    $this->aiApi('PUT', '/flood', [
                        'on'          => (bool) $request->input('flood_on'),
                        'per_minute'  => (int) $request->input('per_minute', 300),
                        'ban_minutes' => (int) $request->input('ban_minutes', 10),
                    ]);
                    $msg = 'Flood protection saved.'; $tab = 'security';
                    break;
                case 'notify_save':
                    $this->aiApi('PUT', '/notify', [
                        'telegram' => ['on' => (bool) $request->input('tg_on'), 'bot_token' => (string) $request->input('tg_token', ''), 'chat_id' => (string) $request->input('tg_chat', '')],
                        'discord'  => ['on' => (bool) $request->input('dc_on'), 'webhook_url' => (string) $request->input('dc_url', '')],
                    ]);
                    $msg = 'Alert channels saved.'; $tab = 'security';
                    break;
                case 'notify_test':
                    $res = $this->aiApi('POST', '/notify/test', []);
                    $msg = 'Test alert delivered: ' . json_encode($res['delivered'] ?? $res);
                    $tab = 'security';
                    break;
                case 'alert_send':
                    $this->aiApi('POST', '/alert', ['text' => (string) $request->input('text'), 'kind' => 'manual']);
                    $msg = 'Alert sent.'; $tab = 'security';
                    break;
                case 'backup_now':
                    $res = $this->aiApi('POST', '/backup', []);
                    $msg = 'Backup uploaded: ' . ($res['url'] ?? json_encode($res));
                    $tab = 'storage';
                    break;
                case 'webhook_test':
                    $this->aiApi('POST', '/webhooks/test', []);
                    $msg = 'Test event sent.'; $tab = 'security';
                    break;
                default:
                    return redirect()->route('admin.brandpanels.aistudio')->with('error', 'Unknown op.');
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.brandpanels.aistudio', ['tab' => $tab])->with('error', 'Save failed: ' . $e->getMessage());
        }
        return redirect()->route('admin.brandpanels.aistudio', ['tab' => $tab])->with('success', $msg);
    }

    public function aistudioExport(string $kind)
    {
        if (!in_array($kind, ['usage', 'audit'], true)) { abort(404); }
        $key = (string) env('AISTUDIO_ADMIN_KEY', '');
        $resp = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Host' => 'ai.believoo.com',
        ])->timeout(20)->get('http://127.0.0.1:8090/v1/admin/export', ['kind' => $kind]);
        return response($resp->body(), $resp->status(), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="believoo-' . $kind . '-' . date('Ymd') . '.csv"',
        ]);
    }


    /* ================= HiTune (music.hitune.in — musicpro DB) ================= */
    private function htDb(): PDO
    {
        static $pdo = null;
        if ($pdo) return $pdo;
        $env = @file_get_contents('/www/wwwroot/music/.env') ?: '';
        preg_match('/^DB_PASS=(.*)$/m', $env, $m);
        $pdo = new PDO('mysql:host=localhost;dbname=musicpro;charset=utf8mb4', 'musicpro', trim($m[1] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    public function hitune(\Illuminate\Http\Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        $dbError = null;
        $stats = $users = $plays = $subs = $payments = $withdrawals = $submissions = $reports = [];
        $storyStats = $storyPeople = [];
        try {
            $db = $this->htDb();
            $stats = [
                'users'        => (int) $db->query("SELECT COUNT(*) FROM `_u_list`")->fetchColumn(),
                'plays'        => (int) $db->query("SELECT COUNT(*) FROM `_htx_plays`")->fetchColumn(),
                'plays_24h'    => (int) $db->query("SELECT COUNT(*) FROM `_htx_plays` WHERE time_add > DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn(),
                'subs'         => (int) $db->query("SELECT COUNT(*) FROM `_u_subs`")->fetchColumn(),
                'playlists'    => (int) $db->query("SELECT COUNT(*) FROM `_u_playlists`")->fetchColumn(),
                'submissions'  => (int) $db->query("SELECT COUNT(*) FROM `_dist_submissions`")->fetchColumn(),
                'sub_pending'  => (int) $db->query("SELECT COUNT(*) FROM `_dist_submissions` WHERE status IN ('submitted','in_review')")->fetchColumn(),
                'payments'     => (int) $db->query("SELECT COUNT(*) FROM `_u_payments`")->fetchColumn(),
            ];
            $users = $db->query("SELECT ID, username, name, email, s_playlists, s_payments, s_subscriptions, time_add, time_online FROM `_u_list` ORDER BY ID DESC LIMIT 100")->fetchAll();
            $plays = $db->query("SELECT * FROM `_htx_plays` ORDER BY id DESC LIMIT 100")->fetchAll();
            $subs = $db->query("SELECT s.*, u.username, u.email FROM `_u_subs` s LEFT JOIN `_u_list` u ON u.ID=s.user_id ORDER BY s.ID DESC LIMIT 100")->fetchAll();
            $payments = $db->query("SELECT p.*, u.username, u.email FROM `_u_payments` p LEFT JOIN `_u_list` u ON u.ID=p.user_id ORDER BY p.ID DESC LIMIT 100")->fetchAll();
            $withdrawals = $db->query("SELECT w.*, u.username, u.email FROM `_u_withdrawal` w LEFT JOIN `_u_list` u ON u.ID=w.user_id ORDER BY w.ID DESC LIMIT 100")->fetchAll();
            $submissions = $db->query("SELECT s.*, u.username, u.email FROM `_dist_submissions` s LEFT JOIN `_u_list` u ON u.ID=s.user_id ORDER BY s.id DESC LIMIT 100")->fetchAll();
            $reports = $db->query("SELECT * FROM `_u_reports` ORDER BY time_add DESC LIMIT 50")->fetchAll();
            $stories = $db->query("SELECT * FROM `_ht_admin_stories` ORDER BY ID DESC LIMIT 60")->fetchAll();
            if ($stories) {
                $refs = array_map(fn($s) => 'a:' . $s['ID'], $stories);
                $in = "'" . implode("','", $refs) . "'";
                foreach (['_ht_story_views' => 'views', '_ht_story_likes' => 'likes', '_ht_story_replies' => 'replies'] as $t => $k) {
                    $q = @$db->query("SELECT story_ref, COUNT(*) c FROM `$t` WHERE story_ref IN ($in) GROUP BY story_ref");
                    foreach ($q ?: [] as $r) $storyStats[$r['story_ref']][$k] = (int) $r['c'];
                }
                // Viewers + replies detail (latest first, capped)
                $q = @$db->query("SELECT v.story_ref, u.username, v.time_add FROM `_ht_story_views` v LEFT JOIN `_u_list` u ON u.ID=v.user_id WHERE v.story_ref IN ($in) ORDER BY v.ID DESC LIMIT 400");
                foreach ($q ?: [] as $r) $storyPeople[$r['story_ref']]['viewers'][] = ['u' => $r['username'] ?: 'guest', 't' => $r['time_add']];
                $q = @$db->query("SELECT r.story_ref, u.username, r.message, r.time_add FROM `_ht_story_replies` r LEFT JOIN `_u_list` u ON u.ID=r.user_id WHERE r.story_ref IN ($in) ORDER BY r.ID DESC LIMIT 300");
                foreach ($q ?: [] as $r) $storyPeople[$r['story_ref']]['replies'][] = ['u' => $r['username'] ?: '?', 'm' => $r['message'], 't' => $r['time_add']];
                // IyolMe-side engagement on mirrored stories
                $idb = $this->iyolDb();
                if ($idb) {
                    $imap = [];
                    foreach ($stories as $s) if (!empty($s['iyol_story_id'])) $imap[(int) $s['iyol_story_id']] = 'a:' . $s['ID'];
                    if ($imap) {
                        $qin = implode(',', array_keys($imap));
                        foreach ($idb->query("SELECT id, view_by_user_ids FROM stories WHERE id IN ($qin)") as $r) {
                            $storyStats[$imap[$r['id']]]['iyol_views'] = count(array_filter(explode(',', (string) $r['view_by_user_ids'])));
                        }
                        foreach ($idb->query("SELECT story_id, sticker, COUNT(*) c FROM tbl_story_interactions WHERE story_id IN ($qin) GROUP BY story_id, sticker") as $r) {
                            $k = $r['sticker'] === 'reaction' ? 'iyol_likes' : ($r['sticker'] === 'reply' ? 'iyol_replies' : 'iyol_other');
                            $storyStats[$imap[$r['story_id']]][$k] = ($storyStats[$imap[$r['story_id']]][$k] ?? 0) + (int) $r['c'];
                        }
                        foreach ($idb->query("SELECT i.story_id, u.username, i.note, i.created_at FROM tbl_story_interactions i LEFT JOIN tbl_users u ON u.id=i.user_id WHERE i.story_id IN ($qin) AND i.sticker='reply' ORDER BY i.id DESC LIMIT 300") as $r) {
                            $storyPeople[$imap[$r['story_id']]]['iyol_replies'][] = ['u' => $r['username'] ?: '?', 'm' => $r['note'], 't' => $r['created_at']];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }
        $siteStatus = $this->probe('https://music.hitune.in/');
        $mainStatus = $this->probe('https://hitune.in/');
        $stories = $stories ?? [];
        $storyStats = $storyStats ?? [];
        $storyPeople = $storyPeople ?? [];
        return view('admin.brandpanels.hitune', compact('tab', 'stats', 'users', 'plays', 'subs', 'payments', 'withdrawals', 'submissions', 'reports', 'stories', 'storyStats', 'storyPeople', 'siteStatus', 'mainStatus', 'dbError'));
    }

    public function hituneSave(\Illuminate\Http\Request $request)
    {
        $op = $request->input('op');
        $tab = $request->input('tab', 'dashboard');
        $msg = 'Saved.';
        try {
            $db = $this->htDb();
            switch ($op) {
                case 'sub_update':
                    $allowed = ['submitted', 'in_review', 'in_progress', 'approved', 'rejected', 'launched', 'taken_down'];
                    $id = (int) $request->input('id');
                    $fields = [];
                    $params = [];
                    if ($request->filled('status') && in_array($request->input('status'), $allowed, true)) {
                        $fields[] = 'status=?'; $params[] = $request->input('status');
                    }
                    foreach (['admin_notes', 'isrc', 'upc', 'launch_date', 'tunecore_url', 'tunecore_status'] as $col) {
                        if ($request->has($col)) { $fields[] = "$col=?"; $params[] = (string) $request->input($col); }
                    }
                    if (!$fields) return redirect()->route('admin.brandpanels.hitune', ['tab' => 'dist'])->with('error', 'Nothing to update.');
                    $params[] = $id;
                    $db->prepare("UPDATE `_dist_submissions` SET " . implode(',', $fields) . ", time_update=NOW() WHERE id=?")->execute($params);
                    $msg = 'Submission updated.'; $tab = 'dist';
                    break;
                case 'story_save':
                    $content = trim((string) $request->input('content', ''));
                    $type = (int) $request->input('type', 0) ? 1 : 0;
                    if ($request->hasFile('media') && $request->file('media')->isValid()) {
                        $f = $request->file('media');
                        $mime = $f->getMimeType() ?: '';
                        $isImg = str_starts_with($mime, 'image/');
                        $isVid = str_starts_with($mime, 'video/');
                        if (!$isImg && !$isVid) throw new \RuntimeException('Media must be an image or video.');
                        if ($f->getSize() > 40 * 1024 * 1024) throw new \RuntimeException('Media too large (40MB max).');
                        $dir = '/www/wwwroot/music/files/ht_stories';
                        if (!is_dir($dir)) @mkdir($dir, 0755, true);
                        $name = 'story_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $f->getClientOriginalExtension();
                        $f->move($dir, $name);
                        @chmod($dir . '/' . $name, 0644);
                        $content = 'https://music.hitune.in/files/ht_stories/' . $name;
                        $type = $isVid ? 1 : 0;
                    }
                    if ($content === '' || !preg_match('/^https?:\/\//i', $content))
                        throw new \RuntimeException('Upload a media file or paste a media URL.');
                    $dur = is_numeric($request->input('duration')) ? (string)(int)$request->input('duration') : '5';
                    $repeat = $request->input('repeat') ? 1 : 0;
                    $db->prepare("INSERT INTO `_ht_admin_stories` (type,content,thumbnail,duration,caption,link_url,display_name,avatar,repeat_forever,active) VALUES (?,?,?,?,?,?,?,?,?,1)")
                        ->execute([$type, $content, trim((string)$request->input('thumbnail','')), $dur,
                            trim((string)$request->input('caption','')), trim((string)$request->input('link_url','')),
                            trim((string)$request->input('display_name','')) ?: 'HiTune', trim((string)$request->input('avatar','')), $repeat]);
                    $newId = (int)$db->lastInsertId();
                    $iyolId = $this->iyolStorySync([$newId,$type,$content,trim((string)$request->input('thumbnail','')),$dur,trim((string)$request->input('caption','')),trim((string)$request->input('link_url','')),$repeat,0]);
                    if ($iyolId) $db->prepare('UPDATE `_ht_admin_stories` SET iyol_story_id=? WHERE ID=?')->execute([$iyolId, $newId]);
                    $msg = 'Story posted' . ($iyolId ? ' · synced to IyolMe' : '') . '.';
                    $tab = 'stories';
                    break;
                case 'story_toggle':
                    $id = (int) $request->input('id');
                    $field = $request->input('field');
                    if (!in_array($field, ['repeat_forever','active'], true)) throw new \RuntimeException('Bad field.');
                    $row = $db->query('SELECT * FROM `_ht_admin_stories` WHERE ID=' . $id . ' LIMIT 1')->fetch();
                    if (!$row) throw new \RuntimeException('Story not found.');
                    $new = $row[$field] ? 0 : 1;
                    $db->prepare("UPDATE `_ht_admin_stories` SET {$field}=? WHERE ID=?")->execute([$new, $id]);
                    $row[$field] = $new;
                    if ($field === 'repeat_forever')
                        $this->iyolStorySync([$row['ID'],$row['type'],$row['content'],$row['thumbnail'],$row['duration'],$row['caption'],$row['link_url'],$new,$row['iyol_story_id']]);
                    $msg = ucfirst(str_replace('_',' ',$field)) . ' → ' . ($new ? 'ON' : 'OFF') . '.';
                    $tab = 'stories';
                    break;
                case 'story_delete':
                    $id = (int) $request->input('id');
                    $row = $db->query('SELECT iyol_story_id FROM `_ht_admin_stories` WHERE ID=' . $id . ' LIMIT 1')->fetch();
                    if ($row && $row['iyol_story_id']) $this->iyolStoryDelete((int)$row['iyol_story_id']);
                    $db->prepare('DELETE FROM `_ht_admin_stories` WHERE ID=?')->execute([$id]);
                    $msg = 'Story deleted.';
                    $tab = 'stories';
                    break;
                default:
                    return redirect()->route('admin.brandpanels.hitune')->with('error', 'Unknown op.');
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.brandpanels.hitune', ['tab' => $tab])->with('error', 'Save failed: ' . $e->getMessage());
        }
        return redirect()->route('admin.brandpanels.hitune', ['tab' => $tab])->with('success', $msg);
    }

    /* ================= IyolMe story mirror ================= */
    private function iyolDb(): ?PDO
    {
        static $pdo = null, $tried = false;
        if ($tried) return $pdo;
        $tried = true;
        $env = @parse_ini_file('/www/wwwroot/iyolme/.env', false, INI_SCANNER_RAW) ?: [];
        if (empty($env['DB_DATABASE']) || empty($env['DB_USERNAME'])) return null;
        try {
            $pdo = new PDO('mysql:host=' . ($env['DB_HOST'] ?? 'localhost') . ';dbname=' . $env['DB_DATABASE'] . ';charset=utf8mb4',
                $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\Throwable $e) { $pdo = null; }
        return $pdo;
    }

    private function iyolOfficialUser(PDO $db): ?int
    {
        $r = $db->query("SELECT id FROM tbl_users WHERE hitune_username='admin' OR username='blitzomnii' ORDER BY is_verify DESC, id ASC LIMIT 1");
        $id = $r ? $r->fetchColumn() : 0;
        if (!$id) $id = (int)$db->query('SELECT id FROM tbl_users WHERE is_verify=1 ORDER BY id ASC LIMIT 1')->fetchColumn();
        return $id ?: null;
    }

    private function iyolStorySync(array $s): ?int
    {
        try {
            $db = $this->iyolDb();
            if (!$db) return null;
            $uid = $this->iyolOfficialUser($db);
            if (!$uid) return null;
            [$id,$type,$content,$thumb,$dur,$cap,$link,$repeat,$iyolId] = $s;
            $meta = json_encode(['source'=>'hitune_admin','caption'=>$cap,'link_url'=>$link]);
            if ($iyolId) {
                $db->prepare('UPDATE stories SET type=?,content=?,thumbnail=?,duration=?,is_highlighted=?,metadata=?,deleted_at=NULL,updated_at=NOW() WHERE id=?')
                    ->execute([$type,$content,$thumb ?: null,$dur,$repeat ? 1 : 0,$meta,(int)$iyolId]);
                return (int)$iyolId;
            }
            $db->prepare('INSERT INTO stories (user_id,type,content,thumbnail,duration,audience_type,allow_replies,allow_reshare,metadata,is_highlighted,created_at,updated_at) VALUES (?,?,?,?,?,0,0,1,?,?,NOW(),NOW())')
                ->execute([$uid,$type,$content,$thumb ?: null,$dur,$meta,$repeat ? 1 : 0]);
            return (int)$db->lastInsertId();
        } catch (\Throwable $e) { return null; }
    }

    private function iyolStoryDelete(int $iyolId): void
    {
        try { $db = $this->iyolDb(); if ($db) $db->prepare('DELETE FROM stories WHERE id=?')->execute([$iyolId]); } catch (\Throwable $e) {}
    }

    /* ================= HiTune Distribution (distribution.hitune.in — web DB) ================= */
    private function distDb(): PDO
    {
        static $pdo = null;
        if ($pdo) return $pdo;
        $cfg = @file_get_contents('/www/wwwroot/web/config.php') ?: '';
        preg_match("/define\\('DB_PASS',\\s*'([^']*)'\\)/", $cfg, $m);
        $pdo = new PDO('mysql:host=localhost;dbname=web;charset=utf8mb4', 'web', $m[1] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    public function dist(\Illuminate\Http\Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        $dbError = null;
        $stats = $releases = $submissions = $artists = $verifications = $payments = $payouts = $withdrawals = $royalties = $users = $platforms = [];
        try {
            $db = $this->distDb();
            $stats = [
                'users'          => (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'releases'       => (int) $db->query("SELECT COUNT(*) FROM releases")->fetchColumn(),
                'releases_live'  => (int) $db->query("SELECT COUNT(*) FROM releases WHERE status='live'")->fetchColumn(),
                'releases_pend'  => (int) $db->query("SELECT COUNT(*) FROM releases WHERE status IN ('submitted','in_progress','ready')")->fetchColumn(),
                'submissions'    => (int) $db->query("SELECT COUNT(*) FROM submissions")->fetchColumn(),
                'sub_pending'    => (int) $db->query("SELECT COUNT(*) FROM submissions WHERE status IN ('submitted','in_review')")->fetchColumn(),
                'artist_reqs'    => (int) $db->query("SELECT COUNT(*) FROM user_artists WHERE status='pending'")->fetchColumn(),
                'verif_pending'  => (int) $db->query("SELECT COUNT(*) FROM artist_verifications WHERE status='pending'")->fetchColumn(),
                'payout_pending' => (int) $db->query("SELECT COUNT(*) FROM payouts WHERE status='pending'")->fetchColumn(),
                'wd_pending'     => (int) $db->query("SELECT COUNT(*) FROM withdrawal_requests WHERE status='pending'")->fetchColumn(),
                'revenue'        => (float) $db->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE payment_status='completed'")->fetchColumn(),
                'royalty_rows'   => (int) $db->query("SELECT COUNT(*) FROM track_royalties")->fetchColumn(),
            ];
            $releases = $db->query("SELECT r.*, u.email, u.name AS user_name FROM releases r LEFT JOIN users u ON u.id=r.user_id ORDER BY r.id DESC LIMIT 100")->fetchAll();
            $submissions = $db->query("SELECT s.*, u.email FROM submissions s LEFT JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT 100")->fetchAll();
            $artists = $db->query("SELECT a.*, u.email FROM user_artists a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 100")->fetchAll();
            $verifications = $db->query("SELECT v.*, u.email FROM artist_verifications v LEFT JOIN users u ON u.id=v.user_id ORDER BY v.id DESC LIMIT 100")->fetchAll();
            $payments = $db->query("SELECT p.*, u.email FROM payments p LEFT JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 100")->fetchAll();
            $payouts = $db->query("SELECT p.*, u.email FROM payouts p LEFT JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 100")->fetchAll();
            $withdrawals = $db->query("SELECT w.*, u.email FROM withdrawal_requests w LEFT JOIN users u ON u.id=w.user_id ORDER BY w.id DESC LIMIT 100")->fetchAll();
            $royalties = $db->query("SELECT * FROM royalty_imports ORDER BY id DESC LIMIT 50")->fetchAll();
            $users = $db->query("SELECT id, name, email, email_verified, auth_type, referral_code, referral_credit, created_at FROM users ORDER BY id DESC LIMIT 100")->fetchAll();
            $platforms = $db->query("SELECT * FROM platform_settings ORDER BY platform_name LIMIT 50")->fetchAll();
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }
        $siteStatus = $this->probe('https://distribution.hitune.in/');
        return view('admin.brandpanels.dist', compact('tab', 'stats', 'releases', 'submissions', 'artists', 'verifications', 'payments', 'payouts', 'withdrawals', 'royalties', 'users', 'platforms', 'siteStatus', 'dbError'));
    }

    public function distSave(\Illuminate\Http\Request $request)
    {
        $op = $request->input('op');
        $tab = $request->input('tab', 'dashboard');
        $msg = 'Saved.';
        try {
            $db = $this->distDb();
            $id = (int) $request->input('id');
            switch ($op) {
                case 'release_status':
                    $allowed = ['draft','submitted','in_progress','ready','live','rejected','takedown_requested','taken_down'];
                    $st = $request->input('status');
                    if (!in_array($st, $allowed, true)) throw new \RuntimeException('Bad status.');
                    $col = ['in_progress'=>'in_progress_at','ready'=>'ready_at','rejected'=>'rejected_at','live'=>'live_at','taken_down'=>'taken_down_at','takedown_requested'=>'takedown_requested_at'][$st] ?? null;
                    $db->prepare("UPDATE releases SET status=?, admin_notes=?" . ($col ? ", $col=NOW()" : '') . " WHERE id=?")
                        ->execute([$st, (string) $request->input('admin_notes', ''), $id]);
                    $msg = "Release #$id → $st."; $tab = 'releases';
                    break;
                case 'sub_status':
                    $allowed = ['submitted','in_review','approved','rejected','launched'];
                    $st = $request->input('status');
                    if (!in_array($st, $allowed, true)) throw new \RuntimeException('Bad status.');
                    $db->prepare("UPDATE submissions SET status=? WHERE id=?")->execute([$st, $id]);
                    $msg = "Submission #$id → $st."; $tab = 'submissions';
                    break;
                case 'artist_status':
                    $st = $request->input('status') === 'rejected' ? 'rejected' : 'approved';
                    $db->prepare("UPDATE user_artists SET status=?, admin_notes=?, approved_at=IF(?='approved', NOW(), approved_at) WHERE id=?")
                        ->execute([$st, (string) $request->input('admin_notes', ''), $st, $id]);
                    $msg = "Artist request #$id → $st."; $tab = 'artists';
                    break;
                case 'artist_toggle':
                    $db->prepare("UPDATE admin_artists SET is_active = NOT is_active WHERE id=?")->execute([$id]);
                    $msg = 'Artist toggled.'; $tab = 'artists';
                    break;
                case 'verif_status':
                    $allowed = ['pending','verified','rejected','revoked'];
                    $st = $request->input('status');
                    if (!in_array($st, $allowed, true)) throw new \RuntimeException('Bad status.');
                    $db->prepare("UPDATE artist_verifications SET status=?, admin_notes=?, reviewed_at=NOW() WHERE id=?")
                        ->execute([$st, (string) $request->input('admin_notes', ''), $id]);
                    $msg = "Verification #$id → $st."; $tab = 'verifications';
                    break;
                case 'payout_status':
                    $st = $request->input('status') === 'rejected' ? 'rejected' : 'approved';
                    $db->prepare("UPDATE payouts SET status=? WHERE id=?")->execute([$st, $id]);
                    $msg = "Payout #$id → $st."; $tab = 'payouts';
                    break;
                case 'wd_status':
                    $allowed = ['pending','approved','rejected','processing','completed','failed'];
                    $st = $request->input('status');
                    if (!in_array($st, $allowed, true)) throw new \RuntimeException('Bad status.');
                    $db->prepare("UPDATE withdrawal_requests SET status=?, admin_notes=?, transaction_id=?, processed_at=IF(? IN ('approved','processing','completed'), NOW(), processed_at), completed_at=IF(?='completed', NOW(), completed_at) WHERE id=?")
                        ->execute([$st, (string) $request->input('admin_notes', ''), (string) $request->input('transaction_id', ''), $st, $st, $id]);
                    $msg = "Withdrawal #$id → $st."; $tab = 'withdrawals';
                    break;
                case 'user_verify':
                    $db->prepare("UPDATE users SET email_verified=1 WHERE id=?")->execute([$id]);
                    $msg = 'User verified.'; $tab = 'users';
                    break;
                default:
                    return redirect()->route('admin.brandpanels.dist')->with('error', 'Unknown op.');
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.brandpanels.dist', ['tab' => $tab])->with('error', 'Save failed: ' . $e->getMessage());
        }
        return redirect()->route('admin.brandpanels.dist', ['tab' => $tab])->with('success', $msg);
    }


    /* ================= Hitune VIBE (rv.hitune.in — hitune_blog DB) ================= */
    private function vibeDb(): PDO
    {
        static $pdo = null;
        if ($pdo) return $pdo;
        $env = @file_get_contents('/www/wwwroot/hitune/.env') ?: '';
        preg_match('/^DB_PASSWORD=(.*)$/m', $env, $m);
        $pass = trim($m[1] ?? '', " \t\"'");
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=hitune_blog;charset=utf8mb4', 'hitune_blog', $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    public function vibe(\Illuminate\Http\Request $request)
    {
        $tab = $request->get('tab', 'dashboard');
        $dbError = null;
        $stats = $posts = $categories = $ads = $comments = $quizzes = $users = $rss = [];
        try {
            $db = $this->vibeDb();
            $stats = [
                'posts'         => (int) $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
                'published'     => (int) $db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn(),
                'pending'       => (int) $db->query("SELECT COUNT(*) FROM posts WHERE is_approved=0")->fetchColumn(),
                'views'         => (int) $db->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn(),
                'quizzes'       => (int) $db->query("SELECT COUNT(*) FROM quizzes")->fetchColumn(),
                'categories'    => (int) $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
                'users'         => (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'comments_pend' => (int) $db->query("SELECT COUNT(*) FROM comments WHERE is_approved=0")->fetchColumn(),
                'ads'           => (int) $db->query("SELECT COUNT(*) FROM ads WHERE is_active=1")->fetchColumn(),
                'rss'           => (int) $db->query("SELECT COUNT(*) FROM rss_sources WHERE is_active=1")->fetchColumn(),
            ];
            $posts = $db->query("SELECT p.id, p.title, p.slug, p.status, p.is_approved, p.is_featured, p.views, p.published_at, p.created_at, c.name AS cat_name, u.name AS author FROM posts p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 100")->fetchAll();
            $categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id=c.id) AS post_count FROM categories c ORDER BY c.`order`, c.id LIMIT 200")->fetchAll();
            $ads = $db->query("SELECT * FROM ads ORDER BY `order`, id LIMIT 100")->fetchAll();
            $comments = $db->query("SELECT cm.*, p.title AS post_title FROM comments cm LEFT JOIN posts p ON p.id=cm.post_id ORDER BY cm.id DESC LIMIT 100")->fetchAll();
            $quizzes = $db->query("SELECT * FROM quizzes ORDER BY id DESC LIMIT 100")->fetchAll();
            $users = $db->query("SELECT id, name, email, balance, total_withdrawn, monetization_status, is_auto_approved, is_admin, email_verified_at, created_at FROM users ORDER BY id DESC LIMIT 100")->fetchAll();
            $rss = $db->query("SELECT r.*, c.name AS cat_name FROM rss_sources r LEFT JOIN categories c ON c.id=r.category_id ORDER BY r.id DESC LIMIT 100")->fetchAll();
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }
        $siteStatus = $this->probe('https://rv.hitune.in/');
        return view('admin.brandpanels.vibe', compact('tab', 'stats', 'posts', 'categories', 'ads', 'comments', 'quizzes', 'users', 'rss', 'siteStatus', 'dbError'));
    }

    public function vibeSave(\Illuminate\Http\Request $request)
    {
        $op = $request->input('op');
        $tab = $request->input('tab', 'dashboard');
        $msg = 'Saved.';
        try {
            $db = $this->vibeDb();
            $id = (int) $request->input('id');
            switch ($op) {
                case 'post_approve':
                    $db->prepare("UPDATE posts SET is_approved = NOT is_approved WHERE id=?")->execute([$id]);
                    $msg = 'Post approval toggled.'; $tab = 'posts';
                    break;
                case 'post_featured':
                    $db->prepare("UPDATE posts SET is_featured = NOT is_featured WHERE id=?")->execute([$id]);
                    $msg = 'Post featured toggled.'; $tab = 'posts';
                    break;
                case 'post_delete':
                    $db->prepare("DELETE FROM comments WHERE post_id=?")->execute([$id]);
                    $db->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
                    $msg = 'Post deleted.'; $tab = 'posts';
                    break;
                case 'cat_add':
                    $name = trim((string) $request->input('name'));
                    if (!$name) return redirect()->route('admin.brandpanels.vibe', ['tab' => 'categories'])->with('error', 'Name required.');
                    $slug = \Illuminate\Support\Str::slug($name);
                    $db->prepare("INSERT INTO categories (name, slug, description, `order`, created_at, updated_at) VALUES (?,?,?,99,NOW(),NOW())")
                        ->execute([$name, $slug, (string) $request->input('description', '')]);
                    $msg = 'Category added.'; $tab = 'categories';
                    break;
                case 'cat_delete':
                    $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
                    $msg = 'Category deleted.'; $tab = 'categories';
                    break;
                case 'ad_toggle':
                    $db->prepare("UPDATE ads SET is_active = NOT is_active WHERE id=?")->execute([$id]);
                    $msg = 'Ad toggled.'; $tab = 'ads';
                    break;
                case 'comment_approve':
                    $db->prepare("UPDATE comments SET is_approved = NOT is_approved WHERE id=?")->execute([$id]);
                    $msg = 'Comment toggled.'; $tab = 'comments';
                    break;
                case 'comment_delete':
                    $db->prepare("DELETE FROM comments WHERE id=?")->execute([$id]);
                    $msg = 'Comment deleted.'; $tab = 'comments';
                    break;
                case 'quiz_toggle':
                    $db->prepare("UPDATE quizzes SET is_active = NOT is_active WHERE id=?")->execute([$id]);
                    $msg = 'Quiz toggled.'; $tab = 'quizzes';
                    break;
                case 'user_autoapprove':
                    $db->prepare("UPDATE users SET is_auto_approved = NOT is_auto_approved WHERE id=?")->execute([$id]);
                    $msg = 'Auto-approve toggled.'; $tab = 'users';
                    break;
                case 'user_verify':
                    $db->prepare("UPDATE users SET email_verified_at=NOW() WHERE id=?")->execute([$id]);
                    $msg = 'User verified.'; $tab = 'users';
                    break;
                case 'rss_toggle':
                    $db->prepare("UPDATE rss_sources SET is_active = NOT is_active WHERE id=?")->execute([$id]);
                    $msg = 'RSS source toggled.'; $tab = 'rss';
                    break;
                default:
                    return redirect()->route('admin.brandpanels.vibe')->with('error', 'Unknown op.');
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.brandpanels.vibe', ['tab' => $tab])->with('error', 'Save failed: ' . $e->getMessage());
        }
        return redirect()->route('admin.brandpanels.vibe', ['tab' => $tab])->with('success', $msg);
    }

}
