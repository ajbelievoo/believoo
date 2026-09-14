"""
Believoo Shield Pro - System Tools Module
Windows Registry persistence, self-protection, HWID generation, and admin utilities.
"""
import os
import sys
import uuid
import hashlib
import platform
import getpass
import subprocess
from pathlib import Path
from typing import Optional, Dict
from config.settings import config


class SystemTools:
    """
    Enterprise system integration tools for Windows.
    
    Features:
    - Registry Run key persistence
    - Self-protection against unauthorized closing
    - Unique HWID generation
    - Admin privilege checks
    - System information gathering
    """
    
    def __init__(self):
        self.is_windows = platform.system() == "Windows"
        self.reg_key_path = r"Software\Microsoft\Windows\CurrentVersion\Run"
        self.app_reg_name = "BelievooShieldPro"
    
    def generate_hwid(self) -> str:
        """
        Generate a unique Hardware ID based on system characteristics.
        Uses multiple system properties for robust identification.
        """
        components = []
        
        # Machine-specific identifiers
        if self.is_windows:
            try:
                import wmi
                c = wmi.WMI()
                
                # CPU info
                for processor in c.Win32_Processor():
                    components.append(processor.ProcessorId or processor.Name)
                
                # BIOS info
                for bios in c.Win32_BIOS():
                    components.append(bios.SerialNumber or "")
                
                # Motherboard
                for board in c.Win32_BaseBoard():
                    components.append(board.SerialNumber or "")
                
                # Disk
                for disk in c.Win32_DiskDrive():
                    components.append(disk.SerialNumber or disk.Model or "")
                    
            except Exception:
                pass
        
        # Cross-platform fallbacks
        components.append(platform.node())
        components.append(platform.machine())
        components.append(platform.processor())
        
        # Add MAC address
        try:
            import psutil
            for iface, addrs in psutil.net_if_addrs().items():
                for addr in addrs:
                    if addr.family == psutil.AF_LINK:
                        components.append(addr.address)
                        break
        except Exception:
            pass
        
        # Generate deterministic hash
        combined = '|'.join(filter(None, components))
        hwid = hashlib.sha256(combined.encode()).hexdigest().upper()
        return hwid
    
    def ensure_hwid(self) -> str:
        """Get or generate HWID."""
        if config.hwid:
            return config.hwid
        
        hwid_file = config.BASE_DIR / ".hwid"
        if hwid_file.exists():
            with open(hwid_file, 'r') as f:
                hwid = f.read().strip()
            if hwid:
                config.hwid = hwid
                return hwid
        
        hwid = self.generate_hwid()
        config.hwid = hwid
        with open(hwid_file, 'w') as f:
            f.write(hwid)
        config.save_config()
        return hwid
    
    def get_device_name(self) -> str:
        """Get user-friendly device name."""
        if config.device_name:
            return config.device_name
        name = platform.node()
        config.device_name = name
        config.save_config()
        return name
    
    def is_admin(self) -> bool:
        """Check if running with administrator privileges."""
        try:
            if self.is_windows:
                import ctypes
                return ctypes.windll.shell32.IsUserAnAdmin()
            else:
                return os.getuid() == 0
        except Exception:
            return False
    
    def add_to_startup(self) -> bool:
        """
        Add application to Windows Registry Run key.
        Returns True on success.
        """
        if not self.is_windows:
            return False
        
        try:
            import winreg
            
            executable_path = sys.executable
            if getattr(sys, 'frozen', False):
                # Running as compiled EXE
                exe_path = sys.executable
            else:
                # Running as script - add pythonw
                script_path = os.path.abspath(sys.argv[0])
                exe_path = f'"{sys.executable}" "{script_path}"'
            
            key = winreg.OpenKey(
                winreg.HKEY_CURRENT_USER,
                self.reg_key_path,
                0,
                winreg.KEY_SET_VALUE
            )
            winreg.SetValueEx(key, self.app_reg_name, 0, winreg.REG_SZ, exe_path)
            winreg.CloseKey(key)
            return True
            
        except Exception:
            return False
    
    def remove_from_startup(self) -> bool:
        """Remove application from Windows Registry Run key."""
        if not self.is_windows:
            return False
        
        try:
            import winreg
            key = winreg.OpenKey(
                winreg.HKEY_CURRENT_USER,
                self.reg_key_path,
                0,
                winreg.KEY_SET_VALUE
            )
            winreg.DeleteValue(key, self.app_reg_name)
            winreg.CloseKey(key)
            return True
            
        except FileNotFoundError:
            return True
        except Exception:
            return False
    
    def is_in_startup(self) -> bool:
        """Check if application is in startup registry."""
        if not self.is_windows:
            return False
        
        try:
            import winreg
            key = winreg.OpenKey(
                winreg.HKEY_CURRENT_USER,
                self.reg_key_path,
                0,
                winreg.KEY_READ
            )
            try:
                winreg.QueryValueEx(key, self.app_reg_name)
                return True
            except FileNotFoundError:
                return False
            finally:
                winreg.CloseKey(key)
        except Exception:
            return False
    
    def setup_self_protection(self, admin_password: str) -> bool:
        """
        Setup self-protection requiring admin password to exit.
        Returns True on success.
        """
        if not admin_password:
            return False
        
        try:
            from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2
            from cryptography.hazmat.primitives import hashes
            import base64
            
            salt = os.urandom(16)
            kdf = PBKDF2(
                algorithm=hashes.SHA256(),
                length=32,
                salt=salt,
                iterations=480000,
            )
            key = kdf.derive(admin_password.encode())
            
            # Store hash
            pwd_file = config.BASE_DIR / ".sp"
            with open(pwd_file, 'wb') as f:
                f.write(salt + key)
            
            config.ADMIN_PASSWORD_HASH = base64.b64encode(salt + key).decode()
            config.SELF_PROTECTION_ENABLED = True
            config.save_config()
            return True
            
        except Exception:
            return False
    
    def verify_admin_password(self, password: str) -> bool:
        """Verify admin password for self-protection."""
        if not config.SELF_PROTECTION_ENABLED:
            return True
        
        try:
            from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2
            from cryptography.hazmat.primitives import hashes
            import base64
            
            pwd_file = config.BASE_DIR / ".sp"
            if not pwd_file.exists():
                return True
            
            with open(pwd_file, 'rb') as f:
                data = f.read()
            
            salt = data[:16]
            stored_key = data[16:]
            
            kdf = PBKDF2(
                algorithm=hashes.SHA256(),
                length=32,
                salt=salt,
                iterations=480000,
            )
            key = kdf.derive(password.encode())
            
            return key == stored_key
            
        except Exception:
            return False
    
    def get_system_info(self) -> Dict:
        """Gather comprehensive system information."""
        info = {
            "os": platform.system(),
            "os_version": platform.version(),
            "architecture": platform.machine(),
            "processor": platform.processor(),
            "hostname": platform.node(),
            "username": getpass.getuser(),
            "python_version": sys.version,
            "is_admin": self.is_admin(),
            "hwid": config.hwid or self.ensure_hwid(),
        }
        
        try:
            import psutil
            info["cpu_count"] = psutil.cpu_count()
            info["memory_total"] = psutil.virtual_memory().total
            info["disk_total"] = psutil.disk_usage('/').total
            info["boot_time"] = psutil.boot_time()
        except Exception:
            pass
        
        return info
    
    def elevate_privileges(self) -> bool:
        """
        Attempt to restart with admin privileges (Windows UAC).
        Returns True if elevated or already admin.
        """
        if not self.is_windows:
            return self.is_admin()
        
        if self.is_admin():
            return True
        
        try:
            import ctypes
            ctypes.windll.shell32.ShellExecuteW(
                None, "runas", sys.executable, ' '.join(sys.argv), None, 1
            )
            return True
        except Exception:
            return False
