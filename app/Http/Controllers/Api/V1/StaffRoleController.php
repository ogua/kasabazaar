<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\StaffRole;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffRoleController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_staff_role'), 403);

        $query = StaffRole::query();
        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }
        $roles = $query->orderBy('name')->get(['id', 'name', 'code', 'description', 'base_salary', 'is_active']);

        return $this->success($roles);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_staff_role'), 403);

        $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:30|unique:staff_roles,code',
            'description' => 'nullable|string',
            'base_salary' => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $role = StaffRole::create($request->only(['name', 'code', 'description', 'base_salary', 'is_active']));

        return $this->success($role, 'Staff role created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_staff_role'), 403);

        $role = StaffRole::findOrFail($id);

        return $this->success($role);
    }
}
