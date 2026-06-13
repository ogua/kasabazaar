<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PayrollEntry;
use App\Models\Staff;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_staff'), 403);

        $branchId = $this->resolveBranch($request);
        $query    = Staff::where('branch_id', $branchId)->with('role:id,name,code');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($status = $request->input('employment_status')) {
            $query->where('employment_status', $status);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($s) => $this->formatStaff($s));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_staff'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'position'          => 'nullable|string|max:100',
            'staff_role_id'     => 'nullable|uuid|exists:staff_roles,id',
            'user_id'           => 'nullable|uuid|exists:users,id',
            'salary'            => 'nullable|numeric|min:0',
            'hire_date'         => 'nullable|date',
            'employment_status' => 'nullable|in:active,inactive,terminated,on_leave,resigned,probation',
            'notes'             => 'nullable|string',
        ]);

        $staff = Staff::create(array_merge(
            $request->only(['name', 'email', 'phone', 'position', 'staff_role_id', 'user_id', 'salary', 'hire_date', 'employment_status', 'notes']),
            ['branch_id' => $branchId]
        ));

        return $this->success($this->formatStaff($staff->load('role')), 'Staff created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_staff'), 403);

        $branchId = $this->resolveBranch($request);
        $staff    = Staff::where('branch_id', $branchId)->with('role')->findOrFail($id);

        return $this->success($this->formatStaff($staff));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_staff'), 403);

        $branchId = $this->resolveBranch($request);
        $staff    = Staff::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'name'              => 'sometimes|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'position'          => 'nullable|string|max:100',
            'staff_role_id'     => 'nullable|uuid|exists:staff_roles,id',
            'user_id'           => 'nullable|uuid|exists:users,id',
            'salary'            => 'nullable|numeric|min:0',
            'hire_date'         => 'nullable|date',
            'employment_status' => 'nullable|in:active,inactive,terminated,on_leave,resigned,probation',
            'notes'             => 'nullable|string',
        ]);

        $staff->update($request->only(['name', 'email', 'phone', 'position', 'staff_role_id', 'user_id', 'salary', 'hire_date', 'employment_status', 'notes']));

        return $this->success($this->formatStaff($staff->fresh('role')));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_staff'), 403);

        $branchId = $this->resolveBranch($request);
        $staff    = Staff::where('branch_id', $branchId)->findOrFail($id);
        $staff->delete();

        return $this->success(null, 'Staff deleted.');
    }

    public function payrollEntries(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_payroll::period'), 403);

        $branchId = $this->resolveBranch($request);
        Staff::where('branch_id', $branchId)->findOrFail($id);

        $entries = PayrollEntry::where('staff_id', $id)
            ->with('payrollPeriod:id,name,pay_date,status')
            ->latest()
            ->get()
            ->map(fn ($e) => [
                'id'               => $e->id,
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
                'period'           => $e->payrollPeriod ? [
                    'id'       => $e->payrollPeriod->id,
                    'name'     => $e->payrollPeriod->name,
                    'pay_date' => $e->payrollPeriod->pay_date,
                    'status'   => $e->payrollPeriod->status instanceof \BackedEnum ? $e->payrollPeriod->status->value : $e->payrollPeriod->status,
                ] : null,
            ]);

        return $this->success($entries);
    }

    private function formatStaff(Staff $s): array
    {
        return [
            'id'                => $s->id,
            'branch_id'         => $s->branch_id,
            'name'              => $s->name,
            'email'             => $s->email,
            'phone'             => $s->phone,
            'position'          => $s->position,
            'employee_id'       => $s->employee_id,
            'salary'            => $s->salary,
            'hire_date'         => $s->hire_date,
            'employment_status' => $s->employment_status instanceof \BackedEnum ? $s->employment_status->value : $s->employment_status,
            'notes'             => $s->notes,
            'user_id'           => $s->user_id,
            'staff_role_id'     => $s->staff_role_id,
            'role'              => $s->role ? ['id' => $s->role->id, 'name' => $s->role->name, 'code' => $s->role->code] : null,
            'created_at'        => $s->created_at,
        ];
    }
}
