<?php

namespace App\Exports;

use App\Enums\CashbookCostCenter;
use App\Models\CashbookEntry;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CashbookExport implements WithMultipleSheets
{
    public function __construct(
        public readonly int $month,
        public readonly int $year
    ) {}

    public function sheets(): array
    {
        return [
            new CashbookEntriesSheet($this->month, $this->year),
            new CashbookSummarySheet($this->month, $this->year),
        ];
    }
}

// ─── Sheet 1: All Entries ─────────────────────────────────────────────────────

class CashbookEntriesSheet implements
    FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        private readonly int $month,
        private readonly int $year
    ) {}

    public function title(): string
    {
        return date('M', mktime(0, 0, 0, $this->month, 1)) . ' ' . $this->year;
    }

    public function collection()
    {
        return CashbookEntry::query()
            ->forMonth($this->month, $this->year)
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'DATE',
            'PV NO',
            'DETAILS',
            'CHQ #',
            'BANK DEBIT',
            'MOMO DEBIT',
            'BANK CREDIT',
            'MOMO CREDIT',
            'BANK BALANCE',
            'MOMO BALANCE',
            'COST CENTER',
            // Receipt analysis
            'OP. BALANCE',
            'SALES',
            'DIR. TRANSFER',
            'SHIPPING FEE',
            'SERVICE FEE',
            'MOMO INTEREST',
            'PROPERTY MGT',
            'REFUND',
            'CONTRA (R)',
            // Expenditure analysis
            'IMPORT DUTY',
            'SHIPPING EXP',
            'SALARIES',
            'BANK CHARGES',
            'SSNIT',
            'PAYE',
            'WHT',
            'MOMO CHARGES',
            'CONTRA (P)',
            'TRANSPORT',
            'MATERIALS',
            'DONATION',
        ];
    }

    public function map($entry): array
    {
        return [
            $entry->date->format('d/m/Y'),
            $entry->pv_no ?? '',
            $entry->details,
            $entry->chq_ref ?? '',
            $entry->bank_debit > 0 ? (float) $entry->bank_debit : '',
            $entry->momo_debit > 0 ? (float) $entry->momo_debit : '',
            $entry->bank_credit > 0 ? (float) $entry->bank_credit : '',
            $entry->momo_credit > 0 ? (float) $entry->momo_credit : '',
            (float) $entry->bank_balance,
            (float) $entry->momo_balance,
            $entry->cost_center->getLabel(),
            $entry->op_balance ? (float) $entry->op_balance : '',
            $entry->sales ? (float) $entry->sales : '',
            $entry->dir_transfer ? (float) $entry->dir_transfer : '',
            $entry->shipping_fee ? (float) $entry->shipping_fee : '',
            $entry->service_fee ? (float) $entry->service_fee : '',
            $entry->momo_interest ? (float) $entry->momo_interest : '',
            $entry->property_management ? (float) $entry->property_management : '',
            $entry->refund ? (float) $entry->refund : '',
            $entry->contra_receipt ? (float) $entry->contra_receipt : '',
            $entry->import_duty ? (float) $entry->import_duty : '',
            $entry->shipping_expenses ? (float) $entry->shipping_expenses : '',
            $entry->salaries_wages ? (float) $entry->salaries_wages : '',
            $entry->bank_charges ? (float) $entry->bank_charges : '',
            $entry->ssnit ? (float) $entry->ssnit : '',
            $entry->paye ? (float) $entry->paye : '',
            $entry->withholding_tax ? (float) $entry->withholding_tax : '',
            $entry->momo_charges ? (float) $entry->momo_charges : '',
            $entry->contra_payment ? (float) $entry->contra_payment : '',
            $entry->transportation ? (float) $entry->transportation : '',
            $entry->materials ? (float) $entry->materials : '',
            $entry->donation ? (float) $entry->donation : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Header row
        $sheet->getStyle('A1:AF1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'A0043C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);

        // Freeze header row
        $sheet->freezePane('A2');

        // Receipt analysis columns (L–T = 12–20) — light green header
        $sheet->getStyle('L1:T1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F6B3A']],
        ]);

        // Expenditure analysis columns (U–AF = 21–32) — dark red header
        $sheet->getStyle('U1:AF1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '7B1515']],
        ]);

        // Balance columns bold
        $sheet->getStyle("I2:J{$lastRow}")->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Data rows: alternating light fill
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:AF{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F9F9F9']],
                ]);
            }
        }

        // All cells border
        $sheet->getStyle("A1:AF{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'DDDDDD']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Number format for currency columns (E–J, L–AF)
        $currencyCols = ['E', 'F', 'G', 'H', 'I', 'J'];
        $analysisCols = range(ord('L'), ord('Z'));
        foreach ($analysisCols as $c) {
            $currencyCols[] = chr($c);
        }
        $currencyCols = array_merge($currencyCols, ['AA', 'AB', 'AC', 'AD', 'AE', 'AF']);
        foreach ($currencyCols as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        // Row height
        $sheet->getDefaultRowDimension()->setRowHeight(16);
        $sheet->getRowDimension(1)->setRowHeight(24);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 8,  'C' => 32, 'D' => 10,
            'E' => 13, 'F' => 13, 'G' => 13, 'H' => 13,
            'I' => 14, 'J' => 14, 'K' => 20,
            // Analysis columns (narrower)
            'L' => 12, 'M' => 10, 'N' => 13, 'O' => 13,
            'P' => 11, 'Q' => 12, 'R' => 12, 'S' => 10,
            'T' => 10, 'U' => 12, 'V' => 12, 'W' => 11,
            'X' => 12, 'Y' => 10, 'Z' => 10, 'AA' => 10,
            'AB' => 12, 'AC' => 10, 'AD' => 11, 'AE' => 11,
            'AF' => 10,
        ];
    }
}

// ─── Sheet 2: Monthly Summary ─────────────────────────────────────────────────

class CashbookSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly int $month,
        private readonly int $year
    ) {}

    public function title(): string
    {
        return 'Summary';
    }

    public function collection()
    {
        $totals = CashbookEntry::monthlyTotals($this->month, $this->year);
        $last   = CashbookEntry::query()
            ->forMonth($this->month, $this->year)
            ->orderByDesc('date')->orderByDesc('created_at')
            ->first();

        $monthName = date('F', mktime(0, 0, 0, $this->month, 1)) . ' ' . $this->year;

        $rows = collect();

        $rows->push(['CASHBOOK SUMMARY — ' . strtoupper($monthName), '', '']);
        $rows->push(['', '', '']);
        $rows->push(['RECEIPTS', 'GH₵', '']);

        $receiptMap = [
            'op_balance'          => 'Opening Balance',
            'sales'               => 'Sales',
            'dir_transfer'        => 'Director Transfer',
            'shipping_fee'        => 'Shipping Fee',
            'service_fee'         => 'Service Fee',
            'momo_interest'       => 'MOMO Interest Received',
            'property_management' => 'Property Management',
            'refund'              => 'Refund',
            'contra_receipt'      => 'Contra (Receipt)',
        ];
        $totalReceipts = 0;
        foreach ($receiptMap as $key => $label) {
            $val = (float) ($totals[$key] ?? 0);
            if ($val > 0) {
                $rows->push([$label, $val, '']);
                $totalReceipts += $val;
            }
        }
        $rows->push(['Total Bank Debit', (float) ($totals['bank_debit'] ?? 0), '']);
        $rows->push(['Total MOMO Debit', (float) ($totals['momo_debit'] ?? 0), '']);
        $rows->push(['', '', '']);

        $rows->push(['EXPENDITURES', 'GH₵', '']);
        $expMap = [
            'import_duty'       => 'Import Duty Charges',
            'shipping_expenses' => 'Shipping Expenses',
            'salaries_wages'    => 'Salaries & Wages',
            'bank_charges'      => 'Bank Charges',
            'ssnit'             => 'SSNIT',
            'paye'              => 'PAYE',
            'withholding_tax'   => 'Withholding Tax',
            'momo_charges'      => 'MOMO Charges',
            'contra_payment'    => 'Contra (Payment)',
            'transportation'    => 'Transportation',
            'materials'         => 'Materials',
            'donation'          => 'Donation',
        ];
        $totalExp = 0;
        foreach ($expMap as $key => $label) {
            $val = (float) ($totals[$key] ?? 0);
            if ($val > 0) {
                $rows->push([$label, $val, '']);
                $totalExp += $val;
            }
        }
        $rows->push(['Total Bank Credit', (float) ($totals['bank_credit'] ?? 0), '']);
        $rows->push(['Total MOMO Credit', (float) ($totals['momo_credit'] ?? 0), '']);
        $rows->push(['', '', '']);

        $rows->push(['CLOSING BALANCES', 'Bank', 'MOMO']);
        $rows->push([
            'Closing Balance',
            (float) ($last?->bank_balance ?? 0),
            (float) ($last?->momo_balance ?? 0),
        ]);
        $rows->push(['', '', '']);
        $rows->push(['NET POSITION (Total Receipts − Total Expenditures)', round($totalReceipts - $totalExp, 2), '']);

        return $rows;
    }

    public function headings(): array
    {
        return ['Description', 'Amount (GH₵)', 'MOMO (GH₵)'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'A0043C']],
        ]);
        // header row
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'A0043C']],
        ]);
        $sheet->getColumnDimension('A')->setWidth(45);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);

        return [1 => ['font' => ['bold' => true]]];
    }
}
