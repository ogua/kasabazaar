<?php

namespace App\Filament\Widgets;

use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use App\Models\Staff;
use App\Enums\PayrollStatus;
use App\Enums\PayrollEntryStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayrollStatsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        $currentPeriod = PayrollPeriod::where('status', PayrollStatus::Draft)
            ->orWhere('status', PayrollStatus::Processing)
            ->latest()
            ->first();

        $totalStaff = Staff::count();

        $lastPaidPeriod = PayrollPeriod::where('status', PayrollStatus::Paid)
            ->latest()
            ->first();

        $pendingPayments = PayrollEntry::where('status', PayrollEntryStatus::Pending)->count();

        $monthlyPayroll = PayrollEntry::whereHas('payrollPeriod', function ($query) {
            $query->whereMonth('pay_date', now()->month)
                  ->whereYear('pay_date', now()->year);
        })->sum('net_salary');

        return [
            Stat::make('Active Staff', $totalStaff)
                ->description('Total employees')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pending Payroll', $pendingPayments)
                ->description($currentPeriod ? $currentPeriod->name : 'No active period')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPayments > 0 ? 'warning' : 'success'),

            Stat::make('Monthly Payroll', '₵' . number_format($monthlyPayroll, 2))
                ->description('Total net salaries')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
