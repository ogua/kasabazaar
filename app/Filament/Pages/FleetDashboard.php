<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\FleetStatsWidget;

class FleetDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static string $view = 'filament.pages.fleet-dashboard';

    protected static ?string $navigationGroup = 'Fleet Management';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Fleet Dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            FleetStatsWidget::class,
        ];
    }
}
