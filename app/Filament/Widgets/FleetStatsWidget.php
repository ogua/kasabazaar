<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use App\Models\Trip;
use App\Enums\VehicleStatus;
use App\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class FleetStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $containerNumber = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->containerNumber = null;
    }

    #[On('dashboardFiltersUpdated')]
    public function updateFilters($start_date, $end_date, $container_number = null): void
    {
        $this->startDate = $start_date;
        $this->endDate = $end_date;
        $this->containerNumber = $container_number;
    }

    protected function getStats(): array
    {
        $startDate = $this->startDate ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->endDate ?? now()->format('Y-m-d');
        $containerNumber = $this->containerNumber;

        $totalVehicles = Vehicle::count();
        $availableVehicles = Vehicle::where('status', VehicleStatus::Available)->count();
        $vehiclesOnTrip = Vehicle::where('status', VehicleStatus::OnTrip)->count();
        $vehiclesMaintenance = Vehicle::where('status', VehicleStatus::Maintenance)->count();

        $activeTrips = Trip::whereIn('status', [TripStatus::InProgress, TripStatus::Loading])->count();

        // Completed trips within date range
        $completedTripsQuery = Trip::where('status', TripStatus::Completed)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($containerNumber) {
            $completedTripsQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $completedTrips = $completedTripsQuery->count();

        // Calculate days in period for label
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        $periodLabel = $days <= 31 ? "({$days} days)" : "(" . round($days / 30, 1) . " months)";
        $filterLabel = $containerNumber ? ' [CON' . $containerNumber . ']' : '';

        return [
            Stat::make('Total Vehicles', $totalVehicles)
                ->description($availableVehicles . ' available')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Vehicles On Trip', $vehiclesOnTrip)
                ->description($vehiclesMaintenance . ' in maintenance')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color($vehiclesOnTrip > 0 ? 'success' : 'gray'),

            Stat::make('Completed Trips ' . $periodLabel . $filterLabel, $completedTrips)
                ->description($activeTrips . ' currently active')
                ->descriptionIcon('heroicon-m-map')
                ->color($completedTrips > 0 ? 'success' : 'gray'),
        ];
    }
}
