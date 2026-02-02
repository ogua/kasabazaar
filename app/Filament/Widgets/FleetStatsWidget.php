<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use App\Models\Trip;
use App\Enums\VehicleStatus;
use App\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FleetStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $totalVehicles = Vehicle::count();
        $availableVehicles = Vehicle::where('status', VehicleStatus::Available)->count();
        $vehiclesOnTrip = Vehicle::where('status', VehicleStatus::OnTrip)->count();
        $vehiclesMaintenance = Vehicle::where('status', VehicleStatus::Maintenance)->count();

        $activeTrips = Trip::whereIn('status', [TripStatus::InProgress, TripStatus::Loading])->count();
        $completedTripsThisMonth = Trip::where('status', TripStatus::Completed)
            ->whereMonth('created_at', now()->month)
            ->count();

        return [
            Stat::make('Total Vehicles', $totalVehicles)
                ->description($availableVehicles . ' available')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Vehicles On Trip', $vehiclesOnTrip)
                ->description($vehiclesMaintenance . ' in maintenance')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color($vehiclesOnTrip > 0 ? 'success' : 'gray'),

            Stat::make('Active Trips', $activeTrips)
                ->description($completedTripsThisMonth . ' completed this month')
                ->descriptionIcon('heroicon-m-map')
                ->color($activeTrips > 0 ? 'warning' : 'gray'),
        ];
    }
}
