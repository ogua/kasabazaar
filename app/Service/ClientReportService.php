<?php

namespace App\Service;

use App\Enums\ShippingStatus;
use App\Models\Client;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientReportService
{
    protected const PAYMENT_STATUSES = ['pending', 'partial', 'paid'];

    /**
     * Per-client breakdown of every client who shipped within the period:
     * shipment count, revenue/paid/balance, and a shipping/payment status
     * mix for that client. Sorted by revenue, highest first.
     */
    public function clientSummary(Carbon $startDate, Carbon $endDate): Collection
    {
        $shipments = Shipment::whereBetween('created_at', [$startDate, $endDate->copy()->endOfDay()])
            ->with('client')
            ->get();

        return $shipments->groupBy('client_id')
            ->map(fn (Collection $clientShipments) => $this->summarizeClientShipments($clientShipments))
            ->filter()
            ->sortByDesc('total_usd')
            ->values();
    }

    /**
     * Aggregate, company-wide statistics for departmental decision making:
     * total clients served, total shipments, and a shipping/payment status
     * distribution with counts, percentages, and dollar amounts.
     */
    public function statusBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $shipments = Shipment::whereBetween('created_at', [$startDate, $endDate->copy()->endOfDay()])->get();
        $totalShipments = $shipments->count();

        $shippingBreakdown = collect(ShippingStatus::cases())->map(function (ShippingStatus $case) use ($shipments, $totalShipments) {
            $matching = $shipments->where('status', $case);

            return [
                'status' => $case->value,
                'label' => $case->getLabel(),
                'count' => $matching->count(),
                'percentage' => $totalShipments > 0 ? round($matching->count() / $totalShipments * 100, 1) : 0.0,
                'total_usd' => (float) $matching->sum('total'),
            ];
        })->values()->all();

        $paymentBreakdown = collect(self::PAYMENT_STATUSES)->map(function (string $status) use ($shipments, $totalShipments) {
            $matching = $shipments->where('payment_status', $status);

            return [
                'status' => $status,
                'label' => ucfirst($status),
                'count' => $matching->count(),
                'percentage' => $totalShipments > 0 ? round($matching->count() / $totalShipments * 100, 1) : 0.0,
                'total_usd' => (float) $matching->sum('total'),
            ];
        })->values()->all();

        return [
            'period' => ['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()],
            'total_clients' => $shipments->pluck('client_id')->unique()->count(),
            'total_shipments' => $totalShipments,
            'total_revenue_usd' => (float) $shipments->sum('total'),
            'total_collected_usd' => (float) $shipments->sum('paid'),
            'total_outstanding_usd' => (float) ($shipments->sum('total') - $shipments->sum('paid')),
            'shipping_status_breakdown' => $shippingBreakdown,
            'payment_status_breakdown' => $paymentBreakdown,
        ];
    }

    /**
     * Clients registered within the period (client growth), each with a
     * count of shipments they've placed since joining.
     */
    public function newClients(Carbon $startDate, Carbon $endDate): Collection
    {
        return Client::whereBetween('created_at', [$startDate, $endDate->copy()->endOfDay()])
            ->withCount('shipments')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Highest-revenue clients within the period, most valuable first.
     */
    public function topClients(Carbon $startDate, Carbon $endDate, int $limit = 20): Collection
    {
        return $this->clientSummary($startDate, $endDate)->take($limit)->values();
    }

    private function summarizeClientShipments(Collection $clientShipments): ?array
    {
        $client = $clientShipments->first()->client;

        if (! $client) {
            return null;
        }

        $total = (float) $clientShipments->sum('total');
        $paid = (float) $clientShipments->sum('paid');

        return [
            'client_id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'shipment_count' => $clientShipments->count(),
            'total_usd' => $total,
            'paid_usd' => $paid,
            'balance_usd' => round($total - $paid, 2),
            'payment_status_counts' => collect(self::PAYMENT_STATUSES)->mapWithKeys(
                fn (string $status) => [$status => $clientShipments->where('payment_status', $status)->count()]
            )->all(),
            'shipping_status_counts' => collect(ShippingStatus::cases())->mapWithKeys(
                fn (ShippingStatus $case) => [$case->value => $clientShipments->where('status', $case)->count()]
            )->all(),
        ];
    }
}
