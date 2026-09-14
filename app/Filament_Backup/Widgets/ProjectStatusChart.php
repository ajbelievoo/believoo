<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ProjectStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Project Status Distribution';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $statuses = ['In Progress', 'On Hold', 'Delivered', 'Cancelled'];
        $counts = [];
        $colors = [];
        
        $colorMap = [
            'In Progress' => 'rgba(54, 162, 235, 0.8)',
            'On Hold' => 'rgba(255, 206, 86, 0.8)',
            'Delivered' => 'rgba(75, 192, 192, 0.8)',
            'Cancelled' => 'rgba(255, 99, 132, 0.8)',
        ];
        
        foreach ($statuses as $status) {
            $count = Project::where('status', $status)->count();
            $counts[] = $count;
            $colors[] = $colorMap[$status] ?? 'rgba(201, 203, 207, 0.8)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Projects',
                    'data' => $counts,
                    'backgroundColor' => $colors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $statuses,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
