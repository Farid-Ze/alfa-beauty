<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingOrders = Order::where('payment_status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $todayRevenue = Order::whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('total_amount');
        $monthRevenue = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        return [
            Stat::make('Pending Payment', $pendingOrders)
                ->description('Orders awaiting payment')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            
            Stat::make('Processing', $processingOrders)
                ->description('Orders being prepared')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('info'),
            
            Stat::make('Today Revenue', 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->description('Paid orders today')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
            
            Stat::make('Monthly Revenue', 'Rp ' . number_format($monthRevenue, 0, ',', '.'))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('primary'),
        ];
    }
}
