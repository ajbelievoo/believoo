<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Console Proxy Service
 * 
 * Provides secure reverse proxy for Proxmox noVNC console access.
 * Masks the Proxmox host IP and provides domain-based console access.
 */
class ConsoleProxyService
{
    protected string $consoleDomain;
    protected string $baseUrl;
    protected string $apiToken;
    protected string $node;
    protected string $username;
    protected string $password;
    protected bool $verifySsl;

    /**
     * Token expiration in seconds (5 minutes)
     */
    protected int $tokenExpiry = 300;

    public function __construct(?string $baseUrl = null, ?string $apiToken = null, ?string $node = null)
    {
        $settings = $this->getSettings();

        $this->baseUrl = $baseUrl ?? $settings['proxmox_api_url'] ?? config('proxmox.api_url', 'https://127.0.0.1:8006');
        $this->apiToken = (string) ($apiToken ?? $settings['proxmox_api_token'] ?? config('proxmox.api_token', ''));
        $this->username = $settings['proxmox_username'] ?? config('proxmox.username', 'root@pam');
        $this->password = (string) ($password ?? $settings['proxmox_password'] ?? config('proxmox.password', ''));
        $this->node = $node ?? $settings['proxmox_node'] ?? config('proxmox.node', 'pve');
        $this->verifySsl = ($settings['proxmox_verify_ssl'] ?? '0') === '1';
        $this->consoleDomain = $settings['console_domain'] ?? config('proxmox.console_domain', 'console.believoo.com');
    }

    /**
     * Create service instance for a specific node
     */
    public static function forNode(string $baseUrl, string $apiToken, string $node): self
    {
        return new self($baseUrl, $apiToken, $node);
    }

    protected function getSettings(): array
    {
        try {
            if (\Schema::hasTable('settings')) {
                return \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            }
        } catch (\Exception $e) {
            // Silently fail if DB not ready
        }
        return [];
    }

    /**
     * Generate a secure console session for a VM
     * Returns masked console URL via proxy domain
     */
    public function createSecureConsoleSession(int $vmid, int $userId): ?array
    {
        try {
            // Generate unique session ID
            $sessionId = Str::uuid()->toString();

            // Get fresh VNC ticket from Proxmox
            $vncData = $this->createVncProxyTicket($vmid);

            if (!$vncData || empty($vncData['ticket'])) {
                Log::error('ConsoleProxy: Failed to create VNC ticket', ['vmid' => $vmid]);
                return null;
            }

            // Create session cache with VNC credentials
            $sessionData = [
                'vmid' => $vmid,
                'user_id' => $userId,
                'vnc_ticket' => $vncData['ticket'],
                'vnc_port' => $vncData['port'],
                'pve_auth_cookie' => $vncData['pve_auth_cookie'] ?? null,
                'csrf_token' => $vncData['csrf_token'] ?? null,
                'node' => $this->node,
                'created_at' => now()->timestamp,
                'expires_at' => now()->addSeconds($this->tokenExpiry)->timestamp,
            ];

            // Store session data in cache
            Cache::put('console_session:' . $sessionId, $sessionData, $this->tokenExpiry);

            // Generate proxy URL (masks the Proxmox host)
            $proxyUrl = $this->generateProxyUrl($sessionId, $vmid);

            // Generate WebSocket proxy URL
            $wsProxyUrl = $this->generateWebSocketProxyUrl($sessionId);

            Log::info('ConsoleProxy: Secure session created', [
                'vmid' => $vmid,
                'user_id' => $userId,
                'session_id' => substr($sessionId, 0, 8) . '...',
                'expires' => $this->tokenExpiry,
            ]);

            return [
                'session_id' => $sessionId,
                'console_url' => $proxyUrl,
                'websocket_url' => $wsProxyUrl,
                'expires_in' => $this->tokenExpiry,
                'domain' => $this->consoleDomain,
            ];

        } catch (\Exception $e) {
            Log::error('ConsoleProxy: Failed to create secure session', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create VNC proxy ticket using password auth (required for noVNC)
     * API tokens don't work for VNC console access
     */
    protected function createVncProxyTicket(int $vmid): ?array
    {
        try {
            // VNC requires ticket-based auth, not API token
            if (empty($this->password)) {
                Log::warning('ConsoleProxy: No password configured for VNC ticket generation');
                return null;
            }

            // Step 1: Authenticate with username/password to get ticket
            $authResponse = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->post("{$this->baseUrl}/api2/json/access/ticket", [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if (!$authResponse->successful()) {
                Log::error('ConsoleProxy: Auth failed for VNC ticket', [
                    'status' => $authResponse->status(),
                    'body' => $authResponse->body(),
                ]);
                return null;
            }

            $authData = $authResponse->json('data');
            $ticket = $authData['ticket'] ?? null;
            $csrfToken = $authData['CSRFPreventionToken'] ?? null;

            if (!$ticket) {
                Log::error('ConsoleProxy: No ticket in auth response');
                return null;
            }

            // Step 2: Create VNC proxy with ticket auth
            $vncResponse = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->withHeaders([
                    'Cookie' => 'PVEAuthCookie=' . $ticket,
                    'CSRFPreventionToken' => $csrfToken,
                ])
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/vncproxy", [
                    'websocket' => 1,
                ]);

            if (!$vncResponse->successful()) {
                Log::error('ConsoleProxy: VNC proxy creation failed', [
                    'status' => $vncResponse->status(),
                    'body' => $vncResponse->body(),
                ]);
                return null;
            }

            $vncData = $vncResponse->json('data');

            return [
                'ticket' => $vncData['ticket'] ?? null,
                'port' => $vncData['port'] ?? null,
                'pve_auth_cookie' => $ticket,
                'csrf_token' => $csrfToken,
            ];

        } catch (\Exception $e) {
            Log::error('ConsoleProxy: VNC ticket exception', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate proxy console URL (masked domain)
     */
    protected function generateProxyUrl(string $sessionId, int $vmid): string
    {
        $scheme = request()->isSecure() ? 'https' : 'http';
        
        // Use branded console domain if configured, otherwise use app domain
        if ($this->consoleDomain && $this->consoleDomain !== 'console.believoo.com') {
            return "{$scheme}://{$this->consoleDomain}/console/{$sessionId}";
        }

        // Fallback to app URL with console path
        $appUrl = config('app.url');
        return "{$appUrl}/console/{$sessionId}";
    }

    /**
     * Generate WebSocket proxy URL
     */
    protected function generateWebSocketProxyUrl(string $sessionId): string
    {
        $scheme = request()->isSecure() ? 'wss' : 'ws';
        
        if ($this->consoleDomain && $this->consoleDomain !== 'console.believoo.com') {
            return "{$scheme}://{$this->consoleDomain}/ws/{$sessionId}";
        }

        $appUrl = str_replace(['http://', 'https://'], '', config('app.url'));
        return "{$scheme}://{$appUrl}/ws/{$sessionId}";
    }

    /**
     * Validate and retrieve session data
     */
    public function getSessionData(string $sessionId): ?array
    {
        $sessionData = Cache::get('console_session:' . $sessionId);

        if (!$sessionData) {
            Log::warning('ConsoleProxy: Session not found or expired', [
                'session_id' => substr($sessionId, 0, 8) . '...',
            ]);
            return null;
        }

        // Check expiration
        if (now()->timestamp > $sessionData['expires_at']) {
            Cache::forget('console_session:' . $sessionId);
            Log::warning('ConsoleProxy: Session expired', [
                'session_id' => substr($sessionId, 0, 8) . '...',
            ]);
            return null;
        }

        return $sessionData;
    }

    /**
     * Refresh a session (extend expiration)
     */
    public function refreshSession(string $sessionId): bool
    {
        $sessionData = $this->getSessionData($sessionId);

        if (!$sessionData) {
            return false;
        }

        // Extend expiration
        $sessionData['expires_at'] = now()->addSeconds($this->tokenExpiry)->timestamp;
        Cache::put('console_session:' . $sessionId, $sessionData, $this->tokenExpiry);

        return true;
    }

    /**
     * Invalidate a session
     */
    public function invalidateSession(string $sessionId): bool
    {
        Cache::forget('console_session:' . $sessionId);
        
        Log::info('ConsoleProxy: Session invalidated', [
            'session_id' => substr($sessionId, 0, 8) . '...',
        ]);

        return true;
    }

    /**
     * Get noVNC HTML with proxied WebSocket
     */
    public function getNoVncHtml(string $sessionId): ?string
    {
        $sessionData = $this->getSessionData($sessionId);

        if (!$sessionData) {
            return null;
        }

        $wsProxyUrl = $this->generateWebSocketProxyUrl($sessionId);
        $vmid = $sessionData['vmid'];
        $ticket = $sessionData['vnc_ticket'];

        // Build noVNC HTML with proxy WebSocket URL
        return $this->buildNoVncViewer($wsProxyUrl, $vmid, $ticket);
    }

    /**
     * Build noVNC viewer HTML
     */
    protected function buildNoVncViewer(string $wsUrl, int $vmid, string $ticket): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BelieVoo Console - VM {$vmid}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        #header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid #334155;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
        }
        #logo {
            font-weight: 900;
            font-size: 18px;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #00b7ff, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        #vm-info {
            font-size: 13px;
            color: #94a3b8;
        }
        #status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #22c55e;
        }
        #status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        #console-container {
            flex: 1;
            position: relative;
            background: #000;
        }
        #noVNC_screen {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #noVNC_canvas {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        #loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #94a3b8;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #334155;
            border-top-color: #00b7ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        #error {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #ef4444;
            padding: 20px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div id="header">
        <div>
            <div id="logo">BelieVoo Console</div>
            <div id="vm-info">VM {$vmid} • Secure Connection</div>
        </div>
        <div id="status">
            <div id="status-dot"></div>
            <span>Connected</span>
        </div>
    </div>
    <div id="console-container">
        <div id="loading">
            <div class="spinner"></div>
            <div>Connecting to console...</div>
        </div>
        <div id="error">
            <div><i class="fas fa-exclamation-circle"></i></div>
            <div>Connection failed. Please refresh to retry.</div>
        </div>
        <div id="noVNC_screen">
            <canvas id="noVNC_canvas"></canvas>
        </div>
    </div>
    
    <script>
        // WebSocket connection with exponential backoff
        let ws = null;
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 5;
        const baseReconnectDelay = 1000;
        
        const wsUrl = '{$wsUrl}';
        const ticket = '{$ticket}';
        
        function connect() {
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const statusDot = document.getElementById('status-dot');
            
            loading.style.display = 'block';
            error.style.display = 'none';
            statusDot.style.background = '#eab308';
            
            try {
                ws = new WebSocket(wsUrl);
                
                ws.onopen = function() {
                    console.log('Console connected');
                    loading.style.display = 'none';
                    statusDot.style.background = '#22c55e';
                    reconnectAttempts = 0;
                };
                
                ws.onclose = function() {
                    console.log('Console disconnected');
                    statusDot.style.background = '#ef4444';
                    
                    // Attempt reconnection with exponential backoff
                    if (reconnectAttempts < maxReconnectAttempts) {
                        const delay = Math.min(baseReconnectDelay * Math.pow(2, reconnectAttempts), 30000);
                        reconnectAttempts++;
                        console.log('Reconnecting in ' + delay + 'ms (attempt ' + reconnectAttempts + ')');
                        setTimeout(connect, delay);
                    } else {
                        loading.style.display = 'none';
                        error.style.display = 'block';
                    }
                };
                
                ws.onerror = function(err) {
                    console.error('WebSocket error:', err);
                };
                
            } catch (e) {
                console.error('Connection error:', e);
                loading.style.display = 'none';
                error.style.display = 'block';
            }
        }
        
        // Start connection when page loads
        document.addEventListener('DOMContentLoaded', connect);
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible' && (!ws || ws.readyState !== WebSocket.OPEN)) {
                reconnectAttempts = 0;
                connect();
            }
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get Nginx reverse proxy configuration
     */
    public function getNginxConfig(): string
    {
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST) ?? 'believoo.com';
        
        return <<<NGINX
# BelieVoo Console Reverse Proxy Configuration
# Place this in /etc/nginx/sites-available/console.believoo.com

upstream proxmox_backend {
    server {$this->baseUrl}:8006;
    keepalive 32;
}

# WebSocket upstream for noVNC
upstream vnc_websocket {
    server {$this->baseUrl}:8006;
}

server {
    listen 80;
    listen [::]:80;
    server_name {$this->consoleDomain};
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$this->consoleDomain};

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/{$appUrl}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$appUrl}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Console endpoint - proxy to Laravel app
    location /console/ {
        proxy_pass http://127.0.0.1:8000/console/;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    # WebSocket endpoint - proxy to Proxmox VNC
    location /ws/ {
        proxy_pass https://{$this->baseUrl}:8006/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 86400;
        proxy_send_timeout 86400;
    }

    # API proxy (internal use only)
    location /api2/ {
        # Only allow from localhost (Laravel backend)
        allow 127.0.0.1;
        deny all;
        
        proxy_pass https://{$this->baseUrl}:8006/api2/;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_ssl_verify off;
    }

    # Static assets
    location / {
        proxy_pass http://127.0.0.1:8000/;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
NGINX;
    }

    /**
     * Get Apache reverse proxy configuration
     */
    public function getApacheConfig(): string
    {
        return <<<APACHE
# BelieVoo Console Reverse Proxy Configuration
# Place this in /etc/apache2/sites-available/console.believoo.com.conf

<VirtualHost *:80>
    ServerName {$this->consoleDomain}
    Redirect permanent / https://{$this->consoleDomain}/
</VirtualHost>

<VirtualHost *:443>
    ServerName {$this->consoleDomain}
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/{$this->consoleDomain}/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/{$this->consoleDomain}/privkey.pem
    
    # Proxy settings
    ProxyPreserveHost On
    ProxyRequests Off
    
    # Console endpoint
    <Location /console/>
        ProxyPass http://127.0.0.1:8000/console/
        ProxyPassReverse http://127.0.0.1:8000/console/
    </Location>
    
    # WebSocket endpoint
    <Location /ws/>
        ProxyPass wss://{$this->baseUrl}:8006/
        ProxyPassReverse wss://{$this->baseUrl}:8006/
    </Location>
    
    # Enable WebSocket proxy
    RewriteEngine on
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteRule ^/ws/(.*) "wss://{$this->baseUrl}:8006/$1" [P,L]
    
    # Static assets
    <Location />
        ProxyPass http://127.0.0.1:8000/
        ProxyPassReverse http://127.0.0.1:8000/
    </Location>
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
APACHE;
    }
}
