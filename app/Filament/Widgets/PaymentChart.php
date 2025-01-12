<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class PaymentChart extends ChartWidget
{
    protected static ?string $heading = 'Transactions';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getData(): array
    {
        $data = Trend::query(Payment::query()->ofbranch())
        ->dateColumn('paid_on')
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Payments Received',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
