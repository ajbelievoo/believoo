"""
Believoo Shield Pro - Remote Command Service
Admin command queue management and dispatch.
"""
from datetime import datetime
from typing import List, Optional
from sqlalchemy.orm import Session
from app.models.models import RemoteCommand, Client
from app.schemas.schemas import RemoteCommandCreate, RemoteCommandResponse


class RemoteCommandService:
    """Service for managing remote commands from admin to clients."""
    
    def __init__(self, db: Session):
        self.db = db
    
    def create_command(self, cmd_data: RemoteCommandCreate, created_by: Optional[str] = None) -> RemoteCommand:
        """Create a new remote command for a client."""
        command = RemoteCommand(
            client_id=cmd_data.client_id,
            command=cmd_data.command,
            target_path=cmd_data.target_path,
            parameters=cmd_data.parameters,
            priority=cmd_data.priority,
            created_by=created_by
        )
        self.db.add(command)
        self.db.commit()
        self.db.refresh(command)
        return command
    
    def create_broadcast_command(self, command: str, target_path: Optional[str] = None,
                                  parameters: Optional[str] = None,
                                  created_by: Optional[str] = None) -> List[RemoteCommand]:
        """Send a command to all online clients."""
        online_clients = self.db.query(Client).filter(Client.is_online == True).all()
        commands = []
        
        for client in online_clients:
            cmd = RemoteCommand(
                client_id=client.id,
                command=command,
                target_path=target_path,
                parameters=parameters,
                priority=1,  # High priority for broadcast
                created_by=created_by
            )
            self.db.add(cmd)
            commands.append(cmd)
        
        self.db.commit()
        return commands
    
    def get_commands_for_client(self, client_id: str) -> List[RemoteCommand]:
        """Get pending/sent commands for a specific client."""
        return self.db.query(RemoteCommand).filter(
            RemoteCommand.client_id == client_id,
            RemoteCommand.status.in_(["pending", "sent"])
        ).order_by(RemoteCommand.priority.asc()).all()
    
    def get_all_commands(self, skip: int = 0, limit: int = 100) -> List[RemoteCommand]:
        """Get all commands with pagination."""
        return self.db.query(RemoteCommand).order_by(
            RemoteCommand.created_at.desc()
        ).offset(skip).limit(limit).all()
    
    def get_pending_commands(self) -> List[RemoteCommand]:
        """Get all pending commands."""
        return self.db.query(RemoteCommand).filter(
            RemoteCommand.status == "pending"
        ).order_by(RemoteCommand.priority.asc()).all()
    
    def cancel_command(self, command_id: str) -> bool:
        """Cancel a pending command."""
        command = self.db.query(RemoteCommand).filter(
            RemoteCommand.id == command_id,
            RemoteCommand.status == "pending"
        ).first()
        
        if command:
            command.status = "cancelled"
            self.db.commit()
            return True
        return False
    
    def get_command_stats(self) -> dict:
        """Get command statistics."""
        total = self.db.query(RemoteCommand).count()
        pending = self.db.query(RemoteCommand).filter(RemoteCommand.status == "pending").count()
        sent = self.db.query(RemoteCommand).filter(RemoteCommand.status == "sent").count()
        completed = self.db.query(RemoteCommand).filter(RemoteCommand.status == "completed").count()
        failed = self.db.query(RemoteCommand).filter(RemoteCommand.status == "failed").count()
        
        return {
            "total": total,
            "pending": pending,
            "sent": sent,
            "completed": completed,
            "failed": failed
        }
