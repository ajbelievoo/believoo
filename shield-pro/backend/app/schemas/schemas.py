"""
Believoo Shield Pro - Pydantic Schemas
Request/response validation schemas for all API endpoints.
"""
from datetime import datetime
from typing import Optional, List
from pydantic import BaseModel, Field, field_validator


# ==================== License Schemas ====================

class LicenseBase(BaseModel):
    license_key: str
    expiry_date: datetime
    max_devices: int = 1


class LicenseCreate(LicenseBase):
    pass


class LicenseResponse(BaseModel):
    id: str
    license_key: str
    hwid: Optional[str] = None
    device_name: Optional[str] = None
    expiry_date: datetime
    status: str
    max_devices: int
    created_at: datetime
    updated_at: datetime
    
    class Config:
        from_attributes = True


class LicenseActivateRequest(BaseModel):
    license_key: str = Field(..., min_length=23, max_length=23, pattern=r'^[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$')
    hwid: str = Field(..., min_length=10, max_length=128)
    device_name: str = Field(..., max_length=256)
    os_version: Optional[str] = None
    client_version: Optional[str] = None


class LicenseActivateResponse(BaseModel):
    success: bool
    message: str
    token: Optional[str] = None
    expiry_date: Optional[datetime] = None
    status: Optional[str] = None


# ==================== Client Schemas ====================

class HeartbeatRequest(BaseModel):
    hwid: str
    device_name: Optional[str] = None
    ip_address: Optional[str] = None
    health_score: float = Field(..., ge=0, le=100)
    real_time_guard_enabled: bool = True
    threats_found: int = 0
    os_version: Optional[str] = None
    client_version: Optional[str] = None
    last_scan_date: Optional[datetime] = None


class HeartbeatResponse(BaseModel):
    success: bool
    message: str
    server_time: datetime
    commands_pending: int


class ClientResponse(BaseModel):
    id: str
    license_id: str
    device_name: str
    ip_address: Optional[str]
    hwid: str
    os_version: Optional[str]
    client_version: Optional[str]
    health_score: float
    last_online: datetime
    is_online: bool
    real_time_guard_enabled: bool
    threats_found: int
    last_scan_date: Optional[datetime]
    created_at: datetime
    
    class Config:
        from_attributes = True


# ==================== Threat Log Schemas ====================

class ThreatLogCreate(BaseModel):
    hwid: str
    file_path: str
    file_hash: str = Field(..., min_length=64, max_length=64)
    threat_name: str
    threat_type: str = "malware"
    severity: str = "high"
    action_taken: str = "quarantined"
    file_size: Optional[int] = None
    cloud_verified: bool = False


class ThreatLogResponse(BaseModel):
    id: str
    client_id: str
    file_path: str
    file_hash: str
    threat_name: str
    threat_type: str
    severity: str
    action_taken: str
    file_size: Optional[int]
    detected_at: datetime
    resolved: bool
    resolution_details: Optional[str]
    cloud_verified: bool
    
    class Config:
        from_attributes = True


class ThreatStats(BaseModel):
    total_threats: int
    critical_count: int
    high_count: int
    medium_count: int
    low_count: int
    quarantined_count: int
    deleted_count: int
    blocked_count: int
    recent_threats: List[ThreatLogResponse]


# ==================== Remote Command Schemas ====================

class RemoteCommandCreate(BaseModel):
    client_id: str
    command: str = Field(..., pattern=r'^(force_delete|full_scan|quick_scan|update_signatures|shutdown_guard|enable_guard|reboot|isolate)$')
    target_path: Optional[str] = None
    parameters: Optional[str] = None
    priority: int = Field(default=5, ge=1, le=10)


class RemoteCommandResponse(BaseModel):
    id: str
    client_id: str
    command: str
    target_path: Optional[str]
    parameters: Optional[str]
    status: str
    priority: int
    created_at: datetime
    executed_at: Optional[datetime]
    result: Optional[str]
    
    class Config:
        from_attributes = True


class CommandResultUpdate(BaseModel):
    command_id: str
    status: str = Field(..., pattern=r'^(completed|failed|acknowledged)$')
    result: Optional[str] = None


# ==================== Admin Dashboard Schemas ====================

class DashboardStats(BaseModel):
    total_licenses: int
    active_licenses: int
    expired_licenses: int
    total_clients: int
    online_clients: int
    offline_clients: int
    total_threats: int
    threats_today: int
    avg_health_score: float
    pending_commands: int
    signatures_version: Optional[str]
    server_uptime: str


class SignatureUpdateCreate(BaseModel):
    version: str
    total_signatures: int
    critical_updates: bool = False
    download_url: Optional[str] = None
    description: Optional[str] = None
