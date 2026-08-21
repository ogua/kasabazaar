<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The ageing schedule as a spreadsheet. Not built on FinancialStatementExport: that
 * base renders a two-column label/amount statement, whereas this is a per-client
 * matrix across four ageing buckets.
 */
class AccountsReceivableExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /** @param array<string, mixed> $statement */
    public function __construct(private readonly array $statement) {}

    public function title(): string
    {
        return 'Accounts Receivable';
    }

    public function headings(): array
    {
        return [
            [config('financials.company.name')],
            ['Schedule of Accounts Receivable'],
            ['As at '.Carbon::parse($this->statement['as_of'])->format('F j, Y')],
            [
                'Presented in '.$this->statement['currency'].'. Balances are the unpaid portion of each '
                .'shipment (invoiced less received), aged from the date the shipment was raised. Fully '
                .'settled shipments are excluded.',
            ],
            [],
            ['Client', 'Shipments', '0-30', '31-60', '61-90', '90+', 'Total', 'Oldest (days)'],
        ];
    }

    public function collection(): Collection
    {
        $rows = collect($this->statement['clients'])->map(fn (array $client) => [
            $client['client'],
            $client['shipment_count'],
            round($client['buckets']['0-30'], 2),
            round($client['buckets']['31-60'], 2),
            round($client['buckets']['61-90'], 2),
            round($client['buckets']['90+'], 2),
            round($client['outstanding'], 2),
            $client['oldest_days'],
        ]);

        $buckets = collect($this->statement['aging'])->keyBy('bucket');

        return $rows->push([
            'TOTAL ('.$this->statement['totals']['client_count'].' clients)',
            $this->statement['totals']['shipment_count'],
            round($buckets['0-30']['amount'] ?? 0, 2),
            round($buckets['31-60']['amount'] ?? 0, 2),
            round($buckets['61-90']['amount'] ?? 0, 2),
            round($buckets['90+']['amount'] ?? 0, 2),
            round($this->statement['totals']['outstanding'], 2),
            '',
        ]);
    }

    public function columnWidths(): array
    {
        return ['A' => 34, 'B' => 12, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14, 'G' => 16, 'H' => 14];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A4')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A4')->getFont()->setSize(9);
        $sheet->getRowDimension(4)->setRowHeight(30);

        $sheet->getStyle('A6:H6')->getFont()->setBold(true);
        $sheet->getStyle('A6:H6')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F3F4F6');

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("C7:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$lastRow}:H{$lastRow}")->getFont()->setBold(true);

        return [];
    }
}
