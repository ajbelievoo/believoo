<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ProjectsChart extends ChartWidget
{
    protected static ?string $heading = 'Projects by Status';

    protected function getData(): array
    {
        $counts = [
            'Planning' => Project::where('status', 'Planning')->count(),
            'Developing' => Project::where('status', 'Developing')->count(),
            'Testing' => Project::where('status', 'Testing')->count(),
            'Delivered' => Project::where('status', 'Delivered')->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Projects',
                    'data' => array_values($counts),
                    'backgroundColor' => ['#3B82F6', '#8B5CF6', '#F59E0B', '#10B981'],
                ],
            ],
            'labels' => array_keys($counts),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
