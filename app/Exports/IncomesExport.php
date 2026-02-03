<?php

namespace App\Exports;

use App\Models\Income;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IncomesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        return Income::with(['category', 'branch', 'recordedBy', 'shipment'])
            ->whereBetween('income_date', [$this->start_date, $this->end_date])
            ->orderBy('income_date', 'desc')
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
            'Status',
            'Payment Method',
            'Recorded By',
        ];
    }

    public function map($income): array
    {
        return [
            $income->reference,
            $income->income_date->format('Y-m-d'),
            $income->category?->name ?? 'Uncategorized',
            $income->description ?? '',
            $income->amount_usd,
            $income->amount_ghs,
            $income->exchange_rate,
            $income->branch?->name ?? 'N/A',
            $income->shipment?->shipping_reference ?? 'External',
            $income->status?->value ?? 'N/A',
            $income->payment_method?->value ?? 'N/A',
            $income->recordedBy?->name ?? 'System',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
