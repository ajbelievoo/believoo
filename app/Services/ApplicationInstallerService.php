<?php

namespace App\Services;

use App\Models\ProxmoxVm;
use Illuminate\Support\Facades\Log;

class ApplicationInstallerService
{
    /**
     * Available applications/panels for one-click install
     */
    public const AVAILABLE_APPS = [
        'aapanel' => [
            'name' => 'aaPanel',
            'description' => 'Best for beginners - Free hosting control panel',
            'os' => ['ubuntu-20.04', 'ubuntu-22.04', 'debian-11', 'centos-7', 'centos-8'],
            'port' => '8888',
            'username' => 'admin',
            'icon' => 'fa-server',
            'color' => '#22c55e',
        ],
        'cyberpanel' => [
            'name' => 'CyberPanel',
            'description' => 'OpenLiteSpeed - Fast & LiteSpeed Cache',
            'os' => ['ubuntu-20.04', 'ubuntu-22.04', 'centos-7', 'centos-8', 'almalinux-8', 'almalinux-9'],
            'port' => '8090',
            'username' => 'admin',
            'icon' => 'fa-bolt',
            'color' => '#8b5cf6',
        ],
        'cloudpanel' => [
            'name' => 'CloudPanel',
            'description' => 'Fastest for PHP/Next.js - Modern & Clean',
            'os' => ['ubuntu-22.04', 'debian-11'],
            'port' => '8443',
            'username' => 'admin',
            'icon' => 'fa-cloud',
            'color' => '#00b7ff',
        ],
        'fastpanel' => [
            'name' => 'FastPanel',
            'description' => 'Great free alternative - Russian made',
            'os' => ['ubuntu-20.04', 'ubuntu-22.04', 'debian-11', 'centos-7', 'centos-8'],
            'port' => '8888',
            'username' => 'fastuser',
            'icon' => 'fa-rocket',
            'color' => '#f59e0b',
        ],
        'hestiacp' => [
            'name' => 'HestiaCP',
            'description' => 'Free open-source fork of VestaCP',
            'os' => ['ubuntu-20.04', 'ubuntu-22.04', 'debian-11'],
            'port' => '8083',
            'username' => 'admin',
            'icon' => 'fa-shield-alt',
            'color' => '#ef4444',
        ],
        'webmin' => [
            'name' => 'Webmin',
            'description' => 'Classic server management - All Linux distros',
            'os' => ['ubuntu-20.04', 'ubuntu-22.04', 'debian-11', 'centos-7', 'centos-8', 'almalinux-8', 'almalinux-9'],
            'port' => '10000',
            'username' => 'root',
            'icon' => 'fa-cog',
            'color' => '#6b7280',
        ],
        'docker' => [
            'name' => 'Docker + Portainer',
            'description' => 'For developers - Container management',
            'os' => ['ubuntu-20.04', 'ubuntu-22.04', 'debian-11', 'centos-7', 'centos-8'],
            'port' => '9443',
            'username' => 'admin',
            'icon' => 'fa-docker',
            'color' => '#0ea5e9',
        ],
        'cpanel' => [
            'name' => 'cPanel & WHM',
            'description' => 'Industry standard - License required',
            'os' => ['almalinux-8', 'almalinux-9', 'centos-7', 'centos-8'],
            'port' => '2087',
            'username' => 'root',
            'icon' => 'fa-cpanel',
            'color' => '#ff6c2c',
        ],
        'plesk' => [
            'name' => 'Plesk Obsidian',
            'description' => 'Professional hosting panel - License required',
            'os' => ['ubuntu-20.04', 'ubuntu-22.04', 'debian-11', 'centos-7', 'centos-8', 'almalinux-8', 'almalinux-9'],
            'port' => '8443',
            'username' => 'admin',
            'icon' => 'fa-plesk',
            'color' => '#00aeef',
        ],
    ];

    /**
     * Generate cloud-init user data for the selected application
     */
    public function generateCloudInitScript(string $app, array $config = []): string
    {
        $hostname = $config['hostname'] ?? 'vps-' . time();
        $password = $config['password'] ?? $this->generateSecurePassword();
        $email = $config['email'] ?? 'admin@' . $hostname;

        $commonHeader = $this->getCloudInitHeader($hostname, $password, $email);
        $appScript = $this->getAppInstallScript($app, $password, $email);

        return $commonHeader . "\n" . $appScript;
    }

    /**
     * Get cloud-init header with common configuration
     */
    private function getCloudInitHeader(string $hostname, string $password, string $email): string
    {
        return <<<YAML
#cloud-config
hostname: {$hostname}
manage_etc_hosts: true
fqdn: {$hostname}

# Set root password
chpasswd:
  list: |
    root:{$password}
  expire: False

# Enable password authentication for first login
ssh_pwauth: true

# Create believoo user with sudo
users:
  - name: believoo
    sudo: ALL=(ALL) NOPASSWD:ALL
    shell: /bin/bash
    passwd: "$(openssl passwd -1 '{$password}')"

# Install packages
package_update: true
package_upgrade: true
packages:
  - curl
  - wget
  - nano
  - htop
  - net-tools
  - ufw
  - fail2ban

# Run installation script
runcmd:
  - echo "=== Starting Believoo App Installer ===" > /var/log/believoo-install.log
  - mkdir -p /opt/believoo
YAML;
    }

    /**
     * Get installation script for specific app
     */
    private function getAppInstallScript(string $app, string $password, string $email): string
    {
        $scripts = [
            'aapanel' => $this->getAaPanelScript($password),
            'cyberpanel' => $this->getCyberPanelScript($password),
            'cloudpanel' => $this->getCloudPanelScript($password),
            'fastpanel' => $this->getFastPanelScript($password),
            'hestiacp' => $this->getHestiaScript($password, $email),
            'webmin' => $this->getWebminScript($password),
            'docker' => $this->getDockerScript($password),
            'cpanel' => $this->getCpanelScript($password),
            'plesk' => $this->getPleskScript($password, $email),
        ];

        return $scripts[$app] ?? '';
    }

    /**
     * aaPanel Installation Script
     */
    private function getAaPanelScript(string $password): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # aaPanel Installation
    echo "Installing aaPanel..." >> /var/log/believoo-install.log
    
    # Install aaPanel
    URL=https://www.aapanel.com/script/install_7.0_en.sh
    if [ -f /usr/bin/curl ];then
      curl -ksSO "$URL" && bash install_7.0_en.sh aapanel
    else
      wget --no-check-certificate -O install_7.0_en.sh "$URL" && bash install_7.0_en.sh aapanel
    fi
    
    # Get panel info
    BT_PANEL_INFO=$(cat /www/server/panel/data/default.pl 2>/dev/null || echo "")
    BT_USERNAME=$(cat /www/server/panel/data/username.pl 2>/dev/null || echo "admin")
    BT_PASSWORD=$(cat /www/server/panel/data/default.pl 2>/dev/null || echo "$password")
    
    # Save credentials
    echo "PANEL_TYPE=aapanel" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):8888" >> /opt/believoo/panel-info.txt
    echo "USERNAME=$BT_USERNAME" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$BT_PASSWORD" >> /opt/believoo/panel-info.txt
    
    echo "aaPanel installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * CyberPanel Installation Script
     */
    private function getCyberPanelScript(string $password): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # CyberPanel Installation
    echo "Installing CyberPanel..." >> /var/log/believoo-install.log
    
    # Update system
    apt-get update -y || yum update -y
    
    # Install CyberPanel
    sh <(curl https://cyberpanel.net/install.sh || wget -O - https://cyberpanel.net/install.sh) -v ols -p $password -a
    
    # Save credentials
    echo "PANEL_TYPE=cyberpanel" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):8090" >> /opt/believoo/panel-info.txt
    echo "USERNAME=admin" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$password" >> /opt/believoo/panel-info.txt
    
    echo "CyberPanel installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * CloudPanel Installation Script
     */
    private function getCloudPanelScript(string $password): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # CloudPanel Installation
    echo "Installing CloudPanel..." >> /var/log/believoo-install.log
    
    # Install required packages
    apt-get update
    apt-get install -y wget curl openssh-server
    
    # Download and install CloudPanel
    curl -sSL https://installer.cloudpanel.io/ce/v2/install.sh | sudo bash
    
    # Save credentials
    echo "PANEL_TYPE=cloudpanel" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):8443" >> /opt/believoo/panel-info.txt
    echo "USERNAME=admin" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$password" >> /opt/believoo/panel-info.txt
    
    echo "CloudPanel installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * FastPanel Installation Script
     */
    private function getFastPanelScript(string $password): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # FastPanel Installation
    echo "Installing FastPanel..." >> /var/log/believoo-install.log
    
    # Install FastPanel
    wget http://repo.fastpanel.direct/install_fastpanel.sh -O - | bash -
    
    # Get credentials
    FP_PASSWORD=$(cat /usr/local/fastpanel/conf/fastpanel2.conf 2>/dev/null | grep -oP 'password=\K[^"]+' || echo "$password")
    
    # Save credentials
    echo "PANEL_TYPE=fastpanel" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):8888" >> /opt/believoo/panel-info.txt
    echo "USERNAME=fastuser" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$FP_PASSWORD" >> /opt/believoo/panel-info.txt
    
    echo "FastPanel installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * HestiaCP Installation Script
     */
    private function getHestiaScript(string $password, string $email): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # HestiaCP Installation
    echo "Installing HestiaCP..." >> /var/log/believoo-install.log
    
    # Download installer
    wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh
    
    # Install HestiaCP
    bash hst-install.sh -y no -e $email -p $password --hostname $(hostname -f)
    
    # Save credentials
    echo "PANEL_TYPE=hestiacp" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):8083" >> /opt/believoo/panel-info.txt
    echo "USERNAME=admin" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$password" >> /opt/believoo/panel-info.txt
    
    echo "HestiaCP installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * Webmin Installation Script
     */
    private function getWebminScript(string $password): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # Webmin Installation
    echo "Installing Webmin..." >> /var/log/believoo-install.log
    
    # Add Webmin repository
    if [ -f /etc/debian_version ]; then
      # Debian/Ubuntu
      curl -fsSL https://download.webmin.com/jcameron-key.asc | sudo gpg --dearmor -o /usr/share/keyrings/webmin.gpg
      echo "deb [signed-by=/usr/share/keyrings/webmin.gpg] https://download.webmin.com/download/repository sarge contrib" | sudo tee /etc/apt/sources.list.d/webmin.list
      sudo apt-get update
      sudo apt-get install -y webmin
    else
      # RHEL/CentOS
      sudo rpm -Uvh https://download.webmin.com/download/webmin-current.rpm
    fi
    
    # Set root password for Webmin
    echo "root:$password" | sudo chpasswd
    
    # Save credentials
    echo "PANEL_TYPE=webmin" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):10000" >> /opt/believoo/panel-info.txt
    echo "USERNAME=root" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$password" >> /opt/believoo/panel-info.txt
    
    echo "Webmin installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * Docker + Portainer Installation Script
     */
    private function getDockerScript(string $password): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # Docker & Portainer Installation
    echo "Installing Docker & Portainer..." >> /var/log/believoo-install.log
    
    # Install Docker
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker believoo
    
    # Install Docker Compose
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    
    # Install Portainer
    sudo docker volume create portainer_data
    sudo docker run -d -p 8000:8000 -p 9443:9443 --name portainer --restart=always \
      -v /var/run/docker.sock:/var/run/docker.sock \
      -v portainer_data:/data portainer/portainer-ce:latest
    
    # Wait for Portainer to start
    sleep 10
    
    # Create initial admin user via API
    PORTAINER_URL="https://localhost:9443"
    
    # Save credentials
    echo "PANEL_TYPE=portainer" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):9443" >> /opt/believoo/panel-info.txt
    echo "USERNAME=admin" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$password" >> /opt/believoo/panel-info.txt
    echo "NOTE: Create admin on first login" >> /opt/believoo/panel-info.txt
    
    echo "Docker & Portainer installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * cPanel Installation Script
     */
    private function getCpanelScript(string $password): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # cPanel Installation - License Required
    echo "Installing cPanel/WHM..." >> /var/log/believoo-install.log
    
    # Disable NetworkManager
    systemctl stop NetworkManager
    systemctl disable NetworkManager
    
    # Install cPanel
    cd /home && curl -o latest -L https://securedownloads.cpanel.net/latest
    sh latest
    
    # Set root password
    echo "root:$password" | chpasswd
    
    # Save credentials
    echo "PANEL_TYPE=cpanel" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):2087" >> /opt/believoo/panel-info.txt
    echo "USERNAME=root" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$password" >> /opt/believoo/panel-info.txt
    echo "NOTE: License activation required" >> /opt/believoo/panel-info.txt
    
    echo "cPanel installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * Plesk Installation Script
     */
    private function getPleskScript(string $password, string $email): string
    {
        return <<<'SCRIPT'

runcmd:
  - |
    # Plesk Installation - License Required
    echo "Installing Plesk Obsidian..." >> /var/log/believoo-install.log
    
    # Install Plesk
    sh <(curl https://autoinstall.plesk.com/one-click-installer || wget -O - https://autoinstall.plesk.com/one-click-installer) \
      --source https://autoinstall.plesk.com/plesk-installer \
      --select-product-id plesk \
      --select-release-latest \
      --installation-type Full \
      --notify-email $email
    
    # Set admin password
    /usr/local/psa/bin/admin --set-password -passwd $password
    
    # Save credentials
    echo "PANEL_TYPE=plesk" > /opt/believoo/panel-info.txt
    echo "PANEL_URL=https://$(hostname -I | awk '{print $1}'):8443" >> /opt/believoo/panel-info.txt
    echo "USERNAME=admin" >> /opt/believoo/panel-info.txt
    echo "PASSWORD=$password" >> /opt/believoo/panel-info.txt
    echo "NOTE: License activation required" >> /opt/believoo/panel-info.txt
    
    echo "Plesk installation completed!" >> /var/log/believoo-install.log
SCRIPT;
    }

    /**
     * Generate a secure random password
     */
    public function generateSecurePassword(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }

    /**
     * Get app details
     */
    public function getAppDetails(string $app): ?array
    {
        return self::AVAILABLE_APPS[$app] ?? null;
    }

    /**
     * Check if app is compatible with OS
     */
    public function isCompatible(string $app, string $os): bool
    {
        $appDetails = $this->getAppDetails($app);
        
        if (!$appDetails) {
            return false;
        }
        
        // Normalize OS name
        $normalizedOs = strtolower(str_replace(['-', '_', ' '], '', $os));
        
        foreach ($appDetails['os'] as $supportedOs) {
            $normalizedSupported = strtolower(str_replace(['-', '_', ' '], '', $supportedOs));
            if (str_contains($normalizedOs, $normalizedSupported) || 
                str_contains($normalizedSupported, $normalizedOs)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get all available apps
     */
    public function getAllApps(): array
    {
        return self::AVAILABLE_APPS;
    }

    /**
     * Update VM with installation progress
     */
    public function updateInstallationProgress(ProxmoxVm $vm, string $status, ?array $credentials = null): void
    {
        $updateData = [
            'panel_status' => $status,
        ];

        if ($status === 'installed') {
            $updateData['panel_installed_at'] = now();
            
            if ($credentials) {
                $updateData['panel_login_url'] = $credentials['url'] ?? null;
                $updateData['panel_username'] = $credentials['username'] ?? null;
                $updateData['panel_password'] = $credentials['password'] ?? null;
                $updateData['panel_port'] = $credentials['port'] ?? null;
            }
        }

        $vm->update($updateData);
        
        Log::info('Panel installation status updated', [
            'vm_id' => $vm->id,
            'vmid' => $vm->vmid,
            'status' => $status,
        ]);
    }

    /**
     * Build nameservers array
     */
    public function getDefaultNameservers(): array
    {
        return [
            'ns1.believoo.com',
            'ns2.believoo.com',
        ];
    }
}
