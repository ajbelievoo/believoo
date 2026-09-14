"""
Believoo Shield Pro - Kernel-Level Force Delete Module
Identifies processes locking files/folders, kills them, and force-removes directories.
This is the 'Kaspersky Killer' feature - handles 'Folder in use' errors that others can't.
"""
import os
import shutil
import time
import ctypes
from pathlib import Path
from typing import List, Optional, Tuple
from dataclasses import dataclass

import psutil


@dataclass
class ForceDeleteResult:
    """Result of a force delete operation."""
    success: bool
    path: str
    processes_killed: List[str]
    error_message: Optional[str] = None
    retries: int = 0


class ForceDeleteEngine:
    """
    Enterprise-grade force deletion engine.
    
    Features:
    - Identifies all processes locking target files
    - Graceful process termination first
    - Force kill for stubborn processes
    - Recursive directory removal
    - Admin privilege elevation detection
    - Handles Windows ACLs and read-only files
    """
    
    def __init__(self):
        self.is_windows = os.name == 'nt'
        self.retry_count = 3
        self.retry_delay = 1.0  # seconds
        
        if self.is_windows:
            try:
                self._kernel32 = ctypes.windll.kernel32
            except AttributeError:
                self._kernel32 = None
    
    def is_admin(self) -> bool:
        """Check if running with administrator/root privileges."""
        try:
            if self.is_windows:
                return ctypes.windll.shell32.IsUserAnAdmin()
            else:
                return os.getuid() == 0
        except Exception:
            return False
    
    def get_locking_processes(self, target_path: str) -> List[Tuple[int, str]]:
        """
        Find all processes that have handles open to the target path.
        Returns list of (pid, process_name) tuples.
        """
        locking_processes = []
        target_path = os.path.abspath(target_path).lower()
        
        for proc in psutil.process_iter(['pid', 'name', 'open_files', 'exe', 'cwd']):
            try:
                pinfo = proc.info
                # Check open files
                for file in proc.open_files():
                    if target_path in file.path.lower() or file.path.lower().startswith(target_path):
                        locking_processes.append((pinfo['pid'], pinfo['name']))
                        break
                
                # Check if process exe or cwd is in target
                if pinfo.get('exe') and target_path in pinfo['exe'].lower():
                    locking_processes.append((pinfo['pid'], pinfo['name']))
                if pinfo.get('cwd') and target_path in pinfo['cwd'].lower():
                    locking_processes.append((pinfo['pid'], pinfo['name']))
                    
            except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
                continue
        
        # Remove duplicates while preserving order
        seen = set()
        unique = []
        for pid, name in locking_processes:
            if pid not in seen:
                seen.add(pid)
                unique.append((pid, name))
        return unique
    
    def kill_process_graceful(self, pid: int, timeout: float = 5.0) -> bool:
        """
        Attempt graceful process termination first.
        Falls back to force kill if needed.
        """
        try:
            proc = psutil.Process(pid)
            
            # Try graceful termination
            proc.terminate()
            proc.wait(timeout=timeout)
            return True
            
        except psutil.NoSuchProcess:
            return True  # Already gone
        except psutil.TimeoutExpired:
            # Force kill
            try:
                proc.kill()
                proc.wait(timeout=3.0)
                return True
            except Exception:
                return False
        except (psutil.AccessDenied, Exception):
            return False
    
    def kill_processes(self, pids: List[int]) -> List[str]:
        """
        Kill a list of process IDs.
        Returns list of successfully killed process names.
        """
        killed = []
        for pid in pids:
            try:
                proc = psutil.Process(pid)
                name = proc.name()
                if self.kill_process_graceful(pid):
                    killed.append(name)
            except psutil.NoSuchProcess:
                pass
            except Exception:
                pass
        return killed
    
    def remove_readonly(self, func, path, excinfo):
        """Error handler for shutil.rmtree - removes read-only attribute."""
        try:
            os.chmod(path, 0o777)
            func(path)
        except Exception:
            pass
    
    def force_delete_file(self, file_path: str) -> ForceDeleteResult:
        """Force delete a single file."""
        target = os.path.abspath(file_path)
        killed_processes = []
        
        for attempt in range(self.retry_count):
            # Find and kill locking processes
            lockers = self.get_locking_processes(target)
            if lockers:
                pids = [pid for pid, _ in lockers]
                killed = self.kill_processes(pids)
                killed_processes.extend(killed)
                time.sleep(self.retry_delay)
            
            try:
                if os.path.exists(target):
                    os.chmod(target, 0o777)
                    os.remove(target)
                
                if not os.path.exists(target):
                    return ForceDeleteResult(
                        success=True,
                        path=target,
                        processes_killed=list(set(killed_processes)),
                        retries=attempt
                    )
                    
            except PermissionError:
                # Try Windows-specific force delete
                if self.is_windows and self._kernel32:
                    try:
                        self._kernel32.MoveFileExW(target, None, 4)  # MOVEFILE_DELAY_UNTIL_REBOOT
                        return ForceDeleteResult(
                            success=True,
                            path=target,
                            processes_killed=list(set(killed_processes)),
                            retries=attempt,
                            error_message="Scheduled for deletion on reboot"
                        )
                    except Exception:
                        pass
                time.sleep(self.retry_delay)
                continue
                
            except Exception as e:
                if attempt < self.retry_count - 1:
                    time.sleep(self.retry_delay)
                    continue
                return ForceDeleteResult(
                    success=False,
                    path=target,
                    processes_killed=list(set(killed_processes)),
                    error_message=str(e),
                    retries=attempt
                )
        
        return ForceDeleteResult(
            success=False,
            path=target,
            processes_killed=list(set(killed_processes)),
            error_message="Failed after maximum retries",
            retries=self.retry_count
        )
    
    def force_delete_directory(self, dir_path: str) -> ForceDeleteResult:
        """
        Force delete an entire directory tree.
        Handles processes locking any file within the tree.
        """
        target = os.path.abspath(dir_path)
        killed_processes = []
        
        if not os.path.exists(target):
            return ForceDeleteResult(
                success=True,
                path=target,
                processes_killed=[],
                error_message="Path does not exist"
            )
        
        # First pass: kill all processes locking anything in the tree
        for attempt in range(self.retry_count):
            lockers = self.get_locking_processes(target)
            if lockers:
                pids = [pid for pid, _ in lockers]
                killed = self.kill_processes(pids)
                killed_processes.extend(killed)
                time.sleep(self.retry_delay)
            else:
                break
        
        # Second pass: attempt deletion with retries
        for attempt in range(self.retry_count):
            try:
                if os.path.isfile(target):
                    os.chmod(target, 0o777)
                    os.remove(target)
                elif os.path.isdir(target):
                    shutil.rmtree(target, onexc=self.remove_readonly)
                
                if not os.path.exists(target):
                    return ForceDeleteResult(
                        success=True,
                        path=target,
                        processes_killed=list(set(killed_processes)),
                        retries=attempt
                    )
                    
            except Exception as e:
                # Try to unlock any new lockers
                lockers = self.get_locking_processes(target)
                if lockers:
                    pids = [pid for pid, _ in lockers]
                    killed = self.kill_processes(pids)
                    killed_processes.extend(killed)
                
                if attempt < self.retry_count - 1:
                    time.sleep(self.retry_delay)
                    continue
                
                return ForceDeleteResult(
                    success=False,
                    path=target,
                    processes_killed=list(set(killed_processes)),
                    error_message=str(e),
                    retries=attempt
                )
        
        # Windows: schedule for reboot deletion as last resort
        if self.is_windows and self._kernel32 and os.path.exists(target):
            try:
                self._kernel32.MoveFileExW(target, None, 4)
                return ForceDeleteResult(
                    success=True,
                    path=target,
                    processes_killed=list(set(killed_processes)),
                    retries=self.retry_count,
                    error_message="Scheduled for deletion on next reboot"
                )
            except Exception:
                pass
        
        return ForceDeleteResult(
            success=False,
            path=target,
            processes_killed=list(set(killed_processes)),
            error_message="Failed after all attempts",
            retries=self.retry_count
        )
    
    def force_delete(self, path: str) -> ForceDeleteResult:
        """Universal force delete - auto-detects file or directory."""
        if os.path.isfile(path):
            return self.force_delete_file(path)
        else:
            return self.force_delete_directory(path)
