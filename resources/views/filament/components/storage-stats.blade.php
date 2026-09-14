<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
            {{ number_format($totalSize / 1024, 2) }}
        </div>
        <div class="text-sm text-blue-800 dark:text-blue-200">Total Storage (GB)</div>
    </div>
    
    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
            {{ $recordingCount }}
        </div>
        <div class="text-sm text-purple-800 dark:text-purple-200">Total Recordings</div>
    </div>
    
    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
            {{ $activeRecordings }}
        </div>
        <div class="text-sm text-green-800 dark:text-green-200">Currently Recording</div>
    </div>
</div>

<div class="mt-6">
    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Storage Usage by Status</h4>
    <div class="space-y-2">
        @php
            $completedSize = \App\Models\StreamingRecording::where('status', 'completed')->sum('file_size_mb');
            $recordingSize = \App\Models\StreamingRecording::where('status', 'recording')->sum('file_size_mb');
            $processingSize = \App\Models\StreamingRecording::where('status', 'processing')->sum('file_size_mb');
        @endphp
        
        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
            <span class="text-sm text-gray-600 dark:text-gray-400">Completed</span>
            <span class="text-sm font-medium">{{ number_format($completedSize, 2) }} MB</span>
        </div>
        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
            <span class="text-sm text-gray-600 dark:text-gray-400">Recording</span>
            <span class="text-sm font-medium">{{ number_format($recordingSize, 2) }} MB</span>
        </div>
        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
            <span class="text-sm text-gray-600 dark:text-gray-400">Processing</span>
            <span class="text-sm font-medium">{{ number_format($processingSize, 2) }} MB</span>
        </div>
    </div>
</div>
