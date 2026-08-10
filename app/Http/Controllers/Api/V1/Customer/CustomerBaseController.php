<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Client;

abstract class CustomerBaseController extends BaseApiController
{
    protected function clientId(): string
    {
        return auth()->user()->client_id;
    }

    protected function client(): Client
    {
        return Client::findOrFail($this->clientId());
    }

    protected function customerBranchId(): string
    {
        $user = auth('sanctum')->user();

        return $user?->branch_id ?? config('ecommerce.public_branch_id');
    }
}
