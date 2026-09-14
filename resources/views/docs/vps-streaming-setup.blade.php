<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPS Streaming Backend Setup - BelieVoo Live Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .glass {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(20px) saturate(180%);
        }
        .gradient-text {
            background: linear-gradient(90deg, #00b7ff, #7000ff, #ff00a0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .code-block {
            background: #0d1117;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .syntax-keyword { color: #ff7b72; }
        .syntax-string { color: #a5d6ff; }
        .syntax-comment { color: #8b949e; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black text-gray-300 font-sans min-h-screen">
    
    <nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                        <i class="fas fa-broadcast-tower text-white"></i>
                    </div>
                    <span class="text-xl font-black text-white">BelieVoo <span class="gradient-text">Live Engine</span></span>
                </div>
                <a href="/docs/streaming" class="text-sm text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Docs
                </a>
            </div>
        </div>
    </nav>

    <div class="pt-24 pb-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
        
        <div class="text-center mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-green-500/30 mb-4">
                <i class="fas fa-server text-green-400"></i>
                <span class="text-xs font-bold uppercase tracking-wider text-green-300">VPS Backend Setup</span>
            </span>
            <h1 class="text-4xl md:text-5xl font-black text-white mb-4">
                Streaming Engine <span class="gradient-text">VPS Setup</span>
            </h1>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                Complete guide to deploying BelieVoo Live Streaming Engine on client VPS
            </p>
        </div>

        {{-- Prerequisites --}}
        <div class="glass rounded-2xl p-6 border border-white/10 mb-8">
            <h2 class="text-2xl font-bold text-white mb-4"><i class="fas fa-check-circle text-green-400 mr-2"></i> Prerequisites</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <p class="text-sm text-gray-400"><i class="fas fa-microchip mr-2 text-purple-400"></i> VPS with minimum 4 vCPU, 8GB RAM</p>
                    <p class="text-sm text-gray-400"><i class="fas fa-hdd mr-2 text-purple-400"></i> Ubuntu 22.04 LTS or CentOS 8</p>
                    <p class="text-sm text-gray-400"><i class="fas fa-network-wired mr-2 text-purple-400"></i> Public IP with ports 1935, 8080, 8443 open</p>
                </div>
                <div class="space-y-2">
                    <p class="text-sm text-gray-400"><i class="fas fa-shield-alt mr-2 text-purple-400"></i> Domain/subdomain pointed to VPS (e.g., live.client.com)</p>
                    <p class="text-sm text-gray-400"><i class="fas fa-lock mr-2 text-purple-400"></i> SSL certificate (Let's Encrypt recommended)</p>
                    <p class="text-sm text-gray-400"><i class="fas fa-tachometer-alt mr-2 text-purple-400"></i> 100Mbps+ bandwidth recommended</p>
                </div>
            </div>
        </div>

        {{-- Installation Steps --}}
        <div class="space-y-8">
            
            {{-- Step 1 --}}
            <div class="glass rounded-2xl p-6 border-l-4 border-purple-500">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                        <span class="font-bold text-purple-400">1</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-white mb-2">Install Docker & Docker Compose</h3>
                        <p class="text-gray-400 text-sm mb-4">BelieVoo Live Engine runs as containerized services</p>
                        
                        <div class="code-block rounded-xl overflow-hidden">
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-comment"># Update system</span>
sudo apt update && sudo apt upgrade -y

<span class="syntax-comment"># Install Docker</span>
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

<span class="syntax-comment"># Install Docker Compose</span>
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

<span class="syntax-comment"># Add user to docker group</span>
sudo usermod -aG docker $USER
newgrp docker</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="glass rounded-2xl p-6 border-l-4 border-pink-500">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-pink-500/20 flex items-center justify-center flex-shrink-0">
                        <span class="font-bold text-pink-400">2</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-white mb-2">Download BelieVoo Live Engine</h3>
                        <p class="text-gray-400 text-sm mb-4">Get the latest streaming server package</p>
                        
                        <div class="code-block rounded-xl overflow-hidden">
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-comment"># Create directory</span>
mkdir -p /opt/believoo-live
cd /opt/believoo-live

<span class="syntax-comment"># Download package</span>
wget https://cdn.believoo.com/engine/believoo-live-engine-2.0.0.tar.gz
tar -xzf believoo-live-engine-2.0.0.tar.gz

<span class="syntax-comment"># Or clone from repository</span>
git clone https://github.com/believoo/live-engine.git .

<span class="syntax-comment"># Set permissions</span>
chmod +x scripts/*.sh</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="glass rounded-2xl p-6 border-l-4 border-cyan-500">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-cyan-500/20 flex items-center justify-center flex-shrink-0">
                        <span class="font-bold text-cyan-400">3</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-white mb-2">Configure Environment</h3>
                        <p class="text-gray-400 text-sm mb-4">Set up your streaming configuration</p>
                        
                        <div class="code-block rounded-xl overflow-hidden mb-4">
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-comment"># Copy environment template</span>
cp .env.example .env

<span class="syntax-comment"># Edit configuration</span>
nano .env</code></pre>
                        </div>

                        <div class="bg-black/30 rounded-lg p-4">
                            <p class="text-sm text-gray-400 mb-2">Required configuration:</p>
                            <pre class="text-xs font-mono text-cyan-400"># Server Configuration
STREAM_DOMAIN=live.yourclient.com
PUBLIC_IP=YOUR_VPS_IP
MAX_STREAMS=10
MAX_VIEWERS_PER_STREAM=5000

# RTMP Configuration
RTMP_PORT=1935
RTMP_CHUNK_SIZE=4000

# WebRTC Configuration
WEBRTC_PORT=8080
WEBRTC_SSL_PORT=8443
STUN_SERVER=stun:stun.l.google.com:19302

# API Keys (from BelieVoo Dashboard)
BELIEVOO_APP_ID=bel_your_app_id
BELIEVOO_APP_CERT=your_certificate
BELIEVOO_API_KEY=your_api_key

# Storage
RECORDING_PATH=/var/recordings
MAX_RECORDING_SIZE_GB=100

# Database (optional, for analytics)
DB_HOST=localhost
DB_NAME=believoo_live
DB_USER=live_user
DB_PASS=secure_password</pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 4 --}}
            <div class="glass rounded-2xl p-6 border-l-4 border-green-500">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center flex-shrink-0">
                        <span class="font-bold text-green-400">4</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-white mb-2">Start Streaming Services</h3>
                        <p class="text-gray-400 text-sm mb-4">Launch all streaming components</p>
                        
                        <div class="code-block rounded-xl overflow-hidden">
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-comment"># Start all services</span>
docker-compose up -d

<span class="syntax-comment"># Check status</span>
docker-compose ps

<span class="syntax-comment"># View logs</span>
docker-compose logs -f

<span class="syntax-comment"># Check if ports are listening</span>
netstat -tlnp | grep -E '1935|8080|8443'</code></pre>
                        </div>

                        <div class="mt-4 p-4 bg-green-500/10 rounded-lg border border-green-500/30">
                            <p class="text-sm text-green-400">
                                <i class="fas fa-check-circle mr-2"></i>
                                Services should be running on:
                                <br>• RTMP: rtmp://live.yourclient.com:1935/live
                                <br>• WebRTC: https://live.yourclient.com:8443
                                <br>• HLS: https://live.yourclient.com:8080/hls/
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 5 --}}
            <div class="glass rounded-2xl p-6 border-l-4 border-yellow-500">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                        <span class="font-bold text-yellow-400">5</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-white mb-2">SSL Certificate Setup</h3>
                        <p class="text-gray-400 text-sm mb-4">Secure your streaming endpoints</p>
                        
                        <div class="code-block rounded-xl overflow-hidden">
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-comment"># Install certbot</span>
sudo apt install certbot -y

<span class="syntax-comment"># Get certificate</span>
sudo certbot certonly --standalone -d live.yourclient.com

<span class="syntax-comment"># Copy certificates to engine directory</span>
sudo cp /etc/letsencrypt/live/live.yourclient.com/fullchain.pem /opt/believoo-live/ssl/
sudo cp /etc/letsencrypt/live/live.yourclient.com/privkey.pem /opt/believoo-live/ssl/

<span class="syntax-comment"># Set permissions</span>
sudo chown -R $USER:$USER /opt/believoo-live/ssl/
chmod 600 /opt/believoo-live/ssl/*.pem

<span class="syntax-comment"># Restart services</span>
docker-compose restart</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 6 --}}
            <div class="glass rounded-2xl p-6 border-l-4 border-purple-500">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                        <span class="font-bold text-purple-400">6</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-white mb-2">Firewall Configuration</h3>
                        <p class="text-gray-400 text-sm mb-4">Open required ports for streaming</p>
                        
                        <div class="code-block rounded-xl overflow-hidden">
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-comment"># Using UFW (Ubuntu)</span>
sudo ufw allow 1935/tcp    <span class="syntax-comment"># RTMP</span>
sudo ufw allow 8080/tcp  <span class="syntax-comment"># WebRTC / HLS</span>
sudo ufw allow 8443/tcp  <span class="syntax-comment"># WebRTC Secure</span>
sudo ufw allow 80/tcp    <span class="syntax-comment"># HTTP</span>
sudo ufw allow 443/tcp   <span class="syntax-comment"># HTTPS</span>
sudo ufw reload

<span class="syntax-comment"># Or using iptables</span>
sudo iptables -A INPUT -p tcp --dport 1935 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 8080 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 8443 -j ACCEPT
sudo iptables-save</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Testing Section --}}
        <div class="glass rounded-2xl p-6 border border-white/10 mt-8">
            <h2 class="text-2xl font-bold text-white mb-4"><i class="fas fa-vial text-purple-400 mr-2"></i> Testing Your Setup</h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-white font-bold mb-2">1. Test RTMP Ingest</h4>
                    <div class="code-block rounded-lg overflow-hidden">
                        <pre class="p-3 text-xs font-mono"># Using OBS or ffmpeg
ffmpeg -re -i test.mp4 -c copy \
  -f flv rtmp://live.yourclient.com:1935/live/stream_key</pre>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-2">2. Test HLS Playback</h4>
                    <div class="code-block rounded-lg overflow-hidden">
                        <pre class="p-3 text-xs font-mono"># VLC or browser
https://live.yourclient.com:8080/hls/stream_key.m3u8</pre>
                    </div>
                </div>
            </div>

            <div class="mt-6 p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                <h4 class="text-white font-bold mb-2"><i class="fas fa-mobile-alt mr-2"></i> Test with Android App</h4>
                <p class="text-sm text-gray-400 mb-2">Use these settings in your BelieVoo Android SDK:</p>
                <ul class="text-sm text-gray-400 space-y-1">
                    <li><span class="text-purple-400">App ID:</span> bel_your_app_id</li>
                    <li><span class="text-purple-400">RTMP URL:</span> rtmp://live.yourclient.com:1935/live</li>
                    <li><span class="text-purple-400">WebRTC URL:</span> https://live.yourclient.com:8443</li>
                    <li><span class="text-purple-400">Stream Key:</span> Your generated stream key from dashboard</li>
                </ul>
            </div>
        </div>

        {{-- Troubleshooting --}}
        <div class="glass rounded-2xl p-6 border border-red-500/30 mt-8">
            <h2 class="text-2xl font-bold text-white mb-4"><i class="fas fa-wrench text-red-400 mr-2"></i> Troubleshooting</h2>
            
            <div class="space-y-4">
                <div class="p-4 bg-black/30 rounded-lg">
                    <h4 class="text-white font-bold text-sm mb-1">Stream not connecting?</h4>
                    <p class="text-xs text-gray-400">Check firewall rules and ensure ports 1935, 8080, 8443 are open. Verify docker containers are running with `docker-compose ps`</p>
                </div>
                <div class="p-4 bg-black/30 rounded-lg">
                    <h4 class="text-white font-bold text-sm mb-1">High latency?</h4>
                    <p class="text-xs text-gray-400">Enable WebRTC for sub-500ms latency. Check network bandwidth. Consider enabling CDN for global distribution.</p>
                </div>
                <div class="p-4 bg-black/30 rounded-lg">
                    <h4 class="text-white font-bold text-sm mb-1">SSL certificate errors?</h4>
                    <p class="text-xs text-gray-400">Ensure certbot completed successfully. Check certificate paths in docker-compose.yml. Verify domain DNS points to VPS IP.</p>
                </div>
            </div>
        </div>

        {{-- Support --}}
        <div class="text-center mt-12 p-6 glass rounded-2xl border border-white/10">
            <h3 class="text-xl font-bold text-white mb-2">Need Help?</h3>
            <p class="text-gray-400 text-sm mb-4">Our engineering team can assist with VPS setup</p>
            <div class="flex justify-center gap-4">
                <a href="mailto:support@believoo.com" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold text-sm hover:opacity-90 transition">
                    <i class="fas fa-envelope mr-2"></i> Contact Support
                </a>
                <a href="https://discord.believoo.com" target="_blank" class="px-6 py-3 rounded-xl bg-white/5 border border-white/10 text-gray-300 font-bold text-sm hover:bg-white/10 transition">
                    <i class="fab fa-discord mr-2"></i> Join Discord
                </a>
            </div>
        </div>

    </div>

</body>
</html>
