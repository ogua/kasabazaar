<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_expense'), 403);

        $branchId = $this->resolveBranch($request);
        $query = Expense::where('branch_id', $branchId)
            ->with(['category:id,name,code', 'shipment:id,shipping_reference']);

        if ($stage = $request->input('expense_stage')) {
            $query->where('expense_stage', $stage);
        }
        if ($catId = $request->input('expense_category_id')) {
            $query->where('expense_category_id', $catId);
        }
        if ($scope = $request->input('expense_for')) {
            $query->where('expense_for', $scope);
        }
        if ($containerNumber = $request->input('container_number')) {
            $query->forContainer($containerNumber);
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('expense_date', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('expense_date', '<=', $to);
        }

        $paginated = $query->latest('expense_date')->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($e) => $this->formatExpense($e));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_expense'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'expense_category_id' => 'required|uuid|exists:expense_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount_usd' => 'required_without:amount_ghs|nullable|numeric|min:0',
            'amount_ghs' => 'required_without:amount_usd|nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'expense_stage' => 'nullable|in:pre_shipment,during_shipment,post_shipment',
            'expense_for' => 'nullable|in:shipment,container',
            'shipment_id' => 'nullable|uuid|exists:shipments,id',
            'container_number' => 'nullable|string|exists:shipments,container_number',
            'vendor_name' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|image|max:2048',
        ]);

        $data = $request->only([
            'expense_category_id', 'title', 'description', 'amount_usd', 'amount_ghs',
            'exchange_rate', 'expense_date', 'expense_stage', 'shipment_id',
            'container_number', 'expense_for', 'vendor_name',
        ]);
        $data['expense_for'] = ($data['expense_for'] ?? null)
            ?: (! empty($data['container_number']) && empty($data['shipment_id']) ? 'container' : 'shipment');
        $data['branch_id'] = $branchId;
        $data['recorded_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        $expense = Expense::create($data);

        return $this->success($this->formatExpense($expense->load('category')), 'Expense recorded.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_expense'), 403);

        $branchId = $this->resolveBranch($request);
        $expense = Expense::where('branch_id', $branchId)->with('category')->findOrFail($id);

        return $this->success($this->formatExpense($expense));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_expense'), 403);

        $branchId = $this->resolveBranch($request);
        $expense = Expense::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'expense_category_id' => 'sometimes|uuid|exists:expense_categories,id',
            'title' => 'sometimes|string|max:255',
            'amount_usd' => 'sometimes|nullable|numeric|min:0',
            'amount_ghs' => 'sometimes|nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'expense_stage' => 'nullable|in:pre_shipment,during_shipment,post_shipment',
            'expense_for' => 'sometimes|in:shipment,container',
            'shipment_id' => 'sometimes|nullable|uuid|exists:shipments,id',
            'container_number' => 'sometimes|nullable|string|exists:shipments,container_number',
            'vendor_name' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|image|max:2048',
        ]);

        $data = $request->only([
            'expense_category_id', 'title', 'description', 'amount_usd', 'amount_ghs',
            'exchange_rate', 'expense_date', 'expense_stage', 'expense_for',
            'shipment_id', 'container_number', 'vendor_name',
        ]);

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        $expense->update($data);

        return $this->success($this->formatExpense($expense->fresh('category')));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_expense'), 403);

        $branchId = $this->resolveBranch($request);
        $expense = Expense::where('branch_id', $branchId)->findOrFail($id);
        $expense->delete();

        return $this->success(null, 'Expense deleted.');
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = ExpenseCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'description']);

        return $this->success($categories);
    }

    private function formatExpense(Expense $e): array
    {
        return [
            'id' => $e->id,
            'branch_id' => $e->branch_id,
            'expense_for' => $e->expense_for instanceof \BackedEnum ? $e->expense_for->value : $e->expense_for,
            'shipment_id' => $e->shipment_id,
            'container_number' => $e->container_number,
            'container_ref' => $e->container_number ? 'CON'.$e->container_number : null,
            'subject_reference' => $e->subject_reference,
            'expense_category_id' => $e->expense_category_id,
            'reference' => $e->reference,
            'shipping_reference' => $e->shipment?->shipping_reference,
            'title' => $e->title,
            'description' => $e->description,
            'amount_usd' => $e->amount_usd,
            'exchange_rate' => $e->exchange_rate,
            'amount_ghs' => $e->amount_ghs,
            'expense_date' => $e->expense_date,
            'expense_stage' => $e->expense_stage instanceof \BackedEnum ? $e->expense_stage->value : $e->expense_stage,
            'vendor_name' => $e->vendor_name,
            'receipt_path' => $e->receipt_path ? asset('storage/'.$e->receipt_path) : null,
            'recorded_by' => $e->recorded_by,
            'created_at' => $e->created_at,
            'category' => $e->category ? ['id' => $e->category->id, 'name' => $e->category->name] : null,
        ];
    }
}
