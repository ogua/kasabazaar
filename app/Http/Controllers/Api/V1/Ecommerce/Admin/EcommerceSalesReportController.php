<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Enums\EcommerceOrderStatus;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use App\Models\EcommerceProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcommerceSalesReportController extends BaseApiController
{
    public function sales(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $period = $request->input('period', 'monthly');

        $dateFormat = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-W%v',
            'monthly' => '%Y-%m',
            default => '%Y-%m',
        };

        $rows = EcommerceOrder::where('branch_id', $branchId)
            ->whereIn('status', [
                EcommerceOrderStatus::Paid->value,
                EcommerceOrderStatus::Processing->value,
                EcommerceOrderStatus::Packed->value,
                EcommerceOrderStatus::Dispatched->value,
                EcommerceOrderStatus::InTransit->value,
                EcommerceOrderStatus::Delivered->value,
            ])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_ghs) as revenue_ghs'),
                DB::raw('SUM(total_usd) as revenue_usd'),
                DB::raw('AVG(total_ghs) as avg_order_value_ghs')
            )
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit(30)
            ->get();

        $totals = EcommerceOrder::where('branch_id', $branchId)
            ->whereIn('status', [
                EcommerceOrderStatus::Paid->value,
                EcommerceOrderStatus::Processing->value,
                EcommerceOrderStatus::Packed->value,
                EcommerceOrderStatus::Dispatched->value,
                EcommerceOrderStatus::InTransit->value,
                EcommerceOrderStatus::Delivered->value,
            ])
            ->select(
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_ghs) as total_revenue_ghs'),
                DB::raw('SUM(total_usd) as total_revenue_usd')
            )
            ->first();

        return $this->success([
            'period' => $period,
            'totals' => $totals,
            'chart' => $rows,
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $statusBreakdown = EcommerceOrder::where('branch_id', $branchId)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_ghs) as total_ghs'))
            ->groupBy('status')
            ->get();

        $today = EcommerceOrder::where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->count();

        $thisMonth = EcommerceOrder::where('branch_id', $branchId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $pendingApproval = EcommerceOrder::where('branch_id', $branchId)
            ->where('status', EcommerceOrderStatus::Pending->value)
            ->count();

        $awaitingPayment = EcommerceOrder::where('branch_id', $branchId)
            ->where('status', EcommerceOrderStatus::AwaitingPayment->value)
            ->count();

        return $this->success([
            'summary' => [
                'today' => $today,
                'this_month' => $thisMonth,
                'pending_approval' => $pendingApproval,
                'awaiting_payment' => $awaitingPayment,
            ],
            'by_status' => $statusBreakdown,
        ]);
    }

    public function productPerformance(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $products = EcommerceOrderItem::whereHas('order', fn ($q) => $q->where('branch_id', $branchId)
            ->whereNotIn('status', [EcommerceOrderStatus::Cancelled->value, EcommerceOrderStatus::Refunded->value]))
            ->select(
                'ecommerce_product_id',
                'product_name',
                'product_sku',
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('SUM(total_ghs) as revenue_ghs'),
                DB::raw('COUNT(DISTINCT order_id) as order_count')
            )
            ->groupBy('ecommerce_product_id', 'product_name', 'product_sku')
            ->orderByDesc('revenue_ghs')
            ->limit((int) $request->input('limit', 20))
            ->get();

        return $this->success($products);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $products = EcommerceProduct::where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->with('category:id,name')
            ->orderBy('stock')
            ->get(['id', 'name', 'sku', 'stock', 'low_stock_threshold', 'category_id']);

        return $this->success([
            'count' => $products->count(),
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->stock,
                'low_stock_threshold' => $p->low_stock_threshold,
                'category' => $p->category?->name,
            ]),
        ]);
    }
}
