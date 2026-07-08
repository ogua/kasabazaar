<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvestorShipmentSummaryExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(protected array $summary) {}

    public function array(): array
    {
        return $this->summary['by_month']
            ->map(fn (array $row) => [$row['month'], $row['shipment_count'], $row['revenue_usd']])
            ->toArray();
    }

    public function headings(): array
    {
        return ['Month', 'Shipment Count', 'Revenue (USD)'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
