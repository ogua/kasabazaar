<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ShipmentsByContainerChart extends ChartWidget
{
    protected static ?string $heading = 'Shipments by Container (This Year)';

    protected static ?int $sort = 9;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $yearShort = now()->format('y');

        // Get shipments grouped by container sequence for this year
        $data = Shipment::select('client_sequence', DB::raw('COUNT(*) as count'))
            ->where('shipping_reference', 'like', "%-{$yearShort}-%")
            ->whereNotNull('client_sequence')
            ->groupBy('client_sequence')
            ->orderBy('client_sequence')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Shipments',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $data->map(fn ($item) => 'C' . $item->client_sequence)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
