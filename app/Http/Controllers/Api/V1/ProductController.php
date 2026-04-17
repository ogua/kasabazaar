<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_product'), 403);

        $branchId = $this->resolveBranch($request);
        $query    = Product::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        });

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 50));

        return $this->paginated($paginated, fn ($p) => $this->formatProduct($p));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_product'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'name'          => 'required|string|max:255',
            'sku'           => 'nullable|string|max:100',
            'category'      => 'nullable|string|max:100',
            'weight'        => 'nullable|numeric|min:0',
            'value'         => 'nullable|numeric|min:0',
            'product_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'sku', 'category', 'weight', 'value']);
        $data['branch_id'] = $branchId;

        if ($request->hasFile('product_image')) {
            $data['product_image'] = $request->file('product_image')->store('products', 'public');
        }

        $product = Product::create($data);

        return $this->success($this->formatProduct($product), 'Product created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_product'), 403);

        $branchId = $this->resolveBranch($request);
        $product  = Product::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })->findOrFail($id);

        return $this->success($this->formatProduct($product));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_product'), 403);

        $branchId = $this->resolveBranch($request);
        $product  = Product::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'name'          => 'sometimes|string|max:255',
            'sku'           => 'nullable|string|max:100',
            'category'      => 'nullable|string|max:100',
            'weight'        => 'nullable|numeric|min:0',
            'value'         => 'nullable|numeric|min:0',
            'product_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'sku', 'category', 'weight', 'value']);

        if ($request->hasFile('product_image')) {
            $data['product_image'] = $request->file('product_image')->store('products', 'public');
        }

        $product->update($data);

        return $this->success($this->formatProduct($product->fresh()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_product'), 403);

        $branchId = $this->resolveBranch($request);
        $product  = Product::where('branch_id', $branchId)->findOrFail($id);
        $product->delete();

        return $this->success(null, 'Product deleted.');
    }

    private function formatProduct(Product $p): array
    {
        return [
            'id'            => $p->id,
            'branch_id'     => $p->branch_id,
            'name'          => $p->name,
            'sku'           => $p->sku,
            'category'      => $p->category,
            'weight'        => $p->weight,
            'value'         => $p->value,
            'product_image' => $p->product_image ? asset('storage/' . $p->product_image) : null,
            'created_at'    => $p->created_at,
        ];
    }
}
