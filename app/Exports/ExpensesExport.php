<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        return Expense::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('expense_date', [$this->start_date, $this->end_date])
            ->orderBy('expense_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Date',
            'Category',
            'Description',
            'Amount (USD)',
            'Amount (GHS)',
            'Exchange Rate',
            'Branch',
            'Shipment',
            'Stage',
            'Recorded By',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->reference,
            $expense->expense_date->format('Y-m-d'),
            $expense->category?->name ?? 'Uncategorized',
            $expense->description ?? '',
            $expense->amount_usd,
            $expense->amount_ghs,
            $expense->exchange_rate,
            $expense->branch?->name ?? 'N/A',
            $expense->shipment?->shipping_reference ?? 'General',
            $expense->expense_stage?->value ?? 'N/A',
            $expense->recordedBy?->name ?? 'System',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
