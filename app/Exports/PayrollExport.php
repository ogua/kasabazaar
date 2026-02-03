<?php

namespace App\Exports;

use App\Models\PayrollEntry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $start_date;
    protected $end_date;

    public function __construct($start_date, $end_date)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function collection()
    {
        return PayrollEntry::with(['payrollPeriod', 'employee'])
            ->whereHas('payrollPeriod', function($query) {
                $query->whereBetween('pay_date', [$this->start_date, $this->end_date]);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Period',
            'Pay Date',
            'Gross Salary',
            'Deductions',
            'Bonuses',
            'Net Salary',
            'Status',
        ];
    }

    public function map($entry): array
    {
        return [
            $entry->employee?->name ?? 'N/A',
            $entry->payrollPeriod ?
                $entry->payrollPeriod->start_date->format('M d') . ' - ' . $entry->payrollPeriod->end_date->format('M d, Y') :
                'N/A',
            $entry->payrollPeriod?->pay_date?->format('Y-m-d') ?? 'N/A',
            $entry->gross_salary,
            $entry->total_deductions,
            $entry->bonus,
            $entry->net_salary,
            $entry->status?->value ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
