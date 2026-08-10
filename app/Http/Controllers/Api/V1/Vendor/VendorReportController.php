<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorReportController extends VendorBaseController
{
    public function sales(Request $request): JsonResponse
    {
        $from = $request->input('date_from', now()->subDays(30)->toDateString());
        $to = $request->input('date_to', now()->toDateString());

        $orders = EcommerceOrder::where('vendor_id', $this->vendorId())
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->get();

        return $this->success([
            'orders_count' => $orders->count(),
            'gross_sales_ghs' => round($orders->sum('total_ghs'), 2),
        ]);
    }

    public function productPerformance(Request $request): JsonResponse
    {
        $topProducts = EcommerceOrderItem::query()
            ->join('ecommerce_orders', 'ecommerce_orders.id', '=', 'ecommerce_order_items.order_id')
            ->where('ecommerce_orders.vendor_id', $this->vendorId())
            ->where('ecommerce_orders.payment_status', 'paid')
            ->select('ecommerce_order_items.ecommerce_product_id', 'ecommerce_order_items.product_name')
            ->selectRaw('SUM(ecommerce_order_items.quantity) as units_sold')
            ->selectRaw('SUM(ecommerce_order_items.total_ghs) as revenue_ghs')
            ->groupBy('ecommerce_order_items.ecommerce_product_id', 'ecommerce_order_items.product_name')
            ->orderByDesc('units_sold')
            ->limit(20)
            ->get();

        return $this->success($topProducts);
    }
}
