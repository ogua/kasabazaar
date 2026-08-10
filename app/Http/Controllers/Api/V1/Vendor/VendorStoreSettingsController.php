<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorStoreSettingsController extends VendorBaseController
{
    public function show(Request $request): JsonResponse
    {
        return $this->success($this->vendor());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'support_email' => 'nullable|email',
            'payout_method' => 'nullable|string|max:100',
            'payout_details' => 'nullable|string|max:1000',
        ]);

        $vendor = $this->vendor();
        $vendor->update($data);

        return $this->success($vendor->fresh(), 'Store settings updated.');
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate(['logo' => 'required|image|max:2048']);

        $vendor = $this->vendor();

        if ($vendor->logo_path) {
            Storage::disk('public')->delete($vendor->logo_path);
        }

        $path = $request->file('logo')->store("vendor-logos/{$vendor->id}", 'public');
        $vendor->update(['logo_path' => $path]);

        return $this->success(['logo_url' => asset('storage/'.$path)], 'Logo uploaded.');
    }

    public function uploadBanner(Request $request): JsonResponse
    {
        $request->validate(['banner' => 'required|image|max:4096']);

        $vendor = $this->vendor();

        if ($vendor->banner_path) {
            Storage::disk('public')->delete($vendor->banner_path);
        }

        $path = $request->file('banner')->store("vendor-banners/{$vendor->id}", 'public');
        $vendor->update(['banner_path' => $path]);

        return $this->success(['banner_url' => asset('storage/'.$path)], 'Banner uploaded.');
    }
}
