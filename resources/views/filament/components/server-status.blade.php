<div class="space-y-3">
    @forelse($status as $serverName => $serverStatus)
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full {{ $serverStatus === 'Online' ? 'bg-green-500' : ($serverStatus === 'Offline' ? 'bg-red-500' : 'bg-yellow-500') }}"></span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $serverName }}</span>
            </div>
            <span class="px-2 py-1 text-xs rounded-full {{ $serverStatus === 'Online' ? 'bg-green-100 text-green-800' : ($serverStatus === 'Offline' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                {{ $serverStatus }}
            </span>
        </div>
    @empty
        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
            No streaming servers configured.
        </div>
    @endforelse
</div>

<div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
    <h4 class="font-medium text-blue-900 dark:text-blue-200 mb-2">Quick Check</h4>
    <p class="text-sm text-blue-800 dark:text-blue-300">
        Test your streaming server connection by running:
    </p>
    <code class="block mt-2 p-2 bg-blue-100 dark:bg-blue-800 rounded text-sm font-mono">
        curl http://your-server-ip:8080/health
    </code>
</div>
