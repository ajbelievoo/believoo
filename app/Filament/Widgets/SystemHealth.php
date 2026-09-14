<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class SystemHealth extends ChartWidget
{
    protected static ?string $heading = 'System Health (Server Usage)';
    protected static string $color = 'info';

    protected function getData(): array
    {
        // Simple mock of real-time usage for visual impact
        // In a real environment, you'd use something like sys_getloadavg()
        $cpu = rand(10, 45);
        $ram = rand(30, 60);
        $disk = 42; // static for demo

        return [
            'datasets' => [
                [
                    'label' => 'Usage %',
                    'data' => [$cpu, $ram, $disk],
                    'backgroundColor' => [
                        'rgba(0, 183, 255, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)'
                    ],
                ],
            ],
            'labels' => ['CPU Usage', 'RAM Usage', 'Disk Space'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
