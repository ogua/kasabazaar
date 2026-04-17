<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ExchangeRateLog;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\Product;
use App\Models\Receiver;
use App\Models\Staff;
use App\Models\StaffRole;
use App\Models\Vehicle;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends BaseApiController
{
    public function branches(Request $request): JsonResponse
    {
        $branches = auth()->user()->branches()
            ->orderBy('name')
            ->get(['branches.id', 'branches.name', 'branches.slug']);

        return $this->success($branches);
    }

    public function clients(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $query    = Client::where('branch_id', $branchId)->select('id', 'name', 'phone', 'email');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $this->success($query->orderBy('name')->limit(100)->get());
    }

    public function products(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $query    = Product::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })->select('id', 'name', 'sku', 'category', 'value');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $this->success($query->orderBy('name')->limit(100)->get());
    }

    public function staff(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $query    = Staff::where('branch_id', $branchId)
            ->where('employment_status', 'active')
            ->select('id', 'name', 'phone', 'position', 'staff_role_id', 'employee_id');

        if ($driverOnly = $request->boolean('drivers_only')) {
            $query->whereHas('role', fn ($q) => $q->where('code', 'DRIVER'));
        }

        return $this->success($query->orderBy('name')->limit(100)->get());
    }

    public function vehicles(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $query    = Vehicle::where('branch_id', $branchId)
            ->select('id', 'registration_number', 'make', 'model', 'year', 'status');

        if ($request->boolean('available_only')) {
            $query->where('status', 'available');
        }

        return $this->success($query->orderBy('registration_number')->limit(50)->get());
    }

    public function expenseCategories(Request $request): JsonResponse
    {
        $categories = ExpenseCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return $this->success($categories);
    }

    public function incomeCategories(Request $request): JsonResponse
    {
        $categories = IncomeCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return $this->success($categories);
    }

    public function staffRoles(Request $request): JsonResponse
    {
        $roles = StaffRole::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'base_salary']);

        return $this->success($roles);
    }

    public function exchangeRate(Request $request): JsonResponse
    {
        $rate = ExchangeRateLog::where('from_currency', 'USD')
            ->where('to_currency', 'GHS')
            ->latest()
            ->first();

        return $this->success([
            'rate'          => $rate ? (float) $rate->rate : 12.0,
            'from_currency' => 'USD',
            'to_currency'   => 'GHS',
            'source'        => $rate?->source ?? 'default',
            'updated_at'    => $rate?->created_at,
        ]);
    }

    public function previousReceivers(Request $request): JsonResponse
    {
        $clientId = $request->input('client_id');

        if (!$clientId) {
            return $this->success([]);
        }

        $receivers = Receiver::whereHas('shipment', fn ($q) => $q->where('client_id', $clientId))
            ->select('receiver_name', 'receiver_phone', 'receiver_email', 'country', 'state_region', 'city', 'address', 'receiver_id_type', 'receiver_id_number')
            ->distinct()
            ->get()
            ->unique(fn ($r) => strtolower($r->receiver_name) . '-' . $r->receiver_phone)
            ->values();

        return $this->success($receivers);
    }
}
