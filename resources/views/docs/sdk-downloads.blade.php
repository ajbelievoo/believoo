<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BelieVoo Live SDK Downloads</title>
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
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black text-gray-300 font-sans min-h-screen">
    
    <div class="max-w-5xl mx-auto px-4 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-white mb-4">BelieVoo <span class="gradient-text">SDK Downloads</span></h1>
            <p class="text-gray-400">Download native SDKs for your platform</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-12">
            {{-- Android SDK --}}
            <div class="glass rounded-2xl p-6 border border-purple-500/30">
                <div class="w-14 h-14 rounded-xl bg-green-500/20 flex items-center justify-center mb-4">
                    <i class="fab fa-android text-green-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Android SDK</h3>
                <p class="text-sm text-gray-400 mb-4">Kotlin & Java support. Min SDK 21+</p>
                
                <div class="space-y-3">
                    <a href="/sdk/android/believoo-live-sdk-2.0.0.aar" download
                       class="block w-full text-center px-4 py-3 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 text-white font-bold text-sm hover:opacity-90 transition">
                        <i class="fas fa-download mr-2"></i> Download AAR (2.0.0)
                    </a>
                    <p class="text-xs text-center text-gray-500 mt-2">Size: 2.5 MB • Updated: May 2026</p>
                    
                    <div class="p-3 bg-black/30 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Gradle Dependency:</p>
                        <code class="text-xs text-green-400 font-mono">implementation 'com.believoo:live-sdk:2.0.0'</code>
                    </div>
                </div>
            </div>

            {{-- Flutter SDK --}}
            <div class="glass rounded-2xl p-6 border border-blue-500/30">
                <div class="w-14 h-14 rounded-xl bg-blue-500/20 flex items-center justify-center mb-4">
                    <i class="fas fa-mobile-alt text-blue-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Flutter SDK</h3>
                <p class="text-sm text-gray-400 mb-4">Cross-platform. iOS & Android</p>
                
                <div class="space-y-3">
                    <a href="/sdk/flutter/believoo_live-2.0.0.zip" download
                       class="block w-full text-center px-4 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-bold text-sm hover:opacity-90 transition">
                        <i class="fas fa-download mr-2"></i> Download ZIP (2.0.0)
                    </a>
                    <p class="text-xs text-center text-gray-500 mt-2">Size: 1.8 MB • Updated: May 2026</p>
                    
                    <div class="p-3 bg-black/30 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Pubspec.yaml:</p>
                        <code class="text-xs text-blue-400 font-mono">believoo_live: ^2.0.0</code>
                    </div>
                </div>
            </div>

            {{-- React SDK --}}
            <div class="glass rounded-2xl p-6 border border-cyan-500/30">
                <div class="w-14 h-14 rounded-xl bg-cyan-500/20 flex items-center justify-center mb-4">
                    <i class="fab fa-react text-cyan-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">React SDK</h3>
                <p class="text-sm text-gray-400 mb-4">Web streaming with hooks</p>
                
                <div class="space-y-3">
                    <a href="/sdk/react/believoo-react-live-2.0.0.tgz" download
                       class="block w-full text-center px-4 py-3 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-600 text-white font-bold text-sm hover:opacity-90 transition">
                        <i class="fas fa-download mr-2"></i> Download TGZ (2.0.0)
                    </a>
                    <p class="text-xs text-center text-gray-500 mt-2">Size: 850 KB • Updated: May 2026</p>
                    
                    <div class="p-3 bg-black/30 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">NPM Install:</p>
                        <code class="text-xs text-cyan-400 font-mono">npm i @believoo/react-live</code>
                    </div>
                </div>
            </div>
        </div>

        {{-- Maven Repository Info --}}
        <div class="glass rounded-2xl p-6 border border-white/10">
            <h3 class="text-lg font-bold text-white mb-4"><i class="fas fa-code-branch mr-2"></i> Maven Repository Configuration</h3>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-400 mb-2">Add to your project-level build.gradle:</p>
                    <pre class="bg-black/50 rounded-lg p-4 text-xs font-mono text-green-400 overflow-x-auto">allprojects {
    repositories {
        google()
        mavenCentral()
        maven { url 'https://maven.believoo.com/repository/releases' }
    }
}</pre>
                </div>
                
                <div>
                    <p class="text-sm text-gray-400 mb-2">Add to app-level build.gradle:</p>
                    <pre class="bg-black/50 rounded-lg p-4 text-xs font-mono text-green-400 overflow-x-auto">dependencies {
    implementation 'com.believoo:live-sdk:2.0.0'
    // OR for WebRTC enhanced:
    implementation 'com.believoo:live-sdk-webrtc:2.0.0'
}</pre>
                </div>
            </div>
        </div>

        {{-- Direct Links --}}
        <div class="mt-8 glass rounded-2xl p-6 border border-white/10">
            <h3 class="text-lg font-bold text-white mb-4"><i class="fas fa-link mr-2"></i> Direct Download Links</h3>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between p-3 bg-black/30 rounded-lg">
                    <span class="text-gray-400">Android AAR (Direct)</span>
                    <code class="text-xs text-cyan-400">https://cdn.believoo.com/sdk/android/believoo-live-sdk-2.0.0.aar</code>
                </div>
                <div class="flex items-center justify-between p-3 bg-black/30 rounded-lg">
                    <span class="text-gray-400">Maven Repository</span>
                    <code class="text-xs text-cyan-400">https://maven.believoo.com/repository/releases/com/believoo/live-sdk/2.0.0/</code>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="/docs/streaming" class="text-purple-400 hover:text-purple-300 text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to Documentation
            </a>
        </div>
    </div>

</body>
</html>
