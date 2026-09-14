"""
Believoo Shield Pro - Premium Licensing Splash Screen
Professional first-run activation with Midnight Onyx theme and neon glow effects.
"""
import os
import platform
import sys
import threading
from typing import Optional, Callable

try:
    import customtkinter as ctk
    from PIL import Image, ImageDraw, ImageFilter
except ImportError:
    ctk = None

import requests
from config.settings import config
from modules.systemtools.system_tools import SystemTools


class SplashScreen:
    """
    Enterprise-grade activation splash screen.
    
    Features:
    - Acrylic blur background effect
    - Neon glow accents (cyan/violet)
    - Hardware ID display
    - Secure license validation
    - Animated loading states
    """
    
    def __init__(self, on_activation: Optional[Callable] = None):
        self.on_activation = on_activation
        self.root = None
        self._build_ui()
    
    def _build_ui(self):
        """Construct the splash screen UI."""
        ctk.set_appearance_mode("dark")
        ctk.set_default_color_theme("dark-blue")
        
        self.root = ctk.CTk()
        self.root.title(f"{config.APP_NAME} - Activation")
        self.root.geometry("700x500")
        self.root.resizable(False, False)
        self.root.configure(fg_color="#0a0a0f")
        
        # Center window
        self.root.update_idletasks()
        x = (self.root.winfo_screenwidth() // 2) - 350
        y = (self.root.winfo_screenheight() // 2) - 250
        self.root.geometry(f"+{x}+{y}")
        
        # Main container with border glow
        main_frame = ctk.CTkFrame(
            self.root,
            fg_color="#11111a",
            border_color="#00d4ff",
            border_width=1,
            corner_radius=15
        )
        main_frame.pack(padx=20, pady=20, fill="both", expand=True)
        
        # Logo/Title area
        title = ctk.CTkLabel(
            main_frame,
            text="BELIEVOO",
            font=ctk.CTkFont(family="Segoe UI", size=32, weight="bold"),
            text_color="#00d4ff"
        )
        title.pack(pady=(30, 0))
        
        subtitle = ctk.CTkLabel(
            main_frame,
            text="SHIELD PRO",
            font=ctk.CTkFont(family="Segoe UI", size=18, weight="bold"),
            text_color="#a855f7"
        )
        subtitle.pack()
        
        tagline = ctk.CTkLabel(
            main_frame,
            text="Enterprise-Grade Threat Protection",
            font=ctk.CTkFont(family="Segoe UI", size=12),
            text_color="#6b7280"
        )
        tagline.pack(pady=(0, 20))
        
        # Separator line with glow effect
        sep = ctk.CTkFrame(main_frame, fg_color="#00d4ff", height=2, corner_radius=1)
        sep.pack(fill="x", padx=60, pady=10)
        
        # Activation section
        act_label = ctk.CTkLabel(
            main_frame,
            text="Enter Your License Key",
            font=ctk.CTkFont(family="Segoe UI", size=14, weight="bold"),
            text_color="#e5e7eb"
        )
        act_label.pack(pady=(10, 5))
        
        self.license_entry = ctk.CTkEntry(
            main_frame,
            width=400,
            height=40,
            placeholder_text="XXXXX-XXXXX-XXXXX-XXXXX",
            font=ctk.CTkFont(family="Consolas", size=14),
            fg_color="#1a1a2e",
            border_color="#374151",
            border_width=1,
            text_color="#00d4ff"
        )
        self.license_entry.pack(pady=5)
        self.license_entry.bind("<KeyRelease>", self._format_license_key)
        
        # HWID Display
        sys_tools = SystemTools()
        hwid = sys_tools.ensure_hwid()
        
        hwid_frame = ctk.CTkFrame(main_frame, fg_color="transparent")
        hwid_frame.pack(pady=(10, 5))
        
        hwid_label = ctk.CTkLabel(
            hwid_frame,
            text="Hardware ID: ",
            font=ctk.CTkFont(family="Segoe UI", size=10),
            text_color="#6b7280"
        )
        hwid_label.pack(side="left")
        
        hwid_value = ctk.CTkLabel(
            hwid_frame,
            text=hwid[:20] + "...",
            font=ctk.CTkFont(family="Consolas", size=10),
            text_color="#a855f7"
        )
        hwid_value.pack(side="left")
        
        # Status message
        self.status_label = ctk.CTkLabel(
            main_frame,
            text="",
            font=ctk.CTkFont(family="Segoe UI", size=11),
            text_color="#ef4444"
        )
        self.status_label.pack(pady=5)
        
        # Progress bar (hidden initially)
        self.progress = ctk.CTkProgressBar(
            main_frame,
            width=400,
            height=6,
            fg_color="#1a1a2e",
            progress_color="#00d4ff",
            corner_radius=3
        )
        self.progress.set(0)
        self.progress.pack(pady=5)
        self.progress.pack_forget()
        
        # Buttons
        btn_frame = ctk.CTkFrame(main_frame, fg_color="transparent")
        btn_frame.pack(pady=(15, 20))
        
        self.activate_btn = ctk.CTkButton(
            btn_frame,
            text="ACTIVATE LICENSE",
            font=ctk.CTkFont(family="Segoe UI", size=13, weight="bold"),
            fg_color="#00d4ff",
            hover_color="#06b6d4",
            text_color="#0a0a0f",
            width=200,
            height=40,
            corner_radius=8,
            command=self._activate
        )
        self.activate_btn.pack(side="left", padx=5)
        
        trial_btn = ctk.CTkButton(
            btn_frame,
            text="START TRIAL",
            font=ctk.CTkFont(family="Segoe UI", size=13, weight="bold"),
            fg_color="transparent",
            hover_color="#1a1a2e",
            text_color="#6b7280",
            border_color="#374151",
            border_width=1,
            width=150,
            height=40,
            corner_radius=8,
            command=self._start_trial
        )
        trial_btn.pack(side="left", padx=5)
        
        # Footer
        footer = ctk.CTkLabel(
            main_frame,
            text="Powered by BelieVoo Hosting (B-Host)",
            font=ctk.CTkFont(family="Segoe UI", size=10),
            text_color="#374151"
        )
        footer.pack(side="bottom", pady=15)
    
    def _format_license_key(self, event):
        """Auto-format license key with hyphens."""
        value = self.license_entry.get().replace("-", "").upper()
        if len(value) > 20:
            value = value[:20]
        
        formatted = ""
        for i, char in enumerate(value):
            if i > 0 and i % 5 == 0:
                formatted += "-"
            formatted += char
        
        self.license_entry.delete(0, "end")
        self.license_entry.insert(0, formatted)
    
    def _set_loading(self, loading: bool):
        """Toggle loading state."""
        if loading:
            self.progress.pack(pady=5)
            self.progress.start()
            self.activate_btn.configure(state="disabled", text="VALIDATING...")
        else:
            self.progress.stop()
            self.progress.pack_forget()
            self.activate_btn.configure(state="normal", text="ACTIVATE LICENSE")
    
    def _activate(self):
        """Handle license activation."""
        license_key = self.license_entry.get().strip().upper()
        
        if not license_key or len(license_key.replace("-", "")) != 20:
            self.status_label.configure(text="Please enter a valid 20-character license key.", text_color="#ef4444")
            return
        
        self.status_label.configure(text="Connecting to Believoo servers...", text_color="#00d4ff")
        self._set_loading(True)
        
        # Run activation in background thread
        thread = threading.Thread(target=self._activation_worker, args=(license_key,), daemon=True)
        thread.start()
    
    def _activation_worker(self, license_key: str):
        """Background worker for license activation API call."""
        try:
            sys_tools = SystemTools()
            hwid = sys_tools.ensure_hwid()
            device_name = sys_tools.get_device_name()
            
            payload = {
                "license_key": license_key,
                "hwid": hwid,
                "device_name": device_name,
                "os_version": f"{platform.system()} {platform.version()}",
                "client_version": config.APP_VERSION
            }
            
            response = requests.post(
                f"{config.API_BASE_URL}/activate",
                json=payload,
                headers={"Content-Type": "application/json"},
                timeout=30
            )
            
            data = response.json()
            
            if data.get("success"):
                config.license_key = license_key
                config.license_token = data.get("token")
                config.save_config()
                
                self.root.after(0, lambda: self._activation_success(data))
            else:
                self.root.after(0, lambda: self._activation_failed(data.get("message", "Activation failed.")))
                
        except requests.RequestException:
            self.root.after(0, lambda: self._activation_failed("Cannot connect to Believoo servers. Check your internet connection."))
        except Exception as e:
            self.root.after(0, lambda: self._activation_failed(f"Error: {str(e)}"))
    
    def _activation_success(self, data: dict):
        """Handle successful activation."""
        self._set_loading(False)
        self.status_label.configure(
            text=f"Activation successful! Expires: {data.get('expiry_date', 'N/A')[:10]}",
            text_color="#22c55e"
        )
        self.root.after(1500, self._close_and_proceed)
    
    def _activation_failed(self, message: str):
        """Handle failed activation."""
        self._set_loading(False)
        self.status_label.configure(text=message, text_color="#ef4444")
    
    def _start_trial(self):
        """Start trial mode (limited functionality)."""
        self.status_label.configure(
            text="Trial mode active. Full features require license activation.",
            text_color="#f59e0b"
        )
        config.license_key = "TRIAL"
        config.save_config()
        self.root.after(2000, self._close_and_proceed)
    
    def _close_and_proceed(self):
        """Close splash and proceed to main application."""
        if self.on_activation:
            self.on_activation()
        self.root.destroy()
    
    def show(self):
        """Display the splash screen and block until closed."""
        self.root.mainloop()


# Fallback for when customtkinter is not available
class FallbackSplashScreen:
    """Console-based fallback activation."""
    
    def __init__(self, on_activation=None):
        self.on_activation = on_activation
    
    def show(self):
        print("=" * 50)
        print("   BELIEVOO SHIELD PRO - ACTIVATION")
        print("=" * 50)
        
        sys_tools = SystemTools()
        hwid = sys_tools.ensure_hwid()
        print(f"\nHardware ID: {hwid}")
        
        key = input("\nEnter License Key (XXXXX-XXXXX-XXXXX-XXXXX): ").strip().upper()
        
        if key and len(key.replace("-", "")) == 20:
            try:
                payload = {
                    "license_key": key,
                    "hwid": hwid,
                    "device_name": sys_tools.get_device_name(),
                    "os_version": f"{platform.system()} {platform.version()}",
                    "client_version": config.APP_VERSION
                }
                response = requests.post(
                    f"{config.API_BASE_URL}/activate",
                    json=payload,
                    headers={"Content-Type": "application/json"},
                    timeout=30
                )
                data = response.json()
                
                if data.get("success"):
                    config.license_key = key
                    config.license_token = data.get("token")
                    config.save_config()
                    print("\nActivation successful!")
                else:
                    print(f"\nActivation failed: {data.get('message')}")
            except Exception as e:
                print(f"\nError: {e}")
        
        if self.on_activation:
            self.on_activation()


# Choose implementation based on availability
if ctk is None:
    SplashScreen = FallbackSplashScreen
