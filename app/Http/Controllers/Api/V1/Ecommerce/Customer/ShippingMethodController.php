<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Models\DeliveryAddress;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingMethodController extends CustomerBaseController
{
    /**
     * Lists applicable shipping methods for a delivery address so the checkout
     * UI can show shipping cost/ETA before placing the order. The authoritative
     * fee calculation still happens server-side in ShippingService at checkout.
     */
    public function forAddress(Request $request): JsonResponse
    {
        $region = $request->input('region');

        if ($request->filled('address_id')) {
            $address = DeliveryAddress::where('user_id', auth()->id())->findOrFail($request->address_id);
            $region = $address->region;
        }

        abort_unless($region, 422, 'Provide either address_id or region.');

        $zone = ShippingZone::where('is_active', true)
            ->get()
            ->first(fn (ShippingZone $zone) => in_array($region, $zone->regions ?? [], true));

        $methods = ShippingMethod::whereNull('vendor_id')
            ->where('is_active', true)
            ->where(fn ($q) => $zone ? $q->where('shipping_zone_id', $zone->id) : $q->whereNull('shipping_zone_id'))
            ->get(['id', 'name', 'fee_ghs', 'min_days', 'max_days', 'free_shipping_threshold_ghs']);

        return $this->success($methods);
    }
}
