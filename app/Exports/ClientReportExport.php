<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
        if ($this->reportType === 'status_breakdown') {
            $stats = $this->data->first();

            return collect()
                ->concat(
                    collect($stats['shipping_status_breakdown'])->map(fn ($row) => array_merge(['_group' => 'Shipping Status'], $row))
                )
                ->concat(
                    collect($stats['payment_status_breakdown'])->map(fn ($row) => array_merge(['_group' => 'Payment Status'], $row))
                );
        }

        return $this->data;
    }

    public function headings(): array
    {
        return match ($this->reportType) {
            'client_summary', 'top_clients' => [
                'Client', 'Phone', 'Email', 'Shipments', 'Revenue (USD)', 'Paid (USD)', 'Balance (USD)',
                'Pending', 'Partial', 'Paid Shipments', 'Pickup', 'Shipped', 'Delivered', 'Cancelled', 'Cleared',
            ],
            'new_clients' => ['Client', 'Phone', 'Email', 'Registered', 'Shipments Since'],
            'status_breakdown' => ['Group', 'Status', 'Count', 'Percentage', 'Value (USD)'],
            default => ['Data'],
        };
    }

    public function map($row): array
    {
        return match ($this->reportType) {
            'client_summary', 'top_clients' => [
                $row['name'],
                $row['phone'],
                $row['email'],
                $row['shipment_count'],
                $row['total_usd'],
                $row['paid_usd'],
                $row['balance_usd'],
                $row['payment_status_counts']['pending'] ?? 0,
                $row['payment_status_counts']['partial'] ?? 0,
                $row['payment_status_counts']['paid'] ?? 0,
                $row['shipping_status_counts']['pickup'] ?? 0,
                $row['shipping_status_counts']['shipped'] ?? 0,
                $row['shipping_status_counts']['delivered'] ?? 0,
                $row['shipping_status_counts']['cancelled'] ?? 0,
                $row['shipping_status_counts']['cleared'] ?? 0,
            ],
            'new_clients' => [
                $row->name,
                $row->phone,
                $row->email,
                $row->created_at?->format('Y-m-d'),
                $row->shipments_count,
            ],
            'status_breakdown' => [
                $row['_group'],
                $row['label'],
                $row['count'],
                $row['percentage'].'%',
                $row['total_usd'],
            ],
            default => [$row],
        };
    }

    public function title(): string
    {
        return match ($this->reportType) {
            'client_summary' => 'Client Summary',
            'top_clients' => 'Top Clients',
            'new_clients' => 'New Clients',
            'status_breakdown' => 'Status Breakdown',
            default => 'Report',
        };
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
