<div class="aspect-video bg-black rounded-lg overflow-hidden">
    <video 
        src="{{ $url }}" 
        controls 
        class="w-full h-full"
        poster="{{ asset('images/video-placeholder.jpg') }}"
    >
        Your browser does not support the video tag.
    </video>
</div>
<div class="mt-4 flex items-center justify-between">
    <div class="text-sm text-gray-600 dark:text-gray-400">
        <x-heroicon-m-play class="w-4 h-4 inline mr-1" />
        Recording Playback
    </div>
    <a href="{{ $url }}" download class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
        <x-heroicon-m-arrow-down-tray class="w-4 h-4 inline mr-1" />
        Download
    </a>
</div>
