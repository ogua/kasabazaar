<?php

namespace App\Http\Controllers\Api\V1\Cashbook;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CashbookExpenditureLedger;
use App\Models\CashbookIncomeLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookLedgerController extends BaseApiController
{
    public function income(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_cashbook_entry'), 403);

        $month   = (int) $request->input('month', now()->month);
        $year    = (int) $request->input('year', now()->year);

        $ledgers = CashbookIncomeLedger::where('month', $month)
            ->where('year', $year)
            ->orderBy('ledger_type')
            ->get()
            ->map(fn ($l) => [
                'id'          => $l->id,
                'month'       => $l->month,
                'year'        => $l->year,
                'ledger_type' => $l->ledger_type instanceof \BackedEnum ? $l->ledger_type->value : $l->ledger_type,
                'particulars' => $l->particulars,
                'op_balance'  => $l->op_balance,
                'debit'       => $l->debit,
                'credit'      => $l->credit,
                'cl_balance'  => $l->cl_balance,
            ]);

        return $this->success(['month' => $month, 'year' => $year, 'ledgers' => $ledgers]);
    }

    public function expenditure(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_cashbook_entry'), 403);

        $month   = (int) $request->input('month', now()->month);
        $year    = (int) $request->input('year', now()->year);

        $ledgers = CashbookExpenditureLedger::where('month', $month)
            ->where('year', $year)
            ->orderBy('ledger_type')
            ->get()
            ->map(fn ($l) => [
                'id'          => $l->id,
                'month'       => $l->month,
                'year'        => $l->year,
                'ledger_type' => $l->ledger_type instanceof \BackedEnum ? $l->ledger_type->value : $l->ledger_type,
                'particulars' => $l->particulars,
                'op_balance'  => $l->op_balance,
                'debit'       => $l->debit,
                'credit'      => $l->credit,
                'cl_balance'  => $l->cl_balance,
            ]);

        return $this->success(['month' => $month, 'year' => $year, 'ledgers' => $ledgers]);
    }
}
