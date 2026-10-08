<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GhcController extends Controller
{
    protected function ghcApiToken(): ?string
    {
        try {
            $serviceKey = DB::connection('ghc')->table('admin_configs')->where('key', 'ghc_admin_service_key')->value('value');
            if (!$serviceKey) return null;
            $adminEmail = DB::connection('ghc')->table('users')->where('role', 'admin')->value('email') ?? 'admin@believoo.com';
            $r = Http::timeout(5)->post('http://127.0.0.1:8000/api/admin/service-auth', [
                'serviceKey' => $serviceKey,
                'email' => $adminEmail,
            ]);
            return $r->successful() ? $r->json('token') : null;
        } catch (\Exception $e) {
            Log::error('GHC admin token error: ' . $e->getMessage());
            return null;
        }
    }

    protected function ghcApiGet(string $path, array $params = [])
    {
        $token = $this->ghcApiToken();
        if (!$token) return [];
        $r = Http::withToken($token)->timeout(10)->get('http://127.0.0.1:8000/api' . $path, $params);
        return $r->successful() ? $r->json() : [];
    }

    protected function ghcApiPost(string $path, array $body = [])
    {
        $token = $this->ghcApiToken();
        if (!$token) return null;
        $r = Http::withToken($token)->timeout(10)->post('http://127.0.0.1:8000/api' . $path, $body);
        return $r;
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'overview');

        $stats = [
            'total_orders' => DB::connection('ghc')->table('customer_orders')->count(),
            'total_subs' => DB::connection('ghc')->table('subscriptions')->count(),
            'total_domains' => DB::connection('ghc')->table('domain_registrations')->count(),
            'total_users' => DB::connection('ghc')->table('users')->count(),
            'total_tickets' => DB::connection('ghc')->table('support_tickets')->count(),
            'total_payments' => DB::connection('ghc')->table('payment_transactions')->count(),
            'revenue' => DB::connection('ghc')->table('payment_transactions')->where('status', 'COMPLETED')->sum('amount'),
        ];

        $orders = DB::connection('ghc')->table('customer_orders')->orderBy('created_at', 'desc')->limit(50)->get();
        $subscriptions = DB::connection('ghc')->table('subscriptions')->orderBy('created_at', 'desc')->limit(50)->get();
        $domains = DB::connection('ghc')->table('domain_registrations')->orderBy('created_at', 'desc')->limit(50)->get();
        $tickets = DB::connection('ghc')->table('support_tickets')->orderBy('created_at', 'desc')->limit(50)->get();
        $users = DB::connection('ghc')->table('users')->orderBy('created_at', 'desc')->limit(50)->get();
        $payments = DB::connection('ghc')->table('payment_transactions')->orderBy('created_at', 'desc')->limit(50)->get();
        $logs = DB::connection('ghc')->table('system_logs')->orderBy('created_at', 'desc')->limit(50)->get();

        $catalog = $this->ghcApiGet('/admin/plans');
        $tlds = $this->ghcApiGet('/admin/domain-tlds');
        $margins = $this->ghcApiGet('/admin/margins');
        $brand = $this->ghcApiGet('/admin/brand');
        $settings = $this->ghcApiGet('/admin/settings');
        $credentials = $this->ghcApiGet('/admin/credentials');
        $system = $this->ghcApiGet('/public/status');

        return view('admin.ghc.index', compact(
            'tab', 'stats', 'orders', 'subscriptions', 'domains', 'tickets', 'users', 'payments', 'logs',
            'catalog', 'tlds', 'margins', 'brand', 'settings', 'credentials', 'system'
        ));
    }

    public function syncCatalog()
    {
        $this->ghcApiPost('/admin/sync-provider-plans');
        return redirect()->route('admin.ghc.index', ['tab' => 'catalog'])->with('success', 'Catalog sync requested.');
    }

    public function retryOrder($id)
    {
        $this->ghcApiPost('/admin/orders/' . $id . '/retry-provision');
        return redirect()->route('admin.ghc.index', ['tab' => 'orders'])->with('success', 'Retry provisioning requested.');
    }

    public function subscriptionAction(Request $request, $id)
    {
        $this->ghcApiPost('/admin/subscriptions/' . $id . '/lifecycle', [
            'action' => $request->input('action', 'reboot'),
        ]);
        return redirect()->route('admin.ghc.index', ['tab' => 'subscriptions'])->with('success', 'Lifecycle action sent.');
    }

    public function updateCredentials(Request $request)
    {
        $data = $request->except(['_token']);
        $provider = [];
        foreach (['provider_app_key', 'provider_app_secret', 'provider_consumer_key', 'provider_endpoint', 'provider_subsidiary'] as $k) {
            if ($request->has($k)) $provider[$k] = $request->input($k);
        }
        if ($provider) $data['provider'] = $provider;
        if (isset($data['gateways']) && is_array($data['gateways'])) {
            $list = [];
            foreach ($data['gateways'] as $name => $cfg) {
                $isActive = !empty($cfg['isActive']);
                unset($cfg['isActive']);
                $list[] = ['name' => $name, 'isActive' => $isActive, 'config' => $cfg];
            }
            $data['gateways'] = $list;
        }
        $this->ghcApiPost('/admin/credentials', $data);
        return redirect()->route('admin.ghc.index', ['tab' => 'credentials'])->with('success', 'Credentials updated.');
    }

    public function updateSettings(Request $request)
    {
        $this->ghcApiPost('/admin/settings', $request->except(['_token']));
        return redirect()->route('admin.ghc.index', ['tab' => 'settings'])->with('success', 'Settings updated.');
    }

    public function updateTld(Request $request)
    {
        $this->ghcApiPost('/admin/domain-tlds', [
            'tld' => $request->input('tld'),
            'baseCost' => $request->input('baseCost'),
            'marginPercent' => $request->input('marginPercent'),
            'isActive' => $request->boolean('isActive'),
        ]);
        return redirect()->route('admin.ghc.index', ['tab' => 'domain-tlds'])->with('success', 'TLD updated.');
    }

    public function updatePlan(Request $request, $planCode)
    {
        $this->ghcApiPost('/admin/plans/' . $planCode . '/override', [
            'overridePrice' => $request->input('overridePrice'),
            'overrideMargin' => $request->input('overrideMargin'),
        ]);
        return redirect()->route('admin.ghc.index', ['tab' => 'catalog'])->with('success', 'Plan updated.');
    }

    public function updateMargin(Request $request)
    {
        $this->ghcApiPost('/admin/margins', [
            'category' => $request->input('category'),
            'percent' => $request->input('percent'),
        ]);
        return redirect()->route('admin.ghc.index', ['tab' => 'margins'])->with('success', 'Margin updated.');
    }

    public function updateTicket(Request $request, $id)
    {
        $this->ghcApiPost('/support/tickets/' . $id . '/status', [
            'status' => $request->input('status'),
        ]);
        return redirect()->route('admin.ghc.index', ['tab' => 'support'])->with('success', 'Ticket status updated.');
    }

    public function updateUser(Request $request, $id)
    {
        $this->ghcApiPost('/admin/users/' . $id, [
            'role' => $request->input('role'),
            'is_suspended' => $request->boolean('is_suspended'),
        ]);
        return redirect()->route('admin.ghc.index', ['tab' => 'users'])->with('success', 'User updated.');
    }
}
