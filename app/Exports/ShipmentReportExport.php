<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShipmentReportExport implements FromCollection, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $data;

    protected string $reportType;

    protected array $containerRowNumbers = [];

    public function __construct(Collection $data, string $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    public function collection()
    {
        if ($this->reportType !== 'profit_loss') {
            return $this->data;
        }

        // Flatten: container summary row followed by its client rows
        $rows = collect();
        foreach ($this->data as $container) {
            $rows->push(array_merge(['_type' => 'container'], $container));
            foreach ($container['clients'] ?? [] as $client) {
                $rows->push(array_merge(['_type' => 'client'], $client));
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'by_container', 'by_year', 'by_date_range', 'client_shipments' => [
                'Reference',
                'Client',
                'Client Phone',
                'Receiver',
                'Location',
                'Items',
                'Status',
                'Total (USD)',
                'Date',
            ],
            'profit_loss' => [
                'Container',
                'Client',
                'Phone',
                'Shipments',
                'Revenue / Amount (USD)',
                'Expenses / Paid (USD)',
                'Profit / Balance (USD)',
                'Margin / Status',
            ],
            default => ['Data']
        };
    }

    public function map($row): array
    {
        return match ($this->reportType) {
            'by_container', 'by_year', 'by_date_range', 'client_shipments' => $this->mapShipmentRow($row),
            'profit_loss' => $this->mapProfitLossRow($row),
            default => [$row]
        };
    }

    protected function mapShipmentRow($shipment): array
    {
        $receivers = $shipment->receivers ?? collect();
        $receiverNames = $receivers->map(fn ($r) => $r->receiver_name ?: 'SELF')->join(', ');
        $locations = $receivers->map(fn ($r) => $r->city ?? $r->address ?? 'N/A')->unique()->join(', ');

        $allItems = [];
        foreach ($receivers as $receiver) {
            foreach ($receiver->items ?? [] as $item) {
                $itemName = $item->product?->name ?? $item->description ?? 'N/A';
                $qty = $item->quantity > 1 ? " ({$item->quantity})" : '';
                $allItems[] = $itemName.$qty;
            }
        }
        $itemsList = implode(', ', $allItems);

        return [
            $shipment->shipping_reference ?? 'N/A',
            $shipment->client?->company_name ?? $shipment->client?->name ?? 'N/A',
            $shipment->client?->phone ?? 'N/A',
            $receiverNames ?: 'N/A',
            $locations ?: 'N/A',
            $itemsList ?: 'No items',
            $shipment->status?->getLabel() ?? 'Unknown',
            $shipment->total ?? 0,
            $shipment->created_at?->format('Y-m-d') ?? 'N/A',
        ];
    }

    protected function mapProfitLossRow($row): array
    {
        if (($row['_type'] ?? '') === 'client') {
            return [
                '',
                $row['name'] ?? 'N/A',
                $row['phone'] ?? '',
                $row['shipment_count'] ?? 0,
                $row['total'] ?? 0,
                $row['paid'] ?? 0,
                $row['balance'] ?? 0,
                strtoupper($row['payment_status'] ?? 'unpaid'),
            ];
        }

        $revenue = $row['revenue'] ?? 0;
        $expenses = $row['expenses'] ?? 0;
        $profit = $row['profit'] ?? 0;
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

        return [
            $row['container'] ?? 'N/A',
            '',
            '',
            $row['shipment_count'] ?? 0,
            $revenue,
            $expenses,
            $profit,
            $margin.'%',
        ];
    }

    public function title(): string
    {
        return match ($this->reportType) {
            'by_container' => 'Shipments by Container',
            'by_year' => 'Shipments by Year',
            'by_date_range' => 'Shipments by Date Range',
            'client_shipments' => 'Client Shipment History',
            'profit_loss' => 'Profit Loss Report',
            default => 'Report'
        };
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        if ($this->reportType !== 'profit_loss') {
            return [];
        }

        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Row 1 is heading; data starts at row 2
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cellA = $sheet->getCell("A{$row}")->getValue();

                    if (! empty($cellA)) {
                        // Container summary row — gray background, bold
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'E8E8E8'],
                            ],
                        ]);
                    } else {
                        // Client row — check status in column H
                        $status = strtolower($sheet->getCell("H{$row}")->getValue());
                        $color = match ($status) {
                            'paid' => '006400',
                            'partial' => 'B8860B',
                            default => 'CC0000',
                        };
                        $sheet->getStyle("H{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => $color]],
                        ]);
                        // Indent client name
                        $sheet->getStyle("B{$row}")->getAlignment()->setIndent(2);
                    }
                }
            },
        ];
    }
}
