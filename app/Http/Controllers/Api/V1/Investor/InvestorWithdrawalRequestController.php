<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Http\Resources\InvestmentWithdrawalRequestResource;
use App\Models\Investment;
use App\Models\InvestmentWithdrawalRequest;
use App\Services\InvestmentWithdrawalApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestorWithdrawalRequestController extends InvestorBaseController
{
    public function index(): JsonResponse
    {
        $requests = InvestmentWithdrawalRequest::where('investor_id', $this->investorId())
            ->orderByDesc('created_at')
            ->get();

        return $this->success(InvestmentWithdrawalRequestResource::collection($requests));
    }

    public function show(string $id): JsonResponse
    {
        $request = InvestmentWithdrawalRequest::where('investor_id', $this->investorId())->findOrFail($id);

        return $this->success(new InvestmentWithdrawalRequestResource($request));
    }

    public function store(Request $request, InvestmentWithdrawalApprovalService $service): JsonResponse
    {
        $data = $request->validate([
            'investment_id' => 'required|uuid',
            'is_full_withdrawal' => 'required|boolean',
            'requested_amount' => 'required_if:is_full_withdrawal,false|nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $investment = Investment::where('investor_id', $this->investorId())
            ->where('status', 'active')
            ->findOrFail($data['investment_id']);

        try {
            $withdrawalRequest = $service->submit($investment, $data);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new InvestmentWithdrawalRequestResource($withdrawalRequest), 'Withdrawal request submitted.', 201);
    }
}
