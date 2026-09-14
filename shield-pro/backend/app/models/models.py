"""
Believoo Shield Pro - Database Models
Enterprise-grade models for License, Client, and ThreatLog management.
"""
import uuid
from datetime import datetime
from sqlalchemy import Column, String, DateTime, Integer, Float, Boolean, Text, ForeignKey, Index, event
from sqlalchemy.orm import relationship
from app.db.database import Base


def generate_uuid():
    """Generate a unique identifier string."""
    return str(uuid.uuid4())


class License(Base):
    """License key management with HWID binding."""
    __tablename__ = "licenses"
    
    id = Column(String(36), primary_key=True, default=generate_uuid)
    license_key = Column(String(32), unique=True, nullable=False, index=True)
    hwid = Column(String(128), unique=True, nullable=True)
    device_name = Column(String(256), nullable=True)
    expiry_date = Column(DateTime, nullable=False)
    status = Column(String(20), default="active")  # active, revoked, expired, pending
    max_devices = Column(Integer, default=1)
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    clients = relationship("Client", back_populates="license", lazy="dynamic")
    
    __table_args__ = (
        Index('idx_license_status', 'status'),
        Index('idx_license_key', 'license_key'),
    )


class Client(Base):
    """Managed antivirus client device."""
    __tablename__ = "clients"
    
    id = Column(String(36), primary_key=True, default=generate_uuid)
    license_id = Column(String(36), ForeignKey("licenses.id"), nullable=False)
    device_name = Column(String(256), nullable=False)
    ip_address = Column(String(45), nullable=True)  # IPv6 support
    hwid = Column(String(128), nullable=False, index=True)
    os_version = Column(String(128), nullable=True)
    client_version = Column(String(32), nullable=True)
    health_score = Column(Float, default=100.0)  # 0-100
    last_online = Column(DateTime, default=datetime.utcnow)
    is_online = Column(Boolean, default=True)
    real_time_guard_enabled = Column(Boolean, default=True)
    last_scan_date = Column(DateTime, nullable=True)
    threats_found = Column(Integer, default=0)
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    license = relationship("License", back_populates="clients")
    threat_logs = relationship("ThreatLog", back_populates="client", lazy="dynamic")
    remote_commands = relationship("RemoteCommand", back_populates="client", lazy="dynamic")
    
    __table_args__ = (
        Index('idx_client_hwid', 'hwid'),
        Index('idx_client_online', 'is_online'),
        Index('idx_client_last_online', 'last_online'),
    )


class ThreatLog(Base):
    """Centralized threat intelligence log."""
    __tablename__ = "threat_logs"
    
    id = Column(String(36), primary_key=True, default=generate_uuid)
    client_id = Column(String(36), ForeignKey("clients.id"), nullable=False)
    file_path = Column(Text, nullable=False)
    file_hash = Column(String(64), nullable=False, index=True)  # SHA-256
    threat_name = Column(String(256), nullable=False)
    threat_type = Column(String(50), default="malware")  # malware, trojan, ransomware, pup, etc.
    severity = Column(String(20), default="high")  # critical, high, medium, low
    action_taken = Column(String(50), default="quarantined")  # quarantined, deleted, blocked, allowed
    file_size = Column(Integer, nullable=True)
    detected_at = Column(DateTime, default=datetime.utcnow)
    resolved = Column(Boolean, default=False)
    resolution_details = Column(Text, nullable=True)
    cloud_verified = Column(Boolean, default=False)
    
    # Relationships
    client = relationship("Client", back_populates="threat_logs")
    
    __table_args__ = (
        Index('idx_threat_hash', 'file_hash'),
        Index('idx_threat_severity', 'severity'),
        Index('idx_threat_detected', 'detected_at'),
        Index('idx_threat_resolved', 'resolved'),
    )


class RemoteCommand(Base):
    """Admin remote command queue for clients."""
    __tablename__ = "remote_commands"
    
    id = Column(String(36), primary_key=True, default=generate_uuid)
    client_id = Column(String(36), ForeignKey("clients.id"), nullable=False)
    command = Column(String(50), nullable=False)  # force_delete, full_scan, update_signatures, shutdown_guard, enable_guard
    target_path = Column(Text, nullable=True)
    parameters = Column(Text, nullable=True)  # JSON string
    status = Column(String(20), default="pending")  # pending, sent, acknowledged, completed, failed
    priority = Column(Integer, default=5)  # 1-10, 1 = highest
    created_by = Column(String(128), nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow)
    executed_at = Column(DateTime, nullable=True)
    result = Column(Text, nullable=True)
    
    # Relationships
    client = relationship("Client", back_populates="remote_commands")
    
    __table_args__ = (
        Index('idx_command_client_status', 'client_id', 'status'),
        Index('idx_command_priority', 'priority'),
        Index('idx_command_created', 'created_at'),
    )


class SignatureUpdate(Base):
    """Virus definition signature version tracking."""
    __tablename__ = "signature_updates"
    
    id = Column(String(36), primary_key=True, default=generate_uuid)
    version = Column(String(32), nullable=False, unique=True)
    release_date = Column(DateTime, default=datetime.utcnow)
    total_signatures = Column(Integer, default=0)
    critical_updates = Column(Boolean, default=False)
    download_url = Column(Text, nullable=True)
    description = Column(Text, nullable=True)
