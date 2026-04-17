<?php

namespace App\Http\Controllers\Api\V1\Cashbook;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CashbookEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookEntryController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_cashbook_entry'), 403);

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $entries   = CashbookEntry::forMonth($month, $year)
            ->orderBy('date')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($e) => $this->formatEntry($e));

        $totals    = CashbookEntry::monthlyTotals($month, $year);

        return $this->success([
            'month'   => $month,
            'year'    => $year,
            'entries' => $entries,
            'totals'  => $totals,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_cashbook_entry'), 403);

        $request->validate([
            'date'         => 'required|date',
            'pv_no'        => 'nullable|string|max:50',
            'details'      => 'required|string',
            'chq_ref'      => 'nullable|string|max:100',
            'bank_debit'   => 'nullable|numeric|min:0',
            'momo_debit'   => 'nullable|numeric|min:0',
            'bank_credit'  => 'nullable|numeric|min:0',
            'momo_credit'  => 'nullable|numeric|min:0',
            'cost_center'  => 'required|string',
            'month'        => 'nullable|integer|min:1|max:12',
            'year'         => 'nullable|integer|min:2000',
        ]);

        $date  = \Carbon\Carbon::parse($request->date);
        $entry = CashbookEntry::create([
            'date'        => $request->date,
            'pv_no'       => $request->input('pv_no'),
            'details'     => $request->details,
            'chq_ref'     => $request->input('chq_ref'),
            'bank_debit'  => $request->input('bank_debit', 0),
            'momo_debit'  => $request->input('momo_debit', 0),
            'bank_credit' => $request->input('bank_credit', 0),
            'momo_credit' => $request->input('momo_credit', 0),
            'cost_center' => $request->cost_center,
            'month'       => $request->input('month', $date->month),
            'year'        => $request->input('year', $date->year),
        ]);

        return $this->success($this->formatEntry($entry), 'Cashbook entry created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_cashbook_entry'), 403);

        $entry = CashbookEntry::findOrFail($id);

        return $this->success($this->formatEntry($entry));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_cashbook_entry'), 403);

        $entry = CashbookEntry::findOrFail($id);

        $request->validate([
            'date'        => 'sometimes|date',
            'pv_no'       => 'nullable|string|max:50',
            'details'     => 'sometimes|string',
            'chq_ref'     => 'nullable|string|max:100',
            'bank_debit'  => 'nullable|numeric|min:0',
            'momo_debit'  => 'nullable|numeric|min:0',
            'bank_credit' => 'nullable|numeric|min:0',
            'momo_credit' => 'nullable|numeric|min:0',
            'cost_center' => 'sometimes|string',
        ]);

        $data = $request->only(['date', 'pv_no', 'details', 'chq_ref', 'bank_debit', 'momo_debit', 'bank_credit', 'momo_credit', 'cost_center']);

        if (isset($data['date'])) {
            $date          = \Carbon\Carbon::parse($data['date']);
            $data['month'] = $date->month;
            $data['year']  = $date->year;
        }

        $entry->update($data);

        return $this->success($this->formatEntry($entry->fresh()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_cashbook_entry'), 403);

        $entry = CashbookEntry::findOrFail($id);
        $entry->delete();

        return $this->success(null, 'Entry deleted.');
    }

    private function formatEntry(CashbookEntry $e): array
    {
        return [
            'id'                   => $e->id,
            'date'                 => $e->date,
            'pv_no'                => $e->pv_no,
            'details'              => $e->details,
            'chq_ref'              => $e->chq_ref,
            'bank_debit'           => $e->bank_debit,
            'momo_debit'           => $e->momo_debit,
            'bank_credit'          => $e->bank_credit,
            'momo_credit'          => $e->momo_credit,
            'bank_balance'         => $e->bank_balance,
            'momo_balance'         => $e->momo_balance,
            'cost_center'          => $e->cost_center instanceof \BackedEnum ? $e->cost_center->value : $e->cost_center,
            'month'                => $e->month,
            'year'                 => $e->year,
            // Analysis columns
            'op_balance'           => $e->op_balance,
            'sales'                => $e->sales,
            'dir_transfer'         => $e->dir_transfer,
            'shipping_fee'         => $e->shipping_fee,
            'service_fee'          => $e->service_fee,
            'momo_interest'        => $e->momo_interest,
            'property_management'  => $e->property_management,
            'refund'               => $e->refund,
            'contra_receipt'       => $e->contra_receipt,
            'import_duty'          => $e->import_duty,
            'shipping_expenses'    => $e->shipping_expenses,
            'salaries_wages'       => $e->salaries_wages,
            'bank_charges'         => $e->bank_charges,
            'ssnit'                => $e->ssnit,
            'paye'                 => $e->paye,
            'withholding_tax'      => $e->withholding_tax,
            'momo_charges'         => $e->momo_charges,
            'contra_payment'       => $e->contra_payment,
            'transportation'       => $e->transportation,
            'materials'            => $e->materials,
            'donation'             => $e->donation,
            'created_at'           => $e->created_at,
        ];
    }
}
