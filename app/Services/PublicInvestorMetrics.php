<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Shipment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * The figures behind the public investors page (resources/views/site/investors.blade.php):
 * client growth and billed revenue, month by month, plus the headline tiles.
 *
 * Only aggregates leave this class — never a client, a shipment or an amount that could be
 * traced back to one. Revenue is billed shipment value in USD (shipments.total), cancelled
 * shipments excluded, counted in the month the shipment was booked. The result is cached for
 * config('website.investors.cache_minutes').
 */
class PublicInvestorMetrics
{
    public const CACHE_KEY = 'site.investor-metrics';

    /**
     * @return array{
     *     months: list<array{key: string, label: string, short: string, new_clients: int, clients_total: int, revenue: float, shipments: int}>,
     *     years: list<array{year: int, revenue: float, new_clients: int, partial: bool}>,
     *     kpis: array{clients_total: int, clients_last_12: int, clients_growth: ?float, revenue_last_12: float, revenue_growth: ?float, shipments_total: int, states_served: int},
     *     generated_at: string
     * }
     */
    public function summary(?CarbonImmutable $now = null): array
    {
        if ($now !== null) {
            return $this->build($now);
        }

        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes((int) config('website.investors.cache_minutes', 360)),
            fn (): array => $this->build(CarbonImmutable::now()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function build(CarbonImmutable $now): array
    {
        $monthCount = max(6, (int) config('website.investors.months', 24));
        $end = $now->startOfMonth();
        $start = $end->subMonths($monthCount - 1);

        // Two windows of 12 months each feed the growth figures, so read at least 24.
        $readFrom = $end->subMonths(max($monthCount, 24) - 1);

        $clientDates = Client::query()->whereNotNull('created_at')->pluck('created_at');
        $clientsBefore = $clientDates->filter(fn ($date): bool => $date < $readFrom)->count();

        $clientsByMonth = $clientDates
            ->filter(fn ($date): bool => $date >= $readFrom)
            ->countBy(fn ($date): string => $date->format('Y-m'));

        $shipments = Shipment::query()
            ->toBase()
            ->whereNotNull('created_at')
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '<>', 'cancelled'))
            ->get(['created_at', 'total']);

        $revenueByMonth = [];
        $shipmentsByMonth = [];
        $revenueByYear = [];

        foreach ($shipments as $shipment) {
            $date = CarbonImmutable::parse($shipment->created_at);
            $amount = (float) $shipment->total;
            $revenueByYear[$date->year] = ($revenueByYear[$date->year] ?? 0) + $amount;

            if ($date >= $readFrom) {
                $key = $date->format('Y-m');
                $revenueByMonth[$key] = ($revenueByMonth[$key] ?? 0) + $amount;
                $shipmentsByMonth[$key] = ($shipmentsByMonth[$key] ?? 0) + 1;
            }
        }

        $running = $clientsBefore;
        $allMonths = [];

        for ($month = $readFrom; $month <= $end; $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $new = (int) ($clientsByMonth[$key] ?? 0);
            $running += $new;
            $allMonths[] = [
                'key' => $key,
                'label' => $month->format('M Y'),
                'short' => $month->format('M'),
                'new_clients' => $new,
                'clients_total' => $running,
                'revenue' => round($revenueByMonth[$key] ?? 0, 2),
                'shipments' => (int) ($shipmentsByMonth[$key] ?? 0),
            ];
        }

        $last12 = array_slice($allMonths, -12);
        $prior12 = array_slice($allMonths, -24, 12);

        $clientsLast12 = array_sum(array_column($last12, 'new_clients'));
        $clientsPrior12 = array_sum(array_column($prior12, 'new_clients'));
        $revenueLast12 = array_sum(array_column($last12, 'revenue'));
        $revenuePrior12 = array_sum(array_column($prior12, 'revenue'));

        $clientsByYear = $clientDates->countBy(fn ($date): int => (int) $date->format('Y'));
        $years = collect(array_keys($revenueByYear))
            ->merge($clientsByYear->keys())
            ->unique()
            ->sort()
            ->values()
            ->map(fn (int $year): array => [
                'year' => $year,
                'revenue' => round($revenueByYear[$year] ?? 0, 2),
                'new_clients' => (int) ($clientsByYear[$year] ?? 0),
                'partial' => $year === $now->year,
            ])
            ->all();

        return [
            'months' => array_values(array_filter($allMonths, fn (array $row): bool => $row['key'] >= $start->format('Y-m'))),
            'years' => $years,
            'kpis' => [
                'clients_total' => $clientDates->count(),
                'clients_last_12' => $clientsLast12,
                'clients_growth' => self::growth($clientsLast12, $clientsPrior12),
                'revenue_last_12' => round($revenueLast12, 2),
                'revenue_growth' => self::growth($revenueLast12, $revenuePrior12),
                'shipments_total' => $shipments->count(),
                'states_served' => count(array_filter(array_keys(config('website.service_states', [])), fn (string $place): bool => $place !== 'Ghana')),
            ],
            'generated_at' => $now->toIso8601String(),
        ];
    }

    /**
     * Percentage change from $before to $after; null when there is no base to compare with.
     */
    public static function growth(float|int $after, float|int $before): ?float
    {
        if ($before <= 0) {
            return null;
        }

        return round(($after - $before) / $before * 100, 1);
    }

    /**
     * "$1.2M", "$48.5K", "$950" — for axis ticks and tiles.
     */
    public static function compactMoney(float $amount): string
    {
        return '$'.self::compactNumber($amount);
    }

    public static function compactNumber(float $value): string
    {
        $abs = abs($value);

        return match (true) {
            $abs >= 1_000_000 => rtrim(rtrim(number_format($value / 1_000_000, 1), '0'), '.').'M',
            $abs >= 1_000 => rtrim(rtrim(number_format($value / 1_000, 1), '0'), '.').'K',
            default => number_format($value),
        };
    }
}
