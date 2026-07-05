<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Investor;

abstract class InvestorBaseController extends BaseApiController
{
    protected function investorId(): string
    {
        return auth()->user()->investor_id;
    }

    protected function investor(): Investor
    {
        return Investor::findOrFail($this->investorId());
    }
}
