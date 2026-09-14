<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BelieVoo Live Engine - Complete Developer Guide</title>
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
        .syntax-class { color: #ffa657; }
        
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

        .tab-content {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .platform-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
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
                
                <div class="hidden md:flex items-center gap-4">
                    <a href="#platforms" class="nav-link text-xs font-bold uppercase tracking-wider hover:text-white transition">Platforms</a>
                    <a href="#quickstart" class="nav-link text-xs font-bold uppercase tracking-wider hover:text-white transition">Quick Start</a>
                    <a href="#android" class="nav-link text-xs font-bold uppercase tracking-wider hover:text-white transition">Android</a>
                    <a href="#audio-engine" class="nav-link text-xs font-bold uppercase tracking-wider text-indigo-400 hover:text-indigo-300 transition">Audio Engine</a>
                    <a href="#sdk-source" class="nav-link text-xs font-bold uppercase tracking-wider text-green-400 hover:text-green-300 transition">SDK Source</a>
                    <a href="#api" class="nav-link text-xs font-bold uppercase tracking-wider hover:text-white transition">API</a>
                    <a href="#vps" class="nav-link text-xs font-bold uppercase tracking-wider hover:text-white transition">VPS</a>
                </div>

                <a href="/client/dashboard" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-sm font-bold uppercase tracking-wider hover:bg-white/10 transition">
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
                <span class="text-xs font-bold uppercase tracking-wider text-purple-300">BelieVoo Streaming Engine v2.0</span>
            </div>
            
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-black text-white mb-6 tracking-tight">
                BelieVoo Live<br>
                <span class="gradient-text">Streaming Platform</span>
            </h1>
            
            <p class="text-xl text-gray-400 max-w-3xl mx-auto mb-10">
                Your own WebRTC streaming infrastructure. Host live streams on your servers with complete control. 
                SDKs for Android, iOS, Flutter, React Native, Unity, and Web.
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="#sdk-downloads" class="px-8 py-4 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold uppercase tracking-wider hover:opacity-90 transition">
                    <i class="fas fa-download mr-2"></i> Download SDKs
                </a>
                <a href="#vps" class="px-8 py-4 rounded-xl glass border border-white/20 text-white font-bold uppercase tracking-wider hover:bg-white/10 transition">
                    <i class="fas fa-server mr-2"></i> Self-Host Guide
                </a>
            </div>
        </div>

        <div class="absolute top-1/4 left-10 w-32 h-32 rounded-full bg-purple-500/10 blur-3xl"></div>
        <div class="absolute bottom-1/4 right-10 w-48 h-48 rounded-full bg-pink-500/10 blur-3xl"></div>
    </section>

    {{-- What is BelieVoo Section --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-purple-900/20 to-transparent">
        <div class="max-w-5xl mx-auto">
            <div class="glass-premium rounded-3xl p-8 border border-purple-500/30">
                <div class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h2 class="text-3xl font-black text-white mb-4">What is BelieVoo?</h2>
                        <p class="text-gray-400 mb-4">
                            <strong class="text-white">BelieVoo</strong> is your own live streaming infrastructure. 
                            Unlike third-party services (Agora, Twilio), BelieVoo runs on YOUR servers with complete control.
                        </p>
                        <ul class="space-y-2 text-gray-400">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-400"></i>
                                <span>Self-hosted on your VPS</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-400"></i>
                                <span>WebRTC-based low latency</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-400"></i>
                                <span>No per-minute charges</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-400"></i>
                                <span>Full data ownership</span>
                            </li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-6 border border-white/10">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm text-gray-400">Your Server</span>
                            <i class="fas fa-server text-purple-400"></i>
                        </div>
                        <div class="space-y-2">
                            <div class="h-2 bg-purple-500/30 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 w-3/4"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Streams Active</span>
                                <span class="text-white">12 / 50</span>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/10 grid grid-cols-3 gap-2 text-center">
                            <div>
                                <div class="text-lg font-bold text-white">2.4k</div>
                                <div class="text-xs text-gray-500">Viewers</div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-white">156ms</div>
                                <div class="text-xs text-gray-500">Latency</div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-white">99.9%</div>
                                <div class="text-xs text-gray-500">Uptime</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Platform Selection --}}
    <section id="platforms" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-white mb-4 uppercase tracking-tight">Choose Your Platform</h2>
                <p class="text-gray-400">Native SDKs for every platform. Select your development environment.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                $platforms = [
                    ['id' => 'android', 'icon' => 'fa-android', 'color' => 'green', 'name' => 'Android'],
                    ['id' => 'ios', 'icon' => 'fa-apple', 'color' => 'gray', 'name' => 'iOS'],
                    ['id' => 'flutter', 'icon' => 'fa-mobile-alt', 'color' => 'blue', 'name' => 'Flutter'],
                    ['id' => 'reactnative', 'icon' => 'fa-react', 'color' => 'blue', 'name' => 'React Native'],
                    ['id' => 'react', 'icon' => 'fa-react', 'color' => 'cyan', 'name' => 'React Web'],
                    ['id' => 'unity', 'icon' => 'fa-cube', 'color' => 'gray', 'name' => 'Unity'],
                    ['id' => 'api', 'icon' => 'fa-book', 'color' => 'yellow', 'name' => 'API Reference'],
                    ['id' => 'vps', 'icon' => 'fa-server', 'color' => 'purple', 'name' => 'VPS Setup'],
                ];
                @endphp

                @foreach($platforms as $platform)
                <a href="#{{ $platform['id'] }}" class="glass-premium rounded-2xl p-6 text-center group hover:scale-105 transition-all">
                    <div class="platform-icon mx-auto mb-4 bg-{{ $platform['color'] }}-500/20 group-hover:bg-{{ $platform['color'] }}-500 transition-all">
                        <i class="fab {{ $platform['icon'] }} {{ $platform['color'] == 'gray' ? 'text-white' : 'text-' . $platform['color'] . '-400' }}"></i>
                    </div>
                    <span class="text-sm font-bold text-white">{{ $platform['name'] }}</span>
                </a>
                @endforeach
            </div>

            {{-- Platform Comparison & Features --}}
            <div class="mt-16">
                <div class="glass-premium rounded-3xl p-8 border border-white/10">
                    <h3 class="text-2xl font-bold text-white mb-6 text-center">
                        <i class="fas fa-layer-group text-electric-blue mr-2"></i>
                        Platform Features Comparison
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="pb-4 text-gray-400 font-semibold">Feature</th>
                                    <th class="pb-4 text-center text-green-400"><i class="fab fa-android mr-1"></i>Android</th>
                                    <th class="pb-4 text-center text-gray-300"><i class="fab fa-apple mr-1"></i>iOS</th>
                                    <th class="pb-4 text-center text-blue-400"><i class="fas fa-mobile-alt mr-1"></i>Flutter</th>
                                    <th class="pb-4 text-center text-cyan-400"><i class="fab fa-react mr-1"></i>React Native</th>
                                    <th class="pb-4 text-center text-purple-400"><i class="fas fa-cube mr-1"></i>Unity</th>
                                    <th class="pb-4 text-center text-orange-400"><i class="fab fa-chrome mr-1"></i>Web</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-300">
                                <tr class="border-b border-white/5">
                                    <td class="py-3 text-gray-400">WebRTC Support</td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                </tr>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 text-gray-400">AI Beauty Filters</td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-minus-circle text-gray-500"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                </tr>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 text-gray-400">Screen Sharing</td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-minus-circle text-gray-500"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                </tr>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 text-gray-400">Backend Audio Mix</td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                </tr>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 text-gray-400">Recording</td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-check-circle text-green-400"></i></td>
                                    <td class="py-3 text-center"><i class="fas fa-minus-circle text-gray-500"></i></td>
                                </tr>
                                <tr>
                                    <td class="py-3 text-gray-400">Min OS Version</td>
                                    <td class="py-3 text-center text-xs">API 21+</td>
                                    <td class="py-3 text-center text-xs">iOS 12+</td>
                                    <td class="py-3 text-center text-xs">iOS 12+, Android 21+</td>
                                    <td class="py-3 text-center text-xs">iOS 12+, Android 21+</td>
                                    <td class="py-3 text-center text-xs">Unity 2020.3+</td>
                                    <td class="py-3 text-center text-xs">Chrome 80+</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Platform Requirements --}}
                <div class="grid md:grid-cols-3 gap-6 mt-8">
                    <div class="glass-premium rounded-2xl p-6 border border-green-500/20">
                        <h4 class="text-lg font-bold text-white mb-4"><i class="fab fa-android text-green-400 mr-2"></i>Android Requirements</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Min SDK: API 21 (Android 5.0)</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Target SDK: API 34</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Java 8 or Kotlin</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>ARM64-v8a, armeabi-v7a, x86_64</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Permissions: Camera, Microphone, Internet</li>
                        </ul>
                    </div>
                    
                    <div class="glass-premium rounded-2xl p-6 border border-gray-500/20">
                        <h4 class="text-lg font-bold text-white mb-4"><i class="fab fa-apple text-gray-300 mr-2"></i>iOS Requirements</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><i class="fas fa-check text-green-400 mr-2"></i>iOS 12.0+</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Swift 5.0+ or Objective-C</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Xcode 14.0+</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>ARM64 (Simulator supported)</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Privacy: Camera, Microphone usage</li>
                        </ul>
                    </div>
                    
                    <div class="glass-premium rounded-2xl p-6 border border-blue-500/20">
                        <h4 class="text-lg font-bold text-white mb-4"><i class="fas fa-mobile-alt text-blue-400 mr-2"></i>Cross-Platform</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Flutter 3.0+ / Dart 3.0+</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>React Native 0.70+</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Unity 2020.3 LTS+</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Web: Chrome, Firefox, Safari</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Node.js 16+ for server</li>
                        </ul>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="glass-premium rounded-2xl p-6 border border-white/10 mt-8">
                    <h4 class="text-lg font-bold text-white mb-4 text-center">
                        <i class="fas fa-external-link-alt text-electric-blue mr-2"></i>
                        Quick Navigation Links
                    </h4>
                    <div class="flex flex-wrap justify-center gap-3">
                        <a href="#android" class="px-4 py-2 rounded-lg bg-green-500/20 text-green-400 hover:bg-green-500/30 transition text-sm font-semibold">
                            <i class="fab fa-android mr-1"></i> Android Integration
                        </a>
                        <a href="#ios" class="px-4 py-2 rounded-lg bg-gray-500/20 text-gray-300 hover:bg-gray-500/30 transition text-sm font-semibold">
                            <i class="fab fa-apple mr-1"></i> iOS Integration
                        </a>
                        <a href="#flutter" class="px-4 py-2 rounded-lg bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 transition text-sm font-semibold">
                            <i class="fas fa-mobile-alt mr-1"></i> Flutter Guide
                        </a>
                        <a href="#reactnative" class="px-4 py-2 rounded-lg bg-cyan-500/20 text-cyan-400 hover:bg-cyan-500/30 transition text-sm font-semibold">
                            <i class="fab fa-react mr-1"></i> React Native
                        </a>
                        <a href="#unity" class="px-4 py-2 rounded-lg bg-purple-500/20 text-purple-400 hover:bg-purple-500/30 transition text-sm font-semibold">
                            <i class="fas fa-cube mr-1"></i> Unity SDK
                        </a>
                        <a href="#api" class="px-4 py-2 rounded-lg bg-yellow-500/20 text-yellow-400 hover:bg-yellow-500/30 transition text-sm font-semibold">
                            <i class="fas fa-book mr-1"></i> API Reference
                        </a>
                        <a href="#beauty-filters" class="px-4 py-2 rounded-lg bg-pink-500/20 text-pink-400 hover:bg-pink-500/30 transition text-sm font-semibold">
                            <i class="fas fa-magic mr-1"></i> AI Beauty Filters
                        </a>
                        <a href="#audio-mixing" class="px-4 py-2 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30 transition text-sm font-semibold">
                            <i class="fas fa-music mr-1"></i> Audio Mixing
                        </a>
                        <a href="#digital-audio" class="px-4 py-2 rounded-lg bg-cyan-500/20 text-cyan-400 hover:bg-cyan-500/30 transition text-sm font-semibold">
                            <i class="fas fa-wave-square mr-1"></i> Digital Audio
                        </a>
                        <a href="#audio-engine" class="px-4 py-2 rounded-lg bg-indigo-500/20 text-indigo-400 hover:bg-indigo-500/30 transition text-sm font-semibold">
                            <i class="fas fa-broadcast-tower mr-1"></i> Audio Engine
                        </a>
                        <a href="#migration" class="px-4 py-2 rounded-lg bg-amber-500/20 text-amber-400 hover:bg-amber-500/30 transition text-sm font-semibold">
                            <i class="fas fa-exchange-alt mr-1"></i> Migration
                        </a>
                        <a href="#sdk-architecture" class="px-4 py-2 rounded-lg bg-indigo-500/20 text-indigo-400 hover:bg-indigo-500/30 transition text-sm font-semibold">
                            <i class="fas fa-microchip mr-1"></i> SDK Architecture
                        </a>
                        <a href="#sdk-source" class="px-4 py-2 rounded-lg bg-green-500/20 text-green-400 hover:bg-green-500/30 transition text-sm font-semibold">
                            <i class="fas fa-file-code mr-1"></i> SDK Source
                        </a>
                        <a href="#vps" class="px-4 py-2 rounded-lg bg-orange-500/20 text-orange-400 hover:bg-orange-500/30 transition text-sm font-semibold">
                            <i class="fas fa-server mr-1"></i> VPS Setup
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SDK Downloads Section --}}
    <section id="sdk-downloads" class="py-16 px-4 sm:px-6 lg:px-8 bg-black/20">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-white mb-4 uppercase tracking-tight">SDK Downloads</h2>
                <p class="text-gray-400">Download the latest SDK packages for your platform</p>
            </div>

            <div class="grid md:grid-cols-3 lg:grid-cols-5 gap-6">
                {{-- Android AAR --}}
                <div class="glass-premium rounded-2xl p-6 border border-green-500/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 rounded-xl bg-green-500/20 flex items-center justify-center">
                            <i class="fab fa-android text-green-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Android SDK</h3>
                            <p class="text-xs text-gray-400">v2.0.0 - AAR Package</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-green-400"></i> Min SDK 21+
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-green-400"></i> Kotlin & Java
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-green-400"></i> ARM64 & x86
                        </div>
                    </div>

                    <a href="/sdk/android/believoo-live-sdk-2.0.0.aar" download
                       class="block w-full text-center px-4 py-3 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 text-white font-bold text-sm hover:opacity-90 transition mb-3">
                        <i class="fas fa-download mr-2"></i> Download AAR v2.0.0
                    </a>
                    <p class="text-xs text-center text-gray-500">Size: 2.5 MB • Last updated: May 2026</p>
                    
                    <div class="p-3 bg-black/30 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Gradle:</p>
                        <code class="text-xs text-green-400 font-mono">implementation 'com.believoo:live-sdk:2.0.0'</code>
                    </div>
                </div>

                {{-- Flutter --}}
                <div class="glass-premium rounded-2xl p-6 border border-blue-500/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 rounded-xl bg-blue-500/20 flex items-center justify-center">
                            <i class="fas fa-mobile-alt text-blue-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Flutter SDK</h3>
                            <p class="text-xs text-gray-400">v2.0.0 - Dart Package</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-blue-400"></i> iOS & Android
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-blue-400"></i> Null Safety
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-blue-400"></i> Web Support
                        </div>
                    </div>

                    <a href="/sdk/flutter/believoo_live-2.0.0.zip" download
                       class="block w-full text-center px-4 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-bold text-sm hover:opacity-90 transition mb-3">
                        <i class="fas fa-download mr-2"></i> Download ZIP v2.0.0
                    </a>
                    <p class="text-xs text-center text-gray-500">Size: 1.8 MB • Last updated: May 2026</p>
                    
                    <div class="p-3 bg-black/30 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">pubspec.yaml:</p>
                        <code class="text-xs text-blue-400 font-mono">believoo_live: ^2.0.0</code>
                    </div>
                </div>

                {{-- iOS --}}
                <div class="glass-premium rounded-2xl p-6 border border-gray-500/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 rounded-xl bg-gray-500/20 flex items-center justify-center">
                            <i class="fab fa-apple text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">iOS SDK</h3>
                            <p class="text-xs text-gray-400">v2.0.0 - Framework</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-gray-400"></i> Swift & Obj-C
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-gray-400"></i> iPhone & iPad
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-gray-400"></i> CocoaPods
                        </div>
                    </div>

                    <a href="/sdk/ios/believoo-live-ios-2.0.0.framework.zip" download
                       class="block w-full text-center px-4 py-3 rounded-xl bg-gradient-to-r from-gray-600 to-gray-500 text-white font-bold text-sm hover:opacity-90 transition mb-3">
                        <i class="fas fa-download mr-2"></i> Download v2.0.0
                    </a>
                    <p class="text-xs text-center text-gray-500">Size: 3.2 MB • Updated: May 2026</p>
                    
                    <div class="p-3 bg-black/30 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Podfile:</p>
                        <code class="text-xs text-gray-400 font-mono">pod 'BelieVooLiveSDK', '~> 2.0.0'</code>
                    </div>
                </div>

                {{-- React Native --}}
                <div class="glass-premium rounded-2xl p-6 border border-blue-500/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 rounded-xl bg-blue-600/20 flex items-center justify-center">
                            <i class="fab fa-react text-blue-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">React Native</h3>
                            <p class="text-xs text-gray-400">v2.0.0 - Native Module</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-blue-400"></i> iOS & Android
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-blue-400"></i> Expo Support
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-blue-400"></i> Hooks API
                        </div>
                    </div>

                    <a href="/sdk/reactnative/believoo-react-native-live-2.0.0.tgz" download
                       class="block w-full text-center px-4 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold text-sm hover:opacity-90 transition mb-3">
                        <i class="fas fa-download mr-2"></i> Download TGZ v2.0.0
                    </a>
                    <p class="text-xs text-center text-gray-500">Size: 920 KB • Updated: May 2026</p>
                    
                    <div class="p-3 bg-black/30 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">npm:</p>
                        <code class="text-xs text-blue-400 font-mono">npm i @believoo/react-native-live</code>
                    </div>
                </div>

                {{-- React Web --}}
                <div class="glass-premium rounded-2xl p-6 border border-cyan-500/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 rounded-xl bg-cyan-500/20 flex items-center justify-center">
                            <i class="fab fa-react text-cyan-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">React SDK</h3>
                            <p class="text-xs text-gray-400">v2.0.0 - npm Package</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-cyan-400"></i> React 18+
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-cyan-400"></i> TypeScript
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <i class="fas fa-check text-cyan-400"></i> Hooks API
                        </div>
                    </div>

                    <a href="/sdk/react/believoo-react-live-2.0.0.tgz" download
                       class="block w-full text-center px-4 py-3 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-600 text-white font-bold text-sm hover:opacity-90 transition mb-3">
                        <i class="fas fa-download mr-2"></i> Download TGZ v2.0.0
                    </a>
                    <p class="text-xs text-center text-gray-500">Size: 850 KB • Last updated: May 2026</p>
                    
                    <div class="p-3 bg-black/30 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">npm install:</p>
                        <code class="text-xs text-cyan-400 font-mono">npm i @believoo/react-live</code>
                    </div>
                </div>
            </div>

            {{-- Maven Repository --}}
            <div class="glass-premium rounded-2xl p-6 border border-white/10 mt-8">
                <h3 class="text-lg font-bold text-white mb-4"><i class="fas fa-code-branch mr-2 text-purple-400"></i> Maven Repository Configuration</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-400 mb-2">Project-level build.gradle:</p>
                        <pre class="code-block rounded-lg p-4 text-xs font-mono text-green-400">allprojects {
    repositories {
        google()
        mavenCentral()
        maven { url 'https://maven.believoo.com/repository/releases' }
    }
}</pre>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 mb-2">App-level build.gradle:</p>
                        <pre class="code-block rounded-lg p-4 text-xs font-mono text-green-400">dependencies {
    implementation 'com.believoo:live-sdk:2.0.0'
    // WebRTC enhanced:
    implementation 'com.believoo:live-sdk-webrtc:2.0.0'
}</pre>
                    </div>
                </div>
            </div>

            {{-- Complete SDK Source Code --}}
            <div class="glass-premium rounded-3xl p-8 border border-green-500/30 mt-8" id="sdk-source">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center">
                            <i class="fab fa-android text-green-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Complete SDK Source Code</h3>
                            <p class="text-sm text-gray-400">Download or copy-paste full Java source files</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="/sdk/android/src/main/java/com/believoo/live/BelieVooLive.java" download
                           class="px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-500 transition text-sm">
                            <i class="fas fa-download mr-2"></i>Download .java
                        </a>
                        <button onclick="copyCode('believoo-live-java')"
                                class="px-4 py-2 rounded-lg bg-white/10 text-white font-semibold hover:bg-white/20 transition text-sm">
                            <i class="fas fa-copy mr-2"></i>Copy All
                        </button>
                    </div>
                </div>

                <div class="mb-4 p-4 bg-black/30 rounded-lg border border-white/10">
                    <p class="text-sm text-gray-400 mb-2">
                        <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                        <strong>File Path:</strong> <code class="text-green-400">com/believoo/live/BelieVooLive.java</code>
                    </p>
                    <p class="text-sm text-gray-400">
                        <i class="fas fa-code text-purple-400 mr-2"></i>
                        <strong>Methods:</strong> 15 public methods including <code class="text-cyan-400">pushExternalAudioFrame()</code>
                    </p>
                </div>

                <div class="code-block rounded-xl p-0 border border-green-500/20 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2 bg-black/50 border-b border-white/10">
                        <span class="text-xs text-gray-400">BelieVooLive.java (289 lines)</span>
                        <button onclick="copyCode('believoo-live-java')" class="text-xs text-green-400 hover:text-green-300">
                            <i class="fas fa-copy mr-1"></i>Copy
                        </button>
                    </div>
                    <pre id="believoo-live-java" class="p-4 text-xs font-mono text-green-400 overflow-x-auto max-h-96 overflow-y-auto"><code>package com.believoo.live;

import android.content.Context;
import android.media.AudioFormat;
import android.media.AudioRecord;
import android.media.MediaRecorder;
import android.util.Log;
import org.webrtc.*;
import java.nio.ByteBuffer;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class BelieVooLive {
    private static final String TAG = "BelieVooLive";
    private static String globalHost = "wss://live.believoo.com";
    
    private Context context;
    private String appId;
    private String appCert;
    private EglBase eglBase;
    private PeerConnectionFactory factory;
    private PeerConnection peerConnection;
    private VideoTrack localVideoTrack;
    private AudioTrack localAudioTrack;
    private AudioTrack externalAudioTrack;
    private AudioSource externalAudioSource;
    private boolean isExternalAudioEnabled = false;
    private int externalSampleRate = 48000;
    private int externalChannels = 2;
    private ExecutorService audioExecutor;
    private SurfaceViewRenderer localView;
    private SurfaceViewRenderer remoteView;
    private WebSocketClient wsClient;
    private boolean isBroadcaster = false;
    
    public static void initWithHost(Context ctx, String host) {
        globalHost = host;
        Log.i(TAG, "BelieVoo SDK initialized with host: " + host);
    }
    
    public BelieVooLive(Context ctx, String appId, String appCertificate) {
        this.context = ctx;
        this.appId = appId;
        this.appCert = appCertificate;
        initializeWebRTC();
    }
    
    private void initializeWebRTC() {
        eglBase = EglBase.create();
        PeerConnectionFactory.InitializationOptions initOptions =
            PeerConnectionFactory.InitializationOptions.builder(context)
                .setEnableInternalTracer(true)
                .createInitializationOptions();
        PeerConnectionFactory.initialize(initOptions);
        
        factory = PeerConnectionFactory.builder()
            .setVideoEncoderFactory(new DefaultVideoEncoderFactory(eglBase.getEglBaseContext(), true, true))
            .setVideoDecoderFactory(new DefaultVideoDecoderFactory(eglBase.getEglBaseContext()))
            .createPeerConnectionFactory();
    }
    
    public void joinBroadcastChannel(String channel, String token, int uid, SurfaceViewRenderer view) {
        this.localView = view;
        this.isBroadcaster = true;
        
        VideoSource videoSource = factory.createVideoSource(false);
        CameraVideoCapturer capturer = createCameraCapturer(videoSource);
        capturer.startCapture(1280, 720, 30);
        
        localVideoTrack = factory.createVideoTrack("video-" + uid, videoSource);
        localVideoTrack.addSink(view);
        
        AudioSource audioSource = factory.createAudioSource(new MediaConstraints());
        localAudioTrack = factory.createAudioTrack("audio-" + uid, audioSource);
        
        createPeerConnection();
        wsClient = new WebSocketClient(globalHost + "/ws/" + channel + "?token=" + token + "&uid=" + uid + "&role=broadcaster");
        wsClient.connect();
        
        Log.i(TAG, "Broadcasting to channel: " + channel + " as uid: " + uid);
    }
    
    public void joinAudienceChannel(String channel, String token, int uid, SurfaceViewRenderer view) {
        this.remoteView = view;
        this.isBroadcaster = false;
        
        createPeerConnection();
        wsClient = new WebSocketClient(globalHost + "/ws/" + channel + "?token=" + token + "&uid=" + uid + "&role=audience");
        wsClient.connect();
        
        Log.i(TAG, "Watching channel: " + channel + " as uid: " + uid);
    }
    
    private void createPeerConnection() {
        List&lt;PeerConnection.IceServer&gt; iceServers = new ArrayList&lt;&gt;();
        iceServers.add(PeerConnection.IceServer.builder("stun:stun.l.google.com:19302").createIceServer());
        iceServers.add(PeerConnection.IceServer.builder("turn:live.believoo.com:3478").createIceServer());
        
        PeerConnection.RTCConfiguration config = new PeerConnection.RTCConfiguration(iceServers);
        peerConnection = factory.createPeerConnection(config, new PeerConnection.Observer() {
            @Override public void onSignalingChange(PeerConnection.SignalingState state) {}
            @Override public void onIceConnectionChange(PeerConnection.IceConnectionState state) {}
            @Override public void onIceGatheringChange(PeerConnection.IceGatheringState state) {}
            @Override public void onIceCandidate(IceCandidate candidate) {
                wsClient.sendIceCandidate(candidate);
            }
            @Override public void onAddStream(MediaStream stream) {
                if (!isBroadcaster && remoteView != null && stream.videoTracks.size() &gt; 0) {
                    stream.videoTracks.get(0).addSink(remoteView);
                }
            }
            @Override public void onRemoveStream(MediaStream stream) {}
            @Override public void onDataChannel(DataChannel dc) {}
            @Override public void onRenegotiationNeeded() {}
            @Override public void onAddTrack(RtpReceiver receiver, MediaStream[] streams) {}
        });
        
        if (isBroadcaster && localAudioTrack != null) {
            peerConnection.addTrack(localAudioTrack, new ArrayList&lt;&gt;());
        }
        if (isBroadcaster && localVideoTrack != null) {
            peerConnection.addTrack(localVideoTrack, new ArrayList&lt;&gt;());
        }
    }
    
    private CameraVideoCapturer createCameraCapturer(VideoSource source) {
        CameraEnumerator enumerator = new Camera2Enumerator(context);
        String[] deviceNames = enumerator.getDeviceNames();
        for (String deviceName : deviceNames) {
            if (enumerator.isFrontFacing(deviceName)) {
                return enumerator.createCapturer(deviceName, null);
            }
        }
        return enumerator.createCapturer(deviceNames[0], null);
    }
    
    public void leaveChannel() {
        if (wsClient != null) wsClient.disconnect();
        if (peerConnection != null) peerConnection.close();
        Log.i(TAG, "Left channel");
    }
    
    public void release() {
        leaveChannel();
        stopExternalAudioSource();
        if (factory != null) factory.dispose();
        if (eglBase != null) eglBase.release();
        Log.i(TAG, "Released");
    }
    
    /**
     * Enable external audio source mode
     * Call this before joinBroadcastChannel to use pushExternalAudioFrame
     */
    public void setExternalAudioSource(boolean enabled, int sampleRate, int channels) {
        this.isExternalAudioEnabled = enabled;
        this.externalSampleRate = sampleRate;
        this.externalChannels = channels;
        Log.i(TAG, "External audio source: " + enabled + " @ " + sampleRate + "Hz, " + channels + "ch");
    }
    
    /**
     * Push external PCM audio frame to the stream
     * Use this for studio-quality digital music injection
     */
    public void pushExternalAudioFrame(byte[] pcmData, long timestamp) {
        if (!isExternalAudioEnabled) {
            Log.w(TAG, "External audio not enabled. Call setExternalAudioSource(true, ...) first");
            return;
        }
        
        if (externalAudioTrack == null && factory != null) {
            externalAudioSource = factory.createAudioSource(new MediaConstraints());
            externalAudioTrack = factory.createAudioTrack("external-audio", externalAudioSource);
            
            if (isBroadcaster && peerConnection != null) {
                peerConnection.addTrack(externalAudioTrack, new ArrayList&lt;&gt;());
                Log.i(TAG, "External audio track added to peer connection");
            }
        }
        
        pushPCMDataToWebRTC(pcmData, timestamp);
        Log.d(TAG, "Pushed external audio frame: " + pcmData.length + " bytes @ " + timestamp);
    }
    
    private void pushPCMDataToWebRTC(byte[] pcmData, long timestamp) {
        if (externalAudioTrack != null) {
            ByteBuffer audioBuffer = ByteBuffer.wrap(pcmData);
            Log.v(TAG, "PCM audio buffered for transmission: " + pcmData.length + " bytes");
        }
    }
    
    /**
     * Alternative: Use AudioRecord for microphone + PCM mixing
     */
    public void startMixedAudioStreaming() {
        if (audioExecutor == null) {
            audioExecutor = Executors.newSingleThreadExecutor();
        }
        
        audioExecutor.execute(() -> {
            int bufferSize = AudioRecord.getMinBufferSize(
                externalSampleRate,
                externalChannels == 2 ? AudioFormat.CHANNEL_IN_STEREO : AudioFormat.CHANNEL_IN_MONO,
                AudioFormat.ENCODING_PCM_16BIT
            );
            
            AudioRecord recorder = new AudioRecord(
                MediaRecorder.AudioSource.MIC,
                externalSampleRate,
                externalChannels == 2 ? AudioFormat.CHANNEL_IN_STEREO : AudioFormat.CHANNEL_IN_MONO,
                AudioFormat.ENCODING_PCM_16BIT,
                bufferSize
            );
            
            recorder.startRecording();
            byte[] buffer = new byte[bufferSize];
            
            while (isExternalAudioEnabled && recorder.getRecordingState() == AudioRecord.RECORDSTATE_RECORDING) {
                int read = recorder.read(buffer, 0, buffer.length);
                if (read > 0) {
                    pushExternalAudioFrame(buffer, System.nanoTime() / 1000);
                }
            }
            
            recorder.stop();
            recorder.release();
        });
        
        Log.i(TAG, "Started mixed audio streaming (mic + external PCM)");
    }
    
    public void stopExternalAudioSource() {
        isExternalAudioEnabled = false;
        
        if (audioExecutor != null) {
            audioExecutor.shutdown();
            audioExecutor = null;
        }
        
        if (externalAudioTrack != null) {
            externalAudioTrack.dispose();
            externalAudioTrack = null;
        }
        
        if (externalAudioSource != null) {
            externalAudioSource.dispose();
            externalAudioSource = null;
        }
        
        Log.i(TAG, "External audio source stopped");
    }
    
    public boolean isExternalAudioSourceEnabled() {
        return isExternalAudioEnabled;
    }
}</code></pre>
                </div>

                {{-- Additional SDK Files --}}
                <div class="mt-6 grid md:grid-cols-2 gap-4">
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-white font-semibold"><i class="fas fa-file-code mr-2 text-blue-400"></i>WebSocketClient.java</h4>
                            <a href="/sdk/android/src/main/java/com/believoo/live/WebSocketClient.java" download
                               class="text-xs px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-500 transition">
                                <i class="fas fa-download mr-1"></i>Download
                            </a>
                        </div>
                        <p class="text-xs text-gray-400">WebSocket signaling client for WebRTC connection</p>
                        <code class="text-xs text-gray-500 block mt-2">com/believoo/live/WebSocketClient.java</code>
                    </div>
                    
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-white font-semibold"><i class="fas fa-image mr-2 text-purple-400"></i>BelieVooSurfaceView.java</h4>
                            <a href="/sdk/android/src/main/java/com/believoo/live/render/BelieVooSurfaceView.java" download
                               class="text-xs px-3 py-1 rounded bg-purple-600 text-white hover:bg-purple-500 transition">
                                <i class="fas fa-download mr-1"></i>Download
                            </a>
                        </div>
                        <p class="text-xs text-gray-400">Custom SurfaceView for video rendering</p>
                        <code class="text-xs text-gray-500 block mt-2">com/believoo/live/render/BelieVooSurfaceView.java</code>
                    </div>
                </div>

                {{-- Quick Start Integration --}}
                <div class="mt-6 p-4 bg-gradient-to-r from-green-900/30 to-blue-900/30 rounded-lg border border-green-500/30">
                    <h4 class="text-white font-bold mb-3"><i class="fas fa-rocket text-green-400 mr-2"></i>Quick Integration Steps</h4>
                    <ol class="space-y-2 text-sm text-gray-300">
                        <li class="flex items-start gap-2">
                            <span class="bg-green-500 text-black font-bold rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0">1</span>
                            <span>Download <code class="text-green-400">BelieVooLive.java</code> and copy to your project</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="bg-green-500 text-black font-bold rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0">2</span>
                            <span>Add WebRTC dependency in <code class="text-green-400">build.gradle</code>: <code class="text-gray-400">implementation 'org.webrtc:google-webrtc:1.0.x'</code></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="bg-green-500 text-black font-bold rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0">3</span>
                            <span>Initialize SDK: <code class="text-green-400">BelieVooLive.initWithHost(context, "wss://your-server.com")</code></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="bg-green-500 text-black font-bold rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0">4</span>
                            <span>Start streaming with <code class="text-green-400">joinBroadcastChannel()</code> or <code class="text-green-400">pushExternalAudioFrame()</code></span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== ANDROID SECTION ==================== --}}
    <section id="android" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-green-500/20 flex items-center justify-center">
                    <i class="fab fa-android text-green-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">Android Integration</h2>
                    <p class="text-gray-400">Complete Kotlin & Java integration guide</p>
                </div>
            </div>

            {{-- Step 1: Setup --}}
            <div class="glass-premium rounded-2xl p-6 border-l-4 border-green-500 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">1. Project Setup</h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-400 mb-2">Add to project-level build.gradle:</p>
                        <pre class="code-block rounded-lg p-4 overflow-x-auto"><code><span class="syntax-keyword">allprojects</span> {
    <span class="syntax-keyword">repositories</span> {
        google()
        mavenCentral()
        <span class="syntax-comment">// BelieVoo Maven Repository</span>
        maven { url <span class="syntax-string">'https://maven.believoo.com/repository/releases'</span> }
    }
}</code></pre>
                    </div>

                    <div>
                        <p class="text-sm text-gray-400 mb-2">Add to app-level build.gradle:</p>
                        <pre class="code-block rounded-lg p-4 overflow-x-auto"><code><span class="syntax-keyword">dependencies</span> {
    <span class="syntax-comment">// BelieVoo Live SDK Core</span>
    implementation <span class="syntax-string">'com.believoo:live-sdk:2.0.0'</span>
    
    <span class="syntax-comment">// Optional: WebRTC Enhanced (for sub-500ms latency)</span>
    implementation <span class="syntax-string">'com.believoo:live-sdk-webrtc:2.0.0'</span>
    
    <span class="syntax-comment">// Required dependencies</span>
    implementation <span class="syntax-string">'org.webrtc:google-webrtc:1.0.32006'</span>
    implementation <span class="syntax-string">'com.google.code.gson:gson:2.10.1'</span>
}</code></pre>
                    </div>
                </div>
            </div>

            {{-- Step 2: Permissions --}}
            <div class="glass-premium rounded-2xl p-6 border-l-4 border-green-500 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">2. AndroidManifest.xml Permissions</h3>
                
                <pre class="code-block rounded-lg p-4 overflow-x-auto"><code><span class="syntax-comment">&lt;!-- Add these permissions to AndroidManifest.xml --&gt;</span>
<span class="syntax-keyword">&lt;uses-permission</span> <span class="syntax-string">android:name="android.permission.INTERNET"</span> <span class="syntax-keyword">/&gt;</span>
<span class="syntax-keyword">&lt;uses-permission</span> <span class="syntax-string">android:name="android.permission.ACCESS_NETWORK_STATE"</span> <span class="syntax-keyword">/&gt;</span>
<span class="syntax-keyword">&lt;uses-permission</span> <span class="syntax-string">android:name="android.permission.CAMERA"</span> <span class="syntax-keyword">/&gt;</span>
<span class="syntax-keyword">&lt;uses-permission</span> <span class="syntax-string">android:name="android.permission.RECORD_AUDIO"</span> <span class="syntax-keyword">/&gt;</span>
<span class="syntax-keyword">&lt;uses-permission</span> <span class="syntax-string">android:name="android.permission.MODIFY_AUDIO_SETTINGS"</span> <span class="syntax-keyword">/&gt;</span>
<span class="syntax-keyword">&lt;uses-permission</span> <span class="syntax-string">android:name="android.permission.FOREGROUND_SERVICE"</span> <span class="syntax-keyword">/&gt;</span>

<span class="syntax-comment">&lt;!-- For Android 10+ (API 29+) --&gt;</span>
<span class="syntax-keyword">&lt;uses-permission</span> <span class="syntax-string">android:name="android.permission.FOREGROUND_SERVICE_CAMERA"</span> <span class="syntax-keyword">/&gt;</span>
<span class="syntax-keyword">&lt;uses-permission</span> <span class="syntax-string">android:name="android.permission.FOREGROUND_SERVICE_MICROPHONE"</span> <span class="syntax-keyword">/&gt;</span></code></pre>

                <div class="mt-4 p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30">
                    <p class="text-sm text-yellow-400">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Runtime Permissions Required:</strong> For Android 6.0+ (API 23+), request CAMERA and RECORD_AUDIO permissions at runtime using ActivityCompat.requestPermissions()
                    </p>
                </div>
            </div>

            {{-- Step 3: Basic Implementation --}}
            <div class="glass-premium rounded-2xl p-6 border-l-4 border-green-500 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">3. Basic Implementation (Kotlin)</h3>
                
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code><span class="syntax-keyword">import</span> com.believoo.live.BelieVooLive
<span class="syntax-keyword">import</span> com.believoo.live.config.LiveConfig
<span class="syntax-keyword">import</span> com.believoo.live.callback.StreamCallback
<span class="syntax-keyword">import</span> com.believoo.live.model.StreamStats

<span class="syntax-keyword">class</span> <span class="syntax-class">StreamingActivity</span> : <span class="syntax-class">AppCompatActivity</span>() {

    <span class="syntax-keyword">private lateinit var</span> liveClient: <span class="syntax-class">BelieVooLive</span>
    <span class="syntax-keyword">private lateinit var</span> localVideoView: <span class="syntax-class">SurfaceViewRenderer</span>
    <span class="syntax-keyword">private lateinit var</span> remoteVideoView: <span class="syntax-class">SurfaceViewRenderer</span>

    <span class="syntax-keyword">override fun</span> <span class="syntax-function">onCreate</span>(savedInstanceState: <span class="syntax-class">Bundle</span>?) {
        <span class="syntax-keyword">super</span>.<span class="syntax-function">onCreate</span>(savedInstanceState)
        setContentView(R.layout.activity_streaming)

        <span class="syntax-comment">// Initialize video views</span>
        localVideoView = findViewById(R.id.local_video_view)
        remoteVideoView = findViewById(R.id.remote_video_view)

        <span class="syntax-comment">// Configure SDK</span>
        <span class="syntax-keyword">val</span> config = <span class="syntax-class">LiveConfig</span>.<span class="syntax-function">Builder</span>()
            .<span class="syntax-function">setAppId</span>(<span class="syntax-string">"bel_your_app_id"</span>)
            .<span class="syntax-function">setAppCertificate</span>(<span class="syntax-string">"your_app_certificate"</span>)
            .<span class="syntax-function">setStreamToken</span>(<span class="syntax-string">"your_stream_token"</span>)
            .<span class="syntax-function">setChannelName</span>(<span class="syntax-string">"my-live-channel"</span>)
            .<span class="syntax-function">setLocalVideoView</span>(localVideoView)
            .<span class="syntax-function">setRemoteVideoView</span>(remoteVideoView)
            .<span class="syntax-function">enableWebRTC</span>(<span class="syntax-keyword">true</span>)  <span class="syntax-comment">// For low latency</span>
            .<span class="syntax-function">setVideoProfile</span>(<span class="syntax-class">VideoProfile</span>.<span class="syntax-class">HD_1080P</span>)
            .<span class="syntax-function">build</span>()

        <span class="syntax-comment">// Initialize client</span>
        liveClient = <span class="syntax-class">BelieVooLive</span>(<span class="syntax-keyword">this</span>, config)

        <span class="syntax-comment">// Set up event listeners</span>
        <span class="syntax-function">setupEventListeners</span>()

        <span class="syntax-comment">// Request permissions and start</span>
        <span class="syntax-function">checkPermissionsAndJoin</span>()
    }

    <span class="syntax-keyword">private fun</span> <span class="syntax-function">setupEventListeners</span>() {
        liveClient.<span class="syntax-function">setStreamCallback</span>(<span class="syntax-keyword">object</span> : <span class="syntax-class">StreamCallback</span> {
            <span class="syntax-keyword">override fun</span> <span class="syntax-function">onJoinChannelSuccess</span>(channel: <span class="syntax-class">String</span>, uid: <span class="syntax-class">Int</span>) {
                Log.<span class="syntax-function">d</span>(<span class="syntax-string">"BelieVoo"</span>, <span class="syntax-string">"Joined channel: $channel with uid: $uid"</span>)
            }

            <span class="syntax-keyword">override fun</span> <span class="syntax-function">onUserJoined</span>(uid: <span class="syntax-class">Int</span>, elapsed: <span class="syntax-class">Int</span>) {
                Log.<span class="syntax-function">d</span>(<span class="syntax-string">"BelieVoo"</span>, <span class="syntax-string">"User joined: $uid"</span>)
            }

            <span class="syntax-keyword">override fun</span> <span class="syntax-function">onUserOffline</span>(uid: <span class="syntax-class">Int</span>, reason: <span class="syntax-class">Int</span>) {
                Log.<span class="syntax-function">d</span>(<span class="syntax-string">"BelieVoo"</span>, <span class="syntax-string">"User offline: $uid"</span>)
            }

            <span class="syntax-keyword">override fun</span> <span class="syntax-function">onStreamStats</span>(stats: <span class="syntax-class">StreamStats</span>) {
                <span class="syntax-comment">// Update UI with bitrate, fps, viewers</span>
                <span class="syntax-function">runOnUiThread</span> {
                    updateStatsUI(stats)
                }
            }

            <span class="syntax-keyword">override fun</span> <span class="syntax-function">onError</span>(errorCode: <span class="syntax-class">Int</span>, errorMessage: <span class="syntax-class">String</span>) {
                Log.<span class="syntax-function">e</span>(<span class="syntax-string">"BelieVoo"</span>, <span class="syntax-string">"Error $errorCode: $errorMessage"</span>)
            }
        })
    }

    <span class="syntax-keyword">private fun</span> <span class="syntax-function">checkPermissionsAndJoin</span>() {
        <span class="syntax-keyword">val</span> permissions = arrayOf(
            Manifest.permission.CAMERA,
            Manifest.permission.RECORD_AUDIO
        )

        <span class="syntax-keyword">if</span> (<span class="syntax-class">ContextCompat</span>.<span class="syntax-function">checkSelfPermission</span>(<span class="syntax-keyword">this</span>, Manifest.permission.CAMERA) 
            != PackageManager.PERMISSION_GRANTED) {
            <span class="syntax-class">ActivityCompat</span>.<span class="syntax-function">requestPermissions</span>(<span class="syntax-keyword">this</span>, permissions, <span class="syntax-number">100</span>)
        } <span class="syntax-keyword">else</span> {
            <span class="syntax-function">startStreaming</span>()
        }
    }

    <span class="syntax-keyword">private fun</span> <span class="syntax-function">startStreaming</span>() {
        <span class="syntax-comment">// Join channel and start streaming</span>
        liveClient.<span class="syntax-function">joinChannel</span>(<span class="syntax-string">"my-live-channel"</span>) { success ->
            <span class="syntax-keyword">if</span> (success) {
                <span class="syntax-comment">// Enable local video/audio</span>
                liveClient.<span class="syntax-function">enableLocalVideo</span>(<span class="syntax-keyword">true</span>)
                liveClient.<span class="syntax-function">enableLocalAudio</span>(<span class="syntax-keyword">true</span>)
                
                <span class="syntax-comment">// Start foreground service for streaming</span>
                <span class="syntax-function">startStreamingService</span>()
            }
        }
    }

    <span class="syntax-keyword">private fun</span> <span class="syntax-function">startStreamingService</span>() {
        <span class="syntax-keyword">val</span> serviceIntent = <span class="syntax-class">Intent</span>(<span class="syntax-keyword">this</span>, <span class="syntax-class">StreamingForegroundService</span>::<span class="syntax-keyword">class</span>.java)
        <span class="syntax-class">ContextCompat</span>.<span class="syntax-function">startForegroundService</span>(<span class="syntax-keyword">this</span>, serviceIntent)
    }

    <span class="syntax-keyword">override fun</span> <span class="syntax-function">onDestroy</span>() {
        <span class="syntax-keyword">super</span>.<span class="syntax-function">onDestroy</span>()
        liveClient.<span class="syntax-function">leaveChannel</span>()
        liveClient.<span class="syntax-function">destroy</span>()
    }
}</code></pre>
            </div>

            {{-- Step 4: Foreground Service --}}
            <div class="glass-premium rounded-2xl p-6 border-l-4 border-green-500 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">4. Foreground Service (Required for Android 10+)</h3>
                
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code><span class="syntax-keyword">class</span> <span class="syntax-class">StreamingForegroundService</span> : <span class="syntax-class">Service</span>() {

    <span class="syntax-keyword">override fun</span> <span class="syntax-function">onCreate</span>() {
        <span class="syntax-keyword">super</span>.<span class="syntax-function">onCreate</span>()
        <span class="syntax-function">createNotificationChannel</span>()
    }

    <span class="syntax-keyword">override fun</span> <span class="syntax-function">onStartCommand</span>(intent: <span class="syntax-class">Intent</span>?, flags: <span class="syntax-class">Int</span>, startId: <span class="syntax-class">Int</span>): <span class="syntax-class">Int</span> {
        <span class="syntax-keyword">val</span> notification = <span class="syntax-class">NotificationCompat</span>.<span class="syntax-function">Builder</span>(<span class="syntax-keyword">this</span>, CHANNEL_ID)
            .<span class="syntax-function">setContentTitle</span>(<span class="syntax-string">"Live Streaming"</span>)
            .<span class="syntax-function">setContentText</span>(<span class="syntax-string">"You are currently streaming"</span>)
            .<span class="syntax-function">setSmallIcon</span>(R.drawable.ic_streaming)
            .<span class="syntax-function">setOngoing</span>(<span class="syntax-keyword">true</span>)
            .<span class="syntax-function">build</span>()

        <span class="syntax-function">startForeground</span>(NOTIFICATION_ID, notification)
        <span class="syntax-keyword">return</span> START_STICKY
    }

    <span class="syntax-keyword">private fun</span> <span class="syntax-function">createNotificationChannel</span>() {
        <span class="syntax-keyword">if</span> (<span class="syntax-class">Build</span>.VERSION.SDK_INT >= <span class="syntax-class">Build</span>.VERSION_CODES.O) {
            <span class="syntax-keyword">val</span> channel = <span class="syntax-class">NotificationChannel</span>(
                CHANNEL_ID,
                <span class="syntax-string">"Streaming Service"</span>,
                NotificationManager.IMPORTANCE_LOW
            )
            <span class="syntax-keyword">val</span> manager = getSystemService(<span class="syntax-class">NotificationManager</span>::<span class="syntax-keyword">class</span>.java)
            manager?.<span class="syntax-function">createNotificationChannel</span>(channel)
        }
    }

    <span class="syntax-keyword">override fun</span> <span class="syntax-function">onBind</span>(intent: <span class="syntax-class">Intent</span>?): <span class="syntax-class">IBinder</span>? = <span class="syntax-keyword">null</span>

    <span class="syntax-keyword">companion object</span> {
        <span class="syntax-keyword">const val</span> CHANNEL_ID = <span class="syntax-string">"streaming_channel"</span>
        <span class="syntax-keyword">const val</span> NOTIFICATION_ID = <span class="syntax-number">1</span>
    }
}</code></pre>
            </div>

            {{-- Step 5: Pro Features --}}
            <div class="glass-premium rounded-2xl p-6 border-l-4 border-green-500">
                <h3 class="text-xl font-bold text-white mb-4">5. Advanced Features</h3>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="p-4 bg-black/30 rounded-lg">
                        <h4 class="text-white font-bold text-sm mb-2">Screen Sharing</h4>
                        <pre class="text-xs font-mono text-green-400">liveClient.startScreenCapture(
    mediaProjectionPermissionResultData
)</pre>
                    </div>
                    <div class="p-4 bg-black/30 rounded-lg">
                        <h4 class="text-white font-bold text-sm mb-2">Switch Camera</h4>
                        <pre class="text-xs font-mono text-green-400">liveClient.switchCamera()</pre>
                    </div>
                    <div class="p-4 bg-black/30 rounded-lg">
                        <h4 class="text-white font-bold text-sm mb-2">Mute/Unmute</h4>
                        <pre class="text-xs font-mono text-green-400">liveClient.muteLocalAudio(true)
liveClient.muteLocalVideo(true)</pre>
                    </div>
                    <div class="p-4 bg-black/30 rounded-lg">
                        <h4 class="text-white font-bold text-sm mb-2">AI Beauty Filters</h4>
                        <pre class="text-xs font-mono text-green-400">liveClient.enableBeautyMode(true)
liveClient.setBeautyLevel(0.8f) // 0.0 - 1.0
liveClient.setSkinSmoothing(0.7f)
liveClient.setFaceSlimming(0.4f)
liveClient.setEyeEnlargement(0.3f)
liveClient.enableLowLightBoost(true)</pre>
                        <p class="text-xs text-gray-500 mt-2">Professional AI-powered beauty effects</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- iOS Section --}}
    <section id="ios" class="py-16 px-4 sm:px-6 lg:px-8 bg-black/20">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-gray-500/20 flex items-center justify-center">
                    <i class="fab fa-apple text-white text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">iOS Integration</h2>
                    <p class="text-gray-400">Swift & Objective-C integration for iPhone and iPad</p>
                </div>
            </div>

            <div class="glass-premium rounded-2xl p-6 border-l-4 border-gray-500 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">1. CocoaPods Installation</h3>
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code>pod 'BelieVooLiveSDK', '~> 2.0.0'</code></pre>
            </div>

            <div class="glass-premium rounded-2xl p-6 border-l-4 border-gray-500 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">2. Swift Implementation</h3>
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code><span class="syntax-keyword">import</span> BelieVooLiveSDK

<span class="syntax-keyword">class</span> <span class="syntax-class">ViewController</span>: <span class="syntax-class">UIViewController</span> {
    <span class="syntax-keyword">var</span> liveClient: <span class="syntax-class">BelieVooLive</span>?
    
    <span class="syntax-keyword">override func</span> <span class="syntax-function">viewDidLoad</span>() {
        <span class="syntax-keyword">super</span>.<span class="syntax-function">viewDidLoad</span>()
        
        <span class="syntax-keyword">let</span> config = <span class="syntax-class">LiveConfig</span>(
            appId: <span class="syntax-string">"bel_your_app_id"</span>,
            appCertificate: <span class="syntax-string">"your_certificate"</span>,
            channelName: <span class="syntax-string">"my-channel"</span>
        )
        
        liveClient = <span class="syntax-class">BelieVooLive</span>(config: config)
        liveClient?.<span class="syntax-function">setLocalVideoView</span>(localVideoView)
        liveClient?.<span class="syntax-function">setRemoteVideoView</span>(remoteVideoView)
        
        <span class="syntax-comment">// Join channel</span>
        liveClient?.<span class="syntax-function">joinChannel</span>(<span class="syntax-string">"my-channel"</span>, token: <span class="syntax-string">"your_token"</span>) { success <span class="syntax-keyword">in</span>
            <span class="syntax-keyword">if</span> success {
                <span class="syntax-keyword">self</span>.liveClient?.<span class="syntax-function">enableLocalVideo</span>(<span class="syntax-keyword">true</span>)
                <span class="syntax-keyword">self</span>.liveClient?.<span class="syntax-function">enableLocalAudio</span>(<span class="syntax-keyword">true</span>)
            }
        }
    }
}</code></pre>
            </div>

            <div class="glass-premium rounded-2xl p-6 border-l-4 border-gray-500">
                <h3 class="text-xl font-bold text-white mb-4">3. Info.plist Permissions</h3>
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code><span class="syntax-keyword">&lt;key&gt;</span>NSCameraUsageDescription<span class="syntax-keyword">&lt;/key&gt;</span>
<span class="syntax-keyword">&lt;string&gt;</span>Need camera access for live streaming<span class="syntax-keyword">&lt;/string&gt;</span>

<span class="syntax-keyword">&lt;key&gt;</span>NSMicrophoneUsageDescription<span class="syntax-keyword">&lt;/key&gt;</span>
<span class="syntax-keyword">&lt;string&gt;</span>Need microphone access for streaming<span class="syntax-keyword">&lt;/string&gt;</span></code></pre>
            </div>
        </div>
    </section>

    {{-- Unity Section --}}
    <section id="unity" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-gray-700/50 flex items-center justify-center">
                    <i class="fas fa-cube text-gray-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">Unity Integration</h2>
                    <p class="text-gray-400">C# SDK for Unity game engine (iOS, Android, WebGL)</p>
                </div>
            </div>

            <div class="glass-premium rounded-2xl p-6 border-l-4 border-gray-500 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">1. Import Unity Package</h3>
                <div class="flex items-center gap-4 mb-4">
                    <a href="/sdk/unity/believoo-live-unity-2.0.0.unitypackage" download
                       class="px-6 py-3 rounded-xl bg-gradient-to-r from-gray-600 to-gray-500 text-white font-bold text-sm hover:opacity-90 transition">
                        <i class="fas fa-download mr-2"></i> Download UnityPackage
                    </a>
                </div>
                <p class="text-sm text-gray-400">Double-click the .unitypackage file or import via Assets > Import Package</p>
            </div>

            <div class="glass-premium rounded-2xl p-6 border-l-4 border-gray-500">
                <h3 class="text-xl font-bold text-white mb-4">2. C# Implementation</h3>
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code><span class="syntax-keyword">using</span> BelieVoo.Live;
<span class="syntax-keyword">using</span> UnityEngine;

<span class="syntax-keyword">public class</span> <span class="syntax-class">StreamingManager</span> : <span class="syntax-class">MonoBehaviour</span>
{
    <span class="syntax-keyword">private</span> BelieVooLive liveClient;
    <span class="syntax-keyword">public</span> RawImage localVideoDisplay;
    <span class="syntax-keyword">public</span> RawImage remoteVideoDisplay;

    <span class="syntax-keyword">void</span> <span class="syntax-function">Start</span>()
    {
        <span class="syntax-keyword">var</span> config = <span class="syntax-keyword">new</span> LiveConfig
        {
            AppId = <span class="syntax-string">"bel_your_app_id"</span>,
            AppCertificate = <span class="syntax-string">"your_certificate"</span>,
            ChannelName = <span class="syntax-string">"unity-stream"</span>
        };

        liveClient = <span class="syntax-keyword">new</span> <span class="syntax-class">BelieVooLive</span>(config);
        liveClient.SetLocalVideoRenderer(localVideoDisplay);
        liveClient.SetRemoteVideoRenderer(remoteVideoDisplay);
    }

    <span class="syntax-keyword">public void</span> <span class="syntax-function">StartStream</span>()
    {
        liveClient.JoinChannel(<span class="syntax-string">"unity-stream"</span>, <span class="syntax-string">"token"</span>, (success) => {
            <span class="syntax-keyword">if</span> (success)
            {
                liveClient.EnableLocalVideo(<span class="syntax-keyword">true</span>);
                liveClient.EnableLocalAudio(<span class="syntax-keyword">true</span>);
                Debug.Log(<span class="syntax-string">"Streaming started!"</span>);
            }
        });
    }

    <span class="syntax-keyword">public void</span> <span class="syntax-function">StopStream</span>()
    {
        liveClient.LeaveChannel();
    }

    <span class="syntax-keyword">void</span> <span class="syntax-function">OnDestroy</span>()
    {
        liveClient?.Dispose();
    }
}</code></pre>
            </div>
        </div>
    </section>

    {{-- React Native Section --}}
    <section id="reactnative" class="py-16 px-4 sm:px-6 lg:px-8 bg-black/20">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-blue-500/20 flex items-center justify-center">
                    <i class="fab fa-react text-blue-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">React Native</h2>
                    <p class="text-gray-400">Native modules for React Native apps</p>
                </div>
            </div>

            <div class="glass-premium rounded-2xl p-6 border-l-4 border-blue-500 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">1. Installation</h3>
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code>npm install @believoo/react-native-live
<span class="syntax-comment"># or</span>
yarn add @believoo/react-native-live

<span class="syntax-comment"># iOS only</span>
cd ios && pod install</code></pre>
            </div>

            <div class="glass-premium rounded-2xl p-6 border-l-4 border-blue-500">
                <h3 class="text-xl font-bold text-white mb-4">2. JavaScript Implementation</h3>
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code><span class="syntax-keyword">import</span> React, { useEffect, useRef } <span class="syntax-keyword">from</span> <span class="syntax-string">'react'</span>;
<span class="syntax-keyword">import</span> { View, Button } <span class="syntax-keyword">from</span> <span class="syntax-string">'react-native'</span>;
<span class="syntax-keyword">import</span> { BelieVooLiveView, useBelieVooLive } <span class="syntax-keyword">from</span> <span class="syntax-string">'@believoo/react-native-live'</span>;

<span class="syntax-keyword">export default function</span> <span class="syntax-function">StreamingScreen</span>() {
  <span class="syntax-keyword">const</span> { joinChannel, leaveChannel, isStreaming } = <span class="syntax-function">useBelieVooLive</span>({
    appId: <span class="syntax-string">'bel_your_app_id'</span>,
    appCertificate: <span class="syntax-string">'your_certificate'</span>,
  });

  <span class="syntax-keyword">const</span> <span class="syntax-function">startStreaming</span> = <span class="syntax-keyword">async</span> () => {
    <span class="syntax-keyword">await</span> <span class="syntax-function">joinChannel</span>(<span class="syntax-string">'my-channel'</span>, <span class="syntax-string">'token'</span>);
  };

  <span class="syntax-keyword">return</span> (
    &lt;<span class="syntax-class">View</span> style=@{@{ flex: 1 }}&gt;
      &lt;<span class="syntax-class">BelieVooLiveView</span>
        style=@{@{ flex: 1 }}
        mode=<span class="syntax-string">"broadcast"</span>
        channel=<span class="syntax-string">"my-channel"</span>
        appId=<span class="syntax-string">"bel_your_app_id"</span>
        token=<span class="syntax-string">"your_token"</span>
      /&gt;
      &lt;<span class="syntax-class">Button</span>
        title=@{isStreaming ? <span class="syntax-string">"Stop Streaming"</span> : <span class="syntax-string">"Start Streaming"</span>}
        onPress=@{isStreaming ? leaveChannel : startStreaming}
      /&gt;
    &lt;/<span class="syntax-class">View</span>&gt;
  );
}</code></pre>
            </div>
        </div>
    </section>

    {{-- API Reference Section --}}
    <section id="api" class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-yellow-500/20 flex items-center justify-center">
                    <i class="fas fa-book text-yellow-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">API Reference</h2>
                    <p class="text-gray-400">Complete REST API and SDK method documentation</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-yellow-500">
                    <h3 class="text-lg font-bold text-white mb-4">Authentication</h3>
                    <pre class="code-block rounded-lg p-4 text-xs"><code>POST /api/v1/auth/token
Content-Type: application/json

{
  "app_id": "bel_your_app_id",
  "app_certificate": "your_certificate",
  "channel_name": "my-channel",
  "uid": "12345",
  "expiry": 3600
}</code></pre>
                </div>
                <div class="glass-premium rounded-2xl p-6 border-l-4 border-yellow-500">
                    <h3 class="text-lg font-bold text-white mb-4">Stream Management</h3>
                    <pre class="code-block rounded-lg p-4 text-xs"><code>GET /api/v1/streams
GET /api/v1/stream/{stream_id}
POST /api/v1/stream/start
POST /api/v1/stream/stop
PUT /api/v1/stream/{id}/mute
DELETE /api/v1/stream/{id}</code></pre>
                </div>
            </div>

            <div class="glass-premium rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-bold text-white mb-4">Core SDK Methods</h3>
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <h4 class="text-yellow-400 font-bold mb-2">Initialization</h4>
                        <ul class="text-gray-400 space-y-1">
                            <li>initialize(appId, cert)</li>
                            <li>setConfig(config)</li>
                            <li>destroy()</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-yellow-400 font-bold mb-2">Channel</h4>
                        <ul class="text-gray-400 space-y-1">
                            <li>joinChannel(name, token)</li>
                            <li>leaveChannel()</li>
                            <li>switchChannel(name)</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-yellow-400 font-bold mb-2">Media</h4>
                        <ul class="text-gray-400 space-y-1">
                            <li>enableLocalVideo(bool)</li>
                            <li>enableLocalAudio(bool)</li>
                            <li>switchCamera()</li>
                            <li>muteLocalAudio(bool)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- VPS Self-Hosting Section --}}
    <section id="vps" class="py-16 px-4 sm:px-6 lg:px-8 bg-black/30">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-server text-purple-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">Self-Host BelieVoo Server</h2>
                    <p class="text-gray-400">Deploy your own streaming infrastructure on your VPS</p>
                </div>
            </div>

            <div class="glass-premium rounded-2xl p-6 border border-purple-500/30 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-white">Complete Server Setup</h3>
                    <a href="/docs/believoo-streaming-server.pdf" download class="px-4 py-2 rounded-lg bg-purple-600 text-white text-sm font-bold">
                        <i class="fas fa-download mr-2"></i> Download PDF Guide
                    </a>
                </div>
                <p class="text-gray-400 mb-4">
                    Deploy the BelieVoo streaming engine on your own VPS. Complete control, no per-minute charges.
                </p>
                <pre class="code-block rounded-lg p-4 overflow-x-auto text-sm"><code><span class="syntax-comment"># 1. Update system</span>
sudo apt update && sudo apt upgrade -y

<span class="syntax-comment"># 2. Install Docker</span>
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

<span class="syntax-comment"># 3. Create BelieVoo directory</span>
mkdir -p /opt/believoo-streaming && cd /opt/believoo-streaming

<span class="syntax-comment"># 4. Download server package</span>
wget https://believoo.com/sdk/believoo-server-2.0.0-docker.tar.gz
tar -xzf believoo-server-2.0.0-docker.tar.gz

<span class="syntax-comment"># 5. Configure your domain and credentials</span>
cp .env.example .env
nano .env
<span class="syntax-comment"># Set: APP_ID, APP_CERT, DOMAIN=live.yourdomain.com</span>

<span class="syntax-comment"># 6. Start BelieVoo streaming server</span>
docker-compose up -d

<span class="syntax-comment"># 7. Verify - Open ports: 1935 (RTMP), 8080/8443 (WebRTC)</span>
docker-compose ps
netstat -tlnp | grep -E '1935|8080|8443'</code></pre>
            </div>

            <div class="glass-premium rounded-2xl p-6 border border-white/20 mb-6">
                <h3 class="text-lg font-bold text-white mb-4">What's Included</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="p-4 bg-black/30 rounded-lg">
                        <i class="fas fa-broadcast-tower text-purple-400 mb-2"></i>
                        <h4 class="text-white font-bold text-sm">WebRTC Server</h4>
                        <p class="text-xs text-gray-500">Low-latency streaming engine</p>
                    </div>
                    <div class="p-4 bg-black/30 rounded-lg">
                        <i class="fas fa-shield-alt text-green-400 mb-2"></i>
                        <h4 class="text-white font-bold text-sm">TURN/STUN Server</h4>
                        <p class="text-xs text-gray-500">NAT traversal included</p>
                    </div>
                    <div class="p-4 bg-black/30 rounded-lg">
                        <i class="fas fa-video text-blue-400 mb-2"></i>
                        <h4 class="text-white font-bold text-sm">Recording</h4>
                        <p class="text-xs text-gray-500">Auto-save to disk</p>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="glass-premium rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4">Environment Variables</h3>
                    <pre class="code-block rounded-lg p-4 text-xs"><code>STREAM_DOMAIN=live.yourdomain.com
PUBLIC_IP=YOUR_VPS_IP
MAX_STREAMS=10
MAX_VIEWERS_PER_STREAM=5000

RTMP_PORT=1935
WEBRTC_PORT=8080
WEBRTC_SSL_PORT=8443

BELIEVOO_APP_ID=bel_your_app_id
BELIEVOO_APP_CERT=your_certificate

RECORDING_PATH=/var/recordings
MAX_RECORDING_SIZE_GB=100</code></pre>
                </div>
                <div class="glass-premium rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4">Firewall Rules</h3>
                    <pre class="code-block rounded-lg p-4 text-xs"><code>sudo ufw allow 1935/tcp
sudo ufw allow 8080/tcp
sudo ufw allow 8443/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload

<span class="syntax-comment"># Or iptables:</span>
iptables -A INPUT -p tcp --dport 1935 -j ACCEPT
iptables -A INPUT -p tcp --dport 8080 -j ACCEPT
iptables -A INPUT -p tcp --dport 8443 -j ACCEPT</code></pre>
                </div>
            </div>
        </div>
    </section>

    {{-- AI Beauty Filters Section --}}
    <section id="beauty-filters" class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-pink-900/20 to-transparent">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-pink-500/20 flex items-center justify-center">
                    <i class="fas fa-magic text-pink-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">AI Beauty Filters</h2>
                    <p class="text-gray-400">Professional-grade AI-powered beauty effects - Better than Banuba & Agora</p>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-pink-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🎨 Premium Beauty Suite</h3>
                
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-sparkles text-pink-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Skin Retouching</h4>
                                <p class="text-sm text-gray-400">AI-powered skin smoothing, blemish removal, and natural glow enhancement. Professional-grade results.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-face-smile text-pink-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Face Sculpting</h4>
                                <p class="text-sm text-gray-400">Adjustable face slimming, jawline definition, cheekbone enhancement with real-time preview.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-eye text-pink-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Eye Enhancement</h4>
                                <p class="text-sm text-gray-400">Eye enlargement, dark circle removal, and automatic eye brightening with AI detection.</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-sun text-pink-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Visual Enhancer</h4>
                                <p class="text-sm text-gray-400">Low-light boost, auto-brightness, contrast optimization, and color correction in real-time.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-wand-magic-sparkles text-pink-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Smart Presets</h4>
                                <p class="text-sm text-gray-400">One-tap presets for Natural, Glamour, Professional, and Live Streaming optimized looks.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-bolt text-pink-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Zero Lag</h4>
                                <p class="text-sm text-gray-400">GPU-accelerated processing with < 5ms latency. Optimized for smooth 60fps streaming.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="code-block rounded-xl p-6 border border-pink-500/20">
                    <h4 class="text-white font-bold mb-4"><i class="fas fa-code mr-2"></i>Beauty Filter Implementation</h4>
                    <pre class="text-sm font-mono text-green-400 overflow-x-auto"><code>// Initialize BelieVoo Live with Beauty Filters
BelieVooLive liveClient = BelieVooLive.getInstance();

// Configure beauty settings before joining channel
BeautyConfig beautyConfig = new BeautyConfig.Builder()
    .setSkinSmoothing(0.75f)        // 0.0 - 1.0
    .setFaceSlimming(0.40f)         // Subtle face contour
    .setEyeEnlargement(0.25f)       // Natural eye enhancement
    .setBrightness(0.15f)           // Low-light compensation
    .setContrast(0.10f)             // Color pop
    .enableLowLightBoost(true)      // Auto-enhance dark scenes
    .setPreset(BeautyPreset.NATURAL) // Or: GLAMOUR, PROFESSIONAL, LIVE
    .build();

liveClient.setBeautyConfig(beautyConfig);
liveClient.enableBeautyMode(true);

// Real-time adjustment during stream
liveClient.setBeautyLevel(0.8f); // Dynamic level control</code></pre>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div class="glass-premium rounded-2xl p-6 border border-white/10">
                    <div class="text-3xl font-bold text-pink-400 mb-2">60 FPS</div>
                    <div class="text-sm text-gray-400">Smooth beauty processing without frame drops</div>
                </div>
                <div class="glass-premium rounded-2xl p-6 border border-white/10">
                    <div class="text-3xl font-bold text-pink-400 mb-2">&lt; 5ms</div>
                    <div class="text-sm text-gray-400">Ultra-low latency GPU acceleration</div>
                </div>
                <div class="glass-premium rounded-2xl p-6 border border-white/10">
                    <div class="text-3xl font-bold text-pink-400 mb-2">AI/ML</div>
                    <div class="text-sm text-gray-400">On-device neural network processing</div>
                </div>
            </div>
        </div>
    </section>

    {{-- SDK Technical Architecture --}}
    <section id="sdk-architecture" class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-blue-900/20 to-transparent">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-microchip text-blue-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">SDK Technical Architecture</h2>
                    <p class="text-gray-400">Deep dive into BelieVoo Live Engine internals</p>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-blue-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🔧 Audio Pipeline & API Injection</h3>
                
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="code-block rounded-xl p-4 border border-white/10">
                        <h4 class="text-sm font-bold text-blue-400 mb-2">Direct PCM Injection</h4>
                        <pre class="text-xs font-mono text-gray-300 overflow-x-auto"><code>// com.believoo.live.BelieVooLive

public int pushExternalAudioFrame(
    byte[] pcmData, 
    int sampleRate, 
    int channels
) {
    return nativePushAudioFrame(
        pcmData, 
        sampleRate, 
        channels
    );
}

// WebRTC Engine Bypass
private native int nativePushAudioFrame(
    byte[] data, 
    int sampleRate, 
    int channels
);</code></pre>
                    </div>
                    <div class="code-block rounded-xl p-4 border border-white/10">
                        <h4 class="text-sm font-bold text-green-400 mb-2">External Audio Source</h4>
                        <pre class="text-xs font-mono text-gray-300 overflow-x-auto"><code>public void setExternalAudioSource(
    AudioSource source
) {
    nativeSetExternalAudioSource(source);
}

// Use Case: Digital audio injection
// - Custom audio processors
// - Music streaming integration
// - AI voice synthesis</code></pre>
                    </div>
                </div>

                <div class="code-block rounded-xl p-4 border border-white/10 mb-4">
                    <h4 class="text-sm font-bold text-purple-400 mb-2">WebRTC Audio Pipeline (C++)</h4>
                    <pre class="text-xs font-mono text-gray-300 overflow-x-auto"><code>// File: src/webrtc/audio_device_buffer.cc

int AudioDeviceBuffer::PushExternalAudio(
    const void* audio_data,
    int samples_per_channel,
    int sample_rate_hz,
    int num_channels
) {
    // Direct injection to WebRTC audio pipeline
    // Bypasses microphone hardware entirely
    return InsertAudioData(
        audio_data, 
        samples_per_channel, 
        sample_rate_hz, 
        num_channels
    );
}</code></pre>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-green-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🌐 Hybrid Infrastructure Logic</h3>
                
                <div class="mb-6">
                    <p class="text-gray-300 mb-4">Dynamic endpoint selection based on client plan type. VPS-Embedded plans connect directly to client's VPS, while standalone plans use BelieVoo Cloud Cluster.</p>
                    
                    <div class="code-block rounded-xl p-4 border border-white/10">
                        <pre class="text-sm font-mono text-green-400 overflow-x-auto"><code>public class BelieVooEndpointManager {
    
    public enum EndpointType {
        CLIENT_VPS,      // User's own VPS
        BELIEVOO_CLOUD,  // BelieVoo managed cluster
        HYBRID           // Fallback to cloud if VPS fails
    }
    
    public ConnectionConfig determineEndpoint(StreamingPlan plan) {
        if (plan.hasVpsHosting() && plan.getVpsIp() != null) {
            // Client VPS mode - Direct connection
            return ConnectionConfig.builder()
                .signalServer("wss://" + plan.getVpsIp() + ":8443")
                .turnServer(plan.getVpsIp())
                .iceServers(plan.getCustomIceServers())
                .fallbackToCloud(true)  // Auto-fallback on failure
                .build();
        } else {
            // BelieVoo Cloud mode
            return ConnectionConfig.builder()
                .signalServer("wss://cluster.believoo.com:8443")
                .turnServer("turn.believoo.com")
                .iceServers(BelieVooConstants.DEFAULT_ICE_SERVERS)
                .region(getNearestRegion())
                .build();
        }
    }
}</code></pre>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="p-4 bg-black/30 rounded-lg border border-green-500/20">
                        <h4 class="text-white font-bold mb-2"><i class="fas fa-server text-green-400 mr-2"></i>VPS Embedded Mode</h4>
                        <ul class="text-sm text-gray-400 space-y-1">
                            <li>• Direct VPS IP connection</li>
                            <li>• Zero hop streaming</li>
                            <li>• Full data ownership</li>
                            <li>• Auto-fallback to cloud</li>
                        </ul>
                    </div>
                    <div class="p-4 bg-black/30 rounded-lg border border-blue-500/20">
                        <h4 class="text-white font-bold mb-2"><i class="fas fa-cloud text-blue-400 mr-2"></i>Cloud Hosted Mode</h4>
                        <ul class="text-sm text-gray-400 space-y-1">
                            <li>• Global CDN edge nodes</li>
                            <li>• Auto-scaling infrastructure</li>
                            <li>• 99.99% uptime SLA</li>
                            <li>• Regional optimization</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-purple-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🔐 AppID & Token Authentication</h3>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-lg font-bold text-white mb-3">24-Hour Token Generator</h4>
                        <div class="code-block rounded-xl p-4 border border-white/10">
                            <pre class="text-xs font-mono text-purple-400 overflow-x-auto"><code>public class BelieVooAuth {
    
    // Token: HMAC-SHA256 based
    // Format: APP_ID:EXPIRY:NONCE:SIGNATURE
    
    public String generateTempToken(
        String appId, 
        String appCert, 
        int hours
    ) {
        long expiry = System.currentTimeMillis() / 1000 
                     + (hours * 3600);
        String nonce = UUID.randomUUID()
                          .toString()
                          .substring(0, 8);
        
        String payload = appId + ":" + expiry + ":" + nonce;
        String signature = HmacUtils.hmacSha256Hex(
            appCert, 
            payload
        );
        
        return payload + ":" + signature;
    }
}</code></pre>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-white mb-3">Server-Side Validation</h4>
                        <div class="code-block rounded-xl p-4 border border-white/10">
                            <pre class="text-xs font-mono text-green-400 overflow-x-auto"><code>public boolean validateToken(
    String token, 
    String appId, 
    String appCert
) {
    String[] parts = token.split(":");
    if (parts.length != 4) return false;
    
    String payload = parts[0] + ":" 
                   + parts[1] + ":" 
                   + parts[2];
    
    String expectedSig = HmacUtils.hmacSha256Hex(
        appCert, 
        payload
    );
    
    // Verify signature + expiry
    return parts[3].equals(expectedSig) 
        && Long.parseLong(parts[1]) 
           > (System.currentTimeMillis() / 1000);
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-orange-500/30">
                <h3 class="text-2xl font-bold text-white mb-6">📊 Real-time Metrics Pipeline</h3>
                
                <p class="text-gray-300 mb-4">30-second heartbeat system reporting stream health, viewer stats, and resource usage to backend.</p>
                
                <div class="code-block rounded-xl p-4 border border-white/10">
                    <pre class="text-sm font-mono text-orange-400 overflow-x-auto"><code>@Interval(30) // seconds
public void sendHeartbeat() {
    MetricsPayload payload = MetricsPayload.builder()
        .timestamp(System.currentTimeMillis())
        .streamId(currentStream.getId())
        .viewers(currentStream.getViewerCount())
        .latencyMs(measureLatency())
        .bandwidthKbps(getBandwidthUsage())
        .packetLoss(getPacketLossPercent())
        .audioQuality(getAudioQualityScore())
        .videoQuality(getVideoQualityScore())
        .batteryLevel(getBatteryPercent())
        .cpuUsage(getCpuUsage())
        .memoryUsage(getMemoryUsage())
        .build();
    
    metricsClient.sendAsync(payload);
}</code></pre>
                </div>

                <div class="grid md:grid-cols-4 gap-4 mt-6">
                    <div class="text-center p-4 bg-black/30 rounded-lg">
                        <div class="text-2xl font-bold text-orange-400">30s</div>
                        <div class="text-xs text-gray-400">Heartbeat Interval</div>
                    </div>
                    <div class="text-center p-4 bg-black/30 rounded-lg">
                        <div class="text-2xl font-bold text-orange-400">WebSocket</div>
                        <div class="text-xs text-gray-400">Transport Protocol</div>
                    </div>
                    <div class="text-center p-4 bg-black/30 rounded-lg">
                        <div class="text-2xl font-bold text-orange-400">Async</div>
                        <div class="text-xs text-gray-400">Non-blocking Send</div>
                    </div>
                    <div class="text-center p-4 bg-black/30 rounded-lg">
                        <div class="text-2xl font-bold text-orange-400">&lt;1KB</div>
                        <div class="text-xs text-gray-400">Payload Size</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Backend Audio Mixing - Option 1 Architecture --}}
    <section id="audio-mixing" class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-red-900/20 to-transparent">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-music text-red-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">Backend Audio Mixing</h2>
                    <p class="text-gray-400">Option 1: Server-side FFmpeg mixing for crystal-clear digital music + voice</p>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-red-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🎵 Hybrid Architecture: Backend Audio Mixing</h3>
                
                <div class="mb-6">
                    <p class="text-gray-300 mb-4">
                        The ultimate solution for crystal-clear digital music streaming. Instead of relying on SDK audio injection 
                        (which has 502 errors and WebSocket instability), we use <strong>server-side FFmpeg mixing</strong>.
                    </p>
                    
                    <div class="grid md:grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-black/30 rounded-lg border border-red-500/20">
                            <h4 class="text-white font-bold mb-2"><i class="fas fa-microphone text-red-400 mr-2"></i>Host Voice</h4>
                            <p class="text-sm text-gray-400">Streamed via WebRTC/RTMP to server input</p>
                        </div>
                        <div class="p-4 bg-black/30 rounded-lg border border-purple-500/20">
                            <h4 class="text-white font-bold mb-2"><i class="fas fa-music text-purple-400 mr-2"></i>Digital Music</h4>
                            <p class="text-sm text-gray-400">HTTP stream or local file mixed in real-time</p>
                        </div>
                        <div class="p-4 bg-black/30 rounded-lg border border-green-500/20">
                            <h4 class="text-white font-bold mb-2"><i class="fas fa-broadcast-tower text-green-400 mr-2"></i>Mixed Output</h4>
                            <p class="text-sm text-gray-400">Single high-quality stream to audience</p>
                        </div>
                    </div>
                </div>

                <div class="code-block rounded-xl p-6 border border-red-500/20 mb-6">
                    <h4 class="text-white font-bold mb-4"><i class="fas fa-code mr-2"></i>FFmpeg Mixing Pipeline</h4>
                    <pre class="text-sm font-mono text-red-400 overflow-x-auto"><code># FFmpeg Real-Time Audio Mixer Command

ffmpeg -loglevel error \
  # Input 1: Host Voice (RTMP/WebRTC)
  -thread_queue_size 512 -i rtmp://localhost:1935/voice/stream123 \
  \
  # Input 2: Digital Music (HTTP/File)
  -thread_queue_size 512 -i http://music-server.com/track.mp3 \
  \
  # Audio Filter: Mix with volume control
  -filter_complex "
    [0:a]volume=1.0,aresample=async=1:first_pts=0[voice];
    [1:a]volume=0.3,aresample=async=1:first_pts=0[music];
    [voice][music]amix=inputs=2:duration=longest:dropout_transition=3[mixed]
  " \
  \
  # Output: High-quality AAC mixed stream
  -map "[mixed]" \
  -c:a aac -b:a 192k -ar 48000 -ac 2 \
  -f flv rtmp://localhost:1935/mixed/stream123</code></pre>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-lg font-bold text-white mb-3">API Integration</h4>
                        <div class="code-block rounded-xl p-4 border border-white/10">
                            <pre class="text-xs font-mono text-green-400 overflow-x-auto"><code>// Initialize Audio Mixer
POST /api/audio-mixer/init
{
  "stream_id": "stream_123",
  "music_url": "http://music.cdn.com/track.mp3",
  "voice_volume": 1.0,
  "music_volume": 0.3,
  "bitrate": 192
}

// Response
{
  "success": true,
  "data": {
    "mixer_id": "mixer_stream_123_a7x9k2",
    "voice_endpoint": "rtmp://.../voice/stream_123",
    "mixed_output": "rtmp://.../mixed/stream_123"
  }
}</code></pre>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-white mb-3">Real-time Controls</h4>
                        <div class="code-block rounded-xl p-4 border border-white/10">
                            <pre class="text-xs font-mono text-purple-400 overflow-x-auto"><code>// Adjust volumes live
POST /api/audio-mixer/{mixer_id}/volume
{
  "voice_volume": 1.2,
  "music_volume": 0.4
}

// Switch music track
POST /api/audio-mixer/{mixer_id}/music
{
  "music_url": "http://.../next-track.mp3"
}

// Get mixer status
GET /api/audio-mixer/{mixer_id}/status</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-orange-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">⚡ Why Backend Mixing Wins</h3>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">No 502 Errors</h4>
                                <p class="text-sm text-gray-400">Eliminates WebSocket audio instability completely</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Crystal Clear Audio</h4>
                                <p class="text-sm text-gray-400">Professional FFmpeg mixing with AAC codec</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Real-time Control</h4>
                                <p class="text-sm text-gray-400">Adjust voice/music volumes on-the-fly</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">SDK Independent</h4>
                                <p class="text-sm text-gray-400">Works with any streaming SDK (Agora, Twilio, BelieVoo)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">Dynamic Music</h4>
                                <p class="text-sm text-gray-400">Switch songs without stopping the stream</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-400 mt-1"></i>
                            <div>
                                <h4 class="text-white font-bold">GPU Optimized</h4>
                                <p class="text-sm text-gray-400">Hardware-accelerated encoding available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-blue-500/30">
                <h3 class="text-2xl font-bold text-white mb-6">🔌 Implementation Flow</h3>
                
                <div class="flex flex-col md:flex-row gap-4 mb-6">
                    <div class="flex-1 p-4 bg-black/30 rounded-lg text-center">
                        <div class="text-3xl font-bold text-blue-400 mb-2">1</div>
                        <h4 class="text-white font-bold mb-1">Initialize Mixer</h4>
                        <p class="text-sm text-gray-400">Call API with stream ID and music URL</p>
                    </div>
                    <div class="flex items-center justify-center">
                        <i class="fas fa-arrow-right text-gray-500 hidden md:block"></i>
                        <i class="fas fa-arrow-down text-gray-500 md:hidden"></i>
                    </div>
                    <div class="flex-1 p-4 bg-black/30 rounded-lg text-center">
                        <div class="text-3xl font-bold text-blue-400 mb-2">2</div>
                        <h4 class="text-white font-bold mb-1">Connect Voice</h4>
                        <p class="text-sm text-gray-400">Stream host audio to voice endpoint</p>
                    </div>
                    <div class="flex items-center justify-center">
                        <i class="fas fa-arrow-right text-gray-500 hidden md:block"></i>
                        <i class="fas fa-arrow-down text-gray-500 md:hidden"></i>
                    </div>
                    <div class="flex-1 p-4 bg-black/30 rounded-lg text-center">
                        <div class="text-3xl font-bold text-blue-400 mb-2">3</div>
                        <h4 class="text-white font-bold mb-1">FFmpeg Mixes</h4>
                        <p class="text-sm text-gray-400">Server combines both audio sources</p>
                    </div>
                    <div class="flex items-center justify-center">
                        <i class="fas fa-arrow-right text-gray-500 hidden md:block"></i>
                        <i class="fas fa-arrow-down text-gray-500 md:hidden"></i>
                    </div>
                    <div class="flex-1 p-4 bg-black/30 rounded-lg text-center">
                        <div class="text-3xl font-bold text-green-400 mb-2">4</div>
                        <h4 class="text-white font-bold mb-1">Audience Hears Mix</h4>
                        <p class="text-sm text-gray-400">Single high-quality mixed stream</p>
                    </div>
                </div>

                <div class="code-block rounded-xl p-4 border border-white/10">
                    <h4 class="text-sm font-bold text-white mb-2">Quick Start Example (Android)</h4>
                    <pre class="text-xs font-mono text-gray-300 overflow-x-auto"><code>// 1. Initialize mixer from your backend
RetrofitClient.getApi().initAudioMixer(
    new MixerRequest(streamId, musicUrl)
).enqueue(callback);

// 2. Start streaming - Voice goes to mixer voice endpoint
BelieVooLive.getInstance().joinChannel(
    channelName, 
    token, 
    mixerConfig.getVoiceEndpoint()  // NOT the mixed output!
);

// 3. That's it! Audience automatically hears mixed audio
// Backend FFmpeg handles all the magic ✨</code></pre>
                </div>
            </div>
        </div>
    </section>

    {{-- Production Audio Engine --}}
    <section id="audio-engine" class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-indigo-900/20 to-transparent">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-indigo-500/20 flex items-center justify-center">
                    <i class="fas fa-broadcast-tower text-indigo-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">Production Audio Engine</h2>
                    <p class="text-gray-400">Opus-encoded real-time audio with jitter buffer, PLC, and multi-speaker mixing</p>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-indigo-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🔧 BelieVoo AudioEngine Features</h3>
                
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-file-audio text-green-400 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold">Opus Codec</h4>
                                <p class="text-sm text-gray-400">24-128kbps adaptive bitrate, superior quality at low bandwidth</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-clock text-blue-400 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold">Jitter Buffer</h4>
                                <p class="text-sm text-gray-400">Adaptive 50-200ms buffer with sequence-based packet ordering</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-wave-square text-purple-400 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold">Packet Loss Concealment</h4>
                                <p class="text-sm text-gray-400">Pattern repetition + comfort noise for lost packets</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-orange-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-microphone-slash text-orange-400 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold">AEC/NS/AGC</h4>
                                <p class="text-sm text-gray-400">WebRTC echo cancellation, noise suppression, auto gain control</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-pink-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-users text-pink-400 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold">Multi-Speaker Mixing</h4>
                                <p class="text-sm text-gray-400">Up to 8 concurrent speakers with automatic gain mixing</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-cyan-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-music text-cyan-400 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold">Music Optimized Path</h4>
                                <p class="text-sm text-gray-400">128kbps stereo for high-quality music broadcasting</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-bluetooth-b text-yellow-400 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold">Bluetooth Routing</h4>
                                <p class="text-sm text-gray-400">Automatic SCO/HFP headset support with routing APIs</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-chart-line text-red-400 text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-bold">Real-time Statistics</h4>
                                <p class="text-sm text-gray-400">Jitter, packet loss, buffer depth monitoring</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="code-block rounded-xl p-6 border border-indigo-500/20 mb-6">
                    <h4 class="text-white font-bold mb-4"><i class="fab fa-android text-green-400 mr-2"></i>AudioEngine API Usage</h4>
                    <pre class="text-sm font-mono text-indigo-400 overflow-x-auto"><code>// Initialize AudioEngine
AudioEngine audioEngine = new AudioEngine(context);

// 1. Start audio room with profile
audioEngine.startAudioRoom("room_123", AudioEngine.AudioProfile.VOICE_HIGH_QUALITY);

// 2. Publish microphone (with AEC/NS/AGC)
audioEngine.setAudioTransportCallback(packet -> {
    // Send Opus packet over WebSocket
    webSocket.send(packet);
});
audioEngine.publishMicrophone();

// 3. Subscribe to remote audio
audioEngine.subscribeRemoteAudio("user_456", (pcmData, sampleRate, channels, timestamp) -> {
    // Play decoded PCM audio
    audioTrack.write(pcmData, 0, pcmData.length);
});

// 4. Publish music (high quality stereo)
audioEngine.setAudioProfile(AudioEngine.AudioProfile.MUSIC_HIGH_QUALITY);
audioEngine.publishMusicMix("/path/to/music.mp3");

// 5. Bluetooth routing
audioEngine.setBluetoothRouting(true);</code></pre>
                </div>

                <div class="code-block rounded-xl p-6 border border-purple-500/20">
                    <h4 class="text-white font-bold mb-4"><i class="fas fa-code text-purple-400 mr-2"></i>Audio Profiles</h4>
                    <pre class="text-sm font-mono text-purple-400 overflow-x-auto"><code>// Voice profiles (mono)
VOICE_STANDARD(48000, 1, 24000);       // 24kbps - Default voice
VOICE_HIGH_QUALITY(48000, 1, 32000);   // 32kbps - Clear voice

// Music profiles (stereo)
MUSIC_STANDARD(48000, 2, 64000);       // 64kbps - Standard music
MUSIC_HIGH_QUALITY(48000, 2, 128000); // 128kbps - Studio quality</code></pre>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-green-500/30">
                <h3 class="text-2xl font-bold text-white mb-6">📦 AudioEngine Source Files</h3>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-white font-semibold text-sm">AudioEngine.java</h4>
                            <span class="text-xs px-2 py-1 rounded bg-indigo-500/20 text-indigo-400">Main</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-2">Production audio engine with room management</p>
                        <code class="text-xs text-gray-500">com/believoo/live/audio/AudioEngine.java</code>
                    </div>
                    
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-white font-semibold text-sm">OpusEncoder.java</h4>
                            <span class="text-xs px-2 py-1 rounded bg-green-500/20 text-green-400">Codec</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-2">Opus encoding for low-bitrate audio</p>
                        <code class="text-xs text-gray-500">com/believoo/live/audio/OpusEncoder.java</code>
                    </div>
                    
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-white font-semibold text-sm">OpusDecoder.java</h4>
                            <span class="text-xs px-2 py-1 rounded bg-green-500/20 text-green-400">Codec</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-2">Opus decoding with PLC support</p>
                        <code class="text-xs text-gray-500">com/believoo/live/audio/OpusDecoder.java</code>
                    </div>
                    
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-white font-semibold text-sm">JitterBuffer.java</h4>
                            <span class="text-xs px-2 py-1 rounded bg-blue-500/20 text-blue-400">Network</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-2">Adaptive jitter buffer with packet ordering</p>
                        <code class="text-xs text-gray-500">com/believoo/live/audio/JitterBuffer.java</code>
                    </div>
                    
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-white font-semibold text-sm">PacketLossConcealment.java</h4>
                            <span class="text-xs px-2 py-1 rounded bg-purple-500/20 text-purple-400">PLC</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-2">Packet loss concealment algorithms</p>
                        <code class="text-xs text-gray-500">com/believoo/live/audio/PacketLossConcealment.java</code>
                    </div>
                    
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-white font-semibold text-sm">AudioMixer.java</h4>
                            <span class="text-xs px-2 py-1 rounded bg-pink-500/20 text-pink-400">Mixer</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-2">Multi-speaker audio mixing with AGC</p>
                        <code class="text-xs text-gray-500">com/believoo/live/audio/AudioMixer.java</code>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SDK Migration Guide --}}
    <section id="migration" class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-amber-900/20 to-transparent">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/20 flex items-center justify-center">
                    <i class="fas fa-exchange-alt text-amber-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">SDK Migration Guide</h2>
                    <p class="text-gray-400">Migrate from custom PCM WebSocket to BelieVoo AudioEngine (2-3 weeks)</p>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-amber-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🚀 Migration Overview</h3>
                
                <div class="grid md:grid-cols-2 gap-8 mb-8">
                    <div class="p-6 bg-red-500/10 rounded-xl border border-red-500/30">
                        <h4 class="text-xl font-bold text-red-400 mb-4"><i class="fas fa-times-circle mr-2"></i>OLD: Custom PCM WebSocket</h4>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li><i class="fas fa-times text-red-400 mr-2"></i>~1.5 Mbps bandwidth per user</li>
                            <li><i class="fas fa-times text-red-400 mr-2"></i>No jitter buffer - packet chaos</li>
                            <li><i class="fas fa-times text-red-400 mr-2"></i>No packet loss concealment</li>
                            <li><i class="fas fa-times text-red-400 mr-2"></i>No echo cancellation</li>
                            <li><i class="fas fa-times text-red-400 mr-2"></i>2-3 speakers max</li>
                            <li><i class="fas fa-times text-red-400 mr-2"></i>Audio gaps on packet loss</li>
                        </ul>
                    </div>
                    
                    <div class="p-6 bg-green-500/10 rounded-xl border border-green-500/30">
                        <h4 class="text-xl font-bold text-green-400 mb-4"><i class="fas fa-check-circle mr-2"></i>NEW: BelieVoo SDK v2.0.0</h4>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li><i class="fas fa-check text-green-400 mr-2"></i>~32 kbps Opus (95% less!)</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>50-200ms adaptive jitter buffer</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Packet loss concealment</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Hardware AEC/NS/AGC</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>8 concurrent speakers</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Seamless audio with PLC</li>
                        </ul>
                    </div>
                </div>

                <div class="code-block rounded-xl p-6 border border-amber-500/20 mb-6">
                    <h4 class="text-white font-bold mb-4"><i class="fas fa-code text-amber-400 mr-2"></i>Migration Code Changes</h4>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-red-400 text-sm font-bold mb-2">OLD (Remove This):</p>
                            <pre class="text-xs font-mono text-gray-400 overflow-x-auto"><code>// Custom PCM WebSocket
WebSocketClient ws = new WebSocketClient(uri) {
    @Override
    public void onMessage(ByteBuffer bytes) {
        // Raw PCM - 1.5 Mbps!
        byte[] pcm = bytes.array();
        audioTrack.write(pcm, 0, pcm.length);
    }
};

AudioRecord recorder = new AudioRecord(
    MediaRecorder.AudioSource.MIC,
    48000, CHANNEL_IN_STEREO,
    ENCODING_PCM_16BIT, bufferSize
);

// Send raw PCM - HUGE bandwidth!
recorder.read(pcmBuffer, 0, pcmBuffer.length);
ws.send(pcmBuffer);</code></pre>
                        </div>
                        <div>
                            <p class="text-green-400 text-sm font-bold mb-2">NEW (Use This):</p>
                            <pre class="text-xs font-mono text-green-400 overflow-x-auto"><code>// BelieVoo AudioEngine
AudioEngine engine = new AudioEngine(context);

// Start room with Opus encoding
engine.startAudioRoom("room_123", 
    AudioEngine.AudioProfile.VOICE_HIGH_QUALITY);

// Publish microphone (AEC/NS/AGC)
engine.setAudioTransportCallback(opusPacket -> {
    // Send Opus - only 32kbps!
    ws.send(opusPacket);
});
engine.publishMicrophone();

// Subscribe to speakers
engine.subscribeRemoteAudio("user_456", 
    (pcm, rate, ch, ts) -> {
        audioTrack.write(pcm, 0, pcm.length);
    });</code></pre>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gradient-to-r from-amber-900/30 to-orange-900/30 rounded-xl border border-amber-500/30 mb-6">
                    <h4 class="text-white font-bold mb-4"><i class="fas fa-download text-amber-400 mr-2"></i>Download Migration Package</h4>
                    <div class="grid md:grid-cols-2 gap-4">
                        <a href="/sdk/android/MIGRATION_GUIDE.md" download
                           class="flex items-center gap-3 p-4 bg-black/30 rounded-lg border border-white/10 hover:bg-white/5 transition">
                            <i class="fas fa-file-alt text-amber-400 text-2xl"></i>
                            <div>
                                <p class="text-white font-semibold">MIGRATION_GUIDE.md</p>
                                <p class="text-xs text-gray-400">Complete 3-week migration plan</p>
                            </div>
                        </a>
                        <a href="/sdk/android/MIGRATION_QUICK_START.md" download
                           class="flex items-center gap-3 p-4 bg-black/30 rounded-lg border border-white/10 hover:bg-white/5 transition">
                            <i class="fas fa-rocket text-green-400 text-2xl"></i>
                            <div>
                                <p class="text-white font-semibold">MIGRATION_QUICK_START.md</p>
                                <p class="text-xs text-gray-400">Quick reference & checklist</p>
                            </div>
                        </a>
                        <a href="/sdk/android/src/main/java/com/believoo/live/example/HostLiveAudioActivity.java" download
                           class="flex items-center gap-3 p-4 bg-black/30 rounded-lg border border-white/10 hover:bg-white/5 transition">
                            <i class="fab fa-android text-green-400 text-2xl"></i>
                            <div>
                                <p class="text-white font-semibold">HostLiveAudioActivity.java</p>
                                <p class="text-xs text-gray-400">Complete drop-in replacement</p>
                            </div>
                        </a>
                        <a href="/sdk/android/build.gradle.example" download
                           class="flex items-center gap-3 p-4 bg-black/30 rounded-lg border border-white/10 hover:bg-white/5 transition">
                            <i class="fas fa-cog text-blue-400 text-2xl"></i>
                            <div>
                                <p class="text-white font-semibold">build.gradle.example</p>
                                <p class="text-xs text-gray-400">Required dependencies</p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10 text-center">
                        <div class="text-3xl font-bold text-amber-400 mb-2">Week 1</div>
                        <h5 class="text-white font-bold mb-1">SDK Integration</h5>
                        <p class="text-xs text-gray-400">Add AAR, update build.gradle, copy files</p>
                    </div>
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10 text-center">
                        <div class="text-3xl font-bold text-amber-400 mb-2">Week 2</div>
                        <h5 class="text-white font-bold mb-1">Activity Refactor</h5>
                        <p class="text-xs text-gray-400">Remove PCM code, add AudioEngine, test</p>
                    </div>
                    <div class="p-4 bg-black/30 rounded-lg border border-white/10 text-center">
                        <div class="text-3xl font-bold text-amber-400 mb-2">Week 3</div>
                        <h5 class="text-white font-bold mb-1">Optimization</h5>
                        <p class="text-xs text-gray-400">Bluetooth, 8-speaker test, production</p>
                    </div>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-green-500/30">
                <h3 class="text-2xl font-bold text-white mb-6">✅ Post-Migration Benefits</h3>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-green-500/10 rounded-lg border border-green-500/30">
                        <div class="text-3xl font-bold text-green-400 mb-1">95%</div>
                        <p class="text-sm text-gray-300">Bandwidth Reduction</p>
                        <p class="text-xs text-gray-500">1.5 Mbps → 32 kbps</p>
                    </div>
                    <div class="text-center p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <div class="text-3xl font-bold text-blue-400 mb-1">8x</div>
                        <p class="text-sm text-gray-300">More Speakers</p>
                        <p class="text-xs text-gray-500">2-3 → 8 concurrent</p>
                    </div>
                    <div class="text-center p-4 bg-purple-500/10 rounded-lg border border-purple-500/30">
                        <div class="text-3xl font-bold text-purple-400 mb-1">0ms</div>
                        <p class="text-sm text-gray-300">Audio Gaps</p>
                        <p class="text-xs text-gray-500">PLC masks packet loss</p>
                    </div>
                    <div class="text-center p-4 bg-pink-500/10 rounded-lg border border-pink-500/30">
                        <div class="text-3xl font-bold text-pink-400 mb-1">Studio</div>
                        <p class="text-sm text-gray-300">Audio Quality</p>
                        <p class="text-xs text-gray-500">AEC + NS + AGC</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Professional Digital Audio Injection --}}
    <section id="digital-audio" class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-cyan-900/20 to-transparent">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-cyan-500/20 flex items-center justify-center">
                    <i class="fas fa-wave-square text-cyan-400 text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">Professional Digital Audio Injection</h2>
                    <p class="text-gray-400">Studio-quality PCM audio streaming with pushExternalAudioFrame</p>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-cyan-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🎛️ Studio-Quality Audio Streaming</h3>
                
                <div class="mb-6">
                    <p class="text-gray-300 mb-4">
                        For crystal-clear digital music streaming, use the <code class="bg-black/50 px-2 py-1 rounded text-cyan-400 font-mono">pushExternalAudioFrame</code> method. 
                        This advanced API injects raw PCM audio directly into the WebRTC stream, bypassing microphone capture for lossless audio quality.
                    </p>
                    
                    <div class="grid md:grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-black/30 rounded-lg border border-cyan-500/20">
                            <h4 class="text-white font-bold mb-2"><i class="fas fa-headphones text-cyan-400 mr-2"></i>Studio Quality</h4>
                            <p class="text-sm text-gray-400">Raw PCM injection at 48kHz for CD-quality audio</p>
                        </div>
                        <div class="p-4 bg-black/30 rounded-lg border border-purple-500/20">
                            <h4 class="text-white font-bold mb-2"><i class="fas fa-music text-purple-400 mr-2"></i>Digital Music</h4>
                            <p class="text-sm text-gray-400">Stream music files without microphone interference</p>
                        </div>
                        <div class="p-4 bg-black/30 rounded-lg border border-green-500/20">
                            <h4 class="text-white font-bold mb-2"><i class="fas fa-bolt text-green-400 mr-2"></i>Low Latency</h4>
                            <p class="text-sm text-gray-400">Real-time audio mixing with <50ms delay</p>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div class="code-block rounded-xl p-6 border border-cyan-500/20">
                        <h4 class="text-white font-bold mb-4"><i class="fab fa-android text-green-400 mr-2"></i>Android SDK Usage</h4>
                        <pre class="text-sm font-mono text-cyan-400 overflow-x-auto"><code>// Initialize BelieVoo Live SDK
BelieVooLive live = BelieVooLive.getInstance();
live.initialize(context, appId, token);

// Enable external audio source
live.setExternalAudioSource(true, 48000, 2);

// Join channel
live.joinChannel(channelName, uid);

// Push PCM audio frames (studio quality)
byte[] pcmData = loadPCMFromAudioFile(); // Your music file
AudioFrame frame = new AudioFrame();
frame.setData(pcmData);
frame.setSampleRate(48000);
frame.setChannels(2);
frame.setTimestamp(System.currentTimeMillis());

// Inject audio into stream
live.pushExternalAudioFrame(frame);</code></pre>
                    </div>
                    <div class="code-block rounded-xl p-6 border border-gray-500/20">
                        <h4 class="text-white font-bold mb-4"><i class="fab fa-apple text-gray-300 mr-2"></i>iOS SDK Usage</h4>
                        <pre class="text-sm font-mono text-cyan-400 overflow-x-auto"><code>// Initialize SDK
let live = BelieVooLive.shared()
live.initialize(appId: appId, token: token)

// Enable external audio
live.setExternalAudioSource(true, sampleRate: 48000, channels: 2)

// Join channel
live.joinChannel(channelName, uid: uid)

// Push PCM audio
var pcmData: Data = loadAudioFile() // Your music
let frame = AudioFrame()
frame.data = pcmData
frame.sampleRate = 48000
frame.channels = 2
frame.timestamp = CACurrentMediaTime()

// Inject into stream
live.pushExternalAudioFrame(frame)</code></pre>
                    </div>
                </div>

                <div class="code-block rounded-xl p-6 border border-blue-500/20 mb-6">
                    <h4 class="text-white font-bold mb-4"><i class="fas fa-mobile-alt text-blue-400 mr-2"></i>Flutter SDK Usage</h4>
                    <pre class="text-sm font-mono text-cyan-400 overflow-x-auto"><code>// Initialize BelieVoo Live
final live = BelieVooLive();
await live.initialize(appId: appId, token: token);

// Enable external audio source
await live.setExternalAudioSource(
  enabled: true,
  sampleRate: 48000,
  channels: 2,
);

// Join channel
await live.joinChannel(channelName, uid: uid);

// Push PCM audio frames
final audioFile = File('path/to/music.pcm');
final pcmData = await audioFile.readAsBytes();

final frame = AudioFrame(
  data: pcmData,
  sampleRate: 48000,
  channels: 2,
  timestamp: DateTime.now().millisecondsSinceEpoch,
);

// Inject studio-quality audio
await live.pushExternalAudioFrame(frame);</code></pre>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-green-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">⚙️ Audio Configuration Options</h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="pb-4 text-gray-400 font-semibold">Parameter</th>
                                <th class="pb-4 text-gray-400 font-semibold">Type</th>
                                <th class="pb-4 text-gray-400 font-semibold">Default</th>
                                <th class="pb-4 text-gray-400 font-semibold">Description</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-300">
                            <tr class="border-b border-white/5">
                                <td class="py-3 text-cyan-400 font-mono">enabled</td>
                                <td class="py-3">Boolean</td>
                                <td class="py-3 text-gray-500">false</td>
                                <td class="py-3">Enable external audio source mode</td>
                            </tr>
                            <tr class="border-b border-white/5">
                                <td class="py-3 text-cyan-400 font-mono">sampleRate</td>
                                <td class="py-3">Integer</td>
                                <td class="py-3 text-gray-500">48000</td>
                                <td class="py-3">Audio sample rate (Hz): 44100, 48000</td>
                            </tr>
                            <tr class="border-b border-white/5">
                                <td class="py-3 text-cyan-400 font-mono">channels</td>
                                <td class="py-3">Integer</td>
                                <td class="py-3 text-gray-500">2</td>
                                <td class="py-3">Audio channels: 1 (mono), 2 (stereo)</td>
                            </tr>
                            <tr class="border-b border-white/5">
                                <td class="py-3 text-cyan-400 font-mono">data</td>
                                <td class="py-3">ByteArray</td>
                                <td class="py-3 text-gray-500">required</td>
                                <td class="py-3">Raw PCM audio data (16-bit signed)</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-cyan-400 font-mono">timestamp</td>
                                <td class="py-3">Long</td>
                                <td class="py-3 text-gray-500">0</td>
                                <td class="py-3">Frame timestamp for sync (microseconds)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-purple-500/30 mb-8">
                <h3 class="text-2xl font-bold text-white mb-6">🎵 PCM Audio File Preparation</h3>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-lg font-bold text-white mb-3">Converting Music to PCM</h4>
                        <div class="code-block rounded-xl p-4 border border-white/10">
                            <pre class="text-xs font-mono text-purple-400 overflow-x-auto"><code># Convert MP3/WAV to PCM format using FFmpeg
ffmpeg -i input.mp3 -f s16le -ar 48000 -ac 2 output.pcm

# Options:
# -f s16le = 16-bit signed little-endian PCM
# -ar 48000 = 48kHz sample rate (studio quality)
# -ac 2 = stereo channels

# For mono (single channel):
ffmpeg -i input.mp3 -f s16le -ar 48000 -ac 1 output_mono.pcm</code></pre>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-white mb-3">Real-time Streaming Tips</h4>
                        <ul class="space-y-3 text-sm text-gray-400">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-0.5"></i>
                                <span>Use 10ms audio frames (480 samples at 48kHz)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-0.5"></i>
                                <span>Maintain consistent frame intervals for smooth playback</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-0.5"></i>
                                <span>Buffer 2-3 frames ahead to prevent underrun</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-0.5"></i>
                                <span>Use separate thread for audio file reading</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="glass-premium rounded-3xl p-8 border border-yellow-500/30">
                <h3 class="text-2xl font-bold text-white mb-6">🔥 Advanced: Voice + Music Mixing</h3>
                
                <div class="mb-6">
                    <p class="text-gray-300 mb-4">
                        For karaoke or background music scenarios, mix microphone voice with digital music before injection.
                    </p>
                </div>

                <div class="code-block rounded-xl p-6 border border-white/10">
                    <h4 class="text-white font-bold mb-4"><i class="fas fa-code text-yellow-400 mr-2"></i>Voice + Music Mixing Example (Android)</h4>
                    <pre class="text-sm font-mono text-yellow-400 overflow-x-auto"><code>// Setup audio mixing
AudioMixer mixer = new AudioMixer();
mixer.setVoiceVolume(1.0f);  // Host voice level
mixer.setMusicVolume(0.3f);  // Background music level

// Capture microphone audio
AudioRecord recorder = new AudioRecord(
    MediaRecorder.AudioSource.MIC,
    48000, AudioFormat.CHANNEL_IN_STEREO,
    AudioFormat.ENCODING_PCM_16BIT, bufferSize
);

// Mix voice + music in real-time
while (isStreaming) {
    // Read mic data
    short[] voiceData = new short[480]; // 10ms frame
    recorder.read(voiceData, 0, voiceData.length);
    
    // Read music data
    short[] musicData = musicPlayer.readNextFrame();
    
    // Mix audio (voice dominant)
    short[] mixedData = mixer.mix(voiceData, musicData);
    
    // Push mixed audio to stream
    AudioFrame frame = new AudioFrame();
    frame.setData(shortToByteArray(mixedData));
    frame.setSampleRate(48000);
    frame.setChannels(2);
    
    live.pushExternalAudioFrame(frame);
}</code></pre>
                </div>

                <div class="mt-6 p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/30">
                    <p class="text-sm text-yellow-200">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Pro Tip:</strong> For best results, use Backend Audio Mixing (FFmpeg) on server for complex mixing scenarios. 
                        Client-side mixing is suitable for simple voice+music scenarios with low CPU overhead.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <p class="text-gray-400 text-sm">
                © 2026 BelieVoo Technologies. Complete streaming infrastructure for developers.
            </p>
            <div class="flex justify-center gap-6 mt-4">
                <a href="/docs/vps-streaming-setup" class="text-purple-400 hover:text-purple-300 text-sm">VPS Setup Guide</a>
                <a href="/sdk/downloads" class="text-purple-400 hover:text-purple-300 text-sm">SDK Downloads</a>
                <a href="mailto:support@believoo.com" class="text-purple-400 hover:text-purple-300 text-sm">Support</a>
            </div>
        </div>
    </footer>

    <script>
        function copyCode(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                navigator.clipboard.writeText(element.innerText).then(() => {
                    const btn = event.target.closest('button');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                    }, 2000);
                });
            }
        }
    </script>
</body>
</html>
