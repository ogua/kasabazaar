<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollPeriodController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_payroll_period'), 403);

        $branchId  = $this->resolveBranch($request);
        $paginated = PayrollPeriod::where('branch_id', $branchId)
            ->latest('pay_date')
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => $this->formatPeriod($p));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_payroll_period'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'name'       => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'pay_date'   => 'required|date',
            'notes'      => 'nullable|string',
        ]);

        $period = PayrollPeriod::create(array_merge(
            $request->only(['name', 'start_date', 'end_date', 'pay_date', 'notes']),
            ['branch_id' => $branchId, 'status' => 'draft']
        ));

        return $this->success($this->formatPeriod($period), 'Payroll period created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_payroll_period'), 403);

        $branchId = $this->resolveBranch($request);
        $period   = PayrollPeriod::where('branch_id', $branchId)->findOrFail($id);

        $entries = PayrollEntry::where('payroll_period_id', $id)
            ->with('staff:id,name,employee_id,position')
            ->get()
            ->map(fn ($e) => [
                'id'               => $e->id,
                'staff_id'         => $e->staff_id,
                'base_salary'      => $e->base_salary,
                'overtime'         => $e->overtime,
                'bonus'            => $e->bonus,
                'allowances'       => $e->allowances,
                'tax'              => $e->tax,
                'ssnit'            => $e->ssnit,
                'other_deductions' => $e->other_deductions,
                'gross_pay'        => $e->gross_pay,
                'total_deductions' => $e->total_deductions,
                'net_salary'       => $e->net_salary,
                'status'           => $e->status instanceof \BackedEnum ? $e->status->value : $e->status,
                'paid_at'          => $e->paid_at,
                'payment_reference'=> $e->payment_reference,
                'notes'            => $e->notes,
                'staff'            => $e->staff ? [
                    'id'          => $e->staff->id,
                    'name'        => $e->staff->name,
                    'employee_id' => $e->staff->employee_id,
                    'position'    => $e->staff->position,
                ] : null,
            ]);

        $data          = $this->formatPeriod($period);
        $data['entries'] = $entries;

        return $this->success($data);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_payroll_period'), 403);

        $branchId = $this->resolveBranch($request);
        $period   = PayrollPeriod::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'name'       => 'sometimes|string|max:100',
            'start_date' => 'sometimes|date',
            'end_date'   => 'sometimes|date|after_or_equal:start_date',
            'pay_date'   => 'sometimes|date',
            'status'     => 'sometimes|in:draft,processing,approved,paid,cancelled',
            'notes'      => 'nullable|string',
        ]);

        $data = $request->only(['name', 'start_date', 'end_date', 'pay_date', 'status', 'notes']);

        if ($request->input('status') === 'approved') {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        $period->update($data);

        return $this->success($this->formatPeriod($period->fresh()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_payroll_period'), 403);

        $branchId = $this->resolveBranch($request);
        $period   = PayrollPeriod::where('branch_id', $branchId)->findOrFail($id);
        $period->entries()->delete();
        $period->delete();

        return $this->success(null, 'Payroll period deleted.');
    }

    private function formatPeriod(PayrollPeriod $p): array
    {
        return [
            'id'          => $p->id,
            'branch_id'   => $p->branch_id,
            'name'        => $p->name,
            'start_date'  => $p->start_date,
            'end_date'    => $p->end_date,
            'pay_date'    => $p->pay_date,
            'status'      => $p->status instanceof \BackedEnum ? $p->status->value : $p->status,
            'approved_by' => $p->approved_by,
            'approved_at' => $p->approved_at,
            'notes'       => $p->notes,
            'created_at'  => $p->created_at,
        ];
    }
}
