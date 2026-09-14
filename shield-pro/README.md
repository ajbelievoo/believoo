# Believoo Shield Pro

**Enterprise-Grade Antivirus System with B-Host Centralized Control**

> *"The Kaspersky Killer"* - Built for BelieVoo Hosting (B-Host) server infrastructure.

---

## Overview

Believoo Shield Pro is a world-class, modular antivirus ecosystem consisting of:

- **Backend (FastAPI)**: Centralized brain running on your BelieVoo VPS. Manages licenses, clients, threat intelligence, and remote commands.
- **Client (Python/CustomTkinter)**: Windows-based enterprise antivirus engine with kernel-level force delete, real-time guard, and premium UI.

---

## Architecture

```
shield-pro/
├── backend/              # FastAPI Server (B-Host VPS)
│   ├── app/
│   │   ├── api/          # REST API endpoints
│   │   ├── core/         # Config & security utilities
│   │   ├── db/           # SQLAlchemy database setup
│   │   ├── models/       # Database models
│   │   ├── schemas/      # Pydantic validation schemas
│   │   └── services/     # Business logic layer
│   ├── main.py           # Application entry point
│   └── requirements.txt
│
└── client/               # Windows Antivirus Client
    ├── config/           # Client configuration
    ├── modules/
    │   ├── engine/       # Scanner, Force Delete, Real-Time Guard
    │   ├── quarantine/   # Encrypted threat isolation
    │   ├── cloudsync/    # Partition backup to B-Host
    │   ├── systemtools/  # Registry, HWID, self-protection
    │   ├── admin_listener/  # Remote command processor
    │   └── ui/           # Premium CustomTkinter GUI
    └── main.py           # Client entry point
```

---

## Backend Features

### Database Models

| Entity | Fields |
|--------|--------|
| **License** | license_key, hwid, expiry_date, status, max_devices |
| **Client** | device_name, ip, hwid, health_score, last_online, threats_found |
| **ThreatLog** | file_hash, threat_name, severity, action_taken, cloud_verified |
| **RemoteCommand** | command, target_path, status, priority, result |
| **SignatureUpdate** | version, total_signatures, critical flag |

### API Endpoints

**Client Endpoints:**
- `POST /api/v1/activate` - License activation with HWID binding
- `POST /api/v1/heartbeat` - Client status heartbeat (every 5 min)
- `POST /api/v1/threat-log` - Report detected threats
- `GET /api/v1/remote-commands` - Fetch pending admin commands
- `POST /api/v1/command-result` - Report command execution result
- `GET /api/v1/signatures/latest` - Get latest signature version

**Admin Endpoints (API Key Protected):**
- `POST /api/v1/admin/licenses` - Create new license
- `GET /api/v1/admin/licenses` - List all licenses
- `POST /api/v1/admin/licenses/{key}/revoke` - Revoke license
- `GET /api/v1/admin/clients` - List all managed clients
- `POST /api/v1/admin/commands` - Send command to specific client
- `POST /api/v1/admin/commands/broadcast` - Broadcast to all online clients
- `GET /api/v1/admin/threats` - View global threat logs
- `GET /api/v1/admin/threats/stats` - Threat analytics
- `GET /api/v1/admin/dashboard` - Comprehensive dashboard stats
- `POST /api/v1/admin/signatures` - Register new signature update

### Supported Remote Commands

- `force_delete` - Force-remove locked files/directories
- `full_scan` - Full system scan
- `quick_scan` - Critical area scan
- `update_signatures` - Update virus definitions
- `shutdown_guard` - Disable real-time protection
- `enable_guard` - Enable real-time protection
- `reboot` - Reboot target device
- `isolate` - Network isolation mode

---

## Client Features

### Engine Modules

**Smart Signature Engine**
- SHA-256 hashing with local cache (LRU, 10K entries)
- Cloud API verification for unknown hashes
- Automatic local DB population from cloud detections
- Sub-millisecond cache hits for known files

**Kernel-Level Force Delete**
- Identifies ALL processes locking a target file/folder
- Graceful termination with fallback to force kill
- Recursive directory removal with read-only bypass
- Windows: Schedule deletion on reboot as last resort
- **Handles "Folder in use" errors that other AVs cannot**

**Real-Time Guard**
- Watchdog-based filesystem monitoring
- Monitors Downloads, Desktop, and USB drives
- Auto-detects USB insertion and scans immediately
- Debounced scanning to avoid duplicate alerts
- Auto-quarantine on detection

### Support Modules

**Quarantine Manager**
- AES encryption of quarantined files
- Original path preservation for restore
- Secure overwrite deletion (3-pass)
- Automatic cloud threat reporting

**Cloud Backup**
- ZIP compression of critical partitions
- API + SFTP upload to Believoo servers
- Incremental backup support
- Background threaded execution

**System Tools**
- Windows Registry Run key persistence
- Self-protection (PBKDF2 password hash)
- Hardware ID generation (CPU + BIOS + Disk + MAC)
- Admin privilege detection and UAC elevation

**Admin Command Listener**
- Background polling of B-Host commands
- Priority-based command queue execution
- Acknowledgment + result reporting
- Full command history logging

### Premium UI (CustomTkinter)

**Splash / Licensing Screen**
- Midnight Onyx dark theme
- Neon cyan/violet glow accents
- Auto-formatted license key input
- HWID display for support
- Trial mode fallback

**Main Dashboard**
- Tabbed interface: Dashboard, Scan, Quarantine, Backup, Settings
- Real-time health score display
- Animated scan progress with per-file tracking
- Quarantine browser with restore/delete
- Module status indicators
- Admin command log viewer

### Running Modes

```bash
# GUI Mode (default)
python client/main.py

# CLI Mode
python client/main.py --cli

# Background Daemon (no GUI)
python client/main.py --daemon
```

---

## Quick Start

### Backend Setup

```bash
cd shield-pro/backend
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt

# Configure environment
cp .env.example .env
# Edit .env with production settings

# Run server
uvicorn main:app --host 0.0.0.0 --port 8443
```

### Client Setup (Windows)

```bash
cd shield-pro/client
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt

# Run GUI
python main.py
```

---

## Monetization Features

1. **License Key System**: Cryptographically generated keys bound to HWID. Supports expiry dates and multi-device limits.
2. **Admin Panel Control**: Full remote management of all deployed clients from a single B-Host server.
3. **Cloud Backup Subscription**: User data backed up to Believoo servers drives hosting sales.
4. **Trial Mode**: Time-limited functionality to convert users to paid licenses.

---

## Security Considerations

- **API Keys**: Change `ADMIN_API_KEY` and `SECRET_KEY` in production immediately.
- **HTTPS**: Always run backend with TLS in production.
- **Self-Protection**: Client requires admin password to close when enabled.
- **Encrypted Quarantine**: Files are AES-encrypted before storage.
- **HWID Binding**: Licenses are locked to specific hardware to prevent sharing.

---

## License

Proprietary - Believoo Hosting (B-Host)

---

## Power Features

> **No. 1 Power**: This isn't just a scanner. It has "Force Delete" and "Process Killer" capabilities that even big-name antivirus products sometimes fail at.

> **BelieVoo Control**: Manage installed software across India (or the world) from your hosting panel.

> **B-Host Cloud Backup**: User data stays safe on your servers, driving hosting sales growth.
