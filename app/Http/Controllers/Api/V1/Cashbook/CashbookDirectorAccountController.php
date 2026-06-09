<?php

namespace App\Http\Controllers\Api\V1\Cashbook;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CashbookDirectorAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookDirectorAccountController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_cashbook_entry'), 403);

        $paginated = CashbookDirectorAccount::latest('date')
            ->paginate((int) $request->input('per_page', 30));

        return $this->paginated($paginated, fn ($d) => $this->formatDirector($d));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_cashbook_entry'), 403);

        $request->validate([
            'date'        => 'required|date',
            'particulars' => 'required|string',
            'op_balance'  => 'nullable|numeric',
            'debit'       => 'nullable|numeric|min:0',
            'credit'      => 'nullable|numeric|min:0',
        ]);

        $record = CashbookDirectorAccount::create(
            $request->only(['date', 'particulars', 'op_balance', 'debit', 'credit'])
        );

        return $this->success($this->formatDirector($record), 'Director account entry created.', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_cashbook_entry'), 403);

        $record = CashbookDirectorAccount::findOrFail($id);

        $request->validate([
            'date'        => 'sometimes|date',
            'particulars' => 'sometimes|string',
            'op_balance'  => 'nullable|numeric',
            'debit'       => 'nullable|numeric|min:0',
            'credit'      => 'nullable|numeric|min:0',
        ]);

        $record->update($request->only(['date', 'particulars', 'op_balance', 'debit', 'credit']));

        return $this->success($this->formatDirector($record->fresh()), 'Director account entry updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_cashbook_entry'), 403);

        $record = CashbookDirectorAccount::findOrFail($id);
        $record->delete();

        return $this->success(null, 'Director account entry deleted.');
    }

    private function formatDirector(CashbookDirectorAccount $d): array
    {
        return [
            'id'          => $d->id,
            'date'        => $d->date,
            'particulars' => $d->particulars,
            'op_balance'  => $d->op_balance,
            'debit'       => $d->debit,
            'credit'      => $d->credit,
            'cl_balance'  => $d->cl_balance,
            'created_at'  => $d->created_at,
        ];
    }
}
