<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    public function index()
    {
        $brands = [
            [
                'key' => 'believoo',
                'name' => 'Believoo Pvt Ltd',
                'tagline' => 'Parent company — main dashboard, users, billing & operations.',
                'icon' => 'fa-building',
                'color' => '#00b7ff',
                'link' => route('admin.dashboard'),
                'stats' => [
                    'Users' => DB::table('users')->count(),
                    'Orders' => DB::table('orders')->count(),
                    'Tickets' => DB::table('tickets')->count(),
                ],
            ],
            [
                'key' => 'ghc',
                'name' => 'GHC — Go Host Cloud',
                'tagline' => 'Hosting, cloud, VPS, dedicated servers, domains & billing.',
                'icon' => 'fa-cloud',
                'color' => '#a855f7',
                'link' => route('admin.ghc.index'),
                'stats' => [
                    'Orders' => DB::connection('ghc')->table('customer_orders')->count(),
                    'Subscriptions' => DB::connection('ghc')->table('subscriptions')->count(),
                    'Domains' => DB::connection('ghc')->table('domain_registrations')->count(),
                ],
            ],
            [
                'key' => 'bconnect',
                'name' => 'Bmydesk',
                'tagline' => 'Company & workspace management, projects, invoices.',
                'icon' => 'fa-rocket',
                'color' => '#22c55e',
                'link' => route('admin.bconnect.index'),
                'stats' => [
                    'Companies' => DB::table('bconnect_companies')->count(),
                    'Projects' => DB::table('bconnect_projects')->count(),
                    'Invoices' => DB::table('bconnect_invoices')->count(),
                ],
            ],
            [
                'key' => 'iyolme',
                'name' => 'Iyolme',
                'tagline' => 'Future Iyolme brand control panel.',
                'icon' => 'fa-bolt',
                'color' => '#f59e0b',
                'link' => route('admin.brands.index') . '?tab=iyolme',
                'stats' => ['Status' => 'Coming soon'],
            ],
            [
                'key' => 'hitune',
                'name' => 'Hitune Music',
                'tagline' => 'Future Hitune Music brand control panel.',
                'icon' => 'fa-music',
                'color' => '#ef4444',
                'link' => route('admin.brands.index') . '?tab=hitune',
                'stats' => ['Status' => 'Coming soon'],
            ],
        ];

        return view('admin.brands.index', compact('brands'));
    }
}
