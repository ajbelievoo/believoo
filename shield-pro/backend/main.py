"""
Believoo Shield Pro - Backend Server
Enterprise FastAPI server for centralized antivirus management.

Run with: uvicorn main:app --host 0.0.0.0 --port 8443 --ssl-keyfile key.pem --ssl-certfile cert.pem
"""
import time
import os
from contextlib import asynccontextmanager
from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.middleware.trustedhost import TrustedHostMiddleware
from fastapi.responses import JSONResponse, FileResponse
from fastapi.staticfiles import StaticFiles
import uvicorn

from app.db.database import init_db
from app.api.endpoints import router
from app.core.config import get_settings

settings = get_settings()
start_time = time.time()


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Application lifespan handler - initialize database on startup."""
    init_db()
    yield
    # Cleanup can be added here on shutdown


app = FastAPI(
    title=settings.APP_NAME,
    version=settings.APP_VERSION,
    description="Believoo Shield Pro - Enterprise Antivirus Management API",
    docs_url="/docs" if settings.DEBUG else None,
    redoc_url="/redoc" if settings.DEBUG else None,
    lifespan=lifespan
)

# Security middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"] if settings.DEBUG else ["https://*.believoo.com"],
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE"],
    allow_headers=["*"],
)

app.add_middleware(
    TrustedHostMiddleware,
    allowed_hosts=["*"] if settings.DEBUG else ["*.believoo.com", "localhost"]
)


@app.middleware("http")
async def add_security_headers(request: Request, call_next):
    """Add security headers to all responses."""
    response = await call_next(request)
    response.headers["X-Content-Type-Options"] = "nosniff"
    response.headers["X-Frame-Options"] = "DENY"
    response.headers["X-XSS-Protection"] = "1; mode=block"
    response.headers["Strict-Transport-Security"] = "max-age=31536000; includeSubDomains"
    response.headers["X-Believoo-Shield"] = "Pro-1.0"
    return response


@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception):
    """Global exception handler for unhandled errors."""
    return JSONResponse(
        status_code=500,
        content={
            "success": False,
            "message": "An internal server error occurred.",
            "detail": str(exc) if settings.DEBUG else "Contact Believoo Support."
        }
    )


# Include API routes
app.include_router(router, prefix="/api/v1")

# Mount static files for admin panel
static_dir = os.path.join(os.path.dirname(__file__), "static", "admin")
if os.path.isdir(static_dir):
    app.mount("/static/admin", StaticFiles(directory=static_dir), name="admin_static")


@app.get("/admin", response_class=FileResponse)
async def admin_panel_root():
    """Serve the admin panel SPA root."""
    index_path = os.path.join(static_dir, "index.html")
    if os.path.exists(index_path):
        return FileResponse(index_path)
    return JSONResponse({"detail": "Admin panel not built"}, status_code=404)


@app.get("/")
async def root():
    """Root endpoint - API status check."""
    uptime = int(time.time() - start_time)
    return {
        "name": settings.APP_NAME,
        "version": settings.APP_VERSION,
        "status": "operational",
        "environment": settings.ENVIRONMENT,
        "uptime_seconds": uptime
    }


@app.get("/health")
async def health_check():
    """Health check endpoint for monitoring."""
    return {
        "status": "healthy",
        "timestamp": time.time(),
        "service": "shield-pro-api"
    }


if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host=settings.HOST,
        port=settings.PORT,
        reload=settings.DEBUG,
        ssl_keyfile="key.pem" if not settings.DEBUG else None,
        ssl_certfile="cert.pem" if not settings.DEBUG else None
    )
