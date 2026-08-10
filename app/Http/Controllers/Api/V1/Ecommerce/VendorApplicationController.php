<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\EcommerceVendorApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VendorApplicationController extends BaseApiController
{
    /**
     * Public endpoint — prospective vendors apply with KYC documents for staff review
     * in Filament (EcommerceVendorApplicationResource). No auth required to apply.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'product_category' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000',
            'business_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'ghana_card_front' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'ghana_card_back' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'proof_of_address' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $applicationId = (string) Str::uuid();
        $directory = "vendor-applications/{$applicationId}";

        $paths = [
            'business_certificate_path' => $request->file('business_certificate')->store($directory, 'vendor_documents'),
            'ghana_card_front_path' => $request->file('ghana_card_front')->store($directory, 'vendor_documents'),
            'ghana_card_back_path' => $request->file('ghana_card_back')->store($directory, 'vendor_documents'),
        ];

        if ($request->hasFile('proof_of_address')) {
            $paths['proof_of_address_path'] = $request->file('proof_of_address')->store($directory, 'vendor_documents');
        }

        $application = EcommerceVendorApplication::create([
            'id' => $applicationId,
            'business_name' => $data['business_name'],
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'product_category' => $data['product_category'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'pending',
            ...$paths,
        ]);

        return $this->success(
            ['id' => $application->id, 'status' => $application->status->value],
            'Application submitted. Our team will review it and reach out by email.',
            201
        );
    }
}
