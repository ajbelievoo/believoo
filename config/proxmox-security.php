<?php

/**
 * BelieVoo Proxmox Security Configuration
 * 
 * This file contains security settings for Proxmox VE infrastructure.
 * It isolates management access from client VMs and prevents unauthorized access.
 */

return [
    /**
     * SSH Isolation Settings
     */
    'ssh' => [
        // Management SSH port (non-standard to avoid brute force)
        'management_port' => env('PROXMOX_SSH_MGMT_PORT', 2200),
        
        // Public SSH port (should route to VMs, not host)
        'public_port' => 22,
        
        // Admin IP addresses allowed for management SSH access
        'admin_ips' => explode(',', env('PROXMOX_ADMIN_IPS', '127.0.0.1')),
        
        // Require key-based auth for management
        'require_key_auth' => env('PROXMOX_SSH_KEY_AUTH', true),
    ],

    /**
     * Proxmox Web UI/API Access
     */
    'web' => [
        // Port 8006 should only be accessible from admin IPs
        'admin_only' => true,
        
        // Use reverse proxy for client access
        'use_reverse_proxy' => true,
        
        // Console domain (masks Proxmox host IP)
        'console_domain' => env('PROXMOX_CONSOLE_DOMAIN', 'console.believoo.com'),
    ],

    /**
     * Firewall Rules
     */
    'firewall' => [
        // Enable client isolation (block inter-VM traffic)
        'client_isolation' => env('PROXMOX_CLIENT_ISOLATION', true),
        
        // Allowed VM-to-VM communication pairs (override isolation)
        'allowed_pairs' => [],
        
        // Block port 22 on host (force routing to VMs)
        'block_host_ssh' => true,
        
        // Allow DNS queries
        'allow_dns' => true,
        
        // Allow HTTP/HTTPS outbound
        'allow_http_outbound' => true,
    ],

    /**
     * API Authentication
     */
    'api' => [
        // Rotate tickets for every console session
        'fresh_console_tickets' => true,
        
        // Ticket expiration in minutes
        'ticket_expiry' => 5,
        
        // Use API tokens where possible (except VNC which needs password)
        'prefer_api_tokens' => true,
    ],

    /**
     * Cloud-Init Settings
     */
    'cloud_init' => [
        // Force reboot after Cloud-Init injection
        'force_reboot' => true,
        
        // Default password length
        'password_length' => 16,
        
        // Auto-install guest agent
        'install_guest_agent' => true,
        
        // Configure SSH on first boot
        'configure_ssh' => true,
    ],
];
