<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - Believoo Support</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    @livewire('agent-chat')

    @livewireScripts

    {{-- Keep agent session alive while dashboard is open --}}
    <script>
        (function() {
            const keepAliveUrl = '{{ url(($base ?? "/agent") . "/keep-alive") }}';
            function ping() {
                fetch(keepAliveUrl, { credentials: 'same-origin', cache: 'no-store' }).catch(() => {});
            }
            setInterval(ping, 60000);
            document.addEventListener('visibilitychange', function() { if (!document.hidden) ping(); });
            ping();
        })();
    </script>
</body>
</html>
