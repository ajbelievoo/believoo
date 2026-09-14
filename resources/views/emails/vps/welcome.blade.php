<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to BelieVoo Cloud</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
            line-height: 1.6;
        }
        
        .email-wrapper {
            max-width: 680px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .email-container {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        }
        
        .header {
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
            padding: 60px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            right: -50%;
            bottom: -50%;
            background: radial-gradient(circle, rgba(0, 183, 255, 0.1) 0%, transparent 70%);
            animation: shimmer 8s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, -30px) rotate(5deg); }
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #a5a5a5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .logo span {
            color: #00b7ff;
            -webkit-text-fill-color: #00b7ff;
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        
        .welcome-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 400;
            position: relative;
            z-index: 1;
        }
        
        .content {
            padding: 50px 40px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #86868b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        
        .credentials-card {
            background: linear-gradient(135deg, #f5f5f7 0%, #ffffff 100%);
            border: 1px solid #e8e8ed;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
        }
        
        .credential-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #e8e8ed;
        }
        
        .credential-row:last-child {
            border-bottom: none;
        }
        
        .credential-label {
            font-size: 0.9rem;
            color: #86868b;
            font-weight: 500;
        }
        
        .credential-value {
            font-size: 1rem;
            color: #1d1d1f;
            font-weight: 600;
            font-family: 'SF Mono', Monaco, monospace;
        }
        
        .credential-value.password {
            background: linear-gradient(135deg, #00b7ff, #7000ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #00b7ff 0%, #0066cc 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 32px;
        }
        
        .highlight-box h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .highlight-box p {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .spec-card {
            background: #f5f5f7;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .spec-icon {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }
        
        .spec-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 4px;
        }
        
        .spec-label {
            font-size: 0.8rem;
            color: #86868b;
        }
        
        .cta-button {
            display: inline-block;
            background: #0071e3;
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            text-align: center;
        }
        
        .cta-button:hover {
            background: #0077ed;
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(0, 113, 227, 0.3);
        }
        
        .footer {
            background: #f5f5f7;
            padding: 40px;
            text-align: center;
        }
        
        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 16px;
        }
        
        .footer-logo span {
            color: #00b7ff;
        }
        
        .footer-text {
            font-size: 0.875rem;
            color: #86868b;
            margin-bottom: 8px;
        }
        
        .footer-links {
            margin-top: 20px;
        }
        
        .footer-links a {
            color: #0071e3;
            text-decoration: none;
            font-size: 0.875rem;
            margin: 0 12px;
        }
        
        @media (max-width: 600px) {
            .specs-grid {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .credentials-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            {{-- Header --}}
            <div class="header">
                <div class="logo">Belie<span>Voo</span></div>
                <h1 class="welcome-title">Your VPS is Ready!</h1>
                <p class="welcome-subtitle">Welcome to the future of cloud hosting</p>
            </div>
            
            {{-- Content --}}
            <div class="content">
                {{-- Welcome Message --}}
                <div class="section">
                    <h2 style="font-size: 1.75rem; font-weight: 600; margin-bottom: 16px; color: #1d1d1f;">
                        Hello {{ $userName ?? 'there' }} 👋
                    </h2>
                    <p style="font-size: 1.1rem; color: #515154; line-height: 1.7; margin-bottom: 24px;">
                        Your virtual private server has been successfully provisioned and is now running. 
                        All systems are operational and your server is ready for deployment.
                    </p>
                </div>
                
                {{-- Server Credentials --}}
                <div class="section">
                    <div class="section-title">Server Credentials</div>
                    <div class="credentials-card">
                        <div class="credential-row">
                            <span class="credential-label">Server IP Address</span>
                            <span class="credential-value">{{ $ipAddress ?? '192.168.1.100' }}</span>
                        </div>
                        <div class="credential-row">
                            <span class="credential-label">Username</span>
                            <span class="credential-value">root</span>
                        </div>
                        <div class="credential-row">
                            <span class="credential-label">Password</span>
                            <span class="credential-value password">{{ $password ?? 'AutoGenerated123!' }}</span>
                        </div>
                        <div class="credential-row">
                            <span class="credential-label">Control Panel</span>
                            <span class="credential-value">{{ $controlPanel ?? 'aaPanel' }}</span>
                        </div>
                    </div>
                    
                    <div class="highlight-box">
                        <h3>🔒 Auto-Secured</h3>
                        <p>Your server comes with DDoS protection, firewall, and SSL pre-configured</p>
                    </div>
                </div>
                
                {{-- Server Specs --}}
                <div class="section">
                    <div class="section-title">Your Configuration</div>
                    <div class="specs-grid">
                        <div class="spec-card">
                            <div class="spec-icon">🖥️</div>
                            <div class="spec-value">{{ $cpuCores ?? '2' }} vCPU</div>
                            <div class="spec-label">Cores</div>
                        </div>
                        <div class="spec-card">
                            <div class="spec-icon">💾</div>
                            <div class="spec-value">{{ $ram ?? '4' }} GB</div>
                            <div class="spec-label">RAM</div>
                        </div>
                        <div class="spec-card">
                            <div class="spec-icon">💿</div>
                            <div class="spec-value">{{ $disk ?? '50' }} GB</div>
                            <div class="spec-label">NVMe SSD</div>
                        </div>
                        <div class="spec-card">
                            <div class="spec-icon">🌐</div>
                            <div class="spec-value">{{ $location ?? 'Singapore' }}</div>
                            <div class="spec-label">Location</div>
                        </div>
                    </div>
                </div>
                
                {{-- Quick Access --}}
                <div class="section" style="text-align: center;">
                    <div class="section-title">Quick Access</div>
                    <a href="{{ $panelUrl ?? '#' }}" class="cta-button">
                        Open Control Panel →
                    </a>
                    <p style="margin-top: 16px; font-size: 0.875rem; color: #86868b;">
                        Or SSH directly: <code style="background: #f5f5f7; padding: 4px 8px; border-radius: 6px;">ssh root@{{ $ipAddress ?? 'your-ip' }}</code>
                    </p>
                </div>
                
                {{-- Getting Started --}}
                <div class="section" style="background: #f5f5f7; padding: 24px; border-radius: 16px; margin-top: 32px;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 16px; color: #1d1d1f;">
                        🚀 Getting Started
                    </h3>
                    <ul style="list-style: none; font-size: 0.95rem; color: #515154; line-height: 2;">
                        <li>✓ Connect via SSH using the credentials above</li>
                        <li>✓ Access your control panel for 1-click app installs</li>
                        <li>✓ Configure your firewall and security settings</li>
                        <li>✓ Deploy your first website or application</li>
                    </ul>
                </div>
            </div>
            
            {{-- Footer --}}
            <div class="footer">
                <div class="footer-logo">Belie<span>Voo</span></div>
                <p class="footer-text">Premium Cloud Infrastructure</p>
                <p class="footer-text">24/7 Support: support@believoo.com</p>
                <div class="footer-links">
                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                    <a href="{{ url('/support') }}">Support</a>
                    <a href="{{ url('/docs') }}">Documentation</a>
                </div>
                <p style="margin-top: 24px; font-size: 0.75rem; color: #a1a1a6;">
                    © {{ date('Y') }} BelieVoo Cloud. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
