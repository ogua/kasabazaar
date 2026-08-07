<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Models\VendorTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorEarningsController extends VendorBaseController
{
    public function summary(Request $request): JsonResponse
    {
        $wallet = $this->vendor()->wallet()->firstOrCreate([]);

        return $this->success([
            'balance_ghs' => $wallet->balance_ghs,
            'pending_balance_ghs' => $wallet->pending_balance_ghs,
            'lifetime_earnings_ghs' => $wallet->lifetime_earnings_ghs,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = VendorTransaction::where('vendor_id', $this->vendorId())->with('order:id,order_number');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated);
    }
}
