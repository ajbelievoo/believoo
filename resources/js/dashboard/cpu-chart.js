/**
 * CPU Chart Initialiser
 *
 * Reads initial data from window.__cpuHistory (array of floats, CPU %)
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

    function initCpuChart() {
        // Guard: Chart.js must be loaded globally
        if (typeof window.Chart === 'undefined') {
            console.warn('[cpu-chart] Chart.js not loaded yet, retrying...');
            setTimeout(initCpuChart, 100);
            return;
        }

        var canvas = document.getElementById('cpu-chart');
        if (!canvas) {
            console.warn('[cpu-chart] Canvas element #cpu-chart not found.');
            return;
        }

        var ctx = canvas.getContext('2d');

        // Build fill gradient
        var gradient = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
        gradient.addColorStop(0, 'rgba(0,198,255,0.3)');
        gradient.addColorStop(1, 'rgba(0,198,255,0)');

        // Read initial data injected by Blade
        var initialData = Array.isArray(window.__cpuHistory) ? window.__cpuHistory.slice() : [];

        var chart = new window.Chart(ctx, {
            type: 'line',
            data: {
                labels: initialData.map(function (_, i) { return i; }),
                datasets: [{
                    label: 'CPU %',
                    data: initialData,
                    borderColor: '#00c6ff',
                    borderWidth: 2,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#00c6ff',
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
                if (detail.cpu && Array.isArray(detail.cpu)) {
                    chart.data.datasets[0].data = detail.cpu.slice();
                    chart.data.labels = detail.cpu.map(function (_, i) { return i; });
                }

                // Handle data unavailable — mark last point red
                if (detail.dataUnavailable === true) {
                    var len = chart.data.datasets[0].data.length;
                    var pointColors = chart.data.datasets[0].data.map(function () { return '#00c6ff'; });
                    if (len > 0) {
                        pointColors[len - 1] = 'rgba(239,68,68,0.8)';
                    }
                    chart.data.datasets[0].pointBackgroundColor = pointColors;
                    chart.data.datasets[0].pointRadius = chart.data.datasets[0].data.map(function (_, i) {
                        return i === len - 1 ? 5 : 0;
                    });
                } else {
                    chart.data.datasets[0].pointBackgroundColor = '#00c6ff';
                    chart.data.datasets[0].pointRadius = 0;
                }

                chart.update('none');

                // Update the numeric label element
                var label = document.getElementById('cpu-current-label');
                if (label && detail.current && detail.current.cpu !== undefined) {
                    label.textContent = parseFloat(detail.current.cpu).toFixed(1) + '%';
                }
            });
        }
    }

    // Initialise after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCpuChart);
    } else {
        initCpuChart();
    }
}());
