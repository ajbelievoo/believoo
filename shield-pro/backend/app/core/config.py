"""
Believoo Shield Pro - Backend Configuration
Enterprise-grade configuration management with environment-aware settings.
"""
from pydantic_settings import BaseSettings
from functools import lru_cache


class Settings(BaseSettings):
    """Application settings loaded from environment variables."""
    
    # Application
    APP_NAME: str = "Believoo Shield Pro API"
    APP_VERSION: str = "1.0.0"
    DEBUG: bool = False
    ENVIRONMENT: str = "development"
    
    # Server
    HOST: str = "0.0.0.0"
    PORT: int = 8443
    
    # Database
    DATABASE_URL: str = "sqlite:///./shield_pro.db"
    # Production: mysql+pymysql://user:pass@host/db
    
    # Security
    SECRET_KEY: str = "believoo-shield-pro-secret-key-2024-ultra-secure"
    ADMIN_API_KEY: str = "bhost-admin-api-key-ultra-secure-2024"
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 30
    
    # Licensing
    LICENSE_ENCRYPTION_SALT: str = "believoo-salt"
    
    # Cloud Sync / Backup
    SFTP_HOST: str = "sftp.believoo.com"
    SFTP_PORT: int = 22
    SFTP_USER: str = "shield_backup"
    
    # Rate Limiting
    HEARTBEAT_INTERVAL_SECONDS: int = 300  # 5 minutes
    
    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"


@lru_cache()
def get_settings() -> Settings:
    """Get cached settings instance."""
    return Settings()
