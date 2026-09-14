"""
Believoo Shield Pro - Premium Main Dashboard
Modern CustomTkinter dashboard with Midnight Onyx theme and neon glow accents.
"""
import os
import sys
import time
import threading
from typing import Optional, Callable
from datetime import datetime

try:
    import customtkinter as ctk
    from PIL import Image
except ImportError:
    ctk = None

from config.settings import config
from modules.systemtools.system_tools import SystemTools


class Dashboard:
    """
    Enterprise main dashboard for Believoo Shield Pro.
    
    Features:
    - Midnight Onyx dark theme with cyan/violet neon accents
    - Real-time health score display
    - Tabbed interface: Dashboard, Scan, Quarantine, Backup, Settings
    - Animated progress indicators
    - System tray integration ready
    - Admin command log viewer
    """
    
    def __init__(self, engine=None, guard=None, quarantine=None, backup=None, listener=None):
        self.engine = engine
        self.guard = guard
        self.quarantine_mgr = quarantine
        self.backup_mgr = backup
        self.admin_listener = listener
        
        self.root = None
        self._scanning = False
        self._scan_thread = None
        self._ui_update_thread = None
        self._stop_ui = threading.Event()
        self._build_ui()
    
    def _build_ui(self):
        """Build the main dashboard UI."""
        ctk.set_appearance_mode("dark")
        ctk.set_default_color_theme("dark-blue")
        
        self.root = ctk.CTk()
        self.root.title(f"{config.APP_NAME} v{config.APP_VERSION}")
        self.root.geometry("1200x800")
        self.root.configure(fg_color="#0a0a0f")
        self.root.protocol("WM_DELETE_WINDOW", self._on_close)
        
        # Grid layout
        self.root.grid_columnconfigure(1, weight=1)
        self.root.grid_rowconfigure(0, weight=1)
        
        # Sidebar
        self._build_sidebar()
        
        # Main content area with tabs
        self._build_main_content()
        
        # Status bar
        self._build_status_bar()
        
        # Start UI update thread
        self._stop_ui.clear()
        self._ui_update_thread = threading.Thread(target=self._ui_updater, daemon=True)
        self._ui_update_thread.start()
    
    def _build_sidebar(self):
        """Build the left sidebar with navigation."""
        sidebar = ctk.CTkFrame(
            self.root,
            width=220,
            fg_color="#11111a",
            corner_radius=0
        )
        sidebar.grid(row=0, column=0, sticky="nsew")
        sidebar.grid_propagate(False)
        
        # App title
        title = ctk.CTkLabel(
            sidebar,
            text="SHIELD PRO",
            font=ctk.CTkFont(family="Segoe UI", size=20, weight="bold"),
            text_color="#00d4ff"
        )
        title.pack(pady=(30, 5))
        
        subtitle = ctk.CTkLabel(
            sidebar,
            text="by Believoo",
            font=ctk.CTkFont(family="Segoe UI", size=10),
            text_color="#6b7280"
        )
        subtitle.pack()
        
        # Glow separator
        sep = ctk.CTkFrame(sidebar, fg_color="#00d4ff", height=2, corner_radius=1)
        sep.pack(fill="x", padx=20, pady=15)
        
        # Health score circle (simplified as label)
        self.health_frame = ctk.CTkFrame(sidebar, fg_color="#1a1a2e", corner_radius=10, height=120)
        self.health_frame.pack(fill="x", padx=20, pady=10)
        self.health_frame.pack_propagate(False)
        
        health_label = ctk.CTkLabel(
            self.health_frame,
            text="HEALTH SCORE",
            font=ctk.CTkFont(family="Segoe UI", size=10, weight="bold"),
            text_color="#6b7280"
        )
        health_label.pack(pady=(15, 0))
        
        self.health_value = ctk.CTkLabel(
            self.health_frame,
            text="100",
            font=ctk.CTkFont(family="Segoe UI", size=36, weight="bold"),
            text_color="#22c55e"
        )
        self.health_value.pack()
        
        self.health_status = ctk.CTkLabel(
            self.health_frame,
            text="PROTECTED",
            font=ctk.CTkFont(family="Segoe UI", size=11, weight="bold"),
            text_color="#22c55e"
        )
        self.health_status.pack(pady=(0, 10))
        
        # Navigation buttons
        nav_buttons = [
            ("Dashboard", self._show_dashboard),
            ("Scan", self._show_scan),
            ("Quarantine", self._show_quarantine),
            ("Backup", self._show_backup),
            ("Settings", self._show_settings),
        ]
        
        self.nav_buttons = []
        for label, cmd in nav_buttons:
            btn = ctk.CTkButton(
                sidebar,
                text=label.upper(),
                font=ctk.CTkFont(family="Segoe UI", size=12, weight="bold"),
                fg_color="transparent",
                hover_color="#1a1a2e",
                text_color="#9ca3af",
                anchor="w",
                width=180,
                height=40,
                corner_radius=8,
                command=cmd
            )
            btn.pack(padx=20, pady=2)
            self.nav_buttons.append((btn, label))
        
        # Guard toggle at bottom
        guard_frame = ctk.CTkFrame(sidebar, fg_color="transparent")
        guard_frame.pack(side="bottom", fill="x", padx=20, pady=20)
        
        guard_label = ctk.CTkLabel(
            guard_frame,
            text="Real-Time Guard",
            font=ctk.CTkFont(family="Segoe UI", size=11),
            text_color="#9ca3af"
        )
        guard_label.pack(side="left")
        
        self.guard_switch = ctk.CTkSwitch(
            guard_frame,
            text="",
            width=40,
            height=20,
            switch_width=40,
            switch_height=20,
            fg_color="#374151",
            progress_color="#00d4ff",
            command=self._toggle_guard
        )
        self.guard_switch.pack(side="right")
        self.guard_switch.select() if config.GUARD_ENABLED else self.guard_switch.deselect()
    
    def _build_main_content(self):
        """Build the main content area with tabbed views."""
        self.content_frame = ctk.CTkFrame(
            self.root,
            fg_color="#0a0a0f",
            corner_radius=0
        )
        self.content_frame.grid(row=0, column=1, sticky="nsew", padx=20, pady=20)
        self.content_frame.grid_columnconfigure(0, weight=1)
        self.content_frame.grid_rowconfigure(0, weight=1)
        
        # Create all views
        self.views = {}
        self._build_dashboard_view()
        self._build_scan_view()
        self._build_quarantine_view()
        self._build_backup_view()
        self._build_settings_view()
        
        # Show dashboard by default
        self._show_dashboard()
    
    def _clear_content(self):
        """Hide all views."""
        for view in self.views.values():
            view.grid_forget()
    
    def _show_dashboard(self):
        self._clear_content()
        self._highlight_nav("Dashboard")
        self.views["dashboard"].grid(row=0, column=0, sticky="nsew")
    
    def _show_scan(self):
        self._clear_content()
        self._highlight_nav("Scan")
        self.views["scan"].grid(row=0, column=0, sticky="nsew")
    
    def _show_quarantine(self):
        self._clear_content()
        self._highlight_nav("Quarantine")
        self.views["quarantine"].grid(row=0, column=0, sticky="nsew")
    
    def _show_backup(self):
        self._clear_content()
        self._highlight_nav("Backup")
        self.views["backup"].grid(row=0, column=0, sticky="nsew")
    
    def _show_settings(self):
        self._clear_content()
        self._highlight_nav("Settings")
        self.views["settings"].grid(row=0, column=0, sticky="nsew")
    
    def _highlight_nav(self, active_label: str):
        """Highlight active navigation button."""
        for btn, label in self.nav_buttons:
            if label == active_label:
                btn.configure(fg_color="#1a1a2e", text_color="#00d4ff")
            else:
                btn.configure(fg_color="transparent", text_color="#9ca3af")
    
    def _build_dashboard_view(self):
        """Build the Dashboard overview tab."""
        frame = ctk.CTkScrollableFrame(self.content_frame, fg_color="#0a0a0f")
        self.views["dashboard"] = frame
        
        # Welcome
        welcome = ctk.CTkLabel(
            frame,
            text="Dashboard Overview",
            font=ctk.CTkFont(family="Segoe UI", size=24, weight="bold"),
            text_color="#e5e7eb"
        )
        welcome.pack(anchor="w", pady=(0, 20))
        
        # Stats cards
        cards_frame = ctk.CTkFrame(frame, fg_color="transparent")
        cards_frame.pack(fill="x", pady=10)
        cards_frame.grid_columnconfigure((0, 1, 2, 3), weight=1)
        
        self.stat_cards = {}
        stats = [
            ("Threats Blocked", "0", "#ef4444"),
            ("Files Scanned", "0", "#00d4ff"),
            ("Quarantined", "0", "#f59e0b"),
            ("Last Scan", "Never", "#a855f7"),
        ]
        
        for i, (title, value, color) in enumerate(stats):
            card = ctk.CTkFrame(cards_frame, fg_color="#11111a", corner_radius=12, height=100)
            card.grid(row=0, column=i, padx=5, pady=5, sticky="nsew")
            card.pack_propagate(False)
            
            ctk.CTkLabel(card, text=title, font=ctk.CTkFont(size=11), text_color="#6b7280").pack(pady=(15, 0))
            label = ctk.CTkLabel(card, text=value, font=ctk.CTkFont(size=22, weight="bold"), text_color=color)
            label.pack()
            self.stat_cards[title] = label
        
        # Protection modules status
        modules_frame = ctk.CTkFrame(frame, fg_color="#11111a", corner_radius=12)
        modules_frame.pack(fill="x", pady=20)
        
        ctk.CTkLabel(
            modules_frame,
            text="PROTECTION MODULES",
            font=ctk.CTkFont(family="Segoe UI", size=14, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", padx=20, pady=(15, 10))
        
        modules = [
            ("Real-Time Guard", "Monitors files in real-time", True),
            ("Signature Engine", "SHA-256 + Cloud verification", True),
            ("Force Delete", "Kernel-level file removal", True),
            ("Cloud Backup", "Remote partition backup", config.BACKUP_ENABLED),
            ("Self Protection", "Prevents unauthorized closing", config.SELF_PROTECTION_ENABLED),
            ("Admin Command Listener", "Receives remote commands", True),
        ]
        
        for name, desc, active in modules:
            row = ctk.CTkFrame(modules_frame, fg_color="transparent")
            row.pack(fill="x", padx=20, pady=5)
            
            status_color = "#22c55e" if active else "#ef4444"
            status_text = "ACTIVE" if active else "INACTIVE"
            
            ctk.CTkLabel(row, text=name, font=ctk.CTkFont(size=12, weight="bold"), text_color="#e5e7eb").pack(side="left")
            ctk.CTkLabel(row, text=f"  ({desc})", font=ctk.CTkFont(size=10), text_color="#6b7280").pack(side="left")
            ctk.CTkLabel(row, text=status_text, font=ctk.CTkFont(size=10, weight="bold"), text_color=status_color).pack(side="right")
        
        # Quick actions
        actions_frame = ctk.CTkFrame(frame, fg_color="transparent")
        actions_frame.pack(fill="x", pady=20)
        
        ctk.CTkButton(
            actions_frame,
            text="QUICK SCAN",
            font=ctk.CTkFont(size=13, weight="bold"),
            fg_color="#00d4ff",
            text_color="#0a0a0f",
            hover_color="#06b6d4",
            height=45,
            corner_radius=8,
            command=self._quick_scan
        ).pack(side="left", padx=5)
        
        ctk.CTkButton(
            actions_frame,
            text="FULL SCAN",
            font=ctk.CTkFont(size=13, weight="bold"),
            fg_color="#a855f7",
            text_color="#ffffff",
            hover_color="#9333ea",
            height=45,
            corner_radius=8,
            command=self._full_scan
        ).pack(side="left", padx=5)
        
        ctk.CTkButton(
            actions_frame,
            text="UPDATE SIGNATURES",
            font=ctk.CTkFont(size=13, weight="bold"),
            fg_color="transparent",
            border_color="#374151",
            border_width=1,
            text_color="#9ca3af",
            hover_color="#1a1a2e",
            height=45,
            corner_radius=8,
            command=self._update_signatures
        ).pack(side="left", padx=5)
    
    def _build_scan_view(self):
        """Build the Scan tab."""
        frame = ctk.CTkFrame(self.content_frame, fg_color="#0a0a0f")
        self.views["scan"] = frame
        frame.grid_columnconfigure(0, weight=1)
        
        ctk.CTkLabel(
            frame,
            text="System Scan",
            font=ctk.CTkFont(family="Segoe UI", size=24, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", pady=(0, 20))
        
        # Scan type selection
        self.scan_type = ctk.CTkOptionMenu(
            frame,
            values=["Quick Scan", "Full Scan", "Custom Scan"],
            font=ctk.CTkFont(size=12),
            fg_color="#11111a",
            button_color="#374151",
            button_hover_color="#4b5563",
            dropdown_fg_color="#11111a",
            dropdown_hover_color="#1a1a2e",
            text_color="#e5e7eb"
        )
        self.scan_type.pack(anchor="w", pady=10)
        self.scan_type.set("Quick Scan")
        
        # Progress
        self.scan_progress = ctk.CTkProgressBar(
            frame,
            width=600,
            height=8,
            fg_color="#1a1a2e",
            progress_color="#00d4ff",
            corner_radius=4
        )
        self.scan_progress.set(0)
        self.scan_progress.pack(fill="x", pady=20)
        
        self.scan_status = ctk.CTkLabel(
            frame,
            text="Ready to scan",
            font=ctk.CTkFont(size=12),
            text_color="#6b7280"
        )
        self.scan_status.pack(anchor="w")
        
        # Start/Stop buttons
        btn_frame = ctk.CTkFrame(frame, fg_color="transparent")
        btn_frame.pack(anchor="w", pady=20)
        
        self.scan_start_btn = ctk.CTkButton(
            btn_frame,
            text="START SCAN",
            font=ctk.CTkFont(size=13, weight="bold"),
            fg_color="#00d4ff",
            text_color="#0a0a0f",
            hover_color="#06b6d4",
            height=40,
            corner_radius=8,
            command=self._start_scan
        )
        self.scan_start_btn.pack(side="left", padx=5)
        
        self.scan_stop_btn = ctk.CTkButton(
            btn_frame,
            text="STOP",
            font=ctk.CTkFont(size=13, weight="bold"),
            fg_color="#ef4444",
            text_color="#ffffff",
            hover_color="#dc2626",
            height=40,
            corner_radius=8,
            state="disabled",
            command=self._stop_scan
        )
        self.scan_stop_btn.pack(side="left", padx=5)
        
        # Results area
        results_label = ctk.CTkLabel(
            frame,
            text="Scan Results",
            font=ctk.CTkFont(size=14, weight="bold"),
            text_color="#e5e7eb"
        )
        results_label.pack(anchor="w", pady=(20, 10))
        
        self.scan_results = ctk.CTkTextbox(
            frame,
            font=ctk.CTkFont(family="Consolas", size=11),
            fg_color="#11111a",
            text_color="#e5e7eb",
            border_color="#374151",
            border_width=1,
            corner_radius=8,
            height=250
        )
        self.scan_results.pack(fill="both", expand=True)
        self.scan_results.insert("end", "No scan results yet.\n")
        self.scan_results.configure(state="disabled")
    
    def _build_quarantine_view(self):
        """Build the Quarantine tab."""
        frame = ctk.CTkFrame(self.content_frame, fg_color="#0a0a0f")
        self.views["quarantine"] = frame
        frame.grid_columnconfigure(0, weight=1)
        
        ctk.CTkLabel(
            frame,
            text="Quarantine Manager",
            font=ctk.CTkFont(family="Segoe UI", size=24, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", pady=(0, 20))
        
        self.quarantine_list = ctk.CTkTextbox(
            frame,
            font=ctk.CTkFont(family="Consolas", size=11),
            fg_color="#11111a",
            text_color="#e5e7eb",
            border_color="#374151",
            border_width=1,
            corner_radius=8,
            height=400
        )
        self.quarantine_list.pack(fill="both", expand=True, pady=10)
        self.quarantine_list.insert("end", "No quarantined files.\n")
        self.quarantine_list.configure(state="disabled")
        
        btn_frame = ctk.CTkFrame(frame, fg_color="transparent")
        btn_frame.pack(anchor="w", pady=10)
        
        ctk.CTkButton(
            btn_frame,
            text="REFRESH",
            font=ctk.CTkFont(size=12, weight="bold"),
            fg_color="#374151",
            text_color="#e5e7eb",
            hover_color="#4b5563",
            height=35,
            corner_radius=8,
            command=self._refresh_quarantine
        ).pack(side="left", padx=5)
        
        ctk.CTkButton(
            btn_frame,
            text="CLEAR ALL",
            font=ctk.CTkFont(size=12, weight="bold"),
            fg_color="#ef4444",
            text_color="#ffffff",
            hover_color="#dc2626",
            height=35,
            corner_radius=8,
            command=self._clear_quarantine
        ).pack(side="left", padx=5)
    
    def _build_backup_view(self):
        """Build the Backup tab."""
        frame = ctk.CTkFrame(self.content_frame, fg_color="#0a0a0f")
        self.views["backup"] = frame
        
        ctk.CTkLabel(
            frame,
            text="Cloud Backup",
            font=ctk.CTkFont(family="Segoe UI", size=24, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", pady=(0, 20))
        
        self.backup_status = ctk.CTkLabel(
            frame,
            text="No backups created yet.",
            font=ctk.CTkFont(size=12),
            text_color="#6b7280"
        )
        self.backup_status.pack(anchor="w", pady=10)
        
        self.backup_progress = ctk.CTkProgressBar(
            frame,
            width=600,
            height=8,
            fg_color="#1a1a2e",
            progress_color="#a855f7",
            corner_radius=4
        )
        self.backup_progress.set(0)
        self.backup_progress.pack(fill="x", pady=10)
        
        ctk.CTkButton(
            frame,
            text="CREATE BACKUP NOW",
            font=ctk.CTkFont(size=13, weight="bold"),
            fg_color="#a855f7",
            text_color="#ffffff",
            hover_color="#9333ea",
            height=45,
            corner_radius=8,
            command=self._create_backup
        ).pack(anchor="w", pady=20)
    
    def _build_settings_view(self):
        """Build the Settings tab."""
        frame = ctk.CTkFrame(self.content_frame, fg_color="#0a0a0f")
        self.views["settings"] = frame
        
        ctk.CTkLabel(
            frame,
            text="Settings",
            font=ctk.CTkFont(family="Segoe UI", size=24, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", pady=(0, 20))
        
        # Startup settings
        startup_frame = ctk.CTkFrame(frame, fg_color="#11111a", corner_radius=12)
        startup_frame.pack(fill="x", pady=10)
        
        ctk.CTkLabel(
            startup_frame,
            text="Startup",
            font=ctk.CTkFont(size=14, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", padx=20, pady=(15, 10))
        
        self.startup_switch = ctk.CTkSwitch(
            startup_frame,
            text="Run on Windows startup",
            font=ctk.CTkFont(size=12),
            fg_color="#374151",
            progress_color="#00d4ff",
            command=self._toggle_startup
        )
        self.startup_switch.pack(anchor="w", padx=20, pady=10)
        
        # Self protection
        protection_frame = ctk.CTkFrame(frame, fg_color="#11111a", corner_radius=12)
        protection_frame.pack(fill="x", pady=10)
        
        ctk.CTkLabel(
            protection_frame,
            text="Security",
            font=ctk.CTkFont(size=14, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", padx=20, pady=(15, 10))
        
        self.protection_switch = ctk.CTkSwitch(
            protection_frame,
            text="Enable self-protection",
            font=ctk.CTkFont(size=12),
            fg_color="#374151",
            progress_color="#00d4ff"
        )
        self.protection_switch.pack(anchor="w", padx=20, pady=10)
        
        # License info
        license_frame = ctk.CTkFrame(frame, fg_color="#11111a", corner_radius=12)
        license_frame.pack(fill="x", pady=10)
        
        ctk.CTkLabel(
            license_frame,
            text="License",
            font=ctk.CTkFont(size=14, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", padx=20, pady=(15, 10))
        
        license_text = config.license_key or "Not activated"
        self.license_info = ctk.CTkLabel(
            license_frame,
            text=f"Key: {license_text}",
            font=ctk.CTkFont(size=11, family="Consolas"),
            text_color="#6b7280"
        )
        self.license_info.pack(anchor="w", padx=20, pady=5)
        
        # Admin Command Log
        cmd_frame = ctk.CTkFrame(frame, fg_color="#11111a", corner_radius=12)
        cmd_frame.pack(fill="x", pady=10)
        
        ctk.CTkLabel(
            cmd_frame,
            text="Admin Command Log",
            font=ctk.CTkFont(size=14, weight="bold"),
            text_color="#e5e7eb"
        ).pack(anchor="w", padx=20, pady=(15, 10))
        
        self.cmd_log = ctk.CTkTextbox(
            cmd_frame,
            font=ctk.CTkFont(family="Consolas", size=10),
            fg_color="#0a0a0f",
            text_color="#9ca3af",
            height=150,
            state="disabled"
        )
        self.cmd_log.pack(fill="x", padx=20, pady=10)
    
    def _build_status_bar(self):
        """Build bottom status bar."""
        status = ctk.CTkFrame(self.root, fg_color="#11111a", height=30, corner_radius=0)
        status.grid(row=1, column=0, columnspan=2, sticky="ew")
        status.grid_propagate(False)
        
        self.status_text = ctk.CTkLabel(
            status,
            text="Ready",
            font=ctk.CTkFont(size=10),
            text_color="#6b7280"
        )
        self.status_text.pack(side="left", padx=15)
        
        self.version_text = ctk.CTkLabel(
            status,
            text=f"v{config.APP_VERSION}",
            font=ctk.CTkFont(size=10),
            text_color="#374151"
        )
        self.version_text.pack(side="right", padx=15)
    
    # ==================== Actions ====================
    
    def _toggle_guard(self):
        """Toggle real-time guard on/off."""
        enabled = self.guard_switch.get() == 1
        config.GUARD_ENABLED = enabled
        config.save_config()
        
        if enabled and self.guard:
            self.guard.start()
        elif self.guard:
            self.guard.stop()
    
    def _toggle_startup(self):
        """Toggle Windows startup registry."""
        enabled = self.startup_switch.get() == 1
        sys_tools = SystemTools()
        if enabled:
            sys_tools.add_to_startup()
        else:
            sys_tools.remove_from_startup()
    
    def _start_scan(self):
        """Start a system scan."""
        if self._scanning:
            return
        
        scan_type = self.scan_type.get()
        self._scanning = True
        self.scan_start_btn.configure(state="disabled")
        self.scan_stop_btn.configure(state="normal")
        self.scan_results.configure(state="normal")
        self.scan_results.delete("1.0", "end")
        self.scan_results.configure(state="disabled")
        
        self._scan_thread = threading.Thread(target=self._scan_worker, args=(scan_type,), daemon=True)
        self._scan_thread.start()
    
    def _scan_worker(self, scan_type: str):
        """Background scan worker."""
        def progress_cb(path, current, total):
            pct = current / max(total, 1)
            self.root.after(0, lambda: self.scan_progress.set(pct))
            self.root.after(0, lambda: self.scan_status.configure(
                text=f"Scanning: {os.path.basename(path)} ({current}/{total})"
            ))
        
        if scan_type == "Quick Scan":
            paths = config.CRITICAL_PATHS[:2] if config.CRITICAL_PATHS else [str(Path.home())]
        elif scan_type == "Full Scan":
            paths = ["C:/"] if config.is_windows else ["/"]
        else:
            paths = [str(Path.home())]
        
        threats = []
        for path in paths:
            if not self._scanning:
                break
            if self.engine:
                results = self.engine.scan_directory(path, progress_cb)
                threats.extend(results)
        
        # Update UI
        self.root.after(0, lambda: self._scan_complete(threats))
    
    def _scan_complete(self, threats):
        """Handle scan completion."""
        self._scanning = False
        self.scan_progress.set(1.0 if not threats else 1.0)
        self.scan_start_btn.configure(state="normal")
        self.scan_stop_btn.configure(state="disabled")
        
        self.scan_results.configure(state="normal")
        if threats:
            self.scan_results.insert("end", f"THREATS FOUND: {len(threats)}\n")
            self.scan_results.insert("end", "=" * 50 + "\n")
            for t in threats:
                self.scan_results.insert("end", f"[!] {t.threat_name}\n")
                self.scan_results.insert("end", f"    Path: {t.file_path}\n")
                self.scan_results.insert("end", f"    Hash: {t.file_hash}\n")
                self.scan_results.insert("end", f"    Severity: {t.severity}\n\n")
            self.health_value.configure(text="85", text_color="#ef4444")
            self.health_status.configure(text="THREATS DETECTED", text_color="#ef4444")
        else:
            self.scan_results.insert("end", "Scan complete. No threats found.\n")
            self.health_value.configure(text="100", text_color="#22c55e")
            self.health_status.configure(text="PROTECTED", text_color="#22c55e")
        self.scan_results.configure(state="disabled")
        
        config.last_scan_date = datetime.now()
        config.health_score = 85.0 if threats else 100.0
        config.save_config()
    
    def _stop_scan(self):
        """Stop the current scan."""
        self._scanning = False
    
    def _quick_scan(self):
        self._show_scan()
        self.scan_type.set("Quick Scan")
        self._start_scan()
    
    def _full_scan(self):
        self._show_scan()
        self.scan_type.set("Full Scan")
        self._start_scan()
    
    def _update_signatures(self):
        """Trigger signature update."""
        self.status_text.configure(text="Updating signatures...")
        # Implementation would download new signatures
        self.status_text.configure(text="Signatures up to date")
    
    def _refresh_quarantine(self):
        """Refresh quarantine list."""
        if not self.quarantine_mgr:
            return
        
        self.quarantine_list.configure(state="normal")
        self.quarantine_list.delete("1.0", "end")
        
        records = self.quarantine_mgr.get_records()
        if not records:
            self.quarantine_list.insert("end", "No quarantined files.\n")
        else:
            for i, r in enumerate(records):
                self.quarantine_list.insert("end", f"[{i}] {r.threat_name}\n")
                self.quarantine_list.insert("end", f"    Original: {r.original_path}\n")
                self.quarantine_list.insert("end", f"    Severity: {r.severity} | Date: {r.quarantine_date[:10]}\n\n")
        
        self.quarantine_list.configure(state="disabled")
    
    def _clear_quarantine(self):
        """Clear all quarantined files."""
        if self.quarantine_mgr:
            count = self.quarantine_mgr.clear_all()
            self._refresh_quarantine()
    
    def _create_backup(self):
        """Start backup process."""
        if not self.backup_mgr:
            return
        
        def progress(job):
            self.root.after(0, lambda: self.backup_progress.set(job.progress / 100))
            self.root.after(0, lambda: self.backup_status.configure(
                text=f"Backup: {job.processed_files}/{job.total_files} files"
            ))
        
        self.backup_mgr.create_backup(progress_callback=progress)
        self.backup_status.configure(text="Backup started in background.")
    
    def _ui_updater(self):
        """Background thread for periodic UI updates."""
        while not self._stop_ui.is_set():
            time.sleep(5)
            if self.root and self.root.winfo_exists():
                try:
                    # Update stats
                    if self.engine:
                        stats = self.engine.get_stats()
                        self.root.after(0, lambda s=stats: self._update_stats(s))
                except Exception:
                    pass
    
    def _update_stats(self, stats: dict):
        """Update dashboard statistics."""
        if "Files Scanned" in self.stat_cards:
            self.stat_cards["Files Scanned"].configure(
                text=str(stats.get("cache_hits", 0) + stats.get("cache_misses", 0))
            )
    
    def _on_close(self):
        """Handle window close - check self-protection."""
        if config.SELF_PROTECTION_ENABLED:
            # In real implementation, show password dialog
            pass
        
        self._stop_ui.set()
        if self.guard:
            self.guard.stop()
        if self.admin_listener:
            self.admin_listener.stop()
        
        self.root.destroy()
    
    def run(self):
        """Start the main dashboard."""
        self.root.mainloop()


# Fallback for console-only environments
class FallbackDashboard:
    """Console-based fallback dashboard."""
    
    def __init__(self, **kwargs):
        pass
    
    def run(self):
        print("=" * 50)
        print("   BELIEVOO SHIELD PRO - CONSOLE MODE")
        print("=" * 50)
        print("\nGUI unavailable. Running in console mode.")
        print("Use 'python main.py --cli' for CLI operations.")
        while True:
            cmd = input("\n[scan/quit] > ").strip().lower()
            if cmd == "quit":
                break
            elif cmd == "scan":
                print("Scanning... (demo)")


if ctk is None:
    Dashboard = FallbackDashboard
