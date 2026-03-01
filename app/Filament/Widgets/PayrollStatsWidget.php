<?php

namespace App\Filament\Widgets;

use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use App\Models\Staff;
use App\Enums\PayrollStatus;
use App\Enums\PayrollEntryStatus;
use App\Service\ExchangeRateService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class PayrollStatsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    #[On('dashboardFiltersUpdated')]
    public function updateFilters($start_date, $end_date, $container_number = null): void
    {
        $this->startDate = $start_date;
        $this->endDate = $end_date;
    }

    protected function getStats(): array
    {
        $startDate = $this->startDate ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->endDate ?? now()->format('Y-m-d');

        $currentPeriod = PayrollPeriod::where('status', PayrollStatus::Draft)
            ->orWhere('status', PayrollStatus::Processing)
            ->latest()
            ->first();

        $totalStaff = Staff::count();

        $lastPaidPeriod = PayrollPeriod::where('status', PayrollStatus::Paid)
            ->latest()
            ->first();

        $pendingPayments = PayrollEntry::where('status', PayrollEntryStatus::Pending)->count();

        // Payroll within date range (net_salary is in GHS, convert to USD)
        $periodPayroll = PayrollEntry::whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pay_date', [$startDate, $endDate]);
        })->sum('net_salary');
        $exchangeRate = app(ExchangeRateService::class)->getCurrentRate('USD', 'GHS');
        $periodPayrollUsd = $exchangeRate > 0 ? $periodPayroll / $exchangeRate : 0;

        // Calculate days in period for label
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        $periodLabel = $days <= 31 ? "({$days} days)" : "(" . round($days / 30, 1) . " months)";

        return [
            Stat::make('Active Staff', $totalStaff)
                ->description('Total employees')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pending Payroll', $pendingPayments)
                ->description($currentPeriod ? $currentPeriod->name : 'No active period')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPayments > 0 ? 'warning' : 'success'),

            Stat::make('Payroll ' . $periodLabel, '$' . number_format($periodPayrollUsd, 2))
                ->description('Total net salaries')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
