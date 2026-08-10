<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\EcommerceInventoryLogType;
use App\Models\EcommerceInventoryLog;
use App\Models\EcommerceProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorInventoryController extends VendorBaseController
{
    public function adjust(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|uuid',
            'change' => 'required|integer|not_in:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = EcommerceProduct::where('vendor_id', $this->vendorId())->findOrFail($data['product_id']);

        $log = app(\App\Services\EcommerceInventoryService::class)->adjust(
            $product,
            $data['change'],
            EcommerceInventoryLogType::Adjustment,
            auth()->user(),
            $data['reason'] ?? 'Vendor adjustment'
        );

        return $this->success($log, 'Stock adjusted.');
    }

    public function logs(Request $request): JsonResponse
    {
        $paginated = EcommerceInventoryLog::whereHas('product', fn ($q) => $q->where('vendor_id', $this->vendorId()))
            ->with('product:id,name,sku')
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $products = EcommerceProduct::where('vendor_id', $this->vendorId())
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->get();

        return $this->success($products);
    }
}
