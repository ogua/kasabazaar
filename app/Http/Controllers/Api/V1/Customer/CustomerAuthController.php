<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\EcommerceCart;
use App\Models\User;
use App\Service\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class CustomerAuthController extends CustomerBaseController
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            // 'branch_id' => 'required|uuid|exists:branches,id',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        $user = DB::transaction(function () use ($request) {
            $branchId = $request->branch_id ?? Branch::query()->value('id');

            $client = Client::create([
                'branch_id' => $branchId,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'country' => $request->country,
                'city' => $request->city,
                'address' => $request->address,
            ]);

            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'client_id' => $client->id,
                'branch_id' => $branchId,
                'role' => 'customer',
            ]);
        });

        $this->mergeGuestCart($user, $request->header('X-Guest-Session-Id'));

        $token = $user->createToken('customer-app')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $this->formatUser($user->load('client')),
        ], 'Registration successful.', 201);
    }

    public function config(): JsonResponse
    {
        return $this->success([
            'minimum_app_version' => SystemSetting::get('minimum_customer_app_version', '1.0.0'),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where(fn ($query) => $query->whereNotNull('client_id')->orWhereNotNull('investor_id'))
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if ($user->status !== UserStatus::Active) {
            return $this->error('Your account has been deactivated.', 401);
        }

        $user->load(['client', 'investor']);

        if ($user->investor_id && $user->investor?->status !== 'active') {
            return $this->error('Your investor account is currently inactive. Please contact us for assistance.', 401);
        }

        $this->mergeGuestCart($user, $request->header('X-Guest-Session-Id'));

        $token = $user->createToken('customer-app')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $this->formatUser($user),
        ], 'Login successful.');
    }

    /**
     * Merges a guest's session-keyed cart (gap D.2) into their user cart on
     * login/registration so items added while browsing anonymously aren't lost.
     */
    private function mergeGuestCart(User $user, ?string $sessionId): void
    {
        if (! $sessionId) {
            return;
        }

        $guestCart = EcommerceCart::where('session_id', $sessionId)->whereNull('user_id')->with('items')->first();

        if (! $guestCart) {
            return;
        }

        $userCart = EcommerceCart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()->where('ecommerce_product_id', $guestItem->ecommerce_product_id)->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $guestItem->quantity]);
                $guestItem->delete();
            } else {
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        $guestCart->delete();
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->formatUser($request->user()->load(['client', 'investor'])));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:30',
            'country' => 'nullable|string|max:100',
            'state_region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        $user->update($request->only(['name', 'phone']));

        if ($user->client_id) {
            Client::where('id', $user->client_id)->update(
                $request->only(['name', 'phone', 'country', 'state_region', 'city', 'address'])
            );
        }

        return $this->success($this->formatUser($user->fresh(['client', 'investor'])));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return $this->success(null, 'Password changed successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out.');
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            if ($user->client_id) {
                Client::where('id', $user->client_id)->delete();
            }
            $user->delete();
        });

        return $this->success(null, 'Account deleted.');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        // Always return success to prevent user enumeration
        $user = User::where('email', $request->email)
            ->where(fn ($query) => $query->whereNotNull('client_id')->orWhereNotNull('investor_id'))
            ->first();

        if ($user) {
            Password::broker()->sendResetLink(['email' => $request->email]);
        }

        return $this->success(null, 'If an account with that email exists, a reset link has been sent.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update(['password' => Hash::make($password)]);
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, 'Password reset successfully. Please log in.');
        }

        return $this->error(
            $status === Password::INVALID_TOKEN
                ? 'Invalid or expired reset token.'
                : 'Could not reset password. Please try again.',
            422
        );
    }

    public function branches(): JsonResponse
    {
        $branches = Branch::select('id', 'name', 'country', 'state')
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'location' => trim(collect([$b->state, $b->country])->filter()->implode(', ')),
            ]);

        return $this->success($branches);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'account_type' => $user->investor_id ? 'investor' : 'client',
            'client_id' => $user->client_id,
            'client' => $user->client ? [
                'id' => $user->client->id,
                'branch_id' => $user->client->branch_id,
                'name' => $user->client->name,
                'email' => $user->client->email,
                'phone' => $user->client->phone,
                'country' => $user->client->country,
                'city' => $user->client->city,
                'address' => $user->client->address,
            ] : null,
            'investor_id' => $user->investor_id,
            'investor' => $user->investor ? [
                'id' => $user->investor->id,
                'name' => $user->investor->name,
                'status' => $user->investor->status,
            ] : null,
        ];
    }
}
