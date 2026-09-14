<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proxmox VE API Configuration
    |--------------------------------------------------------------------------
    */

    // API URL (include protocol and port)
    'api_url' => env('PROXMOX_API_URL', 'https://139.99.122.47:8006'),

    // API Token ID (format: user@realm!tokenname)
    'token_id' => env('PROXMOX_TOKEN_ID', 'root@pam!believoo'),

    // API Secret (UUID format)
    'token_secret' => env('PROXMOX_TOKEN_SECRET', '15beceee-7996-452d-a025-1e73d533586a'),

    // Full API Token for Authorization header
    'api_token' => env(
        'PROXMOX_API_TOKEN',
        env('PROXMOX_TOKEN_ID', 'root@pam!believoo') . '=' . env('PROXMOX_TOKEN_SECRET', '15beceee-7996-452d-a025-1e73d533586a')
    ),

    // Default node name
    'node' => env('PROXMOX_NODE', 'ns548195'),

    // Default bridge for network
    'bridge' => env('PROXMOX_BRIDGE', 'vmbr0'),

    // Default storage for disks
    'storage' => env('PROXMOX_STORAGE', 'local-lvm'),

    // Default ISO storage
    'iso_storage' => env('PROXMOX_ISO_STORAGE', 'local'),

    // SSL Verification (set to false for self-signed certs)
    'verify_ssl' => env('PROXMOX_VERIFY_SSL', false),

    // Connection timeout in seconds
    'timeout' => env('PROXMOX_TIMEOUT', 60),
];
