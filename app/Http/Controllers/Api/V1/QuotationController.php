<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_quotation'), 403);

        $branchId  = $this->resolveBranch($request);
        $paginated = Quotation::where('branch_id', $branchId)
            ->with('client:id,name,phone')
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($q) => $this->formatQuotation($q));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_quotation'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'client_id'     => 'required|uuid|exists:clients,id',
            'shipping_cost' => 'nullable|numeric|min:0',
            'items'         => 'nullable|array',
            'items.*.product_id' => 'nullable|uuid|exists:products,id',
            'items.*.quantity'   => 'nullable|integer|min:1',
            'items.*.item_cost'  => 'nullable|numeric|min:0',
        ]);

        $subTotal = collect($request->input('items', []))->sum(
            fn ($i) => ($i['quantity'] ?? 1) * ($i['item_cost'] ?? 0)
        );

        $quotation = Quotation::create([
            'client_id'     => $request->client_id,
            'branch_id'     => $branchId,
            'shipping_cost' => $request->input('shipping_cost', 0),
            'sub_total'     => $subTotal,
            'total'         => $subTotal + $request->input('shipping_cost', 0),
            'recorded_by'   => auth()->id(),
        ]);

        foreach ($request->input('items', []) as $item) {
            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'product_id'   => $item['product_id'] ?? null,
                'quantity'     => $item['quantity'] ?? 1,
                'item_cost'    => $item['item_cost'] ?? 0,
            ]);
        }

        return $this->success(
            $this->formatQuotation($quotation->load(['client', 'items.product'])),
            'Quotation created.',
            201
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_quotation'), 403);

        $branchId  = $this->resolveBranch($request);
        $quotation = Quotation::where('branch_id', $branchId)
            ->with(['client', 'items.product'])
            ->findOrFail($id);

        return $this->success($this->formatQuotation($quotation));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_quotation'), 403);

        $branchId  = $this->resolveBranch($request);
        $quotation = Quotation::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'shipping_cost' => 'nullable|numeric|min:0',
            'items'         => 'nullable|array',
            'items.*.product_id' => 'nullable|uuid|exists:products,id',
            'items.*.quantity'   => 'nullable|integer|min:1',
            'items.*.item_cost'  => 'nullable|numeric|min:0',
        ]);

        if ($request->has('items')) {
            $quotation->items()->delete();
            $subTotal = 0;
            foreach ($request->input('items', []) as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id'   => $item['product_id'] ?? null,
                    'quantity'     => $item['quantity'] ?? 1,
                    'item_cost'    => $item['item_cost'] ?? 0,
                ]);
                $subTotal += ($item['quantity'] ?? 1) * ($item['item_cost'] ?? 0);
            }
            $quotation->sub_total = $subTotal;
            $quotation->total     = $subTotal + ($request->input('shipping_cost', $quotation->shipping_cost) ?? 0);
        }

        if ($request->has('shipping_cost')) {
            $quotation->shipping_cost = $request->shipping_cost;
            $quotation->total         = $quotation->sub_total + $request->shipping_cost;
        }

        $quotation->save();

        return $this->success($this->formatQuotation($quotation->load(['client', 'items.product'])));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_quotation'), 403);

        $branchId  = $this->resolveBranch($request);
        $quotation = Quotation::where('branch_id', $branchId)->findOrFail($id);
        $quotation->items()->delete();
        $quotation->delete();

        return $this->success(null, 'Quotation deleted.');
    }

    private function formatQuotation(Quotation $q): array
    {
        return [
            'id'            => $q->id,
            'branch_id'     => $q->branch_id,
            'client_id'     => $q->client_id,
            'shipping_cost' => $q->shipping_cost,
            'sub_total'     => $q->sub_total,
            'total'         => $q->total,
            'recorded_by'   => $q->recorded_by,
            'created_at'    => $q->created_at,
            'client'        => $q->client ? ['id' => $q->client->id, 'name' => $q->client->name] : null,
            'items'         => $q->relationLoaded('items') ? $q->items->map(fn ($i) => [
                'id'         => $i->id,
                'product_id' => $i->product_id,
                'quantity'   => $i->quantity,
                'item_cost'  => $i->item_cost,
                'product'    => $i->product ? ['id' => $i->product->id, 'name' => $i->product->name] : null,
            ]) : [],
        ];
    }
}
