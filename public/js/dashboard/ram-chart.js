/**
 * RAM Chart Initialiser
 *
 * Reads initial data from window.__ramHistory (array of floats, RAM %)
 * injected by the Blade partial. Registers a Livewire.on('analytics-updated')
 * listener to keep the chart in sync with live data.
 *
 * Chart.js is loaded globally via CDN (deferred) — no ES module imports.
 */

(function () {
    'use strict';

    // Page Visibility API — pause updates when tab is hidden
    let chartPaused = false;
    document.addEventListener('visibilitychange', function () {
        chartPaused = document.hidden;
    });

    function initRamChart() {
        // Guard: Chart.js must be loaded globally
        if (typeof window.Chart === 'undefined') {
            console.warn('[ram-chart] Chart.js not loaded yet, retrying...');
            setTimeout(initRamChart, 100);
            return;
        }

        var canvas = document.getElementById('ram-chart');
        if (!canvas) {
            console.warn('[ram-chart] Canvas element #ram-chart not found.');
            return;
        }

        var ctx = canvas.getContext('2d');

        // Build fill gradient
        var gradient = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
        gradient.addColorStop(0, 'rgba(124,58,237,0.3)');
        gradient.addColorStop(1, 'rgba(124,58,237,0)');

        // Read initial data injected by Blade
        var initialData = Array.isArray(window.__ramHistory) ? window.__ramHistory.slice() : [];

        var chart = new window.Chart(ctx, {
            type: 'line',
            data: {
                labels: initialData.map(function (_, i) { return i; }),
                datasets: [{
                    label: 'RAM %',
                    data: initialData,
                    borderColor: '#7c3aed',
                    borderWidth: 2,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#7c3aed',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.parsed.y.toFixed(1) + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: false,
                        grid: { display: false }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        display: true,
                        grid: {
                            color: 'rgba(255,255,255,0.05)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            callback: function (value) { return value + '%'; },
                            maxTicksLimit: 5
                        }
                    }
                }
            }
        });

        // Listen for Livewire analytics-updated event
        if (typeof window.Livewire !== 'undefined') {
            window.Livewire.on('analytics-updated', function (event) {
                // Pause updates when tab is hidden
                if (chartPaused) return;

                var detail = event && event[0] ? event[0] : event;

                // Update chart data
                if (detail.ram && Array.isArray(detail.ram)) {
                    chart.data.datasets[0].data = detail.ram.slice();
                    chart.data.labels = detail.ram.map(function (_, i) { return i; });
                }

                // Handle data unavailable — mark last point red
                if (detail.dataUnavailable === true) {
                    var len = chart.data.datasets[0].data.length;
                    var pointColors = chart.data.datasets[0].data.map(function () { return '#7c3aed'; });
                    if (len > 0) {
                        pointColors[len - 1] = 'rgba(239,68,68,0.8)';
                    }
                    chart.data.datasets[0].pointBackgroundColor = pointColors;
                    chart.data.datasets[0].pointRadius = chart.data.datasets[0].data.map(function (_, i) {
                        return i === len - 1 ? 5 : 0;
                    });
                } else {
                    chart.data.datasets[0].pointBackgroundColor = '#7c3aed';
                    chart.data.datasets[0].pointRadius = 0;
                }

                chart.update('none');

                // Update the numeric label element
                var label = document.getElementById('ram-current-label');
                if (label && detail.current) {
                    var used  = detail.current.ramUsedGb  !== undefined ? parseFloat(detail.current.ramUsedGb).toFixed(2)  : '—';
                    var total = detail.current.ramTotalGb !== undefined ? parseFloat(detail.current.ramTotalGb).toFixed(2) : '—';
                    label.textContent = used + ' / ' + total + ' GB';
                }
            });
        }
    }

    // Initialise after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRamChart);
    } else {
        initRamChart();
    }
}());
