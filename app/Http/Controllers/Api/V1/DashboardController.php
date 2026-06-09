<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Shipment;
use App\Models\Payment;
use App\Models\Trip;
use App\Models\Income;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseApiController
{
    public function summary(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $month    = now()->month;
        $year     = now()->year;

        $shipmentsThisMonth = Shipment::where('branch_id', $branchId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $pendingPaymentsCount = Shipment::where('branch_id', $branchId)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->count();

        $revenueThisMonthGhs = Income::where('branch_id', $branchId)
            ->whereMonth('income_date', $month)
            ->whereYear('income_date', $year)
            ->sum('amount_ghs');

        $revenueThisMonthUsd = Income::where('branch_id', $branchId)
            ->whereMonth('income_date', $month)
            ->whereYear('income_date', $year)
            ->sum('amount_usd');

        $activeTrips = Trip::where('branch_id', $branchId)
            ->whereIn('status', ['in_progress', 'loading', 'planned', 'scheduled'])
            ->count();

        $pendingShipments = Shipment::where('branch_id', $branchId)
            ->where('status', 'pending')
            ->count();

        $inTransitShipments = Shipment::where('branch_id', $branchId)
            ->where('status', 'shipped')
            ->count();

        $deliveredThisMonth = Shipment::where('branch_id', $branchId)
            ->where('status', 'delivered')
            ->whereMonth('delivered_at', $month)
            ->whereYear('delivered_at', $year)
            ->count();

        return $this->success([
            'shipments_this_month' => $shipmentsThisMonth,
            'pending_payments'     => $pendingPaymentsCount,
            'revenue_ghs'          => round((float) $revenueThisMonthGhs, 2),
            'revenue_usd'          => round((float) $revenueThisMonthUsd, 2),
            'active_trips'         => $activeTrips,
            'pending_shipments'    => $pendingShipments,
            'in_transit_shipments' => $inTransitShipments,
            'delivered_this_month' => $deliveredThisMonth,
        ]);
    }

    public function recentShipments(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $branchId  = $this->resolveBranch($request);
        $shipments = Shipment::where('branch_id', $branchId)
            ->with(['client:id,name,phone', 'destinationBranch:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'id'                  => $s->id,
                'shipping_reference'  => $s->shipping_reference,
                'tracking_number'     => $s->tracking_number,
                'status'              => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
                'payment_status'      => $s->payment_status,
                'total'               => $s->total,
                'paid'                => $s->paid,
                'total_ghs'           => $s->total_ghs,
                'container_number'    => $s->container_number,
                'shipped_at'          => $s->shipped_at,
                'estimated_delivery_date' => $s->estimated_delivery_date,
                'client'              => $s->client ? [
                    'id'    => $s->client->id,
                    'name'  => $s->client->name,
                    'phone' => $s->client->phone,
                ] : null,
                'destination_branch'  => $s->destinationBranch ? [
                    'id'   => $s->destinationBranch->id,
                    'name' => $s->destinationBranch->name,
                ] : null,
            ]);

        return $this->success($shipments);
    }
}
