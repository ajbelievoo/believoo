/**
 * Believoo Shield Pro - Admin Panel JavaScript
 * Full API integration for license, client, threat, and command management.
 */

// ============ CONFIGURATION ============
const API_BASE = window.location.origin + '/api/v1';

// ============ STATE ============
let apiKey = localStorage.getItem('shield_admin_key') || '';
let allClients = [];
let allLicenses = [];
let allThreats = [];
let allCommands = [];

// ============ UTILITIES ============
function $(id) { return document.getElementById(id); }
function formatDate(iso) {
    if (!iso) return 'Never';
    const d = new Date(iso);
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function formatDateShort(iso) {
    if (!iso) return 'Never';
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
function showToast(message, type = 'info') {
    const toast = $('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type + ' show';
    setTimeout(() => toast.className = 'toast', 3000);
}
function openModal(id) { $(id).classList.add('active'); }
function closeModal(id) { $(id).classList.remove('active'); }

// ============ AUTH ============
function doLogin() {
    apiKey = $('api-key-input').value.trim();
    if (!apiKey) {
        $('login-error').textContent = 'Please enter an API key.';
        return;
    }
    // Verify with a lightweight call
    fetchDashboard().then(ok => {
        if (ok) {
            localStorage.setItem('shield_admin_key', apiKey);
            $('login-overlay').classList.add('hidden');
            $('app').classList.remove('hidden');
            showToast('Authenticated successfully', 'success');
        } else {
            $('login-error').textContent = 'Invalid API key. Access denied.';
        }
    }).catch(() => {
        $('login-error').textContent = 'Cannot connect to server.';
    });
}
function doLogout() {
    apiKey = '';
    localStorage.removeItem('shield_admin_key');
    $('app').classList.add('hidden');
    $('login-overlay').classList.remove('hidden');
    $('api-key-input').value = '';
}

function getHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + apiKey
    };
}

// ============ NAVIGATION ============
function showPage(pageName) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    $('page-' + pageName).classList.add('active');
    const nav = document.querySelector('.nav-item[data-page="' + pageName + '"]');
    if (nav) nav.classList.add('active');

    const titles = {
        dashboard: 'Dashboard Overview',
        licenses: 'License Management',
        clients: 'Managed Devices',
        threats: 'Global Threat Intelligence',
        commands: 'Remote Command Center',
        'api-docs': 'API Documentation'
    };
    $('page-title').textContent = titles[pageName] || 'Dashboard';

    // Load data for the page
    if (pageName === 'dashboard') fetchDashboard();
    if (pageName === 'licenses') fetchLicenses();
    if (pageName === 'clients') fetchClients();
    if (pageName === 'threats') fetchThreats();
    if (pageName === 'commands') fetchCommands();
    if (pageName === 'api-docs') renderApiDocs();
}

function refreshCurrentPage() {
    const active = document.querySelector('.page.active');
    if (active) {
        const id = active.id.replace('page-', '');
        showPage(id);
    }
}

// ============ DASHBOARD ============
async function fetchDashboard() {
    try {
        const res = await fetch(API_BASE + '/admin/dashboard', { headers: getHeaders() });
        if (!res.ok) return false;
        const data = await res.json();

        $('dash-licenses-total').textContent = data.total_licenses || 0;
        $('dash-licenses-active').textContent = 'Active: ' + (data.active_licenses || 0);
        $('dash-licenses-expired').textContent = 'Expired: ' + (data.expired_licenses || 0);

        $('dash-clients-total').textContent = data.total_clients || 0;
        $('dash-clients-online').textContent = 'Online: ' + (data.online_clients || 0);
        $('dash-clients-offline').textContent = 'Offline: ' + (data.offline_clients || 0);

        $('dash-threats-total').textContent = data.total_threats || 0;
        $('dash-threats-today').textContent = 'Today: ' + (data.threats_today || 0);

        $('dash-health-score').textContent = (data.avg_health_score || 0).toFixed(1);
        $('dash-pending-cmds').textContent = 'Pending Cmds: ' + (data.pending_commands || 0);
        $('dash-sig-version').textContent = 'Sig: ' + (data.signatures_version || '1.0.0');

        // Fetch recent threats and commands for dashboard
        fetchRecentThreats();
        fetchPendingCommands();
        return true;
    } catch (e) {
        console.error('Dashboard fetch failed:', e);
        return false;
    }
}

async function fetchRecentThreats() {
    try {
        const res = await fetch(API_BASE + '/admin/threats?limit=5', { headers: getHeaders() });
        const data = await res.json();
        const tbody = $('recent-threats-table').querySelector('tbody');
        if (!data.threats || data.threats.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty">No recent threats</td></tr>';
            return;
        }
        tbody.innerHTML = data.threats.map(t => `
            <tr>
                <td>${formatDate(t.detected_at)}</td>
                <td><strong>${escapeHtml(t.threat_name)}</strong></td>
                <td>${escapeHtml(t.client_id?.slice(0, 8) || 'Unknown')}</td>
                <td><span class="severity-${t.severity}">${t.severity.toUpperCase()}</span></td>
                <td><code>${escapeHtml(t.action_taken)}</code></td>
            </tr>
        `).join('');
    } catch (e) { console.error(e); }
}

async function fetchPendingCommands() {
    try {
        const res = await fetch(API_BASE + '/admin/commands?limit=5', { headers: getHeaders() });
        const data = await res.json();
        const tbody = $('pending-commands-table').querySelector('tbody');
        const pending = (data.commands || []).filter(c => c.status === 'pending' || c.status === 'sent');
        if (pending.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty">No pending commands</td></tr>';
            return;
        }
        tbody.innerHTML = pending.map(c => `
            <tr>
                <td>${formatDate(c.created_at)}</td>
                <td><code>${escapeHtml(c.command)}</code></td>
                <td>${escapeHtml(c.client_id?.slice(0, 8) || 'All')}</td>
                <td>${c.priority}</td>
                <td><span class="badge ${c.status === 'pending' ? 'orange' : 'blue'}">${c.status}</span></td>
            </tr>
        `).join('');
    } catch (e) { console.error(e); }
}

// ============ LICENSES ============
async function fetchLicenses() {
    try {
        const res = await fetch(API_BASE + '/admin/licenses?limit=1000', { headers: getHeaders() });
        const data = await res.json();
        allLicenses = data.licenses || [];
        renderLicenses(allLicenses);
    } catch (e) {
        showToast('Failed to load licenses', 'error');
    }
}

function renderLicenses(licenses) {
    const tbody = $('licenses-table').querySelector('tbody');
    if (licenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty">No licenses found</td></tr>';
        return;
    }
    tbody.innerHTML = licenses.map(l => {
        const statusClass = l.status === 'active' ? 'green' : l.status === 'revoked' ? 'red' : l.status === 'expired' ? 'orange' : 'gray';
        return `
            <tr>
                <td><code>${escapeHtml(l.license_key)}</code></td>
                <td>${l.hwid ? escapeHtml(l.hwid.slice(0, 20) + '...') : '<span class="badge gray">Unbound</span>'}</td>
                <td>${escapeHtml(l.device_name || '-')}</td>
                <td><span class="badge ${statusClass}">${l.status.toUpperCase()}</span></td>
                <td>${formatDateShort(l.expiry_date)}</td>
                <td>${formatDateShort(l.created_at)}</td>
                <td>
                    ${l.status !== 'revoked' ?
                        `<button class="btn-small btn-revoke" onclick="revokeLicense('${escapeHtml(l.license_key)}')">Revoke</button>` :
                        '<span class="badge gray">REVOKED</span>'
                    }
                </td>
            </tr>
        `;
    }).join('');
}

async function revokeLicense(key) {
    if (!confirm('Are you sure you want to revoke license ' + key + '?')) return;
    try {
        const res = await fetch(API_BASE + '/admin/licenses/' + encodeURIComponent(key) + '/revoke', {
            method: 'POST',
            headers: getHeaders()
        });
        const data = await res.json();
        if (data.success) {
            showToast('License revoked successfully', 'success');
            fetchLicenses();
        } else {
            showToast(data.message || 'Failed to revoke', 'error');
        }
    } catch (e) { showToast('Network error', 'error'); }
}

function openCreateLicense() { openModal('modal-create-license'); }

async function submitCreateLicense() {
    const days = parseInt($('license-expiry').value) || 365;
    const maxDev = parseInt($('license-max-devices').value) || 1;
    try {
        const res = await fetch(API_BASE + '/admin/licenses', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ expiry_days: days, max_devices: maxDev })
        });
        const data = await res.json();
        if (data.success) {
            showToast('License created: ' + data.license_key, 'success');
            closeModal('modal-create-license');
            fetchLicenses();
        } else {
            showToast(data.message || 'Failed to create', 'error');
        }
    } catch (e) { showToast('Network error', 'error'); }
}

// ============ CLIENTS ============
async function fetchClients() {
    try {
        const res = await fetch(API_BASE + '/admin/clients?limit=1000', { headers: getHeaders() });
        const data = await res.json();
        allClients = data.clients || [];
        renderClients(allClients);
    } catch (e) {
        showToast('Failed to load clients', 'error');
    }
}

function renderClients(clients) {
    const tbody = $('clients-table').querySelector('tbody');
    if (clients.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">No clients found</td></tr>';
        return;
    }
    tbody.innerHTML = clients.map(c => {
        const online = c.is_online;
        const healthColor = c.health_score >= 90 ? 'green' : c.health_score >= 70 ? 'orange' : 'red';
        return `
            <tr>
                <td><strong>${escapeHtml(c.device_name)}</strong></td>
                <td><code>${escapeHtml(c.hwid?.slice(0, 20) + '...')}</code></td>
                <td>${escapeHtml(c.ip_address || '-')}</td>
                <td><span class="badge ${healthColor}">${c.health_score?.toFixed(0) || '--'}</span></td>
                <td>${c.real_time_guard_enabled ? '<span class="badge green">ON</span>' : '<span class="badge gray">OFF</span>'}</td>
                <td><span class="status-dot ${online ? 'online' : 'offline'}"></span>${online ? 'Online' : formatDate(c.last_online)}</td>
                <td>${c.threats_found || 0}</td>
                <td><button class="btn-small btn-cmd" onclick="quickCommand('${c.id}')">Send Cmd</button></td>
            </tr>
        `;
    }).join('');
}

function filterClients() {
    const text = ($('client-filter').value || '').toLowerCase();
    const status = $('client-status-filter').value;
    let filtered = allClients.filter(c =>
        (c.device_name?.toLowerCase().includes(text) || c.hwid?.toLowerCase().includes(text))
    );
    if (status === 'online') filtered = filtered.filter(c => c.is_online);
    if (status === 'offline') filtered = filtered.filter(c => !c.is_online);
    renderClients(filtered);
}

function quickCommand(clientId) {
    $('cmd-client-id').value = clientId;
    openModal('modal-send-cmd');
}

// ============ THREATS ============
async function fetchThreats() {
    try {
        const res = await fetch(API_BASE + '/admin/threats?limit=200', { headers: getHeaders() });
        const data = await res.json();
        allThreats = data.threats || [];
        renderThreats(allThreats);
    } catch (e) {
        showToast('Failed to load threats', 'error');
    }
}

function renderThreats(threats) {
    const tbody = $('threats-table').querySelector('tbody');
    if (threats.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">No threats logged</td></tr>';
        return;
    }
    tbody.innerHTML = threats.map(t => `
        <tr>
            <td>${formatDate(t.detected_at)}</td>
            <td><strong>${escapeHtml(t.threat_name)}</strong></td>
            <td>${escapeHtml(t.threat_type)}</td>
            <td><span class="severity-${t.severity}">${t.severity.toUpperCase()}</span></td>
            <td><code>${escapeHtml(t.file_path?.slice(-50))}</code></td>
            <td><code>${escapeHtml(t.file_hash?.slice(0, 16))}...</code></td>
            <td>${escapeHtml(t.action_taken)}</td>
            <td>${t.cloud_verified ? '<span class="badge green"><i class="fas fa-check"></i></span>' : '-'}</td>
        </tr>
    `).join('');
}

function filterThreats() {
    const sev = $('threat-severity-filter').value;
    if (sev === 'all') { renderThreats(allThreats); return; }
    renderThreats(allThreats.filter(t => t.severity === sev));
}

// ============ COMMANDS ============
let currentCmdTab = 'pending';

async function fetchCommands() {
    try {
        const res = await fetch(API_BASE + '/admin/commands?limit=200', { headers: getHeaders() });
        const data = await res.json();
        allCommands = data.commands || [];
        renderCommandsByTab(currentCmdTab);
    } catch (e) {
        showToast('Failed to load commands', 'error');
    }
}

function switchCmdTab(tab) {
    currentCmdTab = tab;
    document.querySelectorAll('.command-tabs .tab-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    renderCommandsByTab(tab);
}

function renderCommandsByTab(tab) {
    const filtered = allCommands.filter(c => c.status === tab);
    const tbody = $('commands-table').querySelector('tbody');
    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="empty">No ${tab} commands</td></tr>`;
        return;
    }
    tbody.innerHTML = filtered.map(c => {
        const statusBadge = c.status === 'completed' ? 'green' : c.status === 'failed' ? 'red' : c.status === 'sent' ? 'blue' : 'orange';
        return `
            <tr>
                <td>${formatDate(c.created_at)}</td>
                <td><code>${escapeHtml(c.command)}</code></td>
                <td><code>${escapeHtml(c.target_path || '-')}</code></td>
                <td>${escapeHtml(c.client_id?.slice(0, 8) || 'All')}</td>
                <td>${c.priority}</td>
                <td><span class="badge ${statusBadge}">${c.status.toUpperCase()}</span></td>
                <td>${escapeHtml(c.result?.slice(0, 50) || '-')}</td>
            </tr>
        `;
    }).join('');
}

function openBroadcastCommand() { openModal('modal-broadcast'); }

async function submitBroadcast() {
    const cmd = $('broadcast-cmd').value;
    const target = $('broadcast-target').value;
    try {
        const res = await fetch(API_BASE + '/admin/commands/broadcast?' + new URLSearchParams({ command: cmd, target_path: target }), {
            method: 'POST',
            headers: getHeaders()
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Broadcast sent to ${data.commands_sent} clients`, 'success');
            closeModal('modal-broadcast');
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (e) { showToast('Network error', 'error'); }
}

function openSendCommand() { openModal('modal-send-cmd'); }

async function submitSendCommand() {
    const clientId = $('cmd-client-id').value.trim();
    const cmd = $('cmd-type').value;
    const target = $('cmd-target').value;
    const priority = parseInt($('cmd-priority').value) || 5;
    if (!clientId) { showToast('Client ID is required', 'error'); return; }
    try {
        const res = await fetch(API_BASE + '/admin/commands', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ client_id: clientId, command: cmd, target_path: target || null, priority: priority })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Command sent: ' + data.command_id, 'success');
            closeModal('modal-send-cmd');
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (e) { showToast('Network error', 'error'); }
}

// ============ API DOCS ============
function renderApiDocs() {
    const container = $('api-docs-content');
    container.innerHTML = `
        <h4><i class="fas fa-plug"></i> Client API Endpoints (Antivirus Software Integration)</h4>
        <p>These endpoints are called by the Believoo Shield Pro client application running on end-user devices.</p>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/activate</span></div>
        <p>Activate a license key and bind it to the device's HWID.</p>
        <h5>Request Body</h5>
        <pre>{
  "license_key": "ABCDE-FGHIJ-KLMNO-PQRST",
  "hwid": "SHA256_HASH_OF_HARDWARE",
  "device_name": "John-PC",
  "os_version": "Windows 11",
  "client_version": "1.0.0"
}</pre>
        <h5>Response</h5>
        <pre>{
  "success": true,
  "message": "License activated successfully",
  "token": "jwt_access_token",
  "expiry_date": "2026-05-15T00:00:00",
  "status": "active"
}</pre>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/heartbeat</span></div>
        <p>Send device status every 5 minutes. Returns pending remote commands count.</p>
        <h5>Request Body</h5>
        <pre>{
  "hwid": "SHA256_HASH",
  "device_name": "John-PC",
  "ip_address": "192.168.1.100",
  "health_score": 95.5,
  "real_time_guard_enabled": true,
  "threats_found": 0,
  "last_scan_date": "2026-05-15T10:00:00"
}</pre>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/threat-log</span></div>
        <p>Report a detected threat from the client to central server.</p>
        <h5>Request Body</h5>
        <pre>{
  "hwid": "SHA256_HASH",
  "file_path": "C:/Users/John/Downloads/trojan.exe",
  "file_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "threat_name": "Trojan.GenericKD",
  "threat_type": "trojan",
  "severity": "high",
  "action_taken": "quarantined",
  "file_size": 245760,
  "cloud_verified": false
}</pre>

        <div class="endpoint"><span class="method get">GET</span><span class="path">/api/v1/remote-commands?hwid=...</span></div>
        <p>Fetch pending remote commands for this device.</p>
        <h5>Response</h5>
        <pre>{
  "commands": [
    { "id": "...", "command": "force_delete", "target_path": "...", "priority": 1 }
  ],
  "count": 1
}</pre>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/command-result</span></div>
        <p>Report the execution result of a remote command back to the server.</p>
        <h5>Request Body</h5>
        <pre>{
  "command_id": "uuid",
  "status": "completed",
  "result": "File deleted successfully"
}</pre>

        <div class="endpoint"><span class="method get">GET</span><span class="path">/api/v1/signatures/latest</span></div>
        <p>Get the latest virus signature version information.</p>

        <hr style="border-color:var(--border-color);margin:32px 0;">

        <h4><i class="fas fa-user-shield"></i> Admin API Endpoints (Panel / Backend Management)</h4>
        <p><strong>All admin endpoints require</strong> <code>Authorization: Bearer &lt;ADMIN_API_KEY&gt;</code> header.</p>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/admin/licenses</span></div>
        <p>Create a new license key.</p>
        <pre>{ "expiry_days": 365, "max_devices": 1 }</pre>

        <div class="endpoint"><span class="method get">GET</span><span class="path">/api/v1/admin/licenses</span></div>
        <p>List all licenses with pagination (skip, limit query params).</p>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/admin/licenses/{license_key}/revoke</span></div>
        <p>Revoke an active license immediately.</p>

        <div class="endpoint"><span class="method get">GET</span><span class="path">/api/v1/admin/clients</span></div>
        <p>List all managed client devices.</p>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/admin/commands</span></div>
        <p>Send a remote command to a specific client.</p>
        <pre>{
  "client_id": "uuid",
  "command": "force_delete | full_scan | quick_scan | update_signatures | shutdown_guard | enable_guard | reboot | isolate",
  "target_path": "optional/path",
  "parameters": "optional_json_string",
  "priority": 5
}</pre>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/admin/commands/broadcast</span></div>
        <p>Broadcast a command to ALL online clients.</p>
        <pre>POST /api/v1/admin/commands/broadcast?command=full_scan&target_path=optional</pre>

        <div class="endpoint"><span class="method get">GET</span><span class="path">/api/v1/admin/threats</span></div>
        <p>View global threat intelligence logs.</p>

        <div class="endpoint"><span class="method get">GET</span><span class="path">/api/v1/admin/threats/stats</span></div>
        <p>Get aggregated threat statistics.</p>

        <div class="endpoint"><span class="method get">GET</span><span class="path">/api/v1/admin/dashboard</span></div>
        <p>Get comprehensive dashboard statistics (licenses, clients, threats, health).</p>

        <div class="endpoint"><span class="method post">POST</span><span class="path">/api/v1/admin/signatures</span></div>
        <p>Register a new virus signature update.</p>
        <pre>{
  "version": "1.0.5",
  "total_signatures": 150000,
  "critical_updates": true,
  "download_url": "https://...",
  "description": "Critical ransomware signatures"
}</pre>

        <hr style="border-color:var(--border-color);margin:32px 0;">

        <h4><i class="fas fa-code"></i> Integration Guide for Software Team</h4>

        <h5>1. Client Authentication Flow</h5>
        <pre>Step 1: Generate HWID using SystemTools.generate_hwid()
Step 2: Call POST /activate with license_key + hwid
Step 3: Store returned JWT token in license.dat
Step 4: Include token in Authorization header for all future requests</pre>

        <h5>2. Heartbeat Loop</h5>
        <pre>Every 300 seconds (5 min):
  POST /heartbeat with device stats
  If commands_pending > 0:
    GET /remote-commands
    Execute each command
    POST /command-result for each</pre>

        <h5>3. Threat Reporting</h5>
        <pre>On detection:
  1. Quarantine file locally (encrypted)
  2. POST /threat-log with file_hash, path, threat_name
  3. Update local health_score</pre>

        <h5>4. Admin Panel Access</h5>
        <pre>Navigate to: https://your-server.com/admin/
Login with ADMIN_API_KEY from .env file
Manage licenses, view threats, send remote commands</pre>

        <h5>5. Environment Variables (Backend .env)</h5>
        <pre>DATABASE_URL=sqlite:///./shield_pro.db
SECRET_KEY=your-256-bit-secret
ADMIN_API_KEY=your-admin-panel-key
SFTP_HOST=sftp.believoo.com
SFTP_USER=shield_backup</pre>
    `;
}

// ============ HELPERS ============
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============ INIT ============
document.addEventListener('DOMContentLoaded', () => {
    if (apiKey) {
        fetchDashboard().then(ok => {
            if (ok) {
                $('login-overlay').classList.add('hidden');
                $('app').classList.remove('hidden');
            }
        });
    }
    // Enter key on login
    $('api-key-input').addEventListener('keypress', e => { if (e.key === 'Enter') doLogin(); });
});
