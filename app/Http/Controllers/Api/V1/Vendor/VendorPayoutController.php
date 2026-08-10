<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Models\VendorPayoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorPayoutController extends VendorBaseController
{
    public function index(Request $request): JsonResponse
    {
        $paginated = VendorPayoutRequest::where('vendor_id', $this->vendorId())
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated);
    }

    public function store(Request $request): JsonResponse
    {
        $wallet = $this->vendor()->wallet()->firstOrCreate([]);

        $data = $request->validate([
            'amount_ghs' => 'required|numeric|min:1',
            'payout_method' => 'required|string|max:100',
            'payout_details' => 'required|string|max:1000',
        ]);

        abort_if($data['amount_ghs'] > (float) $wallet->balance_ghs, 422, 'Requested amount exceeds your available balance.');

        $payout = VendorPayoutRequest::create(array_merge($data, [
            'vendor_id' => $this->vendorId(),
            'status' => 'pending',
            'requested_at' => now(),
        ]));

        return $this->success($payout, 'Payout request submitted.', 201);
    }
}
