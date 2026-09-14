"""
Believoo Shield Pro - Client Entry Point
Enterprise Windows Antivirus Client with Premium GUI and Background Services.

Usage:
    python main.py           # Launch GUI
    python main.py --cli     # Launch CLI mode
    python main.py --daemon  # Background service mode (no GUI)
    python main.py --help    # Show help
"""
import os
import sys
import argparse
import threading
import time
from pathlib import Path

# Ensure modules are importable
sys.path.insert(0, str(Path(__file__).parent))

from config.settings import config
from modules.systemtools.system_tools import SystemTools


def setup_services():
    """Initialize all background services."""
    from modules.engine.signature_engine import SignatureEngine
    from modules.engine.realtime_guard import RealtimeGuard
    from modules.quarantine.quarantine_manager import QuarantineManager
    from modules.cloudsync.backup_manager import BackupManager
    from modules.admin_listener.command_listener import AdminCommandListener
    from modules.engine.force_delete import ForceDeleteEngine
    
    # Core engine
    engine = SignatureEngine()
    
    # Quarantine
    quarantine = QuarantineManager()
    
    # Real-time guard
    guard = RealtimeGuard(engine=engine)
    guard.register_quarantine_callback(
        lambda result: quarantine.quarantine(
            result.file_path,
            result.file_hash,
            result.threat_name,
            result.threat_type,
            result.severity,
            result.file_size
        )
    )
    
    # Backup manager
    backup = BackupManager()
    
    # Admin command listener
    listener = AdminCommandListener()
    force_delete = ForceDeleteEngine()
    
    # Register remote command handlers
    def handle_force_delete(target_path, parameters):
        if target_path:
            result = force_delete.force_delete(target_path)
            return f"Force delete: {result.success} | Killed: {result.processes_killed}"
        return "No target path specified"
    
    def handle_full_scan(target_path, parameters):
        paths = [target_path] if target_path else config.CRITICAL_PATHS
        threats = []
        for p in paths:
            if os.path.exists(p):
                threats.extend(engine.scan_directory(p))
        return f"Full scan complete. Threats found: {len(threats)}"
    
    def handle_quick_scan(target_path, parameters):
        paths = config.CRITICAL_PATHS[:2]
        threats = []
        for p in paths:
            if os.path.exists(p):
                threats.extend(engine.scan_directory(p))
        return f"Quick scan complete. Threats found: {len(threats)}"
    
    def handle_update_signatures(target_path, parameters):
        return "Signature update initiated (stub implementation)"
    
    def handle_shutdown_guard(target_path, parameters):
        config.GUARD_ENABLED = False
        config.save_config()
        guard.stop()
        return "Real-time guard disabled by admin command"
    
    def handle_enable_guard(target_path, parameters):
        config.GUARD_ENABLED = True
        config.save_config()
        guard.start()
        return "Real-time guard enabled by admin command"
    
    def handle_reboot(target_path, parameters):
        if sys.platform == "win32":
            os.system("shutdown /r /t 60 /c \"Reboot initiated by Believoo Shield Pro admin\"")
            return "System reboot scheduled in 60 seconds"
        return "Reboot command not supported on this platform"
    
    def handle_isolate(target_path, parameters):
        return "Network isolation mode activated (stub implementation)"
    
    listener.register_handler("force_delete", handle_force_delete)
    listener.register_handler("full_scan", handle_full_scan)
    listener.register_handler("quick_scan", handle_quick_scan)
    listener.register_handler("update_signatures", handle_update_signatures)
    listener.register_handler("shutdown_guard", handle_shutdown_guard)
    listener.register_handler("enable_guard", handle_enable_guard)
    listener.register_handler("reboot", handle_reboot)
    listener.register_handler("isolate", handle_isolate)
    
    return {
        "engine": engine,
        "guard": guard,
        "quarantine": quarantine,
        "backup": backup,
        "listener": listener,
        "force_delete": force_delete
    }


def heartbeat_loop(stop_event, services):
    """Background heartbeat loop."""
    import requests
    
    while not stop_event.is_set():
        try:
            headers = config.get_headers()
            payload = {
                "hwid": config.hwid,
                "device_name": config.device_name,
                "health_score": config.health_score,
                "real_time_guard_enabled": config.GUARD_ENABLED,
                "threats_found": config.threats_found,
                "os_version": f"{sys.platform}",
                "client_version": config.APP_VERSION,
                "last_scan_date": config.last_scan_date.isoformat() if config.last_scan_date else None
            }
            
            response = requests.post(
                f"{config.API_BASE_URL}/heartbeat",
                json=payload,
                headers=headers,
                timeout=20
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get("commands_pending", 0) > 0:
                    print(f"[HEARTBEAT] {data['commands_pending']} remote commands pending")
        except Exception as e:
            print(f"[HEARTBEAT] Error: {e}")
        
        stop_event.wait(config.HEARTBEAT_INTERVAL)


def run_gui(services):
    """Launch the premium GUI."""
    from modules.ui.splash_screen import SplashScreen
    from modules.ui.dashboard import Dashboard
    
    def on_activated():
        # After splash screen, launch dashboard
        dashboard = Dashboard(**services)
        dashboard.run()
    
    # Check if already activated
    if config.license_key:
        on_activated()
    else:
        splash = SplashScreen(on_activation=on_activated)
        splash.show()


def run_cli(services):
    """Run in CLI mode."""
    print("=" * 60)
    print(f"  {config.APP_NAME} v{config.APP_VERSION}")
    print("  Enterprise Console Interface")
    print("=" * 60)
    print("\nCommands:")
    print("  scan [path]    - Scan directory")
    print("  guard          - Toggle real-time guard")
    print("  quarantine     - List quarantined files")
    print("  backup         - Create backup")
    print("  status         - Show system status")
    print("  exit           - Quit")
    print()
    
    engine = services["engine"]
    guard = services["guard"]
    quarantine = services["quarantine"]
    backup = services["backup"]
    
    while True:
        try:
            cmd = input("ShieldPro> ").strip()
            if not cmd:
                continue
            
            parts = cmd.split()
            action = parts[0].lower()
            args = parts[1:]
            
            if action == "exit" or action == "quit":
                break
            
            elif action == "scan":
                path = args[0] if args else str(Path.home())
                print(f"Scanning {path}...")
                results = engine.scan_directory(path, lambda p, c, t: print(f"  {c}/{t} {p[:60]}...", end="\r"))
                print(f"\nScan complete. Threats found: {len(results)}")
                for r in results:
                    print(f"  [!] {r.threat_name} | {r.file_path}")
            
            elif action == "guard":
                if guard.is_running:
                    guard.stop()
                    print("Real-time guard stopped.")
                else:
                    guard.start()
                    print("Real-time guard started.")
            
            elif action == "quarantine":
                records = quarantine.get_records()
                if not records:
                    print("No quarantined files.")
                else:
                    print(f"Quarantined files: {len(records)}")
                    for i, r in enumerate(records):
                        print(f"  [{i}] {r.threat_name} | {r.severity}")
            
            elif action == "backup":
                job_id = backup.create_backup()
                print(f"Backup job started: {job_id}")
            
            elif action == "status":
                stats = engine.get_stats()
                print(f"Engine signatures: {stats['local_signatures']}")
                print(f"Cache hit rate: {stats['cache_hit_rate']:.1f}%")
                print(f"Guard running: {guard.is_running}")
                print(f"License: {config.license_key or 'None'}")
            
            else:
                print(f"Unknown command: {action}")
        
        except KeyboardInterrupt:
            break
        except Exception as e:
            print(f"Error: {e}")
    
    print("\nShutting down...")


def run_daemon(services):
    """Run in background daemon mode (no GUI)."""
    print(f"[{config.APP_NAME}] Starting daemon mode...")
    
    # Start services
    if config.GUARD_ENABLED:
        services["guard"].start()
        print("[DAEMON] Real-time guard started")
    
    services["listener"].start()
    print("[DAEMON] Admin command listener started")
    
    # Start heartbeat
    stop_event = threading.Event()
    hb_thread = threading.Thread(target=heartbeat_loop, args=(stop_event, services), daemon=True)
    hb_thread.start()
    print("[DAEMON] Heartbeat started")
    
    print(f"[{config.APP_NAME}] Daemon running. Press Ctrl+C to stop.")
    
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        print("\n[DAEMON] Shutting down...")
        stop_event.set()
        services["guard"].stop()
        services["listener"].stop()
        hb_thread.join(timeout=5)


def main():
    """Main entry point."""
    parser = argparse.ArgumentParser(description=config.APP_NAME)
    parser.add_argument("--cli", action="store_true", help="Run in CLI mode")
    parser.add_argument("--daemon", action="store_true", help="Run as background daemon")
    parser.add_argument("--no-gui", action="store_true", help="Disable GUI (same as --cli)")
    parser.add_argument("--version", action="version", version=f"%(prog)s {config.APP_VERSION}")
    args = parser.parse_args()
    
    # Ensure HWID and device info
    sys_tools = SystemTools()
    sys_tools.ensure_hwid()
    sys_tools.get_device_name()
    
    # Setup all services
    services = setup_services()
    
    # Run mode selection
    if args.daemon:
        run_daemon(services)
    elif args.cli or args.no_gui:
        run_cli(services)
    else:
        run_gui(services)


if __name__ == "__main__":
    main()
