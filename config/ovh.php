<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OVHcloud API Credentials
    |--------------------------------------------------------------------------
    |
    | These are used by the OVH API wrapper to authenticate. Create an app at:
    | https://eu.api.ovh.com/createApp/ (or ca.api.ovh.com / us.api.ovh.com)
    | Then generate a consumer key using the /auth/credential endpoint.
    |
    */
    'application_key'    => env('OVH_APPLICATION_KEY', ''),
    'application_secret' => env('OVH_APPLICATION_SECRET', ''),
    'consumer_key'       => env('OVH_CONSUMER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | OVH API Endpoint
    |--------------------------------------------------------------------------
    |
    | Choose the endpoint matching your OVH account:
    | EU: https://eu.api.ovh.com/1.0
    | CA: https://ca.api.ovh.com/1.0
    | US: https://api.us.ovhcloud.com/1.0
    |
    */
    'endpoint'           => env('OVH_ENDPOINT', 'https://eu.api.ovh.com/1.0'),

    /*
    |--------------------------------------------------------------------------
    | Reseller / Commercial Settings
    |--------------------------------------------------------------------------
    |
    | commission_percent: markup added on top of OVH cost price.
    | ovh_subsidiary:     used for order cart (e.g. FR, IN, SG, GB).
    |
    */
    'commission_percent' => (float) env('OVH_COMMISSION_PERCENT', 25.0),
    'ovh_subsidiary'     => env('OVH_SUBSIDIARY', 'FR'),

    /*
    |--------------------------------------------------------------------------
    | Order Settings
    |--------------------------------------------------------------------------
    |
    | auto_pay: when true, OVH will charge your default payment method.
    |          When false, you must manually complete payment in OVH manager.
    |
    */
    'auto_pay'           => env('OVH_AUTO_PAY', true),

    /*
    |--------------------------------------------------------------------------
    | Product Sync
    |--------------------------------------------------------------------------
    |
    | Controls which OVH product families are synced into the local catalog.
    |
    */
    'sync' => [
        'vps'       => env('OVH_SYNC_VPS', true),
        'hosting'   => env('OVH_SYNC_HOSTING', false),
        'domain'    => env('OVH_SYNC_DOMAIN', false),
        'license'   => env('OVH_SYNC_LICENSE', false),
    ],
];
