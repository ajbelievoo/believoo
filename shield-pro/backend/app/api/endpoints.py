"""
Believoo Shield Pro - API Endpoints
Enterprise-grade REST API for client communication and admin control.
"""
from datetime import datetime
from typing import List, Optional
from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from pydantic import BaseModel

from app.db.database import get_db
from app.core.security import verify_admin_api_key
from app.schemas.schemas import (
    LicenseActivateRequest, LicenseActivateResponse,
    HeartbeatRequest, HeartbeatResponse,
    ThreatLogCreate, ThreatLogResponse, ThreatStats,
    RemoteCommandCreate, RemoteCommandResponse, CommandResultUpdate,
    DashboardStats, SignatureUpdateCreate
)
from app.services.license_service import LicenseService
from app.services.client_service import ClientService
from app.services.threat_service import ThreatService
from app.services.remote_command_service import RemoteCommandService

router = APIRouter()


# ==================== Client Endpoints ====================

@router.post("/activate", response_model=LicenseActivateResponse)
def activate_license(
    request: LicenseActivateRequest,
    db: Session = Depends(get_db)
):
    """
    Activate a license key and bind to device HWID.
    Called once during client first-run installation.
    """
    service = LicenseService(db)
    result = service.activate_license(request)
    return result


@router.post("/heartbeat", response_model=HeartbeatResponse)
def client_heartbeat(
    request: HeartbeatRequest,
    db: Session = Depends(get_db)
):
    """
    Client heartbeat - called every 5 minutes.
    Updates device status and returns pending remote commands.
    """
    service = ClientService(db)
    result = service.process_heartbeat(request)
    return result


@router.post("/threat-log")
def report_threat(
    threat_data: ThreatLogCreate,
    db: Session = Depends(get_db)
):
    """
    Report a detected threat from client to central server.
    """
    service = ThreatService(db)
    try:
        threat = service.log_threat(threat_data)
        return {"success": True, "threat_id": threat.id, "message": "Threat logged successfully."}
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))


@router.get("/remote-commands")
def get_remote_commands(
    hwid: str,
    db: Session = Depends(get_db)
):
    """
    Get pending remote commands for a specific client device.
    """
    client_service = ClientService(db)
    client = client_service.get_client_by_hwid(hwid)
    
    if not client:
        raise HTTPException(status_code=404, detail="Client not found")
    
    cmd_service = RemoteCommandService(db)
    commands = cmd_service.get_commands_for_client(client.id)
    return {"commands": commands, "count": len(commands)}


@router.post("/command-result")
def update_command_result(
    update: CommandResultUpdate,
    db: Session = Depends(get_db)
):
    """
    Update the result of an executed remote command.
    """
    service = ClientService(db)
    success = service.update_command_result(update)
    
    if not success:
        raise HTTPException(status_code=404, detail="Command not found")
    
    return {"success": True, "message": "Command result updated."}


@router.get("/signatures/latest")
def get_latest_signatures(db: Session = Depends(get_db)):
    """
    Get the latest virus signature version information.
    """
    service = ThreatService(db)
    latest = service.get_latest_signature_version()
    
    if not latest:
        return {"version": "1.0.0", "release_date": None, "critical_updates": False}
    
    return {
        "version": latest.version,
        "release_date": latest.release_date,
        "total_signatures": latest.total_signatures,
        "critical_updates": latest.critical_updates,
        "download_url": latest.download_url,
        "description": latest.description
    }


# ==================== Admin Endpoints (Protected) ====================

class CreateLicenseRequest(BaseModel):
    expiry_days: int
    max_devices: int = 1


@router.post("/admin/licenses")
def admin_create_license(
    request: CreateLicenseRequest,
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Create a new license key (Admin only).
    """
    from datetime import timedelta
    from app.schemas.schemas import LicenseCreate
    
    service = LicenseService(db)
    expiry = datetime.utcnow() + timedelta(days=request.expiry_days)
    license_data = LicenseCreate(expiry_date=expiry, max_devices=request.max_devices)
    license = service.create_license(license_data)
    
    return {
        "success": True,
        "license_key": license.license_key,
        "expiry_date": license.expiry_date,
        "status": license.status
    }


@router.get("/admin/licenses")
def admin_get_licenses(
    skip: int = Query(0, ge=0),
    limit: int = Query(100, ge=1, le=1000),
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Get all licenses (Admin only).
    """
    service = LicenseService(db)
    licenses = service.get_all_licenses(skip=skip, limit=limit)
    return {"licenses": licenses, "total": len(licenses)}


@router.post("/admin/licenses/{license_key}/revoke")
def admin_revoke_license(
    license_key: str,
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Revoke a license key (Admin only).
    """
    service = LicenseService(db)
    success = service.revoke_license(license_key)
    
    if not success:
        raise HTTPException(status_code=404, detail="License not found")
    
    return {"success": True, "message": f"License {license_key} has been revoked."}


@router.get("/admin/clients")
def admin_get_clients(
    skip: int = Query(0, ge=0),
    limit: int = Query(100, ge=1, le=1000),
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Get all client devices (Admin only).
    """
    service = ClientService(db)
    clients = service.get_all_clients(skip=skip, limit=limit)
    return {"clients": clients, "total": len(clients)}


@router.post("/admin/commands")
def admin_create_command(
    cmd_data: RemoteCommandCreate,
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Send a remote command to a specific client (Admin only).
    Commands: force_delete, full_scan, quick_scan, update_signatures, 
             shutdown_guard, enable_guard, reboot, isolate
    """
    service = RemoteCommandService(db)
    command = service.create_command(cmd_data, created_by="admin")
    return {"success": True, "command_id": command.id, "status": command.status}


@router.post("/admin/commands/broadcast")
def admin_broadcast_command(
    command: str,
    target_path: Optional[str] = None,
    parameters: Optional[str] = None,
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Broadcast a command to all online clients (Admin only).
    """
    service = RemoteCommandService(db)
    commands = service.create_broadcast_command(command, target_path, parameters, created_by="admin")
    return {
        "success": True,
        "commands_sent": len(commands),
        "command": command
    }


@router.get("/admin/threats")
def admin_get_threats(
    skip: int = Query(0, ge=0),
    limit: int = Query(100, ge=1, le=1000),
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Get all threat logs (Admin only).
    """
    service = ThreatService(db)
    threats = service.get_all_threats(skip=skip, limit=limit)
    return {"threats": threats, "total": len(threats)}


@router.get("/admin/threats/stats")
def admin_threat_stats(
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Get threat statistics (Admin only).
    """
    service = ThreatService(db)
    stats = service.get_threat_stats()
    return stats


@router.get("/admin/dashboard")
def admin_dashboard(
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Get comprehensive dashboard statistics (Admin only).
    """
    license_service = LicenseService(db)
    client_service = ClientService(db)
    threat_service = ThreatService(db)
    cmd_service = RemoteCommandService(db)
    
    license_stats = license_service.get_license_stats()
    client_stats = client_service.get_client_stats()
    threat_stats = threat_service.get_threat_stats()
    cmd_stats = cmd_service.get_command_stats()
    latest_sig = threat_service.get_latest_signature_version()
    
    # Calculate server uptime (simplified)
    import time
    uptime_seconds = time.time() - getattr(admin_dashboard, '_start_time', time.time())
    if not hasattr(admin_dashboard, '_start_time'):
        admin_dashboard._start_time = time.time()
        uptime_seconds = 0
    
    hours = int(uptime_seconds // 3600)
    minutes = int((uptime_seconds % 3600) // 60)
    
    return DashboardStats(
        total_licenses=license_stats["total"],
        active_licenses=license_stats["active"],
        expired_licenses=license_stats["expired"],
        total_clients=client_stats["total"],
        online_clients=client_stats["online"],
        offline_clients=client_stats["offline"],
        total_threats=threat_stats["total_threats"],
        threats_today=threat_stats["threats_today"],
        avg_health_score=client_stats["avg_health_score"],
        pending_commands=cmd_stats["pending"],
        signatures_version=latest_sig.version if latest_sig else "1.0.0",
        server_uptime=f"{hours}h {minutes}m"
    )


@router.post("/admin/signatures")
def admin_create_signature_update(
    update: SignatureUpdateCreate,
    db: Session = Depends(get_db),
    api_key: str = Depends(verify_admin_api_key)
):
    """
    Register a new virus signature update (Admin only).
    """
    service = ThreatService(db)
    sig = service.create_signature_update(
        version=update.version,
        total_signatures=update.total_signatures,
        critical=update.critical_updates,
        url=update.download_url,
        description=update.description
    )
    return {"success": True, "version": sig.version, "release_date": sig.release_date}
