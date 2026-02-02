<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ClientGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'Client Growth';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Trend::model(Client::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        // Calculate cumulative totals
        $cumulative = [];
        $total = Client::where('created_at', '<', now()->startOfYear())->count();

        foreach ($data as $value) {
            $total += $value->aggregate;
            $cumulative[] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Clients',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'type' => 'bar',
                ],
                [
                    'label' => 'Total Clients',
                    'data' => $cumulative,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'type' => 'line',
                    'fill' => false,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
