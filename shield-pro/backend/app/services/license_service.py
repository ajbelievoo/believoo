"""
Believoo Shield Pro - License Service
Business logic for license creation, activation, validation, and lifecycle management.
"""
from datetime import datetime, timedelta
from typing import Optional, List
from sqlalchemy.orm import Session
from fastapi import HTTPException, status
from app.models.models import License, Client
from app.schemas.schemas import LicenseCreate, LicenseActivateRequest
from app.core.security import generate_license_key


class LicenseService:
    """Service class for license management operations."""
    
    def __init__(self, db: Session):
        self.db = db
    
    def create_license(self, license_data: LicenseCreate) -> License:
        """Create a new license key."""
        db_license = License(
            license_key=generate_license_key(),
            expiry_date=license_data.expiry_date,
            max_devices=license_data.max_devices,
            status="pending"
        )
        self.db.add(db_license)
        self.db.commit()
        self.db.refresh(db_license)
        return db_license
    
    def get_license_by_key(self, license_key: str) -> Optional[License]:
        """Retrieve license by key."""
        return self.db.query(License).filter(License.license_key == license_key).first()
    
    def get_license_by_hwid(self, hwid: str) -> Optional[License]:
        """Retrieve license by HWID."""
        return self.db.query(License).filter(License.hwid == hwid).first()
    
    def activate_license(self, request: LicenseActivateRequest) -> dict:
        """Activate a license and bind to HWID."""
        license = self.get_license_by_key(request.license_key)
        
        if not license:
            return {
                "success": False,
                "message": "Invalid license key.",
                "token": None,
                "expiry_date": None,
                "status": None
            }
        
        # Check if license is revoked
        if license.status == "revoked":
            return {
                "success": False,
                "message": "This license has been revoked. Contact Believoo Support.",
                "token": None,
                "expiry_date": None,
                "status": "revoked"
            }
        
        # Check expiry
        if license.expiry_date < datetime.utcnow():
            license.status = "expired"
            self.db.commit()
            return {
                "success": False,
                "message": "License has expired. Please renew your subscription.",
                "token": None,
                "expiry_date": license.expiry_date,
                "status": "expired"
            }
        
        # Check if already bound to another HWID
        if license.hwid and license.hwid != request.hwid:
            return {
                "success": False,
                "message": "License is already bound to another device.",
                "token": None,
                "expiry_date": None,
                "status": "bound"
            }
        
        # Bind license to HWID
        license.hwid = request.hwid
        license.device_name = request.device_name
        license.status = "active"
        
        # Create or update client record
        client = self.db.query(Client).filter(Client.hwid == request.hwid).first()
        if not client:
            client = Client(
                license_id=license.id,
                device_name=request.device_name,
                hwid=request.hwid,
                os_version=request.os_version,
                client_version=request.client_version,
                is_online=True
            )
            self.db.add(client)
        else:
            client.license_id = license.id
            client.device_name = request.device_name
            client.os_version = request.os_version
            client.client_version = request.client_version
            client.is_online = True
        
        self.db.commit()
        self.db.refresh(license)
        
        from app.core.security import create_access_token
        token = create_access_token({
            "license_key": license.license_key,
            "hwid": request.hwid,
            "expiry": license.expiry_date.isoformat()
        })
        
        return {
            "success": True,
            "message": "License activated successfully. Welcome to Believoo Shield Pro.",
            "token": token,
            "expiry_date": license.expiry_date,
            "status": license.status
        }
    
    def validate_license(self, license_key: str, hwid: str) -> dict:
        """Validate an existing license without modifying state."""
        license = self.get_license_by_key(license_key)
        
        if not license:
            return {"valid": False, "message": "License not found."}
        
        if license.status == "revoked":
            return {"valid": False, "message": "License revoked."}
        
        if license.expiry_date < datetime.utcnow():
            return {"valid": False, "message": "License expired.", "expiry_date": license.expiry_date}
        
        if license.hwid and license.hwid != hwid:
            return {"valid": False, "message": "HWID mismatch."}
        
        return {
            "valid": True,
            "message": "License valid.",
            "status": license.status,
            "expiry_date": license.expiry_date
        }
    
    def revoke_license(self, license_key: str) -> bool:
        """Revoke a license."""
        license = self.get_license_by_key(license_key)
        if license:
            license.status = "revoked"
            self.db.commit()
            return True
        return False
    
    def get_all_licenses(self, skip: int = 0, limit: int = 100) -> List[License]:
        """Get all licenses with pagination."""
        return self.db.query(License).offset(skip).limit(limit).all()
    
    def get_license_stats(self) -> dict:
        """Get license statistics for admin dashboard."""
        total = self.db.query(License).count()
        active = self.db.query(License).filter(License.status == "active").count()
        expired = self.db.query(License).filter(License.status == "expired").count()
        revoked = self.db.query(License).filter(License.status == "revoked").count()
        pending = self.db.query(License).filter(License.status == "pending").count()
        
        return {
            "total": total,
            "active": active,
            "expired": expired,
            "revoked": revoked,
            "pending": pending
        }
