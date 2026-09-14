<div class="glass-card rounded-2xl p-4" wire:poll.5000ms="refreshAnalytics">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold" style="color: var(--text-muted)">CPU Usage</h3>
        <span id="cpu-current-label" class="text-lg font-bold" style="color: #00c6ff">
            {{ number_format($cpuCurrent, 1) }}%
        </span>
    </div>
    <div class="relative h-32">
        <canvas id="cpu-chart" aria-label="CPU usage graph" role="img"></canvas>
    </div>
    @if($dataUnavailable)
        <p class="text-xs mt-1" style="color: var(--text-muted)" data-tooltip="Data unavailable">
            ⚠ Data unavailable
        </p>
    @endif
</div>

<script>
    window.__cpuHistory = @js($cpuHistory);
</script>
<script defer src="{{ asset('js/dashboard/cpu-chart.js') }}"></script>
