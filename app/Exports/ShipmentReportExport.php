<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShipmentReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected Collection $data;
    protected string $reportType;

    public function __construct(Collection $data, string $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'by_container', 'by_year' => [
                'Reference',
                'Client',
                'Client Phone',
                'Receiver',
                'Location',
                'Items',
                'Status',
                'Total (USD)',
                'Date'
            ],
            'profit_loss' => [
                'Container',
                'Shipments',
                'Revenue (USD)',
                'Expenses (USD)',
                'Profit (USD)',
                'Margin (%)'
            ],
            default => ['Data']
        };
    }

    public function map($row): array
    {
        return match ($this->reportType) {
            'by_container', 'by_year' => $this->mapShipmentRow($row),
            'profit_loss' => $this->mapProfitLossRow($row),
            default => [$row]
        };
    }

    protected function mapShipmentRow($shipment): array
    {
        $receivers = $shipment->receivers ?? collect();
        $receiverNames = $receivers->map(fn($r) => $r->receiver_name ?: 'SELF')->join(', ');
        $locations = $receivers->map(fn($r) => $r->city ?? $r->address ?? 'N/A')->unique()->join(', ');

        $allItems = [];
        foreach ($receivers as $receiver) {
            foreach ($receiver->items ?? [] as $item) {
                $itemName = $item->product?->name ?? $item->description ?? 'N/A';
                $qty = $item->quantity > 1 ? " ({$item->quantity})" : '';
                $allItems[] = $itemName . $qty;
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
            $shipment->created_at?->format('Y-m-d') ?? 'N/A'
        ];
    }

    protected function mapProfitLossRow($row): array
    {
        $revenue = $row['revenue'] ?? 0;
        $expenses = $row['expenses'] ?? 0;
        $profit = $row['profit'] ?? 0;
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

        return [
            $row['container'] ?? 'N/A',
            $row['shipment_count'] ?? 0,
            $revenue,
            $expenses,
            $profit,
            $margin
        ];
    }

    public function title(): string
    {
        return match ($this->reportType) {
            'by_container' => 'Shipments by Container',
            'by_year' => 'Shipments by Year',
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
}
