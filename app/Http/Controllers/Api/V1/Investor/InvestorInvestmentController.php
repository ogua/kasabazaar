<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Http\Resources\InvestmentResource;
use App\Http\Resources\InvestmentTransactionResource;
use App\Models\Investment;
use App\Service\InvestmentInterestService;
use Illuminate\Http\JsonResponse;

class InvestorInvestmentController extends InvestorBaseController
{
    public function index(): JsonResponse
    {
        $investments = Investment::where('investor_id', $this->investorId())
            ->orderByDesc('start_date')
            ->get();

        return $this->success(InvestmentResource::collection($investments));
    }

    public function show(string $id): JsonResponse
    {
        $investment = Investment::where('investor_id', $this->investorId())->findOrFail($id);

        $valuation = app(InvestmentInterestService::class)->valuationAsOf($investment, now());

        return $this->success([
            'investment' => new InvestmentResource($investment),
            'valuation' => [
                'principal' => $valuation['principal'],
                'interest_earned_total' => $valuation['interest_earned_total'],
                'compounded_balance' => $valuation['compounded_balance'],
                'as_of' => $valuation['as_of']->toDateString(),
            ],
        ]);
    }

    public function transactions(string $id): JsonResponse
    {
        $investment = Investment::where('investor_id', $this->investorId())->findOrFail($id);

        $transactions = $investment->transactions()
            ->where('posted', true)
            ->orderByDesc('date')
            ->get();

        return $this->success(InvestmentTransactionResource::collection($transactions));
    }
}
