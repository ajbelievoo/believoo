"""
Believoo Shield Pro - Real-Time Guard
Watchdog-based monitoring of Downloads and USB drives.
Instant threat detection and blocking.
"""
import os
import time
import threading
import platform
from pathlib import Path
from typing import Callable, Optional, List

from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler, FileCreatedEvent, FileMovedEvent

from modules.engine.signature_engine import SignatureEngine, ScanResult
from config.settings import config


class RealtimeEventHandler(FileSystemEventHandler):
    """Handles filesystem events for real-time protection."""
    
    def __init__(self, engine: SignatureEngine, threat_callback: Callable, quarantine_callback: Callable):
        self.engine = engine
        self.on_threat = threat_callback
        self.on_quarantine = quarantine_callback
        self.recently_scanned = {}  # Debounce cache
        self.debounce_seconds = 3.0
        self.lock = threading.Lock()
    
    def _should_scan(self, path: str) -> bool:
        """Check if file should be scanned (debounce)."""
        now = time.time()
        with self.lock:
            last_scan = self.recently_scanned.get(path, 0)
            if now - last_scan < self.debounce_seconds:
                return False
            self.recently_scanned[path] = now
            # Clean old entries periodically
            old = [p for p, t in self.recently_scanned.items() if now - t > 60]
            for p in old:
                del self.recently_scanned[p]
        return True
    
    def _is_target_file(self, path: str) -> bool:
        """Check if path is a file we care about."""
        if not os.path.isfile(path):
            return False
        ext = Path(path).suffix.lower()
        if ext in config.SCAN_EXTENSIONS:
            return True
        # Scan files without extension in suspicious locations
        if not ext and any(susp in path.lower() for susp in ['temp', 'tmp', 'download']):
            return True
        return False
    
    def on_created(self, event):
        """Handle file creation event."""
        if event.is_directory:
            return
        self._handle_file(event.src_path)
    
    def on_moved(self, event):
        """Handle file move event (common for downloads)."""
        if event.is_directory:
            return
        self._handle_file(event.dest_path)
    
    def on_modified(self, event):
        """Handle file modification."""
        if event.is_directory:
            return
        # Only scan if file was just created or is suspicious
        path = event.src_path
        if self._is_target_file(path) and os.path.getsize(path) < 50 * 1024 * 1024:  # < 50MB
            self._handle_file(path)
    
    def _handle_file(self, path: str):
        """Process a file - scan and quarantine if needed."""
        if not self._is_target_file(path):
            return
        if not self._should_scan(path):
            return
        
        # Small delay to ensure file write is complete
        time.sleep(0.5)
        
        try:
            result = self.engine.scan_file(path)
            if result.is_threat:
                self.on_threat(result)
                self.on_quarantine(result)
        except Exception:
            pass


class RealtimeGuard:
    """
    Enterprise real-time filesystem protection.
    Monitors critical paths and USB drive insertions.
    """
    
    def __init__(self, engine: Optional[SignatureEngine] = None):
        self.engine = engine or SignatureEngine()
        self.observer = Observer()
        self.handlers = []
        self.is_running = False
        self._threat_callbacks: List[Callable] = []
        self._quarantine_callback: Optional[Callable] = None
        self._usb_monitor_thread: Optional[threading.Thread] = None
        self._usb_stop_event = threading.Event()
        self.watched_paths = []
    
    def register_threat_callback(self, callback: Callable):
        """Register callback for threat detection."""
        self._threat_callbacks.append(callback)
    
    def register_quarantine_callback(self, callback: Callable):
        """Register callback for auto-quarantine."""
        self._quarantine_callback = callback
    
    def _threat_handler(self, result: ScanResult):
        """Internal threat handler - dispatches to callbacks."""
        for cb in self._threat_callbacks:
            try:
                cb(result)
            except Exception:
                pass
    
    def _quarantine_handler(self, result: ScanResult):
        """Internal quarantine handler."""
        if self._quarantine_callback:
            try:
                self._quarantine_callback(result)
            except Exception:
                pass
    
    def _get_watched_paths(self) -> List[str]:
        """Get list of paths to monitor."""
        paths = []
        for path in config.WATCH_PATHS:
            expanded = os.path.expandvars(os.path.expanduser(path))
            if os.path.exists(expanded):
                paths.append(expanded)
        return paths
    
    def _monitor_usb(self):
        """Background thread to monitor USB drive insertion."""
        known_drives = set()
        
        if platform.system() == "Windows":
            import string
            import ctypes
            
            def get_drives():
                drives = []
                bitmask = ctypes.windll.kernel32.GetLogicalDrives()
                for letter in string.ascii_uppercase:
                    if bitmask & 1:
                        drives.append(f"{letter}:/")
                    bitmask >>= 1
                return drives
            
            known_drives = set(get_drives())
            
            while not self._usb_stop_event.is_set():
                try:
                    current_drives = set(get_drives())
                    new_drives = current_drives - known_drives
                    
                    for drive in new_drives:
                        # Check if it's removable (USB)
                        drive_type = ctypes.windll.kernel32.GetDriveTypeW(drive)
                        if drive_type == 2:  # DRIVE_REMOVABLE
                            self._handle_usb_insertion(drive)
                    
                    known_drives = current_drives
                    self._usb_stop_event.wait(2.0)
                except Exception:
                    self._usb_stop_event.wait(5.0)
        else:
            # Linux USB monitoring via udev (simplified)
            while not self._usb_stop_event.is_set():
                self._usb_stop_event.wait(10.0)
    
    def _handle_usb_insertion(self, drive_path: str):
        """Handle newly inserted USB drive - full scan."""
        for cb in self._threat_callbacks:
            try:
                cb(ScanResult(
                    file_path=drive_path,
                    file_hash="",
                    is_threat=False,
                    threat_name=None,
                    threat_type="info",
                    severity="low"
                ))
            except Exception:
                pass
        
        # Auto-scan USB drive
        if config.GUARD_ENABLED:
            results = self.engine.scan_directory(drive_path)
            for result in results:
                self._threat_handler(result)
                self._quarantine_handler(result)
    
    def start(self) -> bool:
        """Start the real-time guard."""
        if self.is_running:
            return True
        
        if not config.GUARD_ENABLED:
            return False
        
        paths = self._get_watched_paths()
        if not paths:
            return False
        
        event_handler = RealtimeEventHandler(
            self.engine,
            self._threat_handler,
            self._quarantine_handler
        )
        
        for path in paths:
            try:
                self.observer.schedule(event_handler, path, recursive=True)
                self.watched_paths.append(path)
            except Exception:
                continue
        
        self.observer.start()
        
        # Start USB monitoring
        self._usb_stop_event.clear()
        self._usb_monitor_thread = threading.Thread(target=self._monitor_usb, daemon=True)
        self._usb_monitor_thread.start()
        
        self.is_running = True
        return True
    
    def stop(self):
        """Stop the real-time guard."""
        if not self.is_running:
            return
        
        self._usb_stop_event.set()
        self.observer.stop()
        self.observer.join()
        
        if self._usb_monitor_thread and self._usb_monitor_thread.is_alive():
            self._usb_monitor_thread.join(timeout=2.0)
        
        self.is_running = False
        self.watched_paths = []
    
    def add_watch_path(self, path: str):
        """Dynamically add a path to watch."""
        if self.is_running and os.path.exists(path):
            from watchdog.observers import Observer
            # Note: Dynamic addition requires observer restart in watchdog
            pass
    
    def get_status(self) -> dict:
        """Get guard status."""
        return {
            "running": self.is_running,
            "watched_paths": self.watched_paths,
            "engine_stats": self.engine.get_stats()
        }
