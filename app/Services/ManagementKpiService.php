<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Shipment;
use App\Service\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The management KPIs on the admin dashboard, for a period (and optionally one
 * container), compared with the period of the same length just before it. Shared
 * by ManagementKPIWidget and the staff app's dashboard API.
 */
class ManagementKpiService
{
    /**
     * @return array{period_days: int, current_rate: float, avg_shipment_value: float, collection_rate: float, avg_payment_days: float, active_clients: int, new_clients: int, total_clients: int, revenue_ghs: float, previous_revenue_ghs: float, revenue_growth: float, shipments: int, previous_shipments: int, shipment_growth: float, outstanding_receivables: float, dso: float, expense_ratio: float}
     */
    public function compute(string $startDate, string $endDate, ?string $containerNumber = null): array
    {
        $days = (int) Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $prevEndDate = Carbon::parse($startDate)->subDay()->format('Y-m-d');
        $prevStartDate = Carbon::parse($prevEndDate)->subDays($days - 1)->format('Y-m-d');

        // The end dates are inclusive: compare timestamps against the end of that day,
        // otherwise everything recorded on the last day (by default, today) is missed.
        $startDate = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
        $endDate = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
        $prevStartDate = Carbon::parse($prevStartDate)->startOfDay()->toDateTimeString();
        $prevEndDate = Carbon::parse($prevEndDate)->endOfDay()->toDateTimeString();

        $currentRate = app(ExchangeRateService::class)->getCurrentRate('USD', 'GHS');

        $shipmentsIn = function (string $from, string $to) use ($containerNumber) {
            return Shipment::whereBetween('created_at', [$from, $to])
                ->when($containerNumber, fn ($query) => $query->where('container_number', $containerNumber));
        };

        $avgShipmentValue = (float) $shipmentsIn($startDate, $endDate)->avg('total');
        $monthRevenue = (float) $shipmentsIn($startDate, $endDate)->sum('total_ghs');

        // Collection efficiency: payments received against revenue generated.
        $monthPayments = (float) Payment::where('payment_type', 'credit')
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->when($containerNumber, fn ($query) => $query->whereHas('shipment', fn ($shipment) => $shipment->where('container_number', $containerNumber)))
            ->sum('amount_ghs');

        $collectionRate = $monthRevenue > 0 ? ($monthPayments / $monthRevenue) * 100 : 0;

        // Days from shipment to payment.
        $avgPaymentDays = (float) (DB::table('shipments')
            ->join('payments', 'payments.shipment_id', '=', 'shipments.id')
            ->whereBetween('shipments.created_at', [$startDate, $endDate])
            ->where('payments.payment_type', 'credit')
            ->when($containerNumber, fn ($query) => $query->where('shipments.container_number', $containerNumber))
            ->selectRaw('AVG(DATEDIFF(payments.paid_on, shipments.created_at)) as avg_days')
            ->value('avg_days') ?: 0);

        $activeClients = $shipmentsIn($startDate, $endDate)->distinct('client_id')->count('client_id');

        $lastMonthRevenue = (float) $shipmentsIn($prevStartDate, $prevEndDate)->sum('total_ghs');
        $revenueGrowth = $lastMonthRevenue > 0 ? (($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        $lastMonthShipments = $shipmentsIn($prevStartDate, $prevEndDate)->count();
        $thisMonthShipments = $shipmentsIn($startDate, $endDate)->count();
        $shipmentGrowth = $lastMonthShipments > 0 ? (($thisMonthShipments - $lastMonthShipments) / $lastMonthShipments) * 100 : 0;

        // Summed per shipment off the maintained `paid` column rather than joining to
        // payments: a join duplicates the shipment row once per payment. Only shipments
        // still owing count — the definition FinancialStatementService::accountsReceivable() uses.
        $outstandingReceivables = (float) (DB::table('shipments')
            ->whereRaw('total > paid')
            ->when($containerNumber, fn ($query) => $query->where('shipments.container_number', $containerNumber))
            ->selectRaw('COALESCE(SUM(shipments.total - shipments.paid), 0) as outstanding')
            ->value('outstanding') ?: 0);

        // Days Sales Outstanding equivalent.
        $monthRevenueUsd = $currentRate > 0 ? $monthRevenue / $currentRate : 0;
        $dailyRevenue = $monthRevenueUsd / max($days, 1);
        $dso = $dailyRevenue > 0 ? $outstandingReceivables / $dailyRevenue : 0;

        $monthExpenses = (float) Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->when($containerNumber, fn ($query) => $query->forContainer($containerNumber))
            ->sum('amount_ghs');

        $expenseRatio = $monthRevenue > 0 ? ($monthExpenses / $monthRevenue) * 100 : 0;

        return [
            'period_days' => $days,
            'current_rate' => (float) $currentRate,
            'avg_shipment_value' => round($avgShipmentValue, 2),
            'collection_rate' => round($collectionRate, 1),
            'avg_payment_days' => round($avgPaymentDays, 1),
            'active_clients' => $activeClients,
            'new_clients' => Client::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_clients' => Client::count(),
            'revenue_ghs' => round($monthRevenue, 2),
            'previous_revenue_ghs' => round($lastMonthRevenue, 2),
            'revenue_growth' => round($revenueGrowth, 1),
            'shipments' => $thisMonthShipments,
            'previous_shipments' => $lastMonthShipments,
            'shipment_growth' => round($shipmentGrowth, 1),
            'outstanding_receivables' => round($outstandingReceivables, 2),
            'dso' => round($dso, 1),
            'expense_ratio' => round($expenseRatio, 1),
        ];
    }
}
