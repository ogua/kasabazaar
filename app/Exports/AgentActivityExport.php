<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AgentActivityExport implements FromCollection, WithHeadings, WithMapping
{
    protected Collection $clearances;

    protected Collection $deliveries;

    public function __construct(Collection $clearances, Collection $deliveries)
    {
        $this->clearances = $clearances;
        $this->deliveries = $deliveries;
    }

    public function collection()
    {
        $clearanceRows = $this->clearances->map(fn ($row) => array_merge(['_type' => 'Clearance'], $row->toArray()));
        $deliveryRows = $this->deliveries->map(fn ($row) => array_merge(['_type' => 'Delivery'], $row->toArray()));

        return $clearanceRows->concat($deliveryRows);
    }

    public function headings(): array
    {
        return [
            'Activity',
            'Agent',
            'Reference',
            'Details',
            'Recorded By',
            'Date/Time',
        ];
    }

    public function map($row): array
    {
        if ($row['_type'] === 'Clearance') {
            return [
                'Clearance',
                $row['clearing_agent']['name'] ?? '',
                'CON'.$row['container_number'],
                $row['is_cleared'] ? 'Cleared' : 'Marked Not Cleared',
                $row['recorded_by']['name'] ?? '',
                $row['cleared_at'] ?? '',
            ];
        }

        return [
            'Delivery',
            $row['clearing_agent']['name'] ?? '',
            $row['shipment']['shipping_reference'] ?? '',
            $row['delivery_notes'] ?? '',
            $row['recorded_by']['name'] ?? '',
            $row['delivered_at'] ?? '',
        ];
    }
}
