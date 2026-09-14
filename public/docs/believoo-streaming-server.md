# BelieVoo Streaming Server - Self-Hosting Guide

## Overview
BelieVoo Streaming Server is a WebRTC-based live streaming infrastructure that runs on your own VPS. This guide will help you set up the complete streaming backend.

## Server Requirements

- **OS**: Ubuntu 20.04/22.04 LTS or CentOS 8+
- **CPU**: 4+ cores (8+ recommended for multiple streams)
- **RAM**: 8GB minimum (16GB+ recommended)
- **Network**: 100Mbps+ dedicated bandwidth
- **Storage**: 100GB+ SSD (for recordings)

## Quick Start (Docker)

### 1. Install Docker

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
sudo usermod -aG docker $USER
newgrp docker
```

### 2. Create Project Directory

```bash
mkdir -p /opt/believoo-streaming
cd /opt/believoo-streaming
```

### 3. Create docker-compose.yml

```yaml
version: '3.8'

services:
  believoo-server:
    image: believoo/streaming-server:2.0.0
    container_name: believoo-streaming
    restart: unless-stopped
    ports:
      - "1935:1935"      # RTMP ingest
      - "8080:8080"      # WebRTC WS
      - "8443:8443"      # WebRTC WSS
      - "3478:3478"      # STUN/TURN
      - "10000-10010:10000-10010/udp"  # WebRTC UDP
    environment:
      - BELIEVOO_APP_ID=${APP_ID}
      - BELIEVOO_APP_CERTIFICATE=${APP_CERT}
      - STREAM_DOMAIN=${DOMAIN}
      - MAX_STREAMS=50
      - MAX_VIEWERS_PER_STREAM=1000
      - ENABLE_RECORDING=true
      - RECORDING_PATH=/recordings
      - ENABLE_AUTH=true
    volumes:
      - ./recordings:/recordings
      - ./config:/config
    networks:
      - believoo-net
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"

  redis:
    image: redis:7-alpine
    container_name: believoo-redis
    restart: unless-stopped
    volumes:
      - redis-data:/data
    networks:
      - believoo-net

  nginx:
    image: nginx:alpine
    container_name: believoo-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./ssl:/etc/nginx/ssl:ro
    depends_on:
      - believoo-server
    networks:
      - believoo-net

volumes:
  redis-data:

networks:
  believoo-net:
    driver: bridge
```

### 4. Create Environment File

```bash
cat > .env << 'EOF'
# Your BelieVoo Credentials
APP_ID=bel_your_app_id
APP_CERT=your_app_certificate
DOMAIN=live.yourdomain.com

# Database (if using external)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=believoo
DB_USER=believoo
DB_PASS=your_db_password

# Redis
REDIS_URL=redis://redis:6379

# Storage
RECORDING_PATH=/var/recordings
MAX_RECORDING_SIZE_GB=500

# Performance
MAX_STREAMS=100
MAX_VIEWERS_PER_STREAM=5000
STREAM_TIMEOUT_MINUTES=120

# Security
ENABLE_AUTH=true
ENABLE_RATE_LIMITING=true
ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com
EOF
```

### 5. Configure Nginx (nginx.conf)

```nginx
events {
    worker_connections 4096;
}

http {
    map $http_upgrade $connection_upgrade {
        default upgrade;
        '' close;
    }

    upstream believoo_backend {
        server believoo-server:8080;
    }

    server {
        listen 80;
        server_name live.yourdomain.com;
        
        location / {
            return 301 https://$server_name$request_uri;
        }
    }

    server {
        listen 443 ssl http2;
        server_name live.yourdomain.com;

        ssl_certificate /etc/nginx/ssl/cert.pem;
        ssl_certificate_key /etc/nginx/ssl/key.pem;

        # WebSocket proxy
        location /ws/ {
            proxy_pass http://believoo_backend;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection $connection_upgrade;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_read_timeout 86400;
        }

        # API endpoints
        location /api/ {
            proxy_pass http://believoo_backend;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            
            # CORS headers
            add_header 'Access-Control-Allow-Origin' '*' always;
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        }

        # Health check
        location /health {
            proxy_pass http://believoo_backend/health;
            access_log off;
        }
    }
}

# RTMP block
rtmp {
    server {
        listen 1935;
        chunk_size 4000;

        application live {
            live on;
            
            # Push to WebRTC bridge
            exec ffmpeg -i rtmp://localhost:1935/live/$name
                -c:v copy -c:a copy
                -f flv rtmp://localhost:8080/webrtc/$name;
            
            # Recording
            record all;
            record_path /recordings;
            record_unique on;
            record_suffix .flv;
        }
    }
}
```

### 6. Start Services

```bash
docker-compose up -d
```

### 7. Verify Installation

```bash
# Check containers
docker-compose ps

# Check logs
docker-compose logs -f believoo-server

# Test WebSocket connection
curl -i -N \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Host: localhost:8080" \
  -H "Origin: http://localhost:8080" \
  http://localhost:8080/ws/test
```

## Firewall Configuration

```bash
# UFW
sudo ufw allow 1935/tcp    # RTMP
sudo ufw allow 8080/tcp    # WebRTC WS
sudo ufw allow 8443/tcp    # WebRTC WSS
sudo ufw allow 3478/tcp    # STUN/TURN
sudo ufw allow 3478/udp    # STUN/TURN UDP
sudo ufw allow 10000:10010/udp  # WebRTC media
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Or iptables
sudo iptables -A INPUT -p tcp --dport 1935 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 8080 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 8443 -j ACCEPT
sudo iptables -A INPUT -p udp --dport 3478 -j ACCEPT
sudo iptables -A INPUT -p udp --dport 10000:10010 -j ACCEPT
```

## SSL Certificate (Let's Encrypt)

```bash
# Install certbot
sudo apt install certbot

# Get certificate
sudo certbot certonly --standalone -d live.yourdomain.com

# Copy to nginx folder
sudo cp /etc/letsencrypt/live/live.yourdomain.com/fullchain.pem ./ssl/cert.pem
sudo cp /etc/letsencrypt/live/live.yourdomain.com/privkey.pem ./ssl/key.pem

# Auto-renewal
sudo certbot renew --dry-run
```

## Monitoring

```bash
# View active streams
curl http://localhost:8080/api/v1/streams

# View statistics
curl http://localhost:8080/api/v1/stats

# Server health
curl http://localhost:8080/health
```

## Troubleshooting

### Issue: WebRTC connection fails
- Check firewall rules for UDP ports
- Verify STUN/TURN server is accessible
- Check browser console for ICE errors

### Issue: High latency
- Use TURN server for better connectivity
- Check server bandwidth
- Enable BBR congestion control:
  ```bash
  echo 'net.core.default_qdisc=fq' >> /etc/sysctl.conf
  echo 'net.ipv4.tcp_congestion_control=bbr' >> /etc/sysctl.conf
  sysctl -p
  ```

### Issue: Recording not working
- Verify /recordings folder exists and is writable
- Check disk space: `df -h`
- Review FFmpeg logs

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/token` | POST | Generate streaming token |
| `/api/v1/streams` | GET | List active streams |
| `/api/v1/stream/{id}` | GET | Get stream info |
| `/api/v1/stream/{id}/viewers` | GET | Get viewer count |
| `/ws/{channel}` | WS | WebSocket connection |

## Support

- Documentation: https://believoo.com/docs/streaming
- Email: support@believoo.com

## License

BelieVoo Streaming Server - Copyright 2026 BelieVoo Technologies
