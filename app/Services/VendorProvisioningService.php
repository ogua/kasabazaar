<?php

namespace App\Services;

use App\Enums\EcommerceVendorApplicationStatus;
use App\Enums\UserStatus;
use App\Enums\VendorStatus;
use App\Models\EcommerceVendorApplication;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Notifications\Ecommerce\VendorAccountProvisioned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class VendorProvisioningService
{
    /**
     * Approves a vendor application and provisions the account it unlocks:
     * a User (role=vendor), the Vendor record itself, and an empty wallet.
     * The vendor never sees a plaintext password — they set one via Laravel's
     * standard password-reset email, same as the customer forgot-password flow.
     */
    public function approve(EcommerceVendorApplication $application, ?string $notes, ?string $approvedById): Vendor
    {
        if (User::where('email', $application->email)->exists()) {
            throw new \RuntimeException("A user with email {$application->email} already exists.");
        }

        return DB::transaction(function () use ($application, $notes, $approvedById) {
            $user = User::create([
                'name' => $application->contact_name,
                'email' => $application->email,
                'phone' => $application->phone,
                'password' => Hash::make(Str::random(40)),
                'role' => 'vendor',
                'status' => UserStatus::Active->value,
            ]);

            $vendor = Vendor::create([
                'user_id' => $user->id,
                'business_name' => $application->business_name,
                'support_email' => $application->email,
                'phone' => $application->phone,
                'commission_rate' => config('ecommerce.default_vendor_commission_rate', 10),
                'status' => VendorStatus::Active->value,
                'approved_at' => now(),
                'approved_by' => $approvedById,
            ]);

            $user->update(['vendor_id' => $vendor->id]);

            VendorWallet::create(['vendor_id' => $vendor->id]);

            $application->update([
                'status' => EcommerceVendorApplicationStatus::Approved->value,
                'review_notes' => $notes,
                'reviewed_by' => $approvedById,
                'reviewed_at' => now(),
            ]);

            Password::broker()->sendResetLink(['email' => $user->email]);

            $user->notify(new VendorAccountProvisioned($vendor));

            return $vendor;
        });
    }
}
