<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Income;
use App\Models\IncomeCategory;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_income'), 403);

        $branchId = $this->resolveBranch($request);
        $query    = Income::where('branch_id', $branchId)->with('category:id,name,code');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($catId = $request->input('income_category_id')) {
            $query->where('income_category_id', $catId);
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('income_date', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('income_date', '<=', $to);
        }

        $paginated = $query->latest('income_date')->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($i) => $this->formatIncome($i));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_income'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'income_category_id' => 'required|uuid|exists:income_categories,id',
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'amount_usd'         => 'required|numeric|min:0',
            'exchange_rate'      => 'nullable|numeric|min:0',
            'source_name'        => 'nullable|string|max:255',
            'source_contact'     => 'nullable|string|max:100',
            'income_date'        => 'required|date',
            'payment_method'     => 'nullable|in:cash,bank_transfer,mobile_money,cheque,card,other',
            'payment_reference'  => 'nullable|string|max:100',
            'shipment_id'        => 'nullable|uuid|exists:shipments,id',
            'status'             => 'nullable|in:pending,received,cancelled',
            'receipt'            => 'nullable|file|image|max:2048',
        ]);

        $data = $request->only([
            'income_category_id', 'title', 'description', 'amount_usd',
            'exchange_rate', 'source_name', 'source_contact', 'income_date',
            'payment_method', 'payment_reference', 'shipment_id', 'status',
        ]);
        $data['branch_id']   = $branchId;
        $data['recorded_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        $income = Income::create($data);

        return $this->success($this->formatIncome($income->load('category')), 'Income recorded.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_income'), 403);

        $branchId = $this->resolveBranch($request);
        $income   = Income::where('branch_id', $branchId)->with('category')->findOrFail($id);

        return $this->success($this->formatIncome($income));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_income'), 403);

        $branchId = $this->resolveBranch($request);
        $income   = Income::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'title'              => 'sometimes|string|max:255',
            'amount_usd'         => 'sometimes|numeric|min:0',
            'exchange_rate'      => 'nullable|numeric|min:0',
            'income_date'        => 'sometimes|date',
            'payment_method'     => 'nullable|in:cash,bank_transfer,mobile_money,cheque,card,other',
            'status'             => 'nullable|in:pending,received,cancelled',
        ]);

        $income->update($request->only([
            'income_category_id', 'title', 'description', 'amount_usd', 'exchange_rate',
            'source_name', 'source_contact', 'income_date', 'payment_method',
            'payment_reference', 'status',
        ]));

        return $this->success($this->formatIncome($income->fresh('category')));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_income'), 403);

        $branchId = $this->resolveBranch($request);
        $income   = Income::where('branch_id', $branchId)->findOrFail($id);
        $income->delete();

        return $this->success(null, 'Income deleted.');
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = IncomeCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'description']);

        return $this->success($categories);
    }

    private function formatIncome(Income $i): array
    {
        return [
            'id'                  => $i->id,
            'branch_id'           => $i->branch_id,
            'income_category_id'  => $i->income_category_id,
            'shipment_id'         => $i->shipment_id,
            'reference'           => $i->reference,
            'title'               => $i->title,
            'description'         => $i->description,
            'amount_usd'          => $i->amount_usd,
            'exchange_rate'       => $i->exchange_rate,
            'amount_ghs'          => $i->amount_ghs,
            'source_name'         => $i->source_name,
            'source_contact'      => $i->source_contact,
            'income_date'         => $i->income_date,
            'payment_method'      => $i->payment_method instanceof \BackedEnum ? $i->payment_method->value : $i->payment_method,
            'payment_reference'   => $i->payment_reference,
            'receipt_path'        => $i->receipt_path ? asset('storage/' . $i->receipt_path) : null,
            'status'              => $i->status instanceof \BackedEnum ? $i->status->value : $i->status,
            'recorded_by'         => $i->recorded_by,
            'created_at'          => $i->created_at,
            'category'            => $i->category ? ['id' => $i->category->id, 'name' => $i->category->name] : null,
        ];
    }
}
