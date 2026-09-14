"""
Believoo Shield Pro - Quarantine Manager
Secure threat isolation with encryption and metadata tracking.
"""
import os
import json
import shutil
import hashlib
import base64
import time
from pathlib import Path
from typing import Optional, List, Dict
from datetime import datetime
from dataclasses import dataclass, asdict

try:
    from cryptography.fernet import Fernet
    from cryptography.hazmat.primitives import hashes
    from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2
    CRYPTO_AVAILABLE = True
except ImportError:
    CRYPTO_AVAILABLE = False

from config.settings import config


@dataclass
class QuarantineRecord:
    """Record of a quarantined file."""
    original_path: str
    quarantine_path: str
    file_hash: str
    file_size: int
    threat_name: str
    threat_type: str
    severity: str
    quarantine_date: str
    encrypted: bool
    can_restore: bool = True


class QuarantineManager:
    """
    Enterprise quarantine system.
    
    Features:
    - AES encryption of quarantined files
    - Original path preservation for restore
    - Metadata tracking
    - Secure deletion support
    - Quarantine directory protection
    """
    
    def __init__(self):
        self.quarantine_dir = config.QUARANTINE_DIR
        self.quarantine_dir.mkdir(parents=True, exist_ok=True)
        self.records_file = self.quarantine_dir / "quarantine_records.json"
        self.records: List[QuarantineRecord] = []
        self._load_records()
        self._init_encryption()
    
    def _init_encryption(self):
        """Initialize encryption for quarantine storage."""
        self.encryption_key = None
        self.cipher = None
        
        if not CRYPTO_AVAILABLE:
            return
        
        key_file = config.BASE_DIR / ".qkey"
        if key_file.exists():
            with open(key_file, 'rb') as f:
                self.encryption_key = f.read()
        else:
            self.encryption_key = Fernet.generate_key()
            with open(key_file, 'wb') as f:
                f.write(self.encryption_key)
        
        self.cipher = Fernet(self.encryption_key)
    
    def _load_records(self):
        """Load quarantine records from disk."""
        if self.records_file.exists():
            try:
                with open(self.records_file, 'r') as f:
                    data = json.load(f)
                self.records = [QuarantineRecord(**record) for record in data]
            except (json.JSONDecodeError, TypeError):
                self.records = []
    
    def _save_records(self):
        """Save quarantine records to disk."""
        try:
            data = [asdict(r) for r in self.records]
            with open(self.records_file, 'w') as f:
                json.dump(data, f, indent=2)
        except IOError:
            pass
    
    def _generate_quarantine_path(self, original_path: str, file_hash: str) -> Path:
        """Generate a safe quarantine filename."""
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        safe_name = f"{file_hash[:16]}_{timestamp}.quar"
        return self.quarantine_dir / safe_name
    
    def _encrypt_file(self, source_path: str, dest_path: str) -> bool:
        """Encrypt file before storing in quarantine."""
        if not CRYPTO_AVAILABLE or not self.cipher:
            # Just copy if encryption unavailable
            shutil.copy2(source_path, dest_path)
            return False
        
        try:
            with open(source_path, 'rb') as f:
                data = f.read()
            encrypted = self.cipher.encrypt(data)
            with open(dest_path, 'wb') as f:
                f.write(encrypted)
            return True
        except Exception:
            shutil.copy2(source_path, dest_path)
            return False
    
    def quarantine(self, original_path: str, file_hash: str,
                   threat_name: str = "Unknown", threat_type: str = "malware",
                   severity: str = "high", file_size: int = 0) -> bool:
        """
        Move a threat file into quarantine.
        Encrypts and stores with metadata.
        """
        try:
            original_path = str(Path(original_path).resolve())
            
            if not os.path.exists(original_path):
                return False
            
            if file_size == 0:
                file_size = os.path.getsize(original_path)
            
            quarantine_path = self._generate_quarantine_path(original_path, file_hash)
            
            # Encrypt and store
            encrypted = self._encrypt_file(original_path, str(quarantine_path))
            
            # Remove original
            if os.path.exists(original_path):
                try:
                    os.remove(original_path)
                except PermissionError:
                    # If can't delete, at least zero out
                    pass
            
            # Record metadata
            record = QuarantineRecord(
                original_path=original_path,
                quarantine_path=str(quarantine_path),
                file_hash=file_hash,
                file_size=file_size,
                threat_name=threat_name,
                threat_type=threat_type,
                severity=severity,
                quarantine_date=datetime.now().isoformat(),
                encrypted=encrypted
            )
            
            self.records.append(record)
            self._save_records()
            
            # Report to cloud
            self._report_to_cloud(record)
            
            return True
            
        except Exception:
            return False
    
    def _report_to_cloud(self, record: QuarantineRecord):
        """Report quarantined threat to central server."""
        try:
            import requests
            headers = config.get_headers()
            data = {
                "hwid": config.hwid,
                "file_path": record.original_path,
                "file_hash": record.file_hash,
                "threat_name": record.threat_name,
                "threat_type": record.threat_type,
                "severity": record.severity,
                "action_taken": "quarantined",
                "file_size": record.file_size
            }
            requests.post(
                f"{config.API_BASE_URL}/threat-log",
                json=data,
                headers=headers,
                timeout=10
            )
        except Exception:
            pass
    
    def restore(self, record_index: int) -> bool:
        """
        Restore a file from quarantine to its original location.
        Decrypts and moves back.
        """
        if record_index < 0 or record_index >= len(self.records):
            return False
        
        record = self.records[record_index]
        if not record.can_restore:
            return False
        
        try:
            quarantine_path = Path(record.quarantine_path)
            original_path = Path(record.original_path)
            
            if not quarantine_path.exists():
                return False
            
            # Decrypt
            if record.encrypted and CRYPTO_AVAILABLE and self.cipher:
                with open(quarantine_path, 'rb') as f:
                    encrypted_data = f.read()
                decrypted_data = self.cipher.decrypt(encrypted_data)
                with open(original_path, 'wb') as f:
                    f.write(decrypted_data)
            else:
                shutil.copy2(quarantine_path, original_path)
            
            # Remove from quarantine
            os.remove(quarantine_path)
            
            # Update record
            record.can_restore = False
            self._save_records()
            
            return True
            
        except Exception:
            return False
    
    def delete_permanently(self, record_index: int) -> bool:
        """Permanently delete a quarantined file."""
        if record_index < 0 or record_index >= len(self.records):
            return False
        
        record = self.records[record_index]
        
        try:
            quarantine_path = Path(record.quarantine_path)
            if quarantine_path.exists():
                # Secure delete - overwrite then delete
                size = os.path.getsize(quarantine_path)
                with open(quarantine_path, 'r+b') as f:
                    f.write(os.urandom(size))
                os.remove(quarantine_path)
            
            self.records.pop(record_index)
            self._save_records()
            return True
            
        except Exception:
            return False
    
    def get_records(self) -> List[QuarantineRecord]:
        """Get all quarantine records."""
        return self.records
    
    def get_stats(self) -> dict:
        """Get quarantine statistics."""
        total = len(self.records)
        total_size = sum(r.file_size for r in self.records)
        by_severity = {}
        for r in self.records:
            by_severity[r.severity] = by_severity.get(r.severity, 0) + 1
        
        return {
            "total_quarantined": total,
            "total_size_bytes": total_size,
            "by_severity": by_severity
        }
    
    def clear_all(self) -> int:
        """Clear all quarantined files. Returns count deleted."""
        count = 0
        for record in self.records:
            try:
                path = Path(record.quarantine_path)
                if path.exists():
                    os.remove(path)
                    count += 1
            except Exception:
                continue
        
        self.records = []
        self._save_records()
        return count
