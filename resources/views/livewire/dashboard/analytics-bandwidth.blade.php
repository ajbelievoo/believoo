<div class="glass-card rounded-2xl p-4" wire:poll.5000ms="refreshAnalytics">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold" style="color: var(--text-muted)">Bandwidth</h3>
        <div class="flex items-center gap-3">
            <span id="netin-current-label" class="text-sm font-bold" style="color: #00c6ff">
                {{ number_format($netinMbps, 3) }} Mbps ↓
            </span>
            <span id="netout-current-label" class="text-sm font-bold" style="color: #f97316">
                {{ number_format($netoutMbps, 3) }} Mbps ↑
            </span>
        </div>
    </div>
    <div class="relative h-32">
        <canvas id="bandwidth-chart" aria-label="Bandwidth usage graph" role="img"></canvas>
    </div>
    @if($dataUnavailable)
        <p class="text-xs mt-1" style="color: var(--text-muted)" data-tooltip="Data unavailable">
            ⚠ Data unavailable
        </p>
    @endif
</div>

<script>
    window.__netinHistory = @js($netinHistory);
    window.__netoutHistory = @js($netoutHistory);
</script>
<script defer src="{{ asset('js/dashboard/bandwidth-chart.js') }}"></script>
