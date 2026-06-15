<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\V1\BaseApiController;

class AuthController extends BaseApiController
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid email or password.', 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $user->load(['branches', 'staff.role']);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success($this->formatUser($user, $token));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['branches', 'staff.role']);
        return $this->success($this->formatUser($user));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'   => 'sometimes|string|max:255',
            'phone'  => 'sometimes|string|max:30',
            'avatar' => 'sometimes|image|max:2048',
        ]);

        /** @var User $user */
        $user = $request->user();

        $data = $request->only(['name', 'phone']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);
        $user->load(['branches', 'staff.role']);

        return $this->success($this->formatUser($user), 'Profile updated.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return $this->success(null, 'Password changed successfully.');
    }

    private function formatUser(User $user, ?string $token = null): array
    {
        $data = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'avatar'      => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'role'        => $user->role,
            'client_id'   => $user->client_id,
            'branch_id'   => $user->branch_id,
            'branches'    => $user->branches->map(fn ($b) => [
                'id'       => $b->id,
                'name'     => $b->name,
                'slug'     => $b->slug,
                'location' => trim(collect([$b->state, $b->country])->filter()->implode(', ')),
            ])->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'roles'       => $user->getRoleNames()->values(),
            'staff'       => $user->staff ? [
                'id'          => $user->staff->id,
                'employee_id' => $user->staff->employee_id,
                'role'        => $user->staff->role ? [
                    'id'   => $user->staff->role->id,
                    'name' => $user->staff->role->name,
                    'code' => $user->staff->role->code,
                ] : null,
            ] : null,
        ];

        if ($token) {
            $data['token'] = $token;
        }

        return $data;
    }
}
