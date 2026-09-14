"""
Believoo Shield Pro - Client Service
Heartbeat processing, remote command queue, and client lifecycle management.
"""
from datetime import datetime, timedelta
from typing import Optional, List
from sqlalchemy.orm import Session
from app.models.models import Client, RemoteCommand, License
from app.schemas.schemas import HeartbeatRequest, CommandResultUpdate


class ClientService:
    """Service class for client device management."""
    
    def __init__(self, db: Session):
        self.db = db
    
    def process_heartbeat(self, request: HeartbeatRequest) -> dict:
        """Process client heartbeat and return pending commands."""
        client = self.db.query(Client).filter(Client.hwid == request.hwid).first()
        
        if not client:
            # Auto-register unknown client if license exists
            license = self.db.query(License).filter(License.hwid == request.hwid).first()
            if license:
                client = Client(
                    license_id=license.id,
                    device_name=request.device_name or "Unknown Device",
                    ip_address=request.ip_address,
                    hwid=request.hwid,
                    os_version=request.os_version,
                    client_version=request.client_version,
                    health_score=request.health_score,
                    real_time_guard_enabled=request.real_time_guard_enabled,
                    threats_found=request.threats_found,
                    last_scan_date=request.last_scan_date,
                    is_online=True
                )
                self.db.add(client)
            else:
                return {
                    "success": False,
                    "message": "Device not registered. Please activate your license first.",
                    "server_time": datetime.utcnow(),
                    "commands_pending": 0
                }
        else:
            # Update client status
            client.device_name = request.device_name or client.device_name
            client.ip_address = request.ip_address or client.ip_address
            client.health_score = request.health_score
            client.real_time_guard_enabled = request.real_time_guard_enabled
            client.threats_found = request.threats_found
            client.last_scan_date = request.last_scan_date or client.last_scan_date
            client.os_version = request.os_version or client.os_version
            client.client_version = request.client_version or client.client_version
            client.last_online = datetime.utcnow()
            client.is_online = True
        
        self.db.commit()
        
        # Get pending commands for this client
        pending_commands = self.db.query(RemoteCommand).filter(
            RemoteCommand.client_id == client.id,
            RemoteCommand.status.in_(["pending", "sent"])
        ).order_by(RemoteCommand.priority.asc()).all()
        
        # Mark commands as sent
        for cmd in pending_commands:
            if cmd.status == "pending":
                cmd.status = "sent"
        self.db.commit()
        
        return {
            "success": True,
            "message": "Heartbeat received.",
            "server_time": datetime.utcnow(),
            "commands_pending": len(pending_commands)
        }
    
    def get_client_by_hwid(self, hwid: str) -> Optional[Client]:
        """Get client by HWID."""
        return self.db.query(Client).filter(Client.hwid == hwid).first()
    
    def get_all_clients(self, skip: int = 0, limit: int = 100) -> List[Client]:
        """Get all clients with pagination."""
        return self.db.query(Client).offset(skip).limit(limit).all()
    
    def get_online_clients(self) -> List[Client]:
        """Get currently online clients."""
        threshold = datetime.utcnow() - timedelta(minutes=10)
        return self.db.query(Client).filter(
            Client.is_online == True,
            Client.last_online >= threshold
        ).all()
    
    def get_offline_clients(self) -> List[Client]:
        """Get clients that haven't checked in recently."""
        threshold = datetime.utcnow() - timedelta(minutes=10)
        return self.db.query(Client).filter(
            Client.last_online < threshold
        ).all()
    
    def update_command_result(self, update: CommandResultUpdate) -> bool:
        """Update the result of a remote command."""
        command = self.db.query(RemoteCommand).filter(RemoteCommand.id == update.command_id).first()
        if command:
            command.status = update.status
            command.result = update.result
            command.executed_at = datetime.utcnow()
            self.db.commit()
            return True
        return False
    
    def get_client_stats(self) -> dict:
        """Get client statistics for dashboard."""
        total = self.db.query(Client).count()
        online = self.db.query(Client).filter(Client.is_online == True).count()
        
        # Calculate average health score
        result = self.db.query(Client.health_score).all()
        avg_health = sum([h[0] for h in result]) / len(result) if result else 0
        
        return {
            "total": total,
            "online": online,
            "offline": total - online,
            "avg_health_score": round(avg_health, 2)
        }
