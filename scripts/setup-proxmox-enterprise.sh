#!/bin/bash
# BelieVoo Proxmox Enterprise Architecture Setup Script
# This script helps with post-deployment configuration
# Run as: sudo bash setup-proxmox-enterprise.sh

set -e

echo "=========================================="
echo "BelieVoo Proxmox Enterprise Setup"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Error: Please run as root or with sudo${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 1: Running database migrations...${NC}"
cd /www/wwwroot/believoo
sudo -u www-data php artisan migrate --force
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

echo -e "${YELLOW}Step 2: Caching configuration...${NC}"
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
echo -e "${GREEN}✓ Cache cleared and rebuilt${NC}"
echo ""

echo -e "${YELLOW}Step 3: Setting up console domain...${NC}"
echo "To set up console.believoo.com, you need to:"
echo "1. Point console.believoo.com DNS to this server IP"
echo "2. Copy nginx config:"
echo "   cp /www/wwwroot/believoo/scripts/nginx-console-proxy.conf /etc/nginx/sites-available/console.believoo.com"
echo "3. Edit the config and replace YOUR_PROXMOX_IP with your actual Proxmox IP"
echo "4. Enable the site:"
echo "   ln -s /etc/nginx/sites-available/console.believoo.com /etc/nginx/sites-enabled/"
echo "5. Get SSL certificate:"
echo "   certbot --nginx -d console.believoo.com"
echo "6. Test and reload nginx:"
echo "   nginx -t && systemctl reload nginx"
echo ""

echo -e "${YELLOW}Step 4: Checking .env configuration...${NC}"
if [ -f "/www/wwwroot/believoo/.env" ]; then
    # Check for required variables
    if grep -q "PROXMOX_CONSOLE_DOMAIN" /www/wwwroot/believoo/.env; then
        echo -e "${GREEN}✓ PROXMOX_CONSOLE_DOMAIN found in .env${NC}"
    else
        echo -e "${RED}✗ PROXMOX_CONSOLE_DOMAIN not found in .env${NC}"
        echo "Add: PROXMOX_CONSOLE_DOMAIN=console.believoo.com"
    fi
    
    if grep -q "PROXMOX_ADMIN_IPS" /www/wwwroot/believoo/.env; then
        echo -e "${GREEN}✓ PROXMOX_ADMIN_IPS found in .env${NC}"
    else
        echo -e "${RED}✗ PROXMOX_ADMIN_IPS not found in .env${NC}"
        echo "Add: PROXMOX_ADMIN_IPS=YOUR_OFFICE_IP"
    fi
    
    if grep -q "PROXMOX_SSH_MGMT_PORT" /www/wwwroot/believoo/.env; then
        echo -e "${GREEN}✓ PROXMOX_SSH_MGMT_PORT found in .env${NC}"
    else
        echo -e "${RED}✗ PROXMOX_SSH_MGMT_PORT not found in .env${NC}"
        echo "Add: PROXMOX_SSH_MGMT_PORT=2200"
    fi
else
    echo -e "${RED}✗ .env file not found${NC}"
fi
echo ""

echo -e "${YELLOW}Step 5: Directory permissions...${NC}"
chown -R www-data:www-data /www/wwwroot/believoo/storage
chown -R www-data:www-data /www/wwwroot/believoo/bootstrap/cache
chmod -R 755 /www/wwwroot/believoo/storage
chmod -R 755 /www/wwwroot/believoo/bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

echo -e "${YELLOW}Step 6: Queue worker (optional)...${NC}"
echo "If using queues for VM provisioning, ensure supervisor is configured:"
echo "   systemctl status supervisor"
echo ""

echo "=========================================="
echo -e "${GREEN}Setup script completed!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Configure DNS for console.believoo.com"
echo "2. Copy and edit nginx config (see Step 3 above)"
echo "3. Configure .env variables"
echo "4. SSH to Proxmox host and apply security settings"
echo "5. Test end-to-end: Order VPS → Check console → Verify masking"
echo ""
echo "For troubleshooting, check:"
echo "- /var/log/nginx/error.log"
echo "- /www/wwwroot/believoo/storage/logs/laravel.log"
echo ""
