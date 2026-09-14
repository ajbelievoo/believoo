"""
Believoo Shield Pro - Client Configuration
Enterprise client configuration with secure defaults.
"""
import os
import json
import platform
from pathlib import Path
from datetime import datetime


class ClientConfig:
    """Centralized client configuration singleton."""
    
    _instance = None
    
    def __new__(cls):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
            cls._instance._init_config()
        return cls._instance
    
    def _init_config(self):
        """Initialize default configuration."""
        # Application Info
        self.APP_NAME = "Believoo Shield Pro"
        self.APP_VERSION = "1.0.0"
        self.APP_CODE_NAME = "KasperskyKiller"
        
        # Backend API
        self.API_BASE_URL = os.getenv("SHIELD_API_URL", "https://api.believoo.com:8443/api/v1")
        self.HEARTBEAT_INTERVAL = 300  # 5 minutes
        self.CONNECT_TIMEOUT = 30
        self.READ_TIMEOUT = 60
        
        # Paths
        self.is_windows = platform.system() == "Windows"
        if self.is_windows:
            self.BASE_DIR = Path(os.getenv("LOCALAPPDATA", Path.home() / "AppData/Local")) / "BelievooShieldPro"
        else:
            self.BASE_DIR = Path.home() / ".believoo-shield-pro"
        
        self.BASE_DIR.mkdir(parents=True, exist_ok=True)
        
        self.QUARANTINE_DIR = self.BASE_DIR / "quarantine"
        self.LOGS_DIR = self.BASE_DIR / "logs"
        self.CACHE_DIR = self.BASE_DIR / "cache"
        self.SIGNATURES_DIR = self.BASE_DIR / "signatures"
        self.CONFIG_FILE = self.BASE_DIR / "shield_config.json"
        self.LICENSE_FILE = self.BASE_DIR / "license.dat"
        
        for d in [self.QUARANTINE_DIR, self.LOGS_DIR, self.CACHE_DIR, self.SIGNATURES_DIR]:
            d.mkdir(exist_ok=True)
        
        # Scanning
        self.SCAN_THREADS = 4
        self.MAX_FILE_SIZE_MB = 500
        self.SCAN_EXTENSIONS = [
            '.exe', '.dll', '.sys', '.bat', '.cmd', '.scr', '.com',
            '.jar', '.py', '.pyc', '.ps1', '.vbs', '.js', '.wsf',
            '.doc', '.docm', '.xls', '.xlsm', '.ppt', '.pptm',
            '.pdf', '.zip', '.rar', '.7z', '.tar', '.gz',
            '.apk', '.ipa', '.app'
        ]
        self.CRITICAL_PATHS = [
            "C:/Windows/System32",
            "C:/Windows/SysWOW64",
            "C:/Program Files",
            "C:/Program Files (x86)"
        ] if self.is_windows else ["/usr/bin", "/usr/local/bin", "/tmp"]
        
        # Real-Time Guard
        self.GUARD_ENABLED = True
        self.WATCH_PATHS = []
        if self.is_windows:
            self.WATCH_PATHS = [
                os.path.expanduser("~/Downloads"),
                os.path.expanduser("~/Desktop"),
            ]
        else:
            self.WATCH_PATHS = [str(Path.home() / "Downloads")]
        
        # Backup
        self.BACKUP_ENABLED = False
        self.BACKUP_REMOTE_HOST = os.getenv("SHIELD_BACKUP_HOST", "sftp.believoo.com")
        self.BACKUP_REMOTE_USER = os.getenv("SHIELD_BACKUP_USER", "shield_backup")
        self.BACKUP_SCHEDULE = "0 2 * * *"  # Daily at 2 AM
        self.BACKUP_PARTITIONS = []  # Auto-detected at runtime
        
        # Security
        self.SELF_PROTECTION_ENABLED = True
        self.ADMIN_PASSWORD_HASH = None  # Set during first run
        self.ALLOW_SHUTDOWN = False
        
        # State
        self.license_key = None
        self.license_token = None
        self.hwid = None
        self.device_name = None
        self.last_heartbeat = None
        self.real_time_guard_enabled = True
        self.health_score = 100.0
        self.threats_found = 0
        self.last_scan_date = None
        self.signature_version = "1.0.0"
        
        self._load_config()
    
    def _load_config(self):
        """Load configuration from file if exists."""
        if self.CONFIG_FILE.exists():
            try:
                with open(self.CONFIG_FILE, 'r') as f:
                    data = json.load(f)
                self.license_key = data.get("license_key")
                self.license_token = data.get("license_token")
                self.hwid = data.get("hwid")
                self.device_name = data.get("device_name")
                self.GUARD_ENABLED = data.get("guard_enabled", True)
                self.BACKUP_ENABLED = data.get("backup_enabled", False)
                self.SELF_PROTECTION_ENABLED = data.get("self_protection", True)
                self.signature_version = data.get("signature_version", "1.0.0")
            except Exception:
                pass
    
    def save_config(self):
        """Save current configuration to file."""
        data = {
            "license_key": self.license_key,
            "license_token": self.license_token,
            "hwid": self.hwid,
            "device_name": self.device_name,
            "guard_enabled": self.GUARD_ENABLED,
            "backup_enabled": self.BACKUP_ENABLED,
            "self_protection": self.SELF_PROTECTION_ENABLED,
            "signature_version": self.signature_version,
            "saved_at": datetime.now().isoformat()
        }
        with open(self.CONFIG_FILE, 'w') as f:
            json.dump(data, f, indent=2)
    
    def get_headers(self) -> dict:
        """Get API request headers with license token."""
        headers = {
            "Content-Type": "application/json",
            "X-Client-Version": self.APP_VERSION,
            "X-Device-ID": self.hwid or "unknown"
        }
        if self.license_token:
            headers["Authorization"] = f"Bearer {self.license_token}"
        return headers


# Global config instance
config = ClientConfig()
