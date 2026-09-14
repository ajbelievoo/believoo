<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BelieVoo Live Engine - Developer Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'electric-blue': '#00B7FF',
                        'dark': '#0a0a0a',
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                        'mono': ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        .glass {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
        }
        
        .glass-premium {
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.8) 0%, rgba(17, 17, 17, 0.9) 100%);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }
        
        .glass-premium:hover {
            border-color: rgba(0, 183, 255, 0.3);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(0, 183, 255, 0.1);
        }
        
        .code-block {
            background: #0d1117;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .syntax-keyword { color: #ff7b72; }
        .syntax-string { color: #a5d6ff; }
        .syntax-function { color: #d2a8ff; }
        .syntax-comment { color: #8b949e; }
        .syntax-number { color: #79c0ff; }
        
        .gradient-text {
            background: linear-gradient(90deg, #00b7ff, #7000ff, #ff00a0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .nav-link {
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #00b7ff, #7000ff);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .scroll-smooth {
            scroll-behavior: smooth;
        }

        /* Animated background gradient */
        .bg-gradient-animate {
            background: linear-gradient(-45deg, #0a0a0a, #1a1a2e, #16213e, #0a0a0a);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Glow effect */
        .glow {
            box-shadow: 0 0 20px rgba(0, 183, 255, 0.3);
        }

        .glow-purple {
            box-shadow: 0 0 20px rgba(112, 0, 255, 0.3);
        }

        /* Tab transitions */
        .tab-content {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gradient-animate text-gray-300 font-sans min-h-screen scroll-smooth">
    
    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                        <i class="fas fa-broadcast-tower text-white"></i>
                    </div>
                    <span class="text-xl font-black text-white">BelieVoo <span class="gradient-text">Live Docs</span></span>
                </div>
                
                <div class="hidden md:flex items-center gap-8">
                    <a href="#quickstart" class="nav-link text-sm font-bold uppercase tracking-wider hover:text-white transition">Quick Start</a>
                    <a href="#sdks" class="nav-link text-sm font-bold uppercase tracking-wider hover:text-white transition">SDKs</a>
                    <a href="#api" class="nav-link text-sm font-bold uppercase tracking-wider hover:text-white transition">API Reference</a>
                    <a href="#examples" class="nav-link text-sm font-bold uppercase tracking-wider hover:text-white transition">Examples</a>
                </div>

                <a href="{{ route('client.dashboard') }}" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-sm font-bold uppercase tracking-wider hover:bg-white/10 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Portal
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <div class="max-w-5xl mx-auto text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-purple-500/30 mb-6">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-purple-300">v2.0 API Available</span>
            </div>
            
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight">
                Build Live Experiences<br>
                <span class="gradient-text">At Global Scale</span>
            </h1>
            
            <p class="text-xl text-gray-400 max-w-3xl mx-auto mb-10">
                BelieVoo Live Engine provides ultra-low latency live streaming with WebRTC and RTMP support. 
                Stream to millions with our enterprise-grade infrastructure. Choose between VPS-Embedded or Cloud-Hosted delivery methods.
            </p>

            {{-- Delivery Method Selection --}}
            <div class="flex flex-wrap justify-center gap-4 mb-10">
                <div class="glass-premium rounded-xl p-4 border border-purple-500/30">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center">
                            <i class="fas fa-server text-purple-400 text-sm"></i>
                        </div>
                        <span class="font-bold text-purple-300">VPS-Embedded</span>
                    </div>
                    <p class="text-xs text-gray-400">Self-hosted on your VPS • Unlimited viewers</p>
                </div>
                <div class="glass-premium rounded-xl p-4 border border-blue-500/30">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                            <i class="fas fa-cloud text-blue-400 text-sm"></i>
                        </div>
                        <span class="font-bold text-blue-300">Cloud-Hosted</span>
                    </div>
                    <p class="text-xs text-gray-400">BelieVoo cluster • Managed infrastructure</p>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="#quickstart" class="px-8 py-4 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold uppercase tracking-wider hover:opacity-90 transition glow-purple">
                    <i class="fas fa-rocket mr-2"></i> Get Started
                </a>
                <button onclick="copyCode('initExample')" class="px-8 py-4 rounded-xl glass border border-white/20 text-white font-bold uppercase tracking-wider hover:bg-white/10 transition">
                    <i class="fas fa-copy mr-2"></i> Copy Quick Code
                </button>
            </div>
        </div>

        {{-- Floating Elements --}}
        <div class="absolute top-1/4 left-10 w-32 h-32 rounded-full bg-purple-500/10 blur-3xl"></div>
        <div class="absolute bottom-1/4 right-10 w-48 h-48 rounded-full bg-pink-500/10 blur-3xl"></div>
    </section>

    {{-- Features Grid --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @php
                $features = [
                    ['icon' => 'fa-bolt', 'color' => 'yellow', 'title' => '<50ms Latency', 'desc' => 'WebRTC ultra-low latency streaming'],
                    ['icon' => 'fa-globe', 'color' => 'blue', 'title' => 'Global CDN', 'desc' => 'Edge servers in 50+ locations'],
                    ['icon' => 'fa-shield-alt', 'color' => 'green', 'title' => 'Secure', 'desc' => 'End-to-end encryption & token auth'],
                    ['icon' => 'fa-chart-line', 'color' => 'purple', 'title' => 'Analytics', 'desc' => 'Real-time streaming metrics'],
                ];
                @endphp
                
                @foreach($features as $feature)
                <div class="glass-premium rounded-2xl p-6 group hover:scale-105 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-{{ $feature['color'] }}-500/20 flex items-center justify-center mb-4 group-hover:glow transition-all">
                        <i class="fas {{ $feature['icon'] }} text-{{ $feature['color'] }}-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-gray-400">{{ $feature['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Quick Start --}}
    <section id="quickstart" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-white mb-4 uppercase tracking-tight">Quick Start</h2>
                <p class="text-gray-400">Get your first stream running in under 5 minutes</p>
            </div>

            <div class="space-y-6">
                {{-- Step 1 --}}
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-purple-500">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                            <span class="font-bold text-purple-400">1</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-2">Initialize the SDK</h3>
                            <p class="text-sm text-gray-400 mb-4">Add your App ID and initialize the BelieVoo Live client</p>
                            
                            <div class="code-block rounded-xl overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2 bg-white/5 border-b border-white/10">
                                    <span class="text-xs font-mono text-gray-400">JavaScript</span>
                                    <button onclick="copyCode('initCode')" class="text-xs text-gray-400 hover:text-white transition">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <pre id="initCode" class="p-4 overflow-x-auto text-sm font-mono leading-relaxed"><code><span class="syntax-keyword">import</span> { BelieVooLive } <span class="syntax-keyword">from</span> <span class="syntax-string">'@believoo/live-sdk'</span>;

<span class="syntax-comment">// Initialize with your App ID</span>
<span class="syntax-keyword">const</span> client = <span class="syntax-keyword">new</span> <span class="syntax-function">BelieVooLive</span>({
  appId: <span class="syntax-string">'bel_your_app_id_here'</span>,
  appCertificate: <span class="syntax-string">'your_app_certificate'</span>,
  mode: <span class="syntax-string">'live'</span>
});

<span class="syntax-comment">// Join a channel</span>
<span class="syntax-keyword">await</span> client.<span class="syntax-function">join</span>({
  channel: <span class="syntax-string">'my-live-stream'</span>,
  token: <span class="syntax-string">'your_stream_token'</span>,
  uid: <span class="syntax-number">12345</span>
});</code></pre>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-pink-500">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-pink-500/20 flex items-center justify-center flex-shrink-0">
                            <span class="font-bold text-pink-400">2</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-2">Start Broadcasting</h3>
                            <p class="text-sm text-gray-400 mb-4">Get user media and start publishing your stream</p>
                            
                            <div class="code-block rounded-xl overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2 bg-white/5 border-b border-white/10">
                                    <span class="text-xs font-mono text-gray-400">JavaScript</span>
                                    <button onclick="copyCode('publishCode')" class="text-xs text-gray-400 hover:text-white transition">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <pre id="publishCode" class="p-4 overflow-x-auto text-sm font-mono leading-relaxed"><code><span class="syntax-comment">// Get camera and microphone</span>
<span class="syntax-keyword">const</span> stream = <span class="syntax-keyword">await</span> navigator.mediaDevices.<span class="syntax-function">getUserMedia</span>({
  video: { width: <span class="syntax-number">1920</span>, height: <span class="syntax-number">1080</span> },
  audio: <span class="syntax-keyword">true</span>
});

<span class="syntax-comment">// Display local video</span>
document.<span class="syntax-function">getElementById</span>(<span class="syntax-string">'local-video'</span>).srcObject = stream;

<span class="syntax-comment">// Publish stream</span>
<span class="syntax-keyword">await</span> client.<span class="syntax-function">publish</span>(stream);

console.<span class="syntax-function">log</span>(<span class="syntax-string">'Live streaming started!'</span>);</code></pre>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-cyan-500">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-cyan-500/20 flex items-center justify-center flex-shrink-0">
                            <span class="font-bold text-cyan-400">3</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-2">Subscribe & View</h3>
                            <p class="text-sm text-gray-400 mb-4">Subscribe to remote streams to view broadcasts</p>
                            
                            <div class="code-block rounded-xl overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2 bg-white/5 border-b border-white/10">
                                    <span class="text-xs font-mono text-gray-400">JavaScript</span>
                                    <button onclick="copyCode('subscribeCode')" class="text-xs text-gray-400 hover:text-white transition">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <pre id="subscribeCode" class="p-4 overflow-x-auto text-sm font-mono leading-relaxed"><code><span class="syntax-comment">// Listen for new streams</span>
client.<span class="syntax-function">on</span>(<span class="syntax-string">'stream-added'</span>, <span class="syntax-keyword">async</span> (event) => {
  <span class="syntax-keyword">const</span> remoteStream = event.stream;
  
  <span class="syntax-comment">// Subscribe to the stream</span>
  <span class="syntax-keyword">await</span> client.<span class="syntax-function">subscribe</span>(remoteStream);
  
  <span class="syntax-comment">// Display remote video</span>
  <span class="syntax-keyword">const</span> videoElement = document.<span class="syntax-function">getElementById</span>(<span class="syntax-string">'remote-video'</span>);
  videoElement.srcObject = remoteStream;
});

<span class="syntax-comment">// Handle stream removal</span>
client.<span class="syntax-function">on</span>(<span class="syntax-string">'stream-removed'</span>, (event) => {
  console.<span class="syntax-function">log</span>(<span class="syntax-string">`Stream ${event.uid} left`</span>);
});</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SDK Tabs Section --}}
    <section id="sdks" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-white mb-4 uppercase tracking-tight">Platform SDKs</h2>
                <p class="text-gray-400">Native SDKs for every platform</p>
            </div>

            {{-- Tab Navigation --}}
            <div class="flex justify-center mb-8">
                <div class="glass rounded-2xl p-1 flex gap-1">
                    <button onclick="switchTab('flutter')" id="tab-flutter" class="px-6 py-3 rounded-xl text-sm font-bold uppercase tracking-wider transition-all bg-purple-500/20 text-purple-300 border border-purple-500/30">
                        <i class="fab fa-flutter mr-2"></i> Flutter
                    </button>
                    <button onclick="switchTab('react')" id="tab-react" class="px-6 py-3 rounded-xl text-sm font-bold uppercase tracking-wider transition-all text-gray-400 hover:text-white">
                        <i class="fab fa-react mr-2"></i> React
                    </button>
                    <button onclick="switchTab('android')" id="tab-android" class="px-6 py-3 rounded-xl text-sm font-bold uppercase tracking-wider transition-all text-gray-400 hover:text-white">
                        <i class="fab fa-android mr-2"></i> Android
                    </button>
                </div>
            </div>

            {{-- Flutter Tab --}}
            <div id="content-flutter" class="tab-content">
                <div class="glass-premium rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
                            <i class="fab fa-flutter text-blue-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Flutter SDK</h3>
                            <p class="text-sm text-gray-400">Cross-platform streaming for iOS & Android</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="code-block rounded-xl overflow-hidden">
                            <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                                <span class="text-xs font-mono text-gray-400">pubspec.yaml</span>
                            </div>
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-comment"># Add dependency</span>
dependencies:
  believoo_live: ^2.0.0</code></pre>
                        </div>

                        <div class="code-block rounded-xl overflow-hidden">
                            <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                                <span class="text-xs font-mono text-gray-400">main.dart</span>
                            </div>
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-keyword">import</span> <span class="syntax-string">'package:believoo_live/believoo_live.dart'</span>;

<span class="syntax-keyword">class</span> <span class="syntax-function">StreamPage</span> <span class="syntax-keyword">extends</span> <span class="syntax-function">StatefulWidget</span> {
  <span class="syntax-keyword">@override</span>
  <span class="syntax-function">_StreamPageState</span> <span class="syntax-function">createState</span>() => <span class="syntax-function">_StreamPageState</span>();
}

<span class="syntax-keyword">class</span> <span class="syntax-function">_StreamPageState</span> <span class="syntax-keyword">extends</span> <span class="syntax-function">State</span><<span class="syntax-function">StreamPage</span>> {
  <span class="syntax-keyword">final</span> BelieVooLive _live = <span class="syntax-function">BelieVooLive</span>();

  <span class="syntax-keyword">void</span> <span class="syntax-function">initLive</span>() <span class="syntax-keyword">async</span> {
    <span class="syntax-keyword">await</span> _live.<span class="syntax-function">initialize</span>(
      appId: <span class="syntax-string">'bel_your_app_id'</span>,
      appCertificate: <span class="syntax-string">'your_certificate'</span>,
    );

    <span class="syntax-keyword">await</span> _live.<span class="syntax-function">joinChannel</span>(
      channelId: <span class="syntax-string">'my-stream'</span>,
      token: <span class="syntax-string">'your_token'</span>,
      uid: <span class="syntax-number">0</span>,
    );
  }

  <span class="syntax-keyword">@override</span>
  <span class="syntax-function">Widget</span> <span class="syntax-function">build</span>(<span class="syntax-function">BuildContext</span> context) {
    <span class="syntax-keyword">return</span> <span class="syntax-function">Scaffold</span>(
      body: <span class="syntax-function">BelieVooLiveView</span>(
        live: _live,
        mode: <span class="syntax-function">LiveMode</span>.broadcast,
      ),
    );
  }
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- React Tab --}}
            <div id="content-react" class="tab-content hidden">
                <div class="glass-premium rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-cyan-500/20 flex items-center justify-center">
                            <i class="fab fa-react text-cyan-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">React SDK</h3>
                            <p class="text-sm text-gray-400">React hooks for web streaming</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="code-block rounded-xl overflow-hidden">
                            <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                                <span class="text-xs font-mono text-gray-400">Install</span>
                            </div>
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code>npm install @believoo/react-live</code></pre>
                        </div>

                        <div class="code-block rounded-xl overflow-hidden">
                            <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                                <span class="text-xs font-mono text-gray-400">StreamComponent.jsx</span>
                            </div>
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-keyword">import</span> { useBelieVooLive, LivePlayer, LivePublisher } <span class="syntax-keyword">from</span> <span class="syntax-string">'@believoo/react-live'</span>;

<span class="syntax-keyword">function</span> <span class="syntax-function">StreamComponent</span>() {
  <span class="syntax-keyword">const</span> { client, isConnected } = <span class="syntax-function">useBelieVooLive</span>({
    appId: <span class="syntax-string">'bel_your_app_id'</span>,
    appCertificate: <span class="syntax-string">'your_certificate'</span>,
  });

  <span class="syntax-keyword">const</span> <span class="syntax-function">startStream</span> = <span class="syntax-keyword">async</span> () => {
    <span class="syntax-keyword">await</span> client.<span class="syntax-function">join</span>({
      channel: <span class="syntax-string">'my-stream'</span>,
      token: <span class="syntax-string">'your_token'</span>,
    });
    
    <span class="syntax-keyword">const</span> stream = <span class="syntax-keyword">await</span> navigator.mediaDevices.<span class="syntax-function">getUserMedia</span>({
      video: <span class="syntax-keyword">true</span>,
      audio: <span class="syntax-keyword">true</span>
    });
    
    <span class="syntax-keyword">await</span> client.<span class="syntax-function">publish</span>(stream);
  };

  <span class="syntax-keyword">return</span> (
    &lt;<span class="syntax-function">div</span> className=<span class="syntax-string">"stream-container"</span>&gt;
      &lt;<span class="syntax-function">LivePublisher</span> 
        client={client}
        onStart={startStream}
      /&gt;
      &lt;<span class="syntax-function">LivePlayer</span> 
        channel=<span class="syntax-string">"my-stream"</span>
        uid={<span class="syntax-number">12345</span>}
      /&gt;
    &lt;/<span class="syntax-function">div</span>&gt;
  );
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Android Tab --}}
            <div id="content-android" class="tab-content hidden">
                <div class="glass-premium rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center">
                            <i class="fab fa-android text-green-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Android SDK</h3>
                            <p class="text-sm text-gray-400">Native Kotlin SDK for Android</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="code-block rounded-xl overflow-hidden">
                            <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                                <span class="text-xs font-mono text-gray-400">build.gradle</span>
                            </div>
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code>dependencies {
    implementation <span class="syntax-string">'com.believoo:live-sdk:2.0.0'</span>
}</code></pre>
                        </div>

                        <div class="code-block rounded-xl overflow-hidden">
                            <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                                <span class="text-xs font-mono text-gray-400">StreamActivity.kt</span>
                            </div>
                            <pre class="p-4 overflow-x-auto text-sm font-mono"><code><span class="syntax-keyword">import</span> com.believoo.live.BelieVooLive
<span class="syntax-keyword">import</span> com.believoo.live.LiveConfig

<span class="syntax-keyword">class</span> <span class="syntax-function">StreamActivity</span> : <span class="syntax-function">AppCompatActivity</span>() {
    
    <span class="syntax-keyword">private lateinit var</span> liveClient: <span class="syntax-function">BelieVooLive</span>
    
    <span class="syntax-keyword">override fun</span> <span class="syntax-function">onCreate</span>(savedInstanceState: <span class="syntax-function">Bundle</span>?) {
        <span class="syntax-keyword">super</span>.<span class="syntax-function">onCreate</span>(savedInstanceState)
        
        <span class="syntax-keyword">val</span> config = <span class="syntax-function">LiveConfig</span>().<span class="syntax-function">apply</span> {
            appId = <span class="syntax-string">"bel_your_app_id"</span>
            appCertificate = <span class="syntax-string">"your_certificate"</span>
        }
        
        liveClient = <span class="syntax-function">BelieVooLive</span>(<span class="syntax-keyword">this</span>, config)
        
        <span class="syntax-comment">// Join channel and start streaming</span>
        liveClient.<span class="syntax-function">joinChannel</span>(
            channelId = <span class="syntax-string">"my-stream"</span>,
            token = <span class="syntax-string">"your_token"</span>,
            uid = <span class="syntax-number">0</span>
        ) { result ->
            <span class="syntax-keyword">if</span> (result.isSuccess) {
                <span class="syntax-comment">// Start local video</span>
                liveClient.<span class="syntax-function">startLocalVideo</span>(localVideoView)
                liveClient.<span class="syntax-function">startLocalAudio</span>()
            }
        }
    }
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- VPS + Streaming Addon Guide --}}
    <section id="vps-addon" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-white mb-4 uppercase tracking-tight">VPS + Streaming Addon</h2>
                <p class="text-gray-400">Add live streaming to your existing VPS plan at any time</p>
            </div>

            <div class="space-y-6">
                {{-- Activation Steps --}}
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-purple-500">
                    <h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-plug mr-2 text-purple-400"></i>Activate Streaming on Your VPS</h3>
                    <div class="space-y-4 text-sm text-gray-300">
                        <div class="flex gap-4">
                            <div class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                                <span class="font-bold text-purple-400 text-xs">1</span>
                            </div>
                            <div>
                                <p class="font-semibold text-white">Go to Client Dashboard</p>
                                <p class="text-gray-400">Login to your <a href="{{ route('client.dashboard') }}" class="text-purple-400 hover:underline">Client Portal</a> and navigate to the <strong>Streaming</strong> tab.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                                <span class="font-bold text-purple-400 text-xs">2</span>
                            </div>
                            <div>
                                <p class="font-semibold text-white">Select Your VPS</p>
                                <p class="text-gray-400">Choose the VPS you want to add streaming to. Only active VPS plans are eligible.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                                <span class="font-bold text-purple-400 text-xs">3</span>
                            </div>
                            <div>
                                <p class="font-semibold text-white">Choose Addon Plan</p>
                                <p class="text-gray-400">Pick a streaming addon tier (Basic, Pro, or Enterprise) based on your expected viewers and bitrate.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                                <span class="font-bold text-purple-400 text-xs">4</span>
                            </div>
                            <div>
                                <p class="font-semibold text-white">Pay & Auto-Deploy</p>
                                <p class="text-gray-400">Complete payment. The streaming server (SRS/Nginx-RTMP) will be automatically installed and configured on your VPS within 2 minutes.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- API Credentials --}}
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-cyan-500">
                    <h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-key mr-2 text-cyan-400"></i>Get Your API Credentials</h3>
                    <p class="text-sm text-gray-400 mb-4">After activation, your API credentials appear in the <strong>Streaming Dashboard</strong>:</p>
                    <div class="code-block rounded-xl overflow-hidden">
                        <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                            <span class="text-xs font-mono text-gray-400">Dashboard → Streaming → API Keys</span>
                        </div>
                        <pre class="p-4 overflow-x-auto text-sm font-mono text-gray-300"><code>App ID:          app_xxxxxxxxxxxxxxxxxxxxxxxx
App Certificate: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
RTMP Endpoint:   rtmp://stream.believoo.com/live
WebRTC Endpoint: wss://stream.believoo.com/webrtc
HLS Playback:    https://stream.believoo.com/hls

REST API Key:    rk_xxxxxxxxxxxxxxxxxxxxxxxx
REST Secret:     xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</code></pre>
                    </div>
                    <p class="text-xs text-gray-500 mt-3"><i class="fas fa-info-circle mr-1"></i> Keep your App Certificate and REST Secret private. Never expose them in client-side code.</p>
                </div>

                {{-- Streaming Dashboard Features --}}
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-pink-500">
                    <h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-chart-bar mr-2 text-pink-400"></i>Streaming Dashboard</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-xl p-4">
                            <h4 class="font-bold text-white mb-2"><i class="fas fa-broadcast-tower mr-2 text-pink-400"></i>Live Metrics</h4>
                            <ul class="text-sm text-gray-400 space-y-1">
                                <li>• Current viewers count</li>
                                <li>• Bandwidth usage (GB)</li>
                                <li>• Stream duration</li>
                                <li>• Bitrate & resolution</li>
                            </ul>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <h4 class="font-bold text-white mb-2"><i class="fas fa-cog mr-2 text-pink-400"></i>Management</h4>
                            <ul class="text-sm text-gray-400 space-y-1">
                                <li>• Regenerate API keys</li>
                                <li>• Whitelist domains/IPs</li>
                                <li>• Start/Stop streams</li>
                                <li>• View stream recordings</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Hybrid Architecture Info --}}
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-green-500">
                    <h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-server mr-2 text-green-400"></i>Delivery Methods</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-purple-500/30 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-server text-purple-400"></i>
                                <span class="font-bold text-white">VPS-Embedded</span>
                            </div>
                            <p class="text-sm text-gray-400">Streaming server runs directly on your VPS. Best for:</p>
                            <ul class="text-sm text-gray-400 mt-2 space-y-1">
                                <li>• Unlimited concurrent viewers</li>
                                <li>• Full control over configuration</li>
                                <li>• No per-viewer bandwidth charges</li>
                                <li>• Custom domain & branding</li>
                            </ul>
                        </div>
                        <div class="border border-blue-500/30 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fas fa-cloud text-blue-400"></i>
                                <span class="font-bold text-white">Cloud-Hosted</span>
                            </div>
                            <p class="text-sm text-gray-400">Managed on BelieVoo infrastructure. Best for:</p>
                            <ul class="text-sm text-gray-400 mt-2 space-y-1">
                                <li>• Zero server management</li>
                                <li>• Auto-scaling & CDN</li>
                                <li>• 50+ global edge locations</li>
                                <li>• Instant activation</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- API Reference Section --}}
    <section id="api" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-white mb-4 uppercase tracking-tight">API Reference</h2>
                <p class="text-gray-400">REST API endpoints for server-side integration</p>
            </div>

            <div class="space-y-4">
                {{-- Token Generation API --}}
                <div class="glass-premium rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-3 py-1 rounded-lg bg-green-500/20 text-green-400 text-xs font-bold uppercase">POST</span>
                        <code class="text-sm font-mono text-white">/api/v2/streaming/token</code>
                    </div>
                    <p class="text-sm text-gray-400 mb-4">Generate a temporary token for stream authentication</p>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="code-block rounded-xl overflow-hidden">
                            <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                                <span class="text-xs font-mono text-gray-400">Request</span>
                            </div>
                            <pre class="p-4 overflow-x-auto text-xs font-mono"><code>{
  <span class="syntax-string">"app_id"</span>: <span class="syntax-string">"bel_your_app_id"</span>,
  <span class="syntax-string">"channel"</span>: <span class="syntax-string">"my-stream"</span>,
  <span class="syntax-string">"uid"</span>: <span class="syntax-number">12345</span>,
  <span class="syntax-string">"expiry"</span>: <span class="syntax-number">3600</span>
}</code></pre>
                        </div>
                        <div class="code-block rounded-xl overflow-hidden">
                            <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                                <span class="text-xs font-mono text-gray-400">Response</span>
                            </div>
                            <pre class="p-4 overflow-x-auto text-xs font-mono"><code>{
  <span class="syntax-string">"success"</span>: <span class="syntax-keyword">true</span>,
  <span class="syntax-string">"token"</span>: <span class="syntax-string">"eyJhbGciOiJIUzI1NiIs..."</span>,
  <span class="syntax-string">"expires_at"</span>: <span class="syntax-string">"2026-05-06T10:00:00Z"</span>
}</code></pre>
                        </div>
                    </div>
                </div>

                {{-- Stream Stats API --}}
                <div class="glass-premium rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-3 py-1 rounded-lg bg-blue-500/20 text-blue-400 text-xs font-bold uppercase">GET</span>
                        <code class="text-sm font-mono text-white">/api/v2/streaming/stats/{stream_id}</code>
                    </div>
                    <p class="text-sm text-gray-400 mb-4">Get real-time stream statistics</p>
                    
                    <div class="code-block rounded-xl overflow-hidden">
                        <div class="px-4 py-2 bg-white/5 border-b border-white/10">
                            <span class="text-xs font-mono text-gray-400">Response</span>
                        </div>
                        <pre class="p-4 overflow-x-auto text-xs font-mono"><code>{
  <span class="syntax-string">"stream_id"</span>: <span class="syntax-string">"stream_123"</span>,
  <span class="syntax-string">"status"</span>: <span class="syntax-string">"live"</span>,
  <span class="syntax-string">"viewers"</span>: <span class="syntax-number">1420</span>,
  <span class="syntax-string">"bitrate"</span>: <span class="syntax-number">4500</span>,
  <span class="syntax-string">"resolution"</span>: <span class="syntax-string">"1920x1080"</span>,
  <span class="syntax-string">"fps"</span>: <span class="syntax-number">60</span>,
  <span class="syntax-string">"duration"</span>: <span class="syntax-number">3600</span>,
  <span class="syntax-string">"bandwidth_used"</span>: <span class="syntax-number">1024</span>
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Web-SDK Sandbox --}}
    <section id="test-stream" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-black text-white mb-4 uppercase tracking-tight">Test My Stream</h2>
                <p class="text-gray-400">Enter your AppID to instantly check if your stream is live</p>
            </div>

            <div class="glass-premium rounded-2xl p-8"
                 x-data="{
                     appId: '',
                     status: null,
                     loading: false,
                     error: null,
                     async checkStream() {
                         if (!this.appId.trim()) return;
                         this.loading = true;
                         this.status = null;
                         this.error = null;
                         try {
                             const res = await fetch('/api/streaming/status/' + encodeURIComponent(this.appId.trim()), {
                                 headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                             });
                             if (res.status === 401) { this.error = 'login_required'; return; }
                             const data = await res.json();
                             if (res.status === 404) { this.status = 'invalid_app_id'; }
                             else { this.status = data.status === 'active' ? 'active' : 'offline'; }
                         } catch (e) {
                             this.error = 'network_error';
                         } finally {
                             this.loading = false;
                         }
                     }
                 }">

                <div class="flex gap-3 mb-6">
                    <input x-model="appId"
                           @keydown.enter="checkStream()"
                           type="text"
                           placeholder="Enter your AppID (e.g. a1b2c3d4...)"
                           aria-label="AppID for stream status check"
                           class="flex-1 rounded-xl px-4 py-3 text-sm font-mono text-white"
                           style="background:rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.1);outline:none;" />
                    <button @click="checkStream()"
                            :disabled="loading || !appId.trim()"
                            aria-label="Check stream status"
                            class="px-6 py-3 rounded-xl font-bold uppercase tracking-wider text-sm transition"
                            style="background:linear-gradient(135deg,rgba(0,245,255,0.2),rgba(112,0,255,0.2));border:1px solid rgba(0,245,255,0.4);color:#00f5ff;cursor:pointer;">
                        <span x-show="!loading">Check Stream</span>
                        <span x-show="loading">Checking...</span>
                    </button>
                </div>

                {{-- Status results --}}
                <div x-show="status === 'active'" class="flex items-center gap-3 p-4 rounded-xl" style="background:rgba(0,245,255,0.08);border:1px solid rgba(0,245,255,0.3);">
                    <span style="width:10px;height:10px;border-radius:50%;background:#00f5ff;display:inline-block;animation:pulse 1.5s infinite;"></span>
                    <span style="color:#00f5ff;font-weight:700;">● Stream Active</span>
                    <span style="color:#64748b;font-size:0.85rem;margin-left:auto;">Your stream is live and broadcasting</span>
                </div>
                <div x-show="status === 'offline'" class="flex items-center gap-3 p-4 rounded-xl" style="background:rgba(100,116,139,0.08);border:1px solid rgba(100,116,139,0.3);">
                    <span style="color:#64748b;font-weight:700;">● Stream Offline</span>
                    <span style="color:#64748b;font-size:0.85rem;margin-left:auto;">No active stream detected for this AppID</span>
                </div>
                <div x-show="status === 'invalid_app_id'" class="flex items-center gap-3 p-4 rounded-xl" style="background:rgba(255,68,68,0.08);border:1px solid rgba(255,68,68,0.3);">
                    <span style="color:#ff4444;font-weight:700;">● Invalid AppID</span>
                    <span style="color:#64748b;font-size:0.85rem;margin-left:auto;">No project found with this AppID</span>
                </div>
                <div x-show="error === 'login_required'" class="p-4 rounded-xl" style="background:rgba(255,179,0,0.08);border:1px solid rgba(255,179,0,0.3);">
                    <span style="color:#ffb300;">Please <a href="{{ route('login') }}" style="text-decoration:underline;">log in</a> to use the stream checker.</span>
                </div>
                <div x-show="error === 'network_error'" class="p-4 rounded-xl" style="background:rgba(255,68,68,0.08);border:1px solid rgba(255,68,68,0.3);">
                    <span style="color:#ff4444;">Network error. Please try again.</span>
                </div>

                @auth
                    @php
                        $firstProject = \App\Models\StreamingProject::where('user_id', Auth::id())->first();
                    @endphp
                    @if($firstProject)
                    <p class="text-xs text-gray-500 mt-4">
                        Your AppID: <code style="color:#00f5ff;">{{ $firstProject->app_id }}</code>
                        <button onclick="document.querySelector('[x-model=appId]')._x_model.set('{{ $firstProject->app_id }}')"
                                style="background:none;border:none;color:#64748b;cursor:pointer;font-size:0.75rem;text-decoration:underline;margin-left:0.5rem;">
                            Use this
                        </button>
                    </p>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                        <i class="fas fa-broadcast-tower text-white"></i>
                    </div>
                    <span class="text-lg font-black text-white">BelieVoo <span class="gradient-text">Live Engine</span></span>
                </div>
                
                <div class="flex items-center gap-6 text-sm text-gray-400">
                    <a href="{{ route('client.dashboard') }}" class="hover:text-white transition">Client Portal</a>
                    <a href="mailto:support@believoo.com" class="hover:text-white transition">Support</a>
                    <span>© 2026 BelieVoo Technologies</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function copyCode(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                navigator.clipboard.writeText(element.innerText).then(() => {
                    // Show feedback
                    const btn = event.target.closest('button');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                    }, 2000);
                });
            }
        }

        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Show selected tab
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Update button styles
            document.querySelectorAll('[id^="tab-"]').forEach(btn => {
                btn.classList.remove('bg-purple-500/20', 'text-purple-300', 'border', 'border-purple-500/30');
                btn.classList.add('text-gray-400');
            });
            
            document.getElementById('tab-' + tabName).classList.add('bg-purple-500/20', 'text-purple-300', 'border', 'border-purple-500/30');
            document.getElementById('tab-' + tabName).classList.remove('text-gray-400');
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
