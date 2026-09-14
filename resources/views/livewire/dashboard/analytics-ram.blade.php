<div class="glass-card rounded-2xl p-4" wire:poll.5000ms="refreshAnalytics">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold" style="color: var(--text-muted)">RAM Usage</h3>
        <span id="ram-current-label" class="text-lg font-bold" style="color: #7c3aed">
            {{ number_format($ramCurrentGb, 2) }} / {{ number_format($ramTotalGb, 2) }} GB
        </span>
    </div>
    <div class="relative h-32">
        <canvas id="ram-chart" aria-label="RAM usage graph" role="img"></canvas>
    </div>
    @if($dataUnavailable)
        <p class="text-xs mt-1" style="color: var(--text-muted)" data-tooltip="Data unavailable">
            ⚠ Data unavailable
        </p>
    @endif
</div>

<script>
    window.__ramHistory = @js($ramHistory);
</script>
<script defer src="{{ asset('js/dashboard/ram-chart.js') }}"></script>
