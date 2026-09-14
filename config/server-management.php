<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WHMCS API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your WHMCS API credentials and settings here.
    | Get your API credentials from WHMCS Admin > Setup > Staff Management >
    | Manage API Credentials
    |
    */
    'whmcs' => [
        'base_url' => env('WHMCS_BASE_URL', 'https://billing.yourdomain.com'),
        'api_identifier' => env('WHMCS_API_IDENTIFIER', ''),
        'api_secret' => env('WHMCS_API_SECRET', ''),
        'cache_ttl' => env('WHMCS_CACHE_TTL', 300), // seconds (5 minutes)
    ],

    /*
    |--------------------------------------------------------------------------
    | Virtualizor API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Virtualizor API credentials here.
    | You can find API keys in Virtualizor Admin > Configuration > API Credentials
    |
    */
    'virtualizor' => [
        'base_url' => env('VIRTUALIZOR_BASE_URL', 'https://virtualizor.yourdomain.com'),
        'api_key' => env('VIRTUALIZOR_API_KEY', ''),
        'api_pass' => env('VIRTUALIZOR_API_PASS', ''),
        'port' => env('VIRTUALIZOR_PORT', 4085), // or 4083/4084 for HTTP
        'cache_ttl' => env('VIRTUALIZOR_CACHE_TTL', 60), // seconds (1 minute)
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configure default dashboard behavior and refresh intervals.
    |
    */
    'dashboard' => [
        'auto_refresh' => env('SERVER_DASHBOARD_AUTO_REFRESH', true),
        'refresh_interval' => env('SERVER_DASHBOARD_REFRESH_INTERVAL', 30), // seconds
        'show_bandwidth_charts' => true,
        'show_invoice_alerts' => true,
        'max_bandwidth_history_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Settings
    |--------------------------------------------------------------------------
    |
    | Configure server action behaviors and timeouts.
    |
    */
    'actions' => [
        'confirmation_required' => true, // Require confirmation for destructive actions
        'power_off_warning' => 'Warning: Power off is a hard shutdown and may cause data loss.',
        'timeout_seconds' => 30,
    ],
];
