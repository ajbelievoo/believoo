<?php

namespace App\Filament\Widgets;

use App\Models\Agreement;
use App\Models\AgreementMilestone;
use App\Models\AgreementRequest;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Revenue calculations
        $thisMonthRevenue = AgreementMilestone::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('payment_amount');
        
        $lastMonthRevenue = AgreementMilestone::where('status', 'paid')
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->sum('payment_amount');
        
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;
        
        // Pending milestones
        $pendingMilestones = AgreementMilestone::where('status', 'pending')->count();
        $pendingAmount = AgreementMilestone::where('status', 'pending')->sum('payment_amount');
        
        // Agreement stats
        $totalAgreements = Agreement::count();
        $signedAgreements = Agreement::where('status', 'signed')->count();
        $conversionRate = $totalAgreements > 0 ? round(($signedAgreements / $totalAgreements) * 100, 1) : 0;
        
        return [
            Stat::make('This Month Revenue', '$' . number_format($thisMonthRevenue, 2))
                ->description($revenueGrowth >= 0 ? "+{$revenueGrowth}% from last month" : "{$revenueGrowth}% from last month")
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger'),
            
            Stat::make('Pending Payments', '$' . number_format($pendingAmount, 2))
                ->description($pendingMilestones . ' milestones awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Agreement Conversion', $conversionRate . '%')
                ->description("{$signedAgreements}/{$totalAgreements} agreements signed")
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),
            
            Stat::make('Active Projects', Project::whereNot('status', 'Delivered')->count())
                ->description('Ongoing development')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('primary'),
            
            Stat::make('Agreement Requests', AgreementRequest::where('status', 'pending')->count())
                ->description('Pending review')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('warning'),
            
            Stat::make('Total Leads', Lead::count())
                ->description('All inquiries received')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
        ];
    }
}
