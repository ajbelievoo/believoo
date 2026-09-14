# BelieVoo Enterprise Architecture Upgrade - Deployment Guide

## Overview

This upgrade transforms BelieVoo into an enterprise-level VPS management platform competing with HostBill and WiseCP. Key features include:

- **SSH Isolation**: Proxmox management on port 2200, client VMs on port 22
- **Console Masking**: Reverse proxy hides Proxmox host IP (console.believoo.com)
- **Cloud-Init Automation**: Instant-on provisioning with unique credentials
- **WebSocket Reliability**: Exponential backoff reconnection strategy
- **Client Isolation**: Firewall rules prevent inter-VM communication

## Security Architecture

### 1. SSH Isolation

The Proxmox host SSH now runs on a non-standard management port (2200) with access restricted to admin IPs only. Port 22 is strictly routed to client VMs.

**Implementation Files:**
- `app/Services/ProxmoxSecurityService.php`
- `config/proxmox-security.php`

**Configuration:**
```bash
# Add to .env
PROXMOX_SSH_MGMT_PORT=2200
PROXMOX_ADMIN_IPS="203.0.113.10,203.0.113.11"
```

**Manual Proxmox Configuration:**
Run the generated script from Admin → Server Management → Security:
```bash
# Generated script location (SSH to Proxmox host and run):
/etc/ssh/sshd_config.d/management.conf
/etc/ssh/sshd_config.d/public.conf
```

### 2. Proxmox Datacenter Firewall

Automatic client isolation with firewall rules:
- Block inter-VM traffic (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
- Restrict port 8006 (Proxmox API/Web) to admin IPs only
- Block direct SSH to host on port 22
- Allow outbound HTTP/HTTPS for VM updates

**Implementation:**
- `app/Services/ProxmoxSecurityService.php`
- Methods: `applyClientIsolationFirewall()`, `createVmSecurityGroup()`

### 3. Console Reverse Proxy

The console now opens via `console.believox.com` instead of exposing the raw Proxmox host IP and port 8006.

**Implementation Files:**
- `app/Services/ConsoleProxyService.php`
- `app/Http/Controllers/Client/ConsoleController.php`
- `routes/web.php` (console routes)

**Nginx Configuration:**
```nginx
# /etc/nginx/sites-available/console.believoo.com
server {
    listen 443 ssl http2;
    server_name console.believoo.com;
    
    location /console/ {
        proxy_pass http://127.0.0.1:8000/console/;
    }
    
    location /ws/ {
        proxy_pass https://PROXMOX_HOST:8006/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

**DNS Configuration:**
```
console.believoo.com  A  YOUR_SERVER_IP
```

### 4. Dynamic Ticket System

Eliminates "Error 401: No Ticket" by generating fresh PVEAuthCookie for every console session.

**Implementation:**
- `app/Services/ProxmoxApiService.php`
- Method: `getConsoleTicket()` - always fetches fresh credentials
- No cached tickets - each session is unique and expires in 5 minutes

## Cloud-Init Automation

### Instant-On Provisioning

When a VPS is ordered, the system automatically:
1. Injects client's unique password
2. Configures network (IPv4, Gateway, DNS)
3. Installs QEMU guest agent
4. Forces reboot to activate credentials

**Implementation Files:**
- `app/Services/CloudInitService.php`
- `app/Http/Controllers/Admin/ProxmoxVmController.php`

**Configuration:**
```bash
# Password is auto-generated (16 chars)
# SSH keys can be added via Cloud-Init user-data
# Network config: ip=IP/SUBNET,gw=GATEWAY
```

### Force Reboot Logic

Tries multiple methods to ensure Cloud-Init takes effect:
1. QEMU agent reboot (graceful)
2. ACPI shutdown + start
3. Force stop + start (hard reboot)

**API Usage:**
```php
$cloudInit = new CloudInitService();
$result = $cloudInit->autoInjectCloudInit($vmid, [
    'hostname' => 'client-vps-123',
    'username' => 'root',
    'password' => $generatedPassword,
    'ip_address' => '203.0.113.100',
    'gateway' => '203.0.113.1',
    'dns_servers' => ['8.8.8.8', '8.8.4.4'],
]);
// Returns: ['success' => true, 'reboot_initiated' => true, ...]
```

## Frontend Improvements

### 1. Fixed JavaScript Errors

- Removed duplicate `submitExternalDomain` function definition
- Added proper error handling for DNS operations

### 2. WebSocket Reconnection

Exponential backoff strategy prevents connection failures:

**Implementation:**
- `resources/js/echo.js`
- `resources/views/livewire/partials/dashboard-hosting.blade.php`

**Backoff Strategy:**
- Base delay: 1000ms
- Max attempts: 5
- Delay doubles each attempt: 1s, 2s, 4s, 8s, 16s
- Max delay capped at 30 seconds

### 3. UX Masking

Raw Proxmox details are hidden from clients:
- Node names → Location names (e.g., "ns548195" → "Singapore DC-1")
- VMID references → VPS ID references
- Proxmox IP → console.believoo.com

## Configuration Checklist

### Environment Variables

```env
# Proxmox Connection
PROXMOX_API_URL=https://203.0.113.50:8006
PROXMOX_API_TOKEN=root@pam!tokenname=abc123...
PROXMOX_USERNAME=root@pam
PROXMOX_PASSWORD=secure_password_here
PROXMOX_NODE=ns548195

# Security
PROXMOX_SSH_MGMT_PORT=2200
PROXMOX_ADMIN_IPS=203.0.113.10,203.0.113.11
PROXMOX_CONSOLE_DOMAIN=console.believoo.com
PROXMOX_VERIFY_SSL=false

# Cloud-Init
PROXMOX_CLOUD_INIT_FORCE_REBOOT=true
```

### Database Settings

Add to `settings` table:
```sql
INSERT INTO settings (`key`, `value`) VALUES
('proxmox_mgmt_ssh_port', '2200'),
('proxmox_admin_ips', '203.0.113.10,203.0.113.11'),
('console_domain', 'console.believoo.com'),
('proxmox_client_isolation', '1');
```

### Proxmox Node Configuration

Ensure ProxmoxNode model has:
- `display_name`: Human-readable location (e.g., "Singapore DC-1")
- `hostname`: Internal Proxmox IP (hidden from clients)
- `port`: 8006
- `city`, `country_code`: For location branding

## Deployment Steps

1. **Deploy Code**
   ```bash
   git pull origin main
   composer install
   php artisan migrate
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your Proxmox credentials
   php artisan config:cache
   ```

3. **Set Up Console Domain**
   ```bash
   # Point console.believoo.com to your server
   # Configure Nginx reverse proxy (see config above)
   # SSL certificate for console.believoo.com
   ```

4. **Apply Proxmox Security**
   ```bash
   # SSH to Proxmox host and run generated scripts
   # Or use Admin Dashboard → Server Management → Security
   ```

5. **Test End-to-End Flow**
   - Order VPS → Check Cloud-Init injection → Verify unique password
   - Open Console → Verify console.believoo.com URL (not raw IP)
   - Check SSH isolation → Port 22 should route to VM, not host
   - Test WebSocket reconnection → Disconnect network, verify auto-reconnect

## API Endpoints

### Console
```
POST   /console/session/{hostingId}  Create console session
GET    /console/{sessionId}           View console page
GET    /console/ws/{sessionId}        WebSocket proxy
GET    /console/health/{sessionId}    Check session health
DELETE /console/session/{sessionId}   Destroy session
```

### Server Actions
```
POST   /api/servers/{vpsId}/action    Start/Stop/Restart/Rebuild
GET    /api/servers/{vpsId}/console   Get console URL (legacy)
POST   /api/hosting/{hostingId}/vnc-proxy  Legacy VNC proxy
```

## Troubleshooting

### Console Not Loading
1. Check `console.believoo.com` DNS points to server
2. Verify Nginx proxy configuration
3. Ensure Proxmox password is set in settings
4. Check logs: `storage/logs/laravel.log`

### Cloud-Init Not Working
1. Verify VM has cloud-init drive (ide0)
2. Check `cicustom` config is applied
3. Ensure VM reboots after provisioning
4. Check guest agent is installed

### SSH Isolation Issues
1. Verify SSH config files on Proxmox host
2. Check firewall rules are applied
3. Confirm admin IPs are whitelisted
4. Test management port 2200 from admin IP

### WebSocket Connection Failures
1. Check Reverb/WebSocket server is running
2. Verify `VITE_REVERB_*` environment variables
3. Review browser console for backoff logs
4. Check `echo.js` is loaded in browser

## Security Considerations

1. **Never expose Proxmox root password to frontend**
   - API tokens used where possible
   - Password only used server-side for VNC tickets

2. **Session expiration**
   - Console sessions expire in 5 minutes
   - Tickets are single-use
   - Sessions tied to user ID for validation

3. **Client Isolation**
   - VMs cannot communicate with each other
   - Firewall rules applied at datacenter level
   - Exceptions can be configured per VM group

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review Proxmox API logs: `/var/log/syslog` on host
- Console error: Check browser DevTools Network tab
- Contact: support@believoo.com

---

**Version**: 2.0 Enterprise  
**Last Updated**: May 2026  
**Compatible With**: Proxmox VE 7.x, 8.x
