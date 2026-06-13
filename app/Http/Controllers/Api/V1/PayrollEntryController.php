<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PayrollEntry;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollEntryController extends BaseApiController
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_payroll::period'), 403);

        $request->validate([
            'payroll_period_id' => 'required|uuid|exists:payroll_periods,id',
            'staff_id'          => 'required|uuid|exists:staff,id',
            'base_salary'       => 'required|numeric|min:0',
            'overtime'          => 'nullable|numeric|min:0',
            'bonus'             => 'nullable|numeric|min:0',
            'allowances'        => 'nullable|numeric|min:0',
            'tax'               => 'nullable|numeric|min:0',
            'ssnit'             => 'nullable|numeric|min:0',
            'other_deductions'  => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
        ]);

        $exists = PayrollEntry::where('payroll_period_id', $request->payroll_period_id)
            ->where('staff_id', $request->staff_id)
            ->exists();

        if ($exists) {
            return $this->error('A payroll entry for this staff member already exists in this period.', 409);
        }

        $entry = PayrollEntry::create($request->only([
            'payroll_period_id', 'staff_id', 'base_salary', 'overtime', 'bonus',
            'allowances', 'tax', 'ssnit', 'other_deductions', 'notes',
        ]));

        return $this->success(
            $this->formatEntry($entry->load(['staff:id,name,employee_id,position', 'payrollPeriod:id,name,pay_date,status'])),
            'Payroll entry created.',
            201
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_payroll::period'), 403);

        $entry = PayrollEntry::with(['staff:id,name,employee_id,position', 'payrollPeriod:id,name,pay_date,status'])
            ->findOrFail($id);

        return $this->success($this->formatEntry($entry));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_payroll::period'), 403);

        $entry = PayrollEntry::findOrFail($id);

        $request->validate([
            'base_salary'      => 'sometimes|numeric|min:0',
            'overtime'         => 'nullable|numeric|min:0',
            'bonus'            => 'nullable|numeric|min:0',
            'allowances'       => 'nullable|numeric|min:0',
            'tax'              => 'nullable|numeric|min:0',
            'ssnit'            => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'status'           => 'sometimes|in:pending,approved,paid',
            'paid_at'          => 'nullable|date',
            'payment_reference'=> 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        // gross_pay, total_deductions, net_salary are computed by PayrollEntry::booted()
        $data = $request->only([
            'base_salary', 'overtime', 'bonus', 'allowances', 'tax', 'ssnit',
            'other_deductions', 'status', 'paid_at', 'payment_reference', 'notes',
        ]);

        if ($request->input('status') === 'paid' && !$entry->paid_at) {
            $data['paid_at'] = $data['paid_at'] ?? now();
        }

        $entry->update($data);

        return $this->success($this->formatEntry($entry->fresh(['staff', 'payrollPeriod'])));
    }

    private function formatEntry(PayrollEntry $e): array
    {
        return [
            'id'                => $e->id,
            'payroll_period_id' => $e->payroll_period_id,
            'staff_id'          => $e->staff_id,
            'base_salary'       => $e->base_salary,
            'overtime'          => $e->overtime,
            'bonus'             => $e->bonus,
            'allowances'        => $e->allowances,
            'tax'               => $e->tax,
            'ssnit'             => $e->ssnit,
            'other_deductions'  => $e->other_deductions,
            'gross_pay'         => $e->gross_pay,
            'total_deductions'  => $e->total_deductions,
            'net_salary'        => $e->net_salary,
            'status'            => $e->status instanceof \BackedEnum ? $e->status->value : $e->status,
            'paid_at'           => $e->paid_at,
            'payment_reference' => $e->payment_reference,
            'notes'             => $e->notes,
            'staff'             => $e->staff ? ['id' => $e->staff->id, 'name' => $e->staff->name, 'employee_id' => $e->staff->employee_id] : null,
            'period'            => $e->payrollPeriod ? ['id' => $e->payrollPeriod->id, 'name' => $e->payrollPeriod->name] : null,
        ];
    }
}
