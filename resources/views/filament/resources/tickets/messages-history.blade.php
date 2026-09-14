<div class="space-y-6 max-h-[600px] overflow-y-auto p-4 custom-scrollbar">
    @php
        $messages = $getRecord()->messages()->orderBy('created_at', 'asc')->get();
    @endphp

    @forelse($messages as $msg)
        <div class="flex {{ $msg->sender_type === 'admin' ? 'justify-end' : 'justify-start' }}">
            <div class="flex flex-col {{ $msg->sender_type === 'admin' ? 'items-end' : 'items-start' }} max-w-[85%]">
                <div class="flex items-center space-x-2 mb-1 px-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">
                        {{ $msg->sender_name }}
                    </span>
                    <span class="text-[10px] text-gray-400">
                        {{ $msg->created_at->diffForHumans() }}
                    </span>
                </div>
                
                <div class="relative px-4 py-3 rounded-2xl shadow-sm text-sm {{ $msg->sender_type === 'admin' ? 'bg-primary-600 text-white rounded-tr-none' : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-tl-none border border-gray-200 dark:border-gray-700' }}">
                    <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->message }}</p>
                    
                    @if($msg->attachment)
                        <div class="mt-2 pt-2 border-t border-white/20">
                            <a href="{{ Storage::url($msg->attachment) }}" target="_blank" class="flex items-center space-x-1 text-[10px] hover:underline">
                                <x-heroicon-o-paper-clip class="w-3 h-3" />
                                <span>Attachment</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-10 opacity-50 italic">
            No messages yet.
        </div>
    @endforelse
</div>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.3);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.5);
}
</style>
