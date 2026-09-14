"""
Believoo Shield Pro - Smart Signature Engine
SHA-256 hashing with local cache + Cloud API verification for unknown files.
"""
import os
import hashlib
import json
import time
import threading
from pathlib import Path
from typing import Dict, Optional, Set, List, Tuple
from dataclasses import dataclass
import requests

from config.settings import config


@dataclass
class ScanResult:
    """Result of a file scan."""
    file_path: str
    file_hash: str
    is_threat: bool
    threat_name: Optional[str] = None
    threat_type: str = "unknown"
    severity: str = "low"
    cloud_verified: bool = False
    file_size: int = 0
    scan_time_ms: float = 0.0


class SignatureEngine:
    """
    Enterprise-grade signature-based detection engine.
    Features:
    - SHA-256 local hash database
    - Cloud API verification for unknown hashes
    - LRU cache for performance
    - Thread-safe operations
    """
    
    def __init__(self):
        self.local_db: Dict[str, dict] = {}
        self.hash_cache: Dict[str, ScanResult] = {}
        self.cache_lock = threading.RLock()
        self.db_lock = threading.RLock()
        self.max_cache_size = 10000
        self.cache_hits = 0
        self.cache_misses = 0
        self.db_file = config.CACHE_DIR / "local_signatures.json"
        self.cloud_api_url = f"{config.API_BASE_URL.replace('/api/v1', '')}/api/v1/signatures/lookup"
        self._load_local_db()
        self._init_builtin_signatures()
    
    def _init_builtin_signatures(self):
        """Initialize with built-in demo signatures for testing."""
        demo_signatures = {
            "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855": {
                "name": "EICAR-TEST-FILE",
                "type": "test",
                "severity": "low"
            },
        }
        with self.db_lock:
            for h, data in demo_signatures.items():
                if h not in self.local_db:
                    self.local_db[h] = data
    
    def _load_local_db(self):
        """Load local signature database from disk."""
        if self.db_file.exists():
            try:
                with open(self.db_file, 'r') as f:
                    self.local_db = json.load(f)
            except (json.JSONDecodeError, IOError):
                self.local_db = {}
    
    def _save_local_db(self):
        """Save local signature database to disk."""
        try:
            with open(self.db_file, 'w') as f:
                json.dump(self.local_db, f, indent=2)
        except IOError:
            pass
    
    def _compute_hash(self, file_path: str) -> Optional[str]:
        """Compute SHA-256 hash of a file."""
        try:
            sha256 = hashlib.sha256()
            with open(file_path, 'rb') as f:
                while True:
                    chunk = f.read(65536)  # 64KB chunks
                    if not chunk:
                        break
                    sha256.update(chunk)
            return sha256.hexdigest()
        except (IOError, OSError, PermissionError):
            return None
    
    def _check_cloud_api(self, file_hash: str) -> Optional[dict]:
        """
        Query cloud API for unknown file hash.
        Returns threat data if found, None if clean.
        """
        try:
            headers = config.get_headers()
            response = requests.get(
                f"{config.API_BASE_URL}/threats/check",
                params={"hash": file_hash},
                headers=headers,
                timeout=10
            )
            if response.status_code == 200:
                data = response.json()
                if data.get("is_threat"):
                    return {
                        "name": data.get("threat_name", "Unknown.Cloud"),
                        "type": data.get("threat_type", "malware"),
                        "severity": data.get("severity", "high"),
                        "cloud_verified": True
                    }
        except requests.RequestException:
            pass
        return None
    
    def _update_cache(self, result: ScanResult):
        """Add result to LRU cache with eviction."""
        with self.cache_lock:
            if len(self.hash_cache) >= self.max_cache_size:
                # Evict oldest entries
                to_remove = list(self.hash_cache.keys())[:1000]
                for key in to_remove:
                    del self.hash_cache[key]
            self.hash_cache[result.file_hash] = result
    
    def scan_file(self, file_path: str) -> ScanResult:
        """
        Scan a single file using signature engine.
        Returns ScanResult with threat determination.
        """
        start_time = time.time()
        
        # Get file info
        try:
            file_size = os.path.getsize(file_path)
        except OSError:
            file_size = 0
        
        # Check max file size
        if file_size > config.MAX_FILE_SIZE_MB * 1024 * 1024:
            return ScanResult(
                file_path=file_path,
                file_hash="",
                is_threat=False,
                file_size=file_size,
                scan_time_ms=(time.time() - start_time) * 1000
            )
        
        # Compute hash
        file_hash = self._compute_hash(file_path)
        if not file_hash:
            return ScanResult(
                file_path=file_path,
                file_hash="",
                is_threat=False,
                file_size=file_size,
                scan_time_ms=(time.time() - start_time) * 1000
            )
        
        # Check cache first
        with self.cache_lock:
            if file_hash in self.hash_cache:
                self.cache_hits += 1
                cached = self.hash_cache[file_hash]
                return ScanResult(
                    file_path=file_path,
                    file_hash=file_hash,
                    is_threat=cached.is_threat,
                    threat_name=cached.threat_name,
                    threat_type=cached.threat_type,
                    severity=cached.severity,
                    cloud_verified=cached.cloud_verified,
                    file_size=file_size,
                    scan_time_ms=(time.time() - start_time) * 1000
                )
            self.cache_misses += 1
        
        # Check local DB
        with self.db_lock:
            local_match = self.local_db.get(file_hash)
        
        if local_match:
            result = ScanResult(
                file_path=file_path,
                file_hash=file_hash,
                is_threat=True,
                threat_name=local_match.get("name", "Unknown.Local"),
                threat_type=local_match.get("type", "malware"),
                severity=local_match.get("severity", "high"),
                cloud_verified=False,
                file_size=file_size,
                scan_time_ms=(time.time() - start_time) * 1000
            )
            self._update_cache(result)
            return result
        
        # Check cloud API for unknown hashes
        cloud_match = self._check_cloud_api(file_hash)
        if cloud_match:
            # Add to local DB for future scans
            with self.db_lock:
                self.local_db[file_hash] = {
                    "name": cloud_match["name"],
                    "type": cloud_match["type"],
                    "severity": cloud_match["severity"],
                    "source": "cloud",
                    "added": time.time()
                }
            self._save_local_db()
            
            result = ScanResult(
                file_path=file_path,
                file_hash=file_hash,
                is_threat=True,
                threat_name=cloud_match["name"],
                threat_type=cloud_match["type"],
                severity=cloud_match["severity"],
                cloud_verified=True,
                file_size=file_size,
                scan_time_ms=(time.time() - start_time) * 1000
            )
            self._update_cache(result)
            return result
        
        # Clean file
        result = ScanResult(
            file_path=file_path,
            file_hash=file_hash,
            is_threat=False,
            file_size=file_size,
            scan_time_ms=(time.time() - start_time) * 1000
        )
        self._update_cache(result)
        return result
    
    def scan_directory(self, directory: str, progress_callback=None) -> List[ScanResult]:
        """
        Recursively scan a directory.
        Calls progress_callback(file_path, current, total) if provided.
        """
        results = []
        scan_path = Path(directory)
        
        if not scan_path.exists():
            return results
        
        # Collect all files to scan
        files = []
        for path in scan_path.rglob("*"):
            if path.is_file():
                if path.suffix.lower() in config.SCAN_EXTENSIONS or not path.suffix:
                    files.append(str(path))
        
        total = len(files)
        for i, file_path in enumerate(files):
            result = self.scan_file(file_path)
            if result.is_threat:
                results.append(result)
            if progress_callback:
                progress_callback(file_path, i + 1, total)
        
        return results
    
    def add_signature(self, file_hash: str, name: str, threat_type: str = "malware", severity: str = "high"):
        """Manually add a signature to the local database."""
        with self.db_lock:
            self.local_db[file_hash] = {
                "name": name,
                "type": threat_type,
                "severity": severity,
                "source": "manual",
                "added": time.time()
            }
        self._save_local_db()
    
    def get_stats(self) -> dict:
        """Get engine statistics."""
        return {
            "local_signatures": len(self.local_db),
            "cache_entries": len(self.hash_cache),
            "cache_hits": self.cache_hits,
            "cache_misses": self.cache_misses,
            "cache_hit_rate": self.cache_hits / max(self.cache_hits + self.cache_misses, 1) * 100
        }
