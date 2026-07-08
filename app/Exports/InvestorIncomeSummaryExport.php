<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvestorIncomeSummaryExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(protected array $summary) {}

    public function array(): array
    {
        return $this->summary['by_category']
            ->map(fn (array $row) => [$row['category'], $row['count'], $row['total_usd'], $row['total_ghs']])
            ->toArray();
    }

    public function headings(): array
    {
        return ['Category', 'Count', 'Total (USD)', 'Total (GHS)'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
