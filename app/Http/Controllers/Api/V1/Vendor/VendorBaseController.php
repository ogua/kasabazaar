<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Vendor;

abstract class VendorBaseController extends BaseApiController
{
    protected function vendor(): Vendor
    {
        return auth()->user()->vendor;
    }

    protected function vendorId(): string
    {
        return auth()->user()->vendor_id;
    }
}
