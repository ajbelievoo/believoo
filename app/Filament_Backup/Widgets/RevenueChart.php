<?php

namespace App\Filament\Widgets;

use App\Models\AgreementMilestone;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Overview';
    
    protected static ?string $description = 'Monthly revenue from milestone payments';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = collect();
        $revenue = collect();
        
        // Get last 12 months data
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M Y');
            
            $monthRevenue = AgreementMilestone::where('status', 'paid')
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('payment_amount');
            
            $months->push($monthName);
            $revenue->push($monthRevenue);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenue->toArray(),
                    'backgroundColor' => 'rgba(0, 183, 255, 0.15)',
                    'borderColor' => '#00b7ff',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#00b7ff',
                    'pointBorderColor' => '#00b7ff',
                    'pointRadius' => 4,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return '$' + value; }",
                    ],
                ],
            ],
        ];
    }
}
