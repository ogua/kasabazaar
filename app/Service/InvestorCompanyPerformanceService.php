<?php

namespace App\Service;

use App\Models\Shipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvestorCompanyPerformanceService
{
    /**
     * Aggregate, PII-free monthly shipment volume/revenue trend for the investor portal.
     * Deliberately a standalone query — never reuse staff-facing report services, which
     * carry client names/addresses/shipment-level detail investors must not see.
     *
     * @return Collection<int, array{month: string, shipment_count: int, revenue_usd: float}>
     */
    public function monthlyRevenueTrend(int $months = 24): Collection
    {
        return Shipment::query()
            ->select([
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as shipment_count'),
                DB::raw('SUM(total) as revenue_usd'),
            ])
            ->where('created_at', '>=', now()->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'shipment_count' => (int) $row->shipment_count,
                'revenue_usd' => round((float) $row->revenue_usd, 2),
            ]);
    }
}
