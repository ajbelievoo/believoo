"""
Believoo Shield Pro - Admin Command Listener
Background thread that listens for commands from Believoo Admin Panel.
Handles: Force Delete, Full Scan, Update Signatures, Enable/Disable Guard, etc.
"""
import os
import time
import json
import threading
from typing import Callable, Dict, Optional, List
from dataclasses import dataclass

import requests

from config.settings import config


@dataclass
class RemoteCommand:
    """Parsed remote command from admin panel."""
    command_id: str
    command: str
    target_path: Optional[str]
    parameters: Optional[Dict]
    priority: int


class AdminCommandListener:
    """
    Enterprise remote command listener.
    Polls backend for commands and dispatches to handler callbacks.
    
    Supported commands:
    - force_delete: Delete a specific file/folder
    - full_scan: Initiate full system scan
    - quick_scan: Quick scan of critical areas
    - update_signatures: Update virus definitions
    - shutdown_guard: Disable real-time protection
    - enable_guard: Enable real-time protection
    - reboot: Reboot the system
    - isolate: Network isolation mode
    """
    
    def __init__(self):
        self.is_running = False
        self._thread: Optional[threading.Thread] = None
        self._stop_event = threading.Event()
        self._handlers: Dict[str, Callable] = {}
        self._command_history: List[dict] = []
        self._last_poll = 0
        self.poll_interval = config.HEARTBEAT_INTERVAL
        self._lock = threading.Lock()
    
    def register_handler(self, command: str, callback: Callable):
        """Register a handler for a specific command."""
        self._handlers[command] = callback
    
    def _fetch_commands(self) -> List[RemoteCommand]:
        """Fetch pending commands from backend."""
        if not config.hwid:
            return []
        
        try:
            headers = config.get_headers()
            response = requests.get(
                f"{config.API_BASE_URL}/remote-commands",
                params={"hwid": config.hwid},
                headers=headers,
                timeout=20
            )
            
            if response.status_code == 200:
                data = response.json()
                commands = []
                for cmd in data.get("commands", []):
                    params = None
                    if cmd.get("parameters"):
                        try:
                            params = json.loads(cmd["parameters"])
                        except json.JSONDecodeError:
                            params = {"raw": cmd["parameters"]}
                    
                    commands.append(RemoteCommand(
                        command_id=cmd["id"],
                        command=cmd["command"],
                        target_path=cmd.get("target_path"),
                        parameters=params,
                        priority=cmd.get("priority", 5)
                    ))
                return commands
                
        except requests.RequestException:
            pass
        return []
    
    def _report_result(self, command_id: str, status: str, result: Optional[str] = None):
        """Report command execution result to backend."""
        try:
            headers = config.get_headers()
            data = {
                "command_id": command_id,
                "status": status,
                "result": result or ""
            }
            requests.post(
                f"{config.API_BASE_URL}/command-result",
                json=data,
                headers=headers,
                timeout=15
            )
        except requests.RequestException:
            pass
    
    def _execute_command(self, command: RemoteCommand):
        """Execute a single remote command."""
        handler = self._handlers.get(command.command)
        
        if not handler:
            self._report_result(
                command.command_id,
                "failed",
                f"No handler registered for command: {command.command}"
            )
            return
        
        try:
            # Report acknowledgment
            self._report_result(command.command_id, "acknowledged", "Command received, executing...")
            
            # Execute handler
            result = handler(
                target_path=command.target_path,
                parameters=command.parameters
            )
            
            self._report_result(
                command.command_id,
                "completed",
                str(result) if result else "Command executed successfully"
            )
            
        except Exception as e:
            self._report_result(
                command.command_id,
                "failed",
                f"Execution error: {str(e)}"
            )
    
    def _poll_loop(self):
        """Main polling loop running in background thread."""
        while not self._stop_event.is_set():
            try:
                commands = self._fetch_commands()
                
                # Sort by priority (lower = higher priority)
                commands.sort(key=lambda c: c.priority)
                
                for command in commands:
                    if self._stop_event.is_set():
                        break
                    self._execute_command(command)
                    time.sleep(0.5)  # Small delay between commands
                
                self._last_poll = time.time()
                
            except Exception:
                pass
            
            # Wait until next poll interval
            self._stop_event.wait(self.poll_interval)
    
    def start(self):
        """Start the command listener in background."""
        if self.is_running:
            return
        
        self._stop_event.clear()
        self._thread = threading.Thread(target=self._poll_loop, daemon=True)
        self._thread.start()
        self.is_running = True
    
    def stop(self):
        """Stop the command listener."""
        if not self.is_running:
            return
        
        self._stop_event.set()
        if self._thread and self._thread.is_alive():
            self._thread.join(timeout=5.0)
        self.is_running = False
    
    def get_status(self) -> dict:
        """Get listener status."""
        return {
            "running": self.is_running,
            "last_poll": self._last_poll,
            "registered_handlers": list(self._handlers.keys()),
            "poll_interval": self.poll_interval
        }
    
    def get_history(self) -> List[dict]:
        """Get command execution history."""
        with self._lock:
            return self._command_history.copy()
