"""
Believoo Shield Pro - Threat Intelligence Service
Centralized threat logging, signature management, and analytics.
"""
from datetime import datetime, timedelta
from typing import List, Optional
from sqlalchemy.orm import Session
from sqlalchemy import func
from app.models.models import ThreatLog, Client, SignatureUpdate
from app.schemas.schemas import ThreatLogCreate


class ThreatService:
    """Service class for threat intelligence operations."""
    
    def __init__(self, db: Session):
        self.db = db
    
    def log_threat(self, threat_data: ThreatLogCreate) -> ThreatLog:
        """Log a detected threat from a client device."""
        client = self.db.query(Client).filter(Client.hwid == threat_data.hwid).first()
        
        if not client:
            raise ValueError(f"Client with HWID {threat_data.hwid} not found")
        
        threat = ThreatLog(
            client_id=client.id,
            file_path=threat_data.file_path,
            file_hash=threat_data.file_hash,
            threat_name=threat_data.threat_name,
            threat_type=threat_data.threat_type,
            severity=threat_data.severity,
            action_taken=threat_data.action_taken,
            file_size=threat_data.file_size,
            cloud_verified=threat_data.cloud_verified
        )
        
        self.db.add(threat)
        
        # Update client's threat count
        client.threats_found += 1
        
        self.db.commit()
        self.db.refresh(threat)
        return threat
    
    def get_threats_by_client(self, client_id: str, skip: int = 0, limit: int = 100) -> List[ThreatLog]:
        """Get threats for a specific client."""
        return self.db.query(ThreatLog).filter(
            ThreatLog.client_id == client_id
        ).order_by(ThreatLog.detected_at.desc()).offset(skip).limit(limit).all()
    
    def get_all_threats(self, skip: int = 0, limit: int = 100) -> List[ThreatLog]:
        """Get all threats with pagination."""
        return self.db.query(ThreatLog).order_by(
            ThreatLog.detected_at.desc()
        ).offset(skip).limit(limit).all()
    
    def get_threats_by_hash(self, file_hash: str) -> List[ThreatLog]:
        """Get all threats matching a specific file hash."""
        return self.db.query(ThreatLog).filter(
            ThreatLog.file_hash == file_hash
        ).all()
    
    def get_threat_stats(self) -> dict:
        """Get comprehensive threat statistics."""
        total = self.db.query(ThreatLog).count()
        
        # Severity counts
        critical = self.db.query(ThreatLog).filter(ThreatLog.severity == "critical").count()
        high = self.db.query(ThreatLog).filter(ThreatLog.severity == "high").count()
        medium = self.db.query(ThreatLog).filter(ThreatLog.severity == "medium").count()
        low = self.db.query(ThreatLog).filter(ThreatLog.severity == "low").count()
        
        # Action counts
        quarantined = self.db.query(ThreatLog).filter(ThreatLog.action_taken == "quarantined").count()
        deleted = self.db.query(ThreatLog).filter(ThreatLog.action_taken == "deleted").count()
        blocked = self.db.query(ThreatLog).filter(ThreatLog.action_taken == "blocked").count()
        
        # Today's threats
        today_start = datetime.utcnow().replace(hour=0, minute=0, second=0, microsecond=0)
        today_count = self.db.query(ThreatLog).filter(
            ThreatLog.detected_at >= today_start
        ).count()
        
        # Recent threats
        recent = self.db.query(ThreatLog).order_by(
            ThreatLog.detected_at.desc()
        ).limit(10).all()
        
        return {
            "total_threats": total,
            "critical_count": critical,
            "high_count": high,
            "medium_count": medium,
            "low_count": low,
            "quarantined_count": quarantined,
            "deleted_count": deleted,
            "blocked_count": blocked,
            "threats_today": today_count,
            "recent_threats": recent
        }
    
    def get_global_threat_map(self) -> dict:
        """Get threat distribution data for visualization."""
        # Threats by type
        type_counts = self.db.query(
            ThreatLog.threat_type,
            func.count(ThreatLog.id)
        ).group_by(ThreatLog.threat_type).all()
        
        # Threats over time (last 7 days)
        seven_days_ago = datetime.utcnow() - timedelta(days=7)
        daily_counts = self.db.query(
            func.date(ThreatLog.detected_at),
            func.count(ThreatLog.id)
        ).filter(ThreatLog.detected_at >= seven_days_ago).group_by(
            func.date(ThreatLog.detected_at)
        ).all()
        
        return {
            "by_type": {t[0]: t[1] for t in type_counts},
            "by_day": {str(t[0]): t[1] for t in daily_counts}
        }
    
    def create_signature_update(self, version: str, total_signatures: int, 
                                critical: bool = False, url: Optional[str] = None,
                                description: Optional[str] = None) -> SignatureUpdate:
        """Register a new signature update."""
        update = SignatureUpdate(
            version=version,
            total_signatures=total_signatures,
            critical_updates=critical,
            download_url=url,
            description=description
        )
        self.db.add(update)
        self.db.commit()
        self.db.refresh(update)
        return update
    
    def get_latest_signature_version(self) -> Optional[SignatureUpdate]:
        """Get the most recent signature update."""
        return self.db.query(SignatureUpdate).order_by(
            SignatureUpdate.release_date.desc()
        ).first()
