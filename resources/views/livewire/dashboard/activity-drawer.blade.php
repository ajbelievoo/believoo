{{--
    Activity Drawer — Module 3
    Slide-in panel showing the Alpine.js eventLog store entries.
    Requirements: 5.1–5.7
--}}
<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @open-activity-drawer.window="open = true"
>
    {{-- ── Trigger Button (placed in top nav via @include) ── --}}
    <button
        @click="open = true"
        aria-label="Activity History"
        class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold transition-all focus-visible:outline-none focus-visible:ring-2"
        style="color: var(--text-muted); background: var(--card-bg); border: 1px solid var(--border-color);"
        x-bind:style="open ? 'color: var(--accent-primary);' : ''"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Activity</span>
        {{-- Badge showing entry count --}}
        <span
            x-show="$store.eventLog.entries.length > 0"
            x-text="$store.eventLog.entries.length"
            class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-black"
            style="background: var(--accent-primary); color: #05070A;"
            x-cloak
        ></span>
    </button>

    {{-- ── Semi-transparent backdrop (does NOT lock dashboard) ── --}}
    <div
        x-show="open"
        @click="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40"
        style="background: rgba(0,0,0,0.35);"
        aria-hidden="true"
        x-cloak
    ></div>

    {{-- ── Drawer Panel ── --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-in-out duration-300"
        x-transition:enter-start="translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in-out duration-300"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="translate-x-full opacity-0"
        class="glass-card fixed right-0 top-0 h-full w-80 z-50 overflow-y-auto flex flex-col"
        role="dialog"
        aria-label="Activity History"
        aria-modal="true"
        x-cloak
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color: var(--border-color);">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color: var(--accent-primary);" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-sm font-black uppercase tracking-widest" style="color: var(--text-primary);">Activity History</h2>
            </div>
            <button
                @click="open = false"
                aria-label="Close Activity Drawer"
                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all focus-visible:outline-none focus-visible:ring-2"
                style="color: var(--text-muted);"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Entry List --}}
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2">

            {{-- Empty state --}}
            <div
                x-show="$store.eventLog.entries.length === 0"
                class="flex flex-col items-center justify-center py-16 text-center"
                x-cloak
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-sm font-semibold" style="color: var(--text-muted);">No activity yet</p>
                <p class="text-xs mt-1 opacity-60" style="color: var(--text-muted);">Actions like DNS updates and server reboots will appear here.</p>
            </div>

            {{-- Entries (reverse-chronological — store prepends) --}}
            <template x-for="(entry, index) in $store.eventLog.entries" :key="index">
                <div
                    class="glass-card rounded-xl px-3 py-2.5 flex items-start gap-3"
                    style="border-color: var(--border-color);"
                >
                    {{-- Outcome dot --}}
                    <span
                        class="mt-0.5 w-2 h-2 rounded-full flex-shrink-0"
                        :class="entry.outcome === 'success' ? 'bg-green-400' : 'bg-red-400'"
                        :style="entry.outcome === 'success'
                            ? 'box-shadow: 0 0 6px rgba(34,197,94,0.7);'
                            : 'box-shadow: 0 0 6px rgba(239,68,68,0.7);'"
                    ></span>

                    <div class="flex-1 min-w-0">
                        {{-- Action label --}}
                        <p class="text-xs font-black uppercase tracking-wider truncate" style="color: var(--text-primary);" x-text="entry.action.replace(/_/g, ' ')"></p>
                        {{-- Description --}}
                        <p class="text-xs mt-0.5 leading-snug" style="color: var(--text-muted);" x-text="entry.description"></p>
                        {{-- Timestamp --}}
                        <p class="text-[10px] mt-1 opacity-50" style="color: var(--text-muted);" x-text="new Date(entry.ts).toLocaleTimeString()"></p>
                    </div>

                    {{-- Outcome badge --}}
                    <span
                        class="flex-shrink-0 text-[10px] font-black uppercase px-1.5 py-0.5 rounded-full"
                        :class="entry.outcome === 'success'
                            ? 'bg-green-500/20 text-green-400'
                            : 'bg-red-500/20 text-red-400'"
                        x-text="entry.outcome"
                    ></span>
                </div>
            </template>
        </div>

        {{-- Footer — Clear History --}}
        <div class="px-4 py-3 border-t" style="border-color: var(--border-color);">
            <button
                @click="$store.eventLog.clear()"
                class="w-full py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all focus-visible:outline-none focus-visible:ring-2"
                style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3);"
                aria-label="Clear activity history"
            >
                Clear History
            </button>
        </div>
    </div>
</div>
