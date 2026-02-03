<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\PayrollStatsWidget;
use Filament\Actions\Action;
use App\Exports\PayrollExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static string $view = 'filament.pages.payroll-report';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Payroll Report';

    protected static ?string $navigationLabel = 'Payroll Report';

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('downloadPdf'),

            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action('downloadExcel'),
        ];
    }

    public function downloadPdf()
    {
        $data = $this->getViewData();

        $pdf = Pdf::loadView('reports.payroll-pdf', [
            'payrolls' => $data['payrolls'],
            'summary' => $data['summary'],
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'payroll-report-' . $this->start_date . '-to-' . $this->end_date . '.pdf');
    }

    public function downloadExcel()
    {
        return Excel::download(
            new PayrollExport($this->start_date, $this->end_date),
            'payroll-report-' . $this->start_date . '-to-' . $this->end_date . '.xlsx'
        );
    }

    public function getViewData(): array
    {
        return [
            'payrolls' => $this->getPayrollData(),
            'summary' => $this->getPayrollSummary(),
        ];
    }

    protected function getPayrollData(): array
    {
        $query = \App\Models\PayrollEntry::with(['payrollPeriod', 'employee'])
            ->whereHas('payrollPeriod', function($q) {
                if ($this->start_date && $this->end_date) {
                    $q->whereBetween('pay_date', [$this->start_date, $this->end_date]);
                }
            })
            ->orderBy('created_at', 'desc');

        $payrolls = $query->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'employee' => $entry->employee?->name ?? 'N/A',
                    'period' => $entry->payrollPeriod ?
                        $entry->payrollPeriod->start_date->format('M d') . ' - ' . $entry->payrollPeriod->end_date->format('M d, Y') :
                        'N/A',
                    'pay_date' => $entry->payrollPeriod?->pay_date,
                    'gross_salary' => $entry->gross_salary,
                    'deductions' => $entry->total_deductions,
                    'bonuses' => $entry->bonus,
                    'net_salary' => $entry->net_salary,
                    'status' => $entry->status?->value ?? 'N/A',
                ];
            })
            ->toArray();

        return $payrolls;
    }

    protected function getPayrollSummary(): array
    {
        $startDate = $this->start_date ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->end_date ?: now()->format('Y-m-d');

        $thisMonthPayroll = \App\Models\PayrollEntry::whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pay_date', [$startDate, $endDate]);
        })->sum('net_salary');

        // Calculate previous period
        $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate));
        $prevEndDate = \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d');
        $prevStartDate = \Carbon\Carbon::parse($prevEndDate)->subDays($days)->format('Y-m-d');

        $lastMonthPayroll = \App\Models\PayrollEntry::whereHas('payrollPeriod', function ($query) use ($prevStartDate, $prevEndDate) {
            $query->whereBetween('pay_date', [$prevStartDate, $prevEndDate]);
        })->sum('net_salary');

        $growth = $lastMonthPayroll > 0 ? (($thisMonthPayroll - $lastMonthPayroll) / $lastMonthPayroll) * 100 : 0;

        // By status
        $byStatus = \App\Models\PayrollEntry::whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pay_date', [$startDate, $endDate]);
        })
            ->get()
            ->groupBy(fn($p) => $p->status?->value ?? 'N/A')
            ->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('net_salary'),
                'avg' => $group->avg('net_salary'),
            ])
            ->toArray();

        $totalEmployees = \App\Models\PayrollEntry::whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pay_date', [$startDate, $endDate]);
        })->distinct('staff_id')->count('staff_id');

        $avgSalary = $totalEmployees > 0 ? $thisMonthPayroll / $totalEmployees : 0;

        $totalDeductions = \App\Models\PayrollEntry::whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pay_date', [$startDate, $endDate]);
        })->sum('total_deductions');

        $totalBonuses = \App\Models\PayrollEntry::whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pay_date', [$startDate, $endDate]);
        })->sum('bonus');

        return [
            'this_month' => $thisMonthPayroll,
            'last_month' => $lastMonthPayroll,
            'growth_percent' => $growth,
            'by_status' => $byStatus,
            'total_employees' => $totalEmployees,
            'avg_salary' => $avgSalary,
            'total_deductions' => $totalDeductions,
            'total_bonuses' => $totalBonuses,
            'total_count' => \App\Models\PayrollEntry::whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pay_date', [$startDate, $endDate]);
            })->count(),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PayrollStatsWidget::class,
        ];
    }
}
