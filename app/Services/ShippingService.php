<?php

namespace App\Services;

use App\Models\DeliveryAddress;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\Vendor;

class ShippingService
{
    public function feeFor(Vendor $vendor, DeliveryAddress $address, float $vendorSubtotalGhs): float
    {
        $method = $this->resolveMethod($vendor, $address);

        if (! $method) {
            return 0;
        }

        if ($method->free_shipping_threshold_ghs !== null && $vendorSubtotalGhs >= (float) $method->free_shipping_threshold_ghs) {
            return 0;
        }

        return (float) $method->fee_ghs;
    }

    private function resolveMethod(Vendor $vendor, DeliveryAddress $address): ?ShippingMethod
    {
        // Vendor-specific override takes precedence over the marketplace default.
        $vendorMethod = ShippingMethod::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->first();

        if ($vendorMethod) {
            return $vendorMethod;
        }

        $zone = ShippingZone::where('is_active', true)
            ->get()
            ->first(fn (ShippingZone $zone) => in_array($address->region, $zone->regions ?? [], true));

        $query = ShippingMethod::whereNull('vendor_id')->where('is_active', true);

        if ($zone) {
            $query->where('shipping_zone_id', $zone->id);
        } else {
            $query->whereNull('shipping_zone_id');
        }

        return $query->first() ?? ShippingMethod::whereNull('vendor_id')
            ->whereNull('shipping_zone_id')
            ->where('is_active', true)
            ->first();
    }
}
