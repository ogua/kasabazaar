<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_user'), 403);

        $query = User::with('branches:id,name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($u) => $this->formatUser($u));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_user'), 403);

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:30',
            'role'      => 'required|in:customer,admin,branch_personnel',
            'password'  => 'required|string|min:6',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'branches'  => 'nullable|array',
            'branches.*'=> 'uuid|exists:branches,id',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->input('phone'),
            'role'      => $request->role,
            'password'  => Hash::make($request->password),
            'branch_id' => $request->input('branch_id'),
        ]);

        if ($request->has('branches')) {
            $user->branches()->sync($request->branches);
        } elseif ($request->branch_id) {
            $user->branches()->sync([$request->branch_id]);
        }

        return $this->success($this->formatUser($user->load('branches')), 'User created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_user'), 403);

        $user = User::with('branches:id,name')->findOrFail($id);

        return $this->success($this->formatUser($user));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_user'), 403);

        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:30',
            'role'  => 'sometimes|in:customer,admin,branch_personnel',
        ]);

        $user->update($request->only(['name', 'phone', 'role', 'branch_id']));

        if ($request->has('branches')) {
            $user->branches()->sync($request->branches);
        }

        return $this->success($this->formatUser($user->fresh('branches')));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_user'), 403);

        $user = User::findOrFail($id);
        abort_if($user->id === auth()->id(), 422, 'Cannot delete your own account.');

        $user->tokens()->delete();
        $user->delete();

        return $this->success(null, 'User deleted.');
    }

    private function formatUser(User $u): array
    {
        return [
            'id'        => $u->id,
            'name'      => $u->name,
            'email'     => $u->email,
            'phone'     => $u->phone,
            'role'      => $u->role,
            'branch_id' => $u->branch_id,
            'client_id' => $u->client_id,
            'avatar'    => $u->avatar ? asset('storage/' . $u->avatar) : null,
            'branches'  => $u->relationLoaded('branches') ? $u->branches->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]) : [],
            'created_at'=> $u->created_at,
        ];
    }
}
