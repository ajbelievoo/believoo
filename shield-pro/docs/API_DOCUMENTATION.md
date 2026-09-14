# Believoo Shield Pro - API Documentation

> **For Software Development Team** - Integration Guide & API Reference

---

## Base URL

```
Development:  http://localhost:8443/api/v1
Production:   https://api.believoo.com:8443/api/v1
```

---

## Authentication

### Client Authentication
Clients authenticate via the license activation flow. After calling `POST /activate`, store the returned JWT token and send it in the `Authorization: Bearer <token>` header for all subsequent requests.

### Admin Authentication
Admin endpoints require the `ADMIN_API_KEY` from the backend `.env` file:
```
Authorization: Bearer <ADMIN_API_KEY>
```

---

## Client API Endpoints

These are called by the Shield Pro antivirus software installed on end-user devices.

---

### `POST /activate`

Activate a license key and bind it permanently to the device's HWID.

**Request:**
```json
{
  "license_key": "ABCDE-FGHIJ-KLMNO-PQRST",
  "hwid": "A1B2C3D4E5F6...",
  "device_name": "John-PC",
  "os_version": "Windows 11 Pro 23H2",
  "client_version": "1.0.0"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "License activated successfully. Welcome to Believoo Shield Pro.",
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "expiry_date": "2026-05-15T00:00:00",
  "status": "active"
}
```

**Response (Failure):**
```json
{
  "success": false,
  "message": "License is already bound to another device.",
  "token": null,
  "status": "bound"
}
```

---

### `POST /heartbeat`

Send device status every 5 minutes. Backend returns count of pending remote commands.

**Request:**
```json
{
  "hwid": "A1B2C3D4E5F6...",
  "device_name": "John-PC",
  "ip_address": "192.168.1.100",
  "health_score": 95.5,
  "real_time_guard_enabled": true,
  "threats_found": 0,
  "os_version": "Windows 11",
  "client_version": "1.0.0",
  "last_scan_date": "2026-05-15T10:00:00"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Heartbeat received.",
  "server_time": "2026-05-15T12:00:00Z",
  "commands_pending": 2
}
```

---

### `POST /threat-log`

Report a detected threat from the client to the central intelligence server.

**Request:**
```json
{
  "hwid": "A1B2C3D4E5F6...",
  "file_path": "C:/Users/John/Downloads/trojan.exe",
  "file_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "threat_name": "Trojan.GenericKD.12345",
  "threat_type": "trojan",
  "severity": "high",
  "action_taken": "quarantined",
  "file_size": 245760,
  "cloud_verified": false
}
```

**Response:**
```json
{
  "success": true,
  "threat_id": "uuid-string",
  "message": "Threat logged successfully."
}
```

---

### `GET /remote-commands`

Fetch pending remote commands for this device. Call immediately after heartbeat if `commands_pending > 0`.

**Query Parameters:**
- `hwid` (string, required) - Device hardware ID

**Response:**
```json
{
  "commands": [
    {
      "id": "cmd-uuid",
      "client_id": "client-uuid",
      "command": "force_delete",
      "target_path": "C:/malware.exe",
      "parameters": null,
      "status": "sent",
      "priority": 1,
      "created_at": "2026-05-15T11:55:00Z"
    }
  ],
  "count": 1
}
```

---

### `POST /command-result`

Report the execution result of a remote command back to the server.

**Request:**
```json
{
  "command_id": "cmd-uuid",
  "status": "completed",
  "result": "File deleted successfully. 3 processes killed."
}
```

**Status values:** `completed`, `failed`, `acknowledged`

---

### `GET /signatures/latest`

Get the latest virus signature version information.

**Response:**
```json
{
  "version": "1.0.5",
  "release_date": "2026-05-15T00:00:00Z",
  "total_signatures": 150000,
  "critical_updates": true,
  "download_url": "https://...",
  "description": "Critical ransomware signatures added"
}
```

---

## Admin API Endpoints

All admin endpoints require `Authorization: Bearer <ADMIN_API_KEY>`.

---

### `POST /admin/licenses`

Create a new license key.

**Request:**
```json
{
  "expiry_days": 365,
  "max_devices": 1
}
```

**Response:**
```json
{
  "success": true,
  "license_key": "XXXXX-XXXXX-XXXXX-XXXXX",
  "expiry_date": "2027-05-15T00:00:00Z",
  "status": "pending"
}
```

---

### `GET /admin/licenses`

List all licenses with pagination.

**Query Parameters:**
- `skip` (int, default 0)
- `limit` (int, default 100, max 1000)

**Response:**
```json
{
  "licenses": [...],
  "total": 50
}
```

---

### `POST /admin/licenses/{license_key}/revoke`

Revoke an active license immediately. The client will be deactivated on next heartbeat.

**Response:**
```json
{
  "success": true,
  "message": "License XXXXX-XXXXX-XXXXX-XXXXX has been revoked."
}
```

---

### `GET /admin/clients`

List all managed client devices.

**Response:**
```json
{
  "clients": [
    {
      "id": "client-uuid",
      "license_id": "license-uuid",
      "device_name": "John-PC",
      "ip_address": "192.168.1.100",
      "hwid": "...",
      "os_version": "Windows 11",
      "client_version": "1.0.0",
      "health_score": 95.5,
      "last_online": "2026-05-15T11:58:00Z",
      "is_online": true,
      "real_time_guard_enabled": true,
      "threats_found": 0
    }
  ],
  "total": 25
}
```

---

### `POST /admin/commands`

Send a remote command to a specific client.

**Request:**
```json
{
  "client_id": "client-uuid",
  "command": "force_delete",
  "target_path": "C:/Users/Name/malware.exe",
  "parameters": null,
  "priority": 5
}
```

**Available Commands:**
| Command | Description | target_path Required |
|---------|-------------|---------------------|
| `force_delete` | Kill locking processes and remove file/folder | Yes |
| `full_scan` | Initiate full system scan | Optional |
| `quick_scan` | Quick scan of critical areas | Optional |
| `update_signatures` | Update virus definitions | No |
| `shutdown_guard` | Disable real-time protection | No |
| `enable_guard` | Enable real-time protection | No |
| `reboot` | Reboot target device | No |
| `isolate` | Enable network isolation | No |

**Response:**
```json
{
  "success": true,
  "command_id": "cmd-uuid",
  "status": "pending"
}
```

---

### `POST /admin/commands/broadcast`

Broadcast a command to ALL online clients simultaneously.

**Query Parameters:**
- `command` (string, required) - Same command list as above
- `target_path` (string, optional)
- `parameters` (string, optional)

**Response:**
```json
{
  "success": true,
  "commands_sent": 18,
  "command": "full_scan"
}
```

---

### `GET /admin/threats`

View global threat intelligence logs.

**Query Parameters:**
- `skip` (int, default 0)
- `limit` (int, default 100, max 1000)

---

### `GET /admin/threats/stats`

Get aggregated threat statistics.

**Response:**
```json
{
  "total_threats": 150,
  "critical_count": 12,
  "high_count": 45,
  "medium_count": 60,
  "low_count": 33,
  "quarantined_count": 140,
  "deleted_count": 8,
  "blocked_count": 2,
  "threats_today": 5,
  "recent_threats": [...]
}
```

---

### `GET /admin/dashboard`

Get comprehensive dashboard statistics.

**Response:**
```json
{
  "total_licenses": 100,
  "active_licenses": 85,
  "expired_licenses": 10,
  "total_clients": 95,
  "online_clients": 78,
  "offline_clients": 17,
  "total_threats": 150,
  "threats_today": 5,
  "avg_health_score": 94.5,
  "pending_commands": 3,
  "signatures_version": "1.0.5",
  "server_uptime": "48h 30m"
}
```

---

### `POST /admin/signatures`

Register a new virus signature update.

**Request:**
```json
{
  "version": "1.0.5",
  "total_signatures": 150000,
  "critical_updates": true,
  "download_url": "https://cdn.believoo.com/signatures/v1.0.5.zip",
  "description": "Critical ransomware signatures"
}
```

---

## Integration Guide

### Step 1: HWID Generation

```python
from modules.systemtools.system_tools import SystemTools
sys_tools = SystemTools()
hwid = sys_tools.ensure_hwid()  # Persistent across reinstalls
```

### Step 2: License Activation

```python
import requests

response = requests.post(
    "https://api.believoo.com:8443/api/v1/activate",
    json={
        "license_key": "XXXXX-XXXXX-XXXXX-XXXXX",
        "hwid": hwid,
        "device_name": "John-PC",
        "os_version": "Windows 11",
        "client_version": "1.0.0"
    }
)
data = response.json()
token = data["token"]  # Store securely
```

### Step 3: Heartbeat Loop

```python
import time
import requests

def heartbeat():
    headers = {"Authorization": f"Bearer {token}"}
    response = requests.post(
        f"{API_BASE}/heartbeat",
        json={
            "hwid": hwid,
            "health_score": 95.5,
            "real_time_guard_enabled": True,
            "threats_found": 0
        },
        headers=headers
    )
    data = response.json()
    if data.get("commands_pending", 0) > 0:
        fetch_and_execute_commands()

while True:
    heartbeat()
    time.sleep(300)  # 5 minutes
```

### Step 4: Remote Command Execution

```python
def fetch_and_execute_commands():
    response = requests.get(
        f"{API_BASE}/remote-commands",
        params={"hwid": hwid},
        headers=headers
    )
    for cmd in response.json()["commands"]:
        if cmd["command"] == "force_delete":
            result = force_delete_engine.force_delete(cmd["target_path"])
        elif cmd["command"] == "full_scan":
            result = signature_engine.scan_directory("C:/")
        # ... etc

        requests.post(
            f"{API_BASE}/command-result",
            json={
                "command_id": cmd["id"],
                "status": "completed" if result.success else "failed",
                "result": str(result)
            },
            headers=headers
        )
```

---

## Admin Panel Access

The web admin panel is served at:

```
https://your-server.com/admin/
```

Login with the `ADMIN_API_KEY` from your `.env` file. From the panel you can:
- Create and revoke licenses
- View all managed devices and their health status
- Browse global threat intelligence
- Send remote commands (single target or broadcast)
- View API documentation inline

---

## Error Codes

| HTTP | Meaning |
|------|---------|
| 400 | Bad Request - Invalid payload |
| 401 | Unauthorized - Missing or invalid token |
| 403 | Forbidden - Invalid admin API key |
| 404 | Not Found - Resource doesn't exist |
| 500 | Internal Server Error |

---

## Rate Limits

- **Heartbeat**: Once per 300 seconds per device
- **Threat Log**: No strict limit, but batch if possible
- **Admin endpoints**: No hard limit (implement reverse proxy rate limiting in production)

---

*Document Version: 1.0.0 | Believoo Shield Pro*
