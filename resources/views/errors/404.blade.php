<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found - Believoo</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        :root {
            --electric-blue: #00B7FF;
            --electric-violet: #7000FF;
            --dark: #050505;
        }
        body {
            background-color: var(--dark);
            font-family: 'Figtree', sans-serif;
        }
        .bg-dark { background-color: var(--dark); }
        .text-electric-blue { color: var(--electric-blue); }
        .bg-electric-blue { background-color: var(--electric-blue); }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes pulse-slow {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
        .animate-pulse-slow {
            animation: pulse-slow 8s ease-in-out infinite;
        }

        .glitch-text {
            position: relative;
            text-shadow: 0.05em 0 0 rgba(255, 0, 0, 0.75),
                        -0.025em -0.05em 0 rgba(0, 255, 0, 0.75),
                        0.025em 0.05em 0 rgba(0, 0, 255, 0.75);
            animation: glitch 500ms infinite;
        }

        @keyframes glitch {
            0% { text-shadow: 0.05em 0 0 rgba(255, 0, 0, 0.75), -0.05em -0.025em 0 rgba(0, 255, 0, 0.75), -0.025em 0.05em 0 rgba(0, 0, 255, 0.75); }
            14% { text-shadow: 0.05em 0 0 rgba(255, 0, 0, 0.75), -0.05em -0.025em 0 rgba(0, 255, 0, 0.75), -0.025em 0.05em 0 rgba(0, 0, 255, 0.75); }
            15% { text-shadow: -0.05em -0.025em 0 rgba(255, 0, 0, 0.75), 0.025em 0.025em 0 rgba(0, 255, 0, 0.75), -0.05em -0.05em 0 rgba(0, 0, 255, 0.75); }
            49% { text-shadow: -0.05em -0.025em 0 rgba(255, 0, 0, 0.75), 0.025em 0.025em 0 rgba(0, 255, 0, 0.75), -0.05em -0.05em 0 rgba(0, 0, 255, 0.75); }
            50% { text-shadow: 0.025em 0.05em 0 rgba(255, 0, 0, 0.75), 0.05em 0 0 rgba(0, 255, 0, 0.75), 0 -0.05em 0 rgba(0, 0, 255, 0.75); }
            99% { text-shadow: 0.025em 0.05em 0 rgba(255, 0, 0, 0.75), 0.05em 0 0 rgba(0, 255, 0, 0.75), 0 -0.05em 0 rgba(0, 0, 255, 0.75); }
            100% { text-shadow: -0.025em 0 0 rgba(255, 0, 0, 0.75), -0.025em -0.025em 0 rgba(0, 255, 0, 0.75), -0.025em -0.05em 0 rgba(0, 0, 255, 0.75); }
        }
    </style>
</head>
<body class="antialiased text-gray-200">
    <div class="min-h-screen bg-dark flex flex-col items-center justify-center px-6 relative overflow-hidden">
        <!-- Animated Background Orbs -->
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-electric-blue/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-electric-violet/10 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: -4s;"></div>

        <!-- Main Content -->
        <div class="relative z-10 flex flex-col items-center text-center">
            <!-- Large 404 Text -->
            <div class="relative mb-8">
                <h1 class="text-[10rem] md:text-[15rem] font-black text-white leading-none tracking-tighter select-none glitch-text">
                    404
                </h1>
                <div class="absolute -top-4 -right-4 w-12 h-12 bg-electric-blue rounded-full animate-ping opacity-20"></div>
            </div>

            <!-- Error Message -->
            <div class="max-w-xl mx-auto space-y-6">
                <h2 class="text-3xl md:text-5xl font-black text-white uppercase tracking-tight">
                    Page <span class="text-electric-blue">Not Found</span>
                </h2>
                <p class="text-gray-400 text-lg font-medium leading-relaxed">
                    Oops! It looks like you've wandered into an uncharted digital territory. 
                    The page you're looking for doesn't exist or has been moved.
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-10">
                    <a href="/" class="group relative px-10 py-5 bg-white text-black font-black uppercase tracking-widest text-xs rounded-2xl hover:scale-105 transition-all duration-300">
                        <span class="relative z-10">Return to Home</span>
                    </a>
                    
                    <button onclick="history.back()" class="px-10 py-5 bg-white/5 border border-white/10 text-white font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-white/10 transition-all duration-300">
                        Go Back
                    </button>
                </div>
            </div>

            <!-- Footer / Contact -->
            <div class="mt-20 flex flex-col items-center space-y-4">
                <div class="flex items-center space-x-6">
                    <a href="https://instagram.com/believoo" class="text-gray-500 hover:text-white transition-colors"><i class="fab fa-instagram text-xl"></i></a>
                    <a href="https://linkedin.com/company/believoo" class="text-gray-500 hover:text-white transition-colors"><i class="fab fa-linkedin text-xl"></i></a>
                    <a href="https://twitter.com/believoo" class="text-gray-500 hover:text-white transition-colors"><i class="fab fa-x-twitter text-xl"></i></a>
                </div>
                <p class="text-[10px] font-black text-gray-700 uppercase tracking-[0.4em]">
                    &copy; {{ date('Y') }} Believoo. All Rights Reserved.
                </p>
            </div>
        </div>

        <!-- Floating Elements Decor -->
        <div class="absolute top-1/4 left-10 w-4 h-4 bg-electric-blue/20 rounded-full animate-float opacity-50"></div>
        <div class="absolute bottom-1/4 right-10 w-6 h-6 bg-electric-violet/20 rounded-full animate-float opacity-50" style="animation-delay: -2s;"></div>
        <div class="absolute top-1/3 right-1/4 w-2 h-2 bg-white/10 rounded-full animate-float opacity-30" style="animation-delay: -4s;"></div>
    </div>
</body>
</html>
