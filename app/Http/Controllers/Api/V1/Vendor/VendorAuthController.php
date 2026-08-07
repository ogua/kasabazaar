<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class VendorAuthController extends VendorBaseController
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->whereNotNull('vendor_id')->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if ($user->status !== UserStatus::Active) {
            return $this->error('Your account has been deactivated.', 401);
        }

        if ($user->vendor?->status?->value !== 'active') {
            return $this->error('Your vendor account is currently inactive. Please contact support.', 403);
        }

        $token = $user->createToken('vendor-app')->plainTextToken;

        return $this->success(array_merge(
            ['token' => $token],
            $this->formatVendor($user)
        ), 'Login successful.');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->whereNotNull('vendor_id')->first();

        if ($user) {
            Password::broker()->sendResetLink(['email' => $request->email]);
        }

        return $this->success(null, 'If a vendor account with that email exists, a reset link has been sent.');
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
            return $this->success(null, 'Password set successfully. Please log in.');
        }

        return $this->error(
            $status === Password::INVALID_TOKEN
                ? 'Invalid or expired reset token.'
                : 'Could not reset password. Please try again.',
            422
        );
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->formatVendor($request->user()));
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

    private function formatVendor(User $user): array
    {
        $vendor = $user->vendor;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'vendor' => $vendor ? [
                'id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'slug' => $vendor->slug,
                'logo_path' => $vendor->logo_path,
                'commission_rate' => $vendor->commission_rate,
                'status' => $vendor->status?->value,
            ] : null,
        ];
    }
}
