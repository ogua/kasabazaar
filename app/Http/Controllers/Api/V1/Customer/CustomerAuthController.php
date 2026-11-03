<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\UserStatus;
use App\Http\Requests\RegisterCustomerRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\CustomerSignupVerification;
use App\Models\DeliveryAddress;
use App\Models\DeviceToken;
use App\Models\EcommerceCart;
use App\Models\User;
use App\Service\SystemSetting;
use App\Services\CustomerSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CustomerAuthController extends CustomerBaseController
{
    /**
     * Sign up a client. When the email or phone matches a client staff already
     * created, no account is made yet: a code goes to that client's own phone or
     * email and the account is created, linked to the existing client, by verifyOtp().
     */
    public function register(RegisterCustomerRequest $request, CustomerSignupService $signups): JsonResponse
    {
        $existingClient = $signups->findClaimableClient($request->email, $request->phone);

        if ($existingClient) {
            $verification = $signups->startVerification($existingClient, $request->validated());

            return $this->success($this->verificationPayload($verification), 'We found your shipping record. Enter the code we sent to confirm it is you.', 202);
        }

        $user = DB::transaction(function () use ($request) {
            $branchId = $request->branch_id ?? CustomerSignupService::defaultBranchId();

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

        if ($request->boolean('whatsapp_opt_in')) {
            $user->setWhatsappConsent(true);
            $user->client?->setWhatsappConsent(true);
        }

        $this->mergeGuestCart($user, $request->header('X-Guest-Session-Id'));

        $token = $user->createToken('customer-app')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $this->formatUser($user->load('client')),
        ], 'Registration successful.', 201);
    }

    public function verifyOtp(Request $request, CustomerSignupService $signups): JsonResponse
    {
        $request->validate([
            'verification_id' => 'required|uuid',
            'code' => 'required|digits:6',
        ]);

        $verification = CustomerSignupVerification::findOrFail($request->verification_id);

        try {
            $user = $signups->verify($verification, $request->code);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $this->mergeGuestCart($user, $request->header('X-Guest-Session-Id'));

        return $this->success([
            'token' => $user->createToken('customer-app')->plainTextToken,
            'user' => $this->formatUser($user->load('client')),
        ], 'Your account is ready.', 201);
    }

    public function resendOtp(Request $request, CustomerSignupService $signups): JsonResponse
    {
        $request->validate(['verification_id' => 'required|uuid']);

        $verification = CustomerSignupVerification::findOrFail($request->verification_id);

        if (! $signups->resend($verification)) {
            return $this->error('We cannot send another code. Please start the signup again.', 422);
        }

        return $this->success($this->verificationPayload($verification->fresh()), 'A new code is on its way.');
    }

    /**
     * @return array{requires_verification: bool, verification_id: string, channel: string, masked_destination: string, expires_at: ?string}
     */
    private function verificationPayload(CustomerSignupVerification $verification): array
    {
        return [
            'requires_verification' => true,
            'verification_id' => $verification->id,
            'channel' => $verification->channel,
            'masked_destination' => $verification->maskedDestination(),
            'expires_at' => $verification->expires_at?->toISOString(),
        ];
    }

    public function config(): JsonResponse
    {
        return $this->success([
            'minimum_app_version' => SystemSetting::get('minimum_customer_app_version', '1.0.0'),
            // The tracking page's "Call us" lines and support address (config/website.php),
            // so the app never carries its own copy of the published contact details.
            'support' => [
                'email' => config('website.contact.email_support'),
                'phones' => [
                    ['label' => config('website.contact.phone_gh_1'), 'tel' => config('website.contact.phone_gh_1_tel'), 'place' => 'Ghana'],
                    ['label' => config('website.contact.phone_gh_2'), 'tel' => config('website.contact.phone_gh_2_tel'), 'place' => 'Ghana'],
                    ['label' => config('website.contact.phone_us_1'), 'tel' => config('website.contact.phone_us_1_tel'), 'place' => 'USA'],
                ],
            ],
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
            'whatsapp_opt_in' => 'sometimes|boolean',
        ]);

        $user->update($request->only(['name', 'phone']));

        if ($user->client_id) {
            Client::where('id', $user->client_id)->update(
                $request->only(['name', 'phone', 'country', 'state_region', 'city', 'address'])
            );
        }

        if ($request->has('whatsapp_opt_in')) {
            $user->setWhatsappConsent($request->boolean('whatsapp_opt_in'));
            $user->client?->setWhatsappConsent($request->boolean('whatsapp_opt_in'));
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

    /**
     * Self-service account deletion (App Store / Play requirement). The client
     * row owns shipments, payments, invoices and expenses whose foreign keys
     * cascade or orphan on delete, so instead of a hard delete the personal data
     * on both the user and the client is stripped and the login deactivated,
     * keeping the financial history intact (as AuthController::deleteAccount does
     * for staff).
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            DeviceToken::where('user_id', $user->id)->delete();
            DeliveryAddress::where('user_id', $user->id)->delete();
            EcommerceCart::where('user_id', $user->id)->delete();

            if ($user->client_id) {
                Client::whereKey($user->client_id)->update([
                    'name' => 'Deleted Client',
                    'email' => null,
                    'phone' => null,
                    'address' => null,
                    'id_type' => null,
                    'id_number' => null,
                    'whatsapp_opt_in_at' => null,
                    'whatsapp_opted_out_at' => now(),
                ]);
            }

            $user->forceFill([
                'name' => 'Deleted User',
                'email' => 'deleted-'.$user->id.'@deleted.invalid',
                'phone' => null,
                'avatar' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'status' => UserStatus::Inactive,
            ])->save();
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
            'whatsapp_opt_in' => $user->hasWhatsappConsent(),
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
