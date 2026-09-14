/**
 * Bandwidth Chart Initialiser
 *
 * Reads initial data from window.__netinHistory and window.__netoutHistory
 * (arrays of floats, Mbps) injected by the Blade partial. Renders two datasets
 * (netin / netout) on a single Chart.js line graph.
 *
 * Registers a Livewire.on('analytics-updated') listener to keep the chart in
 * sync with live data.
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

    function initBandwidthChart() {
        // Guard: Chart.js must be loaded globally
        if (typeof window.Chart === 'undefined') {
            console.warn('[bandwidth-chart] Chart.js not loaded yet, retrying...');
            setTimeout(initBandwidthChart, 100);
            return;
        }

        var canvas = document.getElementById('bandwidth-chart');
        if (!canvas) {
            console.warn('[bandwidth-chart] Canvas element #bandwidth-chart not found.');
            return;
        }

        var ctx = canvas.getContext('2d');

        // Read initial data injected by Blade
        var initialNetin  = Array.isArray(window.__netinHistory)  ? window.__netinHistory.slice()  : [];
        var initialNetout = Array.isArray(window.__netoutHistory) ? window.__netoutHistory.slice() : [];

        // Use the longer of the two arrays to build labels
        var labelCount = Math.max(initialNetin.length, initialNetout.length);

        var chart = new window.Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({ length: labelCount }, function (_, i) { return i; }),
                datasets: [
                    {
                        label: 'Inbound (Mbps)',
                        data: initialNetin,
                        borderColor: '#00c6ff',
                        borderWidth: 2,
                        backgroundColor: 'rgba(0,198,255,0.08)',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointBackgroundColor: '#00c6ff',
                    },
                    {
                        label: 'Outbound (Mbps)',
                        data: initialNetout,
                        borderColor: '#f97316',
                        borderWidth: 2,
                        backgroundColor: 'rgba(249,115,22,0.08)',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointBackgroundColor: '#f97316',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: '#94a3b8',
                            boxWidth: 12,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(3) + ' Mbps';
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
                        display: true,
                        grid: {
                            color: 'rgba(255,255,255,0.05)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            callback: function (value) { return value + ' Mbps'; },
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

                // Update netin dataset
                if (detail.netin && Array.isArray(detail.netin)) {
                    chart.data.datasets[0].data = detail.netin.slice();
                }

                // Update netout dataset
                if (detail.netout && Array.isArray(detail.netout)) {
                    chart.data.datasets[1].data = detail.netout.slice();
                }

                // Rebuild labels from the longer dataset
                var newLen = Math.max(
                    chart.data.datasets[0].data.length,
                    chart.data.datasets[1].data.length
                );
                chart.data.labels = Array.from({ length: newLen }, function (_, i) { return i; });

                // Handle data unavailable — mark last point red on both datasets
                if (detail.dataUnavailable === true) {
                    [0, 1].forEach(function (dsIdx) {
                        var ds  = chart.data.datasets[dsIdx];
                        var len = ds.data.length;
                        var baseColor = dsIdx === 0 ? '#00c6ff' : '#f97316';
                        var colors = ds.data.map(function () { return baseColor; });
                        var radii  = ds.data.map(function () { return 0; });
                        if (len > 0) {
                            colors[len - 1] = 'rgba(239,68,68,0.8)';
                            radii[len - 1]  = 5;
                        }
                        ds.pointBackgroundColor = colors;
                        ds.pointRadius = radii;
                    });
                } else {
                    chart.data.datasets[0].pointBackgroundColor = '#00c6ff';
                    chart.data.datasets[0].pointRadius = 0;
                    chart.data.datasets[1].pointBackgroundColor = '#f97316';
                    chart.data.datasets[1].pointRadius = 0;
                }

                chart.update('none');

                // Update numeric label elements
                if (detail.current) {
                    var netinLabel = document.getElementById('netin-current-label');
                    if (netinLabel && detail.current.netinMbps !== undefined) {
                        netinLabel.textContent = parseFloat(detail.current.netinMbps).toFixed(3) + ' Mbps';
                    }

                    var netoutLabel = document.getElementById('netout-current-label');
                    if (netoutLabel && detail.current.netoutMbps !== undefined) {
                        netoutLabel.textContent = parseFloat(detail.current.netoutMbps).toFixed(3) + ' Mbps';
                    }
                }
            });
        }
    }

    // Initialise after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBandwidthChart);
    } else {
        initBandwidthChart();
    }
}());
