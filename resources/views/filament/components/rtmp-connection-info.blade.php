<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">RTMP Server URL</label>
        <div class="mt-1 flex items-center gap-2">
            <code class="flex-1 bg-gray-100 dark:bg-gray-600 px-3 py-2 rounded text-sm font-mono break-all">
                rtmp://{{ $record->server_ip }}/live
            </code>
            <button 
                onclick="navigator.clipboard.writeText('rtmp://{{ $record->server_ip }}/live')"
                class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
            >
                Copy
            </button>
        </div>
    </div>
    
    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Stream Key</label>
        <div class="mt-1 flex items-center gap-2">
            <code class="flex-1 bg-gray-100 dark:bg-gray-600 px-3 py-2 rounded text-sm font-mono break-all">
                {{ $record->stream_key }}
            </code>
            <button 
                onclick="navigator.clipboard.writeText('{{ $record->stream_key }}')"
                class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
            >
                Copy
            </button>
        </div>
    </div>
    
    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Full RTMP URL</label>
        <div class="mt-1 flex items-center gap-2">
            <code class="flex-1 bg-gray-100 dark:bg-gray-600 px-3 py-2 rounded text-sm font-mono break-all">
                rtmp://{{ $record->server_ip }}/live/{{ $record->stream_key }}
            </code>
            <button 
                onclick="navigator.clipboard.writeText('rtmp://{{ $record->server_ip }}/live/{{ $record->stream_key }}')"
                class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
            >
                Copy
            </button>
        </div>
    </div>
    
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">
            <x-heroicon-m-exclamation-triangle class="w-5 h-5 inline mr-1" />
            Connection Tips
        </h4>
        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
            <li>• Make sure port 1935 is open in your firewall</li>
            <li>• Use H.264 video codec for best compatibility</li>
            <li>• Recommended bitrate: 2500-6000 kbps</li>
            <li>• Keyframe interval: 2 seconds</li>
        </ul>
    </div>
</div>
