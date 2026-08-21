<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * A statement rendered as a spreadsheet, from the same FinancialStatementService
 * payload the PDF uses — so a bank that asks for the figures in Excel gets exactly
 * what the PDF shows, line for line.
 *
 * Subclasses supply the rows; this base handles the header block, styling and the
 * basis-of-preparation note that has to travel with any figure leaving the company.
 */
abstract class FinancialStatementExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    /** @param array<string, mixed> $statement */
    public function __construct(protected readonly array $statement) {}

    /** Rows below the header block: [label, amount|null, isEmphasised]. */
    abstract protected function rows(): Collection;

    abstract protected function reportTitle(): string;

    abstract protected function reportPeriod(): string;

    public function title(): string
    {
        return substr($this->reportTitle(), 0, 31);
    }

    public function headings(): array
    {
        return [
            [config('financials.company.name')],
            [$this->reportTitle()],
            [$this->reportPeriod()],
            ['Presented in '.$this->statement['currency'].'. '.$this->basisNote()],
            [],
            ['', 'Amount ('.$this->statement['currency'].')'],
        ];
    }

    public function collection(): Collection
    {
        return $this->rows()->map(fn (array $row) => [
            $row[0],
            $row[1] === null ? '' : round((float) $row[1], 2),
        ]);
    }

    protected function basisNote(): string
    {
        if (($this->statement['source'] ?? null) === 'manual') {
            return 'Figures for this year were entered from the accounts prepared by the company\'s accountant; '
                .'this system does not hold the underlying transactions for the period.';
        }

        return sprintf(
            'Derived from the company\'s cashbook, shipment, expense, payroll and investor records. '
            .'Amounts held in Ghana Cedis are translated at GHS %s to USD 1.00; records carrying their own '
            .'rate at the transaction date are stated at that rate.',
            number_format((float) ($this->statement['exchange_rate'] ?? 0), 4)
        );
    }

    /**
     * Flatten a service statement group into label/amount rows: one row per statement
     * line, then its underlying accounts indented beneath it.
     *
     * @param  array<int, array{statement_line: string, amount: float, accounts: array}>  $groups
     */
    protected function groupRows(array $groups): Collection
    {
        return collect($groups)->flatMap(function (array $group) {
            $rows = collect([[$group['statement_line'], $group['amount'], true]]);

            foreach ($group['accounts'] as $account) {
                $rows->push(['    '.$account['name'], $account['amount'], false]);
            }

            return $rows;
        });
    }

    public function columnWidths(): array
    {
        return ['A' => 52, 'B' => 20];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A4')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A4')->getFont()->setSize(9);
        $sheet->getRowDimension(4)->setRowHeight(34);

        $sheet->getStyle('A6:B6')->getFont()->setBold(true);
        $sheet->getStyle('A6:B6')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F3F4F6');

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("B7:B{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00;(#,##0.00)');
        $sheet->getStyle("B1:B{$lastRow}")->getAlignment()->setHorizontal('right');

        // Emphasised rows (statement lines and totals) are bolded so the sheet reads
        // like the PDF rather than as an undifferentiated list.
        foreach ($this->rows()->values() as $index => $row) {
            if ($row[2] ?? false) {
                $sheetRow = $index + 7;
                $sheet->getStyle("A{$sheetRow}:B{$sheetRow}")->getFont()->setBold(true);
            }
        }

        return [];
    }
}
