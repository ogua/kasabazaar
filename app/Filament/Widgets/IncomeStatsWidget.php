<?php

namespace App\Filament\Widgets;

use App\Models\Income;
use App\Enums\IncomeStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class IncomeStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

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

        // Calculate previous period
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        $prevEndDate = \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d');
        $prevStartDate = \Carbon\Carbon::parse($prevEndDate)->subDays($days - 1)->format('Y-m-d');

        // Current period income
        $currentIncomeQuery = Income::whereBetween('income_date', [$startDate, $endDate])
            ->where('status', IncomeStatus::Received);

        if ($containerNumber) {
            $currentIncomeQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $thisMonthIncome = $currentIncomeQuery->sum('amount_ghs');

        // Previous period income
        $previousIncomeQuery = Income::whereBetween('income_date', [$prevStartDate, $prevEndDate])
            ->where('status', IncomeStatus::Received);

        if ($containerNumber) {
            $previousIncomeQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $lastMonthIncome = $previousIncomeQuery->sum('amount_ghs');

        // Pending income (no date filter, but apply container filter)
        $pendingIncomeQuery = Income::where('status', IncomeStatus::Pending);
        if ($containerNumber) {
            $pendingIncomeQuery->whereHas('shipment', function ($query) use ($containerNumber) {
                $query->where('container_number', $containerNumber);
            });
        }
        $pendingIncome = $pendingIncomeQuery->sum('amount_ghs');

        $change = $lastMonthIncome > 0
            ? (($thisMonthIncome - $lastMonthIncome) / $lastMonthIncome) * 100
            : 0;

        // Calculate days in period for label
        $periodLabel = $days <= 31 ? "({$days} days)" : "(" . round($days / 30, 1) . " months)";
        $filterLabel = $containerNumber ? ' [CON' . $containerNumber . ']' : '';

        return [
            Stat::make('External Income ' . $periodLabel . $filterLabel, '₵' . number_format($thisMonthIncome, 2))
                ->description($change >= 0 ? abs(round($change, 1)) . '% increase' : abs(round($change, 1)) . '% decrease')
                ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($change >= 0 ? 'success' : 'danger'),

            Stat::make('Pending Income', '₵' . number_format($pendingIncome, 2))
                ->description('Awaiting receipt')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total External Income (YTD)', '₵' . number_format(
                Income::whereYear('income_date', now()->year)
                    ->where('status', IncomeStatus::Received)
                    ->sum('amount_ghs'),
                2
            ))
                ->description('Year to date')
                ->descriptionIcon('heroicon-m-calendar'),
        ];
    }
}
