<?php

namespace App\Http\Controllers\Api\V1\Cashbook;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CashbookWithholdingTax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookWHTController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_cashbook_entry'), 403);

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $records = CashbookWithholdingTax::where('month', $month)
            ->where('year', $year)
            ->orderBy('staff_name')
            ->get()
            ->map(fn ($w) => $this->formatWHT($w));

        $totalGross  = $records->sum('gross_amount');
        $totalWht    = $records->sum('wht_amount');

        return $this->success([
            'month'       => $month,
            'year'        => $year,
            'records'     => $records,
            'total_gross' => $totalGross,
            'total_wht'   => $totalWht,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_cashbook_entry'), 403);

        $request->validate([
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2000',
            'staff_name'   => 'required|string|max:255',
            'gross_amount' => 'required|numeric|min:0',
            'rate'         => 'nullable|numeric|min:0|max:1',
        ]);

        $rate      = $request->input('rate', 0.075);
        $whtAmount = round($request->gross_amount * $rate, 2);

        $record = CashbookWithholdingTax::create([
            'month'        => $request->month,
            'year'         => $request->year,
            'staff_name'   => $request->staff_name,
            'gross_amount' => $request->gross_amount,
            'rate'         => $rate,
            'wht_amount'   => $whtAmount,
        ]);

        return $this->success($this->formatWHT($record), 'WHT entry created.', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_cashbook_entry'), 403);

        $record = CashbookWithholdingTax::findOrFail($id);

        $request->validate([
            'staff_name'   => 'sometimes|string|max:255',
            'gross_amount' => 'sometimes|numeric|min:0',
            'rate'         => 'nullable|numeric|min:0|max:1',
        ]);

        $data         = $request->only(['staff_name', 'gross_amount', 'rate']);
        $grossAmount  = $data['gross_amount'] ?? $record->gross_amount;
        $rate         = $data['rate'] ?? $record->rate;
        $data['wht_amount'] = round($grossAmount * $rate, 2);

        $record->update($data);

        return $this->success($this->formatWHT($record->fresh()));
    }

    private function formatWHT(CashbookWithholdingTax $w): array
    {
        return [
            'id'           => $w->id,
            'month'        => $w->month,
            'year'         => $w->year,
            'staff_name'   => $w->staff_name,
            'gross_amount' => $w->gross_amount,
            'rate'         => $w->rate,
            'wht_amount'   => $w->wht_amount,
            'created_at'   => $w->created_at,
        ];
    }
}
