<?php

namespace App\Http\Controllers\Api\V1\Cashbook;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CashbookLoan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookLoanController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_cashbook_entry'), 403);

        $query = CashbookLoan::query();

        if ($lender = $request->input('lender_name')) {
            $query->where('lender_name', 'like', "%{$lender}%");
        }

        $paginated = $query->latest('date')->paginate((int) $request->input('per_page', 30));

        return $this->paginated($paginated, fn ($l) => $this->formatLoan($l));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_cashbook_entry'), 403);

        $request->validate([
            'lender_name' => 'required|string|max:255',
            'date'        => 'required|date',
            'particulars' => 'required|string',
            'op_balance'  => 'nullable|numeric',
            'debit'       => 'nullable|numeric|min:0',
            'credit'      => 'nullable|numeric|min:0',
        ]);

        $loan = CashbookLoan::create($request->only(['lender_name', 'date', 'particulars', 'op_balance', 'debit', 'credit']));

        return $this->success($this->formatLoan($loan), 'Loan entry created.', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_cashbook_entry'), 403);

        $loan = CashbookLoan::findOrFail($id);

        $request->validate([
            'lender_name' => 'sometimes|string|max:255',
            'date'        => 'sometimes|date',
            'particulars' => 'sometimes|string',
            'op_balance'  => 'nullable|numeric',
            'debit'       => 'nullable|numeric|min:0',
            'credit'      => 'nullable|numeric|min:0',
        ]);

        $loan->update($request->only(['lender_name', 'date', 'particulars', 'op_balance', 'debit', 'credit']));

        return $this->success($this->formatLoan($loan->fresh()));
    }

    private function formatLoan(CashbookLoan $l): array
    {
        return [
            'id'          => $l->id,
            'lender_name' => $l->lender_name,
            'date'        => $l->date,
            'particulars' => $l->particulars,
            'op_balance'  => $l->op_balance,
            'debit'       => $l->debit,
            'credit'      => $l->credit,
            'cl_balance'  => $l->cl_balance,
            'created_at'  => $l->created_at,
        ];
    }
}
