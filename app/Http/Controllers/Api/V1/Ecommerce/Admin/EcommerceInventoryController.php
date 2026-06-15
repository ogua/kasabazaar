<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Enums\EcommerceInventoryLogType;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\EcommerceInventoryLog;
use App\Models\EcommerceProduct;
use App\Services\EcommerceInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceInventoryController extends BaseApiController
{
    public function __construct(protected EcommerceInventoryService $inventoryService) {}

    public function adjust(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $data = $request->validate([
            'product_id' => 'required|uuid|exists:ecommerce_products,id',
            'type' => 'required|in:increase,decrease,adjustment',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = EcommerceProduct::where('branch_id', $branchId)->findOrFail($data['product_id']);

        $change = $data['type'] === 'decrease' ? -abs($data['quantity']) : abs($data['quantity']);

        $log = $this->inventoryService->adjust(
            $product,
            $change,
            EcommerceInventoryLogType::from($data['type']),
            auth()->user(),
            $data['reason'] ?? null
        );

        return $this->success([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity_before' => $log->quantity_before,
            'quantity_after' => $log->quantity_after,
            'new_stock' => $product->fresh()->stock,
        ], 'Stock adjusted.');
    }

    public function logs(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $paginated = EcommerceInventoryLog::where('branch_id', $branchId)
            ->with(['product:id,name,sku', 'createdBy:id,name'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('ecommerce_product_id', $request->product_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate((int) $request->input('per_page', 30));

        return $this->paginated($paginated, fn ($log) => [
            'id' => $log->id,
            'type' => $log->type instanceof \BackedEnum ? $log->type->value : $log->type,
            'quantity_change' => $log->quantity_change,
            'quantity_before' => $log->quantity_before,
            'quantity_after' => $log->quantity_after,
            'reason' => $log->reason,
            'created_at' => $log->created_at,
            'product' => $log->product ? ['id' => $log->product->id, 'name' => $log->product->name, 'sku' => $log->product->sku] : null,
            'created_by' => $log->createdBy ? ['id' => $log->createdBy->id, 'name' => $log->createdBy->name] : null,
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $products = EcommerceProduct::where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('stock', '>', 0)
            ->with('category:id,name')
            ->orderBy('stock')
            ->get(['id', 'name', 'sku', 'stock', 'low_stock_threshold', 'category_id']);

        return $this->success($products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'stock' => $p->stock,
            'low_stock_threshold' => $p->low_stock_threshold,
            'category' => $p->category?->name,
        ]));
    }

    public function outOfStock(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $products = EcommerceProduct::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->with('category:id,name')
            ->get(['id', 'name', 'sku', 'stock', 'category_id']);

        return $this->success($products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'category' => $p->category?->name,
        ]));
    }
}
