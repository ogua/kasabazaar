<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Shipment;
use Filament\Widgets\Widget;

class ContainerProfitWidget extends Widget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static string $view = 'filament.widgets.container-profit-widget';

    public function getContainerData(): array
    {
        // Get unique container references from all shipments
        $containers = Shipment::whereNotNull('shipping_reference')
            ->get()
            ->map(function ($shipment) {
                // Extract container reference (e.g., "CON51" from "CON51-26-C1-001")
                if (preg_match('/^(CON\d+)/', $shipment->shipping_reference, $matches)) {
                    return [
                        'container_ref' => $matches[1],
                        'created_at' => $shipment->created_at,
                    ];
                }
                return null;
            })
            ->filter()
            ->groupBy('container_ref')
            ->map(fn($group) => $group->sortBy('created_at')->first())
            ->sortByDesc('created_at')
            ->take(10)
            ->pluck('container_ref');

        // Calculate metrics for each container
        return $containers->map(function ($containerRef) {
            $shipmentIds = Shipment::whereRaw('SUBSTRING_INDEX(shipping_reference, "-", 1) = ?', [$containerRef])
                ->pluck('id');

            $shipments = Shipment::whereIn('id', $shipmentIds)->get();

            // Get payments in both USD and GHS
            $payments = Payment::where('payment_type', 'credit')
                ->whereIn('shipment_id', $shipmentIds)
                ->get();

            $paymentsReceivedUsd = $payments->sum('amount_usd') ?: $payments->sum('amount');
            $paymentsReceivedGhs = $payments->sum('amount_ghs') ?: 0;

            // Get expenses in both USD and GHS
            $expensesData = Expense::whereIn('shipment_id', $shipmentIds)
                ->get();

            $expensesUsd = $expensesData->sum('amount_usd') ?: 0;
            $expensesGhs = $expensesData->sum('amount_ghs') ?: 0;

            // Calculate profit/loss in both currencies
            $profitLossUsd = $paymentsReceivedUsd - $expensesUsd;
            $profitLossGhs = $paymentsReceivedGhs - $expensesGhs;

            // Calculate margin based on USD (primary currency for payments)
            $totalRevenueUsd = $shipments->sum('total') ?: 1;
            $marginPercent = $totalRevenueUsd > 0 ? ($profitLossUsd / $totalRevenueUsd) * 100 : 0;

            return [
                'container_ref' => $containerRef,
                'shipment_count' => $shipments->count(),
                'total_revenue_usd' => $shipments->sum('total'),
                'total_revenue_ghs' => $shipments->sum('total_ghs'),
                'payments_received_usd' => $paymentsReceivedUsd,
                'payments_received_ghs' => $paymentsReceivedGhs,
                'expenses_usd' => $expensesUsd,
                'expenses_ghs' => $expensesGhs,
                'profit_loss_usd' => $profitLossUsd,
                'profit_loss_ghs' => $profitLossGhs,
                'margin_percent' => $marginPercent,
                'margin_class' => $marginPercent >= 20 ? 'success' : ($marginPercent >= 0 ? 'warning' : 'danger'),
                'container_date' => $shipments->min('created_at'),
            ];
        })->toArray();
    }
}
