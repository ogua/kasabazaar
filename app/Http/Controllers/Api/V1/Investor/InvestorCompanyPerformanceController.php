<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Service\InvestorCompanyPerformanceService;
use Illuminate\Http\JsonResponse;

class InvestorCompanyPerformanceController extends InvestorBaseController
{
    public function index(InvestorCompanyPerformanceService $service): JsonResponse
    {
        return $this->success($service->monthlyRevenueTrend());
    }
}
