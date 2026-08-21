<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Http\Resources\InvestmentConversionResource;
use App\Models\Investment;
use App\Models\InvestmentConversion;
use App\Models\InvestmentConversionSource;
use App\Service\InvestmentConversionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorConversionController extends InvestorBaseController
{
    public function index(): JsonResponse
    {
        $conversions = InvestmentConversion::where('investor_id', $this->investorId())
            ->with(['sources.sourceInvestment', 'targetInvestment'])
            ->orderByDesc('created_at')
            ->get();

        return $this->success(InvestmentConversionResource::collection($conversions));
    }

    public function show(string $id): JsonResponse
    {
        $conversion = InvestmentConversion::where('investor_id', $this->investorId())
            ->with(['sources.sourceInvestment', 'targetInvestment'])
            ->findOrFail($id);

        return $this->success(new InvestmentConversionResource($conversion));
    }

    /**
     * Settlement preview. Writes nothing — the investor sees exactly what would be
     * rolled before committing, and the same service call backs the executed figures.
     */
    public function quote(Request $request, InvestmentConversionService $service): JsonResponse
    {
        $data = $request->validate([
            'sources' => 'required|array|min:1',
            'sources.*.investment_id' => 'required|uuid',
            'sources.*.mode' => ['required', 'string', 'in:full,principal_only,partial'],
            'sources.*.amount' => 'required_if:sources.*.mode,partial|nullable|numeric|min:0.01',
            'conversion_date' => 'nullable|date',
        ]);

        $conversionDate = isset($data['conversion_date'])
            ? Carbon::parse($data['conversion_date'])
            : now();

        try {
            $quote = $service->quote($this->investor(), $data['sources'], $conversionDate);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'conversion_date' => $quote['conversion_date']->toDateString(),
            'total_principal_rolled' => $quote['total_principal_rolled'],
            'total_interest_rolled' => $quote['total_interest_rolled'],
            'total_amount' => $quote['total_amount'],
            'total_paid_out' => $quote['total_paid_out'],
            'sources' => collect($quote['sources'])->map(fn (array $source) => [
                'investment_id' => $source['investment']->id,
                'investment_reference' => $source['investment']->reference,
                'capital_type' => $source['investment']->capital_type->value,
                'mode' => $source['mode']->value,
                'mode_label' => $source['mode']->getLabel(),
                'principal_at_conversion' => $source['principal_at_conversion'],
                'interest_at_conversion' => $source['interest_at_conversion'],
                'settlement_value' => $source['settlement_value'],
                'amount_rolled' => $source['amount_rolled'],
                'amount_paid_out' => $source['amount_paid_out'],
                'remaining_balance_after' => $source['remaining_balance_after'],
                'source_fully_closed' => $source['source_fully_closed'],
                'is_contract_due' => $source['investment']->isContractDue(),
            ])->all(),
        ]);
    }

    /**
     * Raise a conversion request for staff to review. Deliberately does not execute:
     * an investor-initiated conversion changes the instrument their capital sits
     * under — and, converting to a loan, removes their right to request a withdrawal
     * before maturity — so it goes through the same review gate withdrawals do.
     */
    public function store(Request $request, InvestmentConversionService $service): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'string', 'in:to_loan,to_investment'],
            'sources' => 'required|array|min:1',
            'sources.*.investment_id' => 'required|uuid',
            'sources.*.mode' => ['required', 'string', 'in:full,principal_only,partial'],
            'sources.*.amount' => 'required_if:sources.*.mode,partial|nullable|numeric|min:0.01',
            'target_contract_term_months' => 'required|integer|min:1|max:600',
            'target_payout_frequency' => [
                'required_if:direction,to_loan',
                'nullable',
                'string',
                'in:monthly,quarterly,semi_annual,annual',
            ],
            'notes' => 'nullable|string',
        ]);

        $direction = InvestmentConversionDirection::from($data['direction']);
        $investor = $this->investor();
        $conversionDate = now();

        // Validate the selection through the same settlement path execute() will use,
        // so an unconvertible tranche is rejected here rather than at approval time.
        try {
            $quote = $service->quote($investor, $data['sources'], $conversionDate);
        } catch (\InvalidArgumentException|\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error(
                $e instanceof \InvalidArgumentException ? $e->getMessage() : 'One of the selected investments could not be found.',
                422
            );
        }

        foreach ($quote['sources'] as $source) {
            if ($source['investment']->capital_type === $direction->targetCapitalType()) {
                return $this->error(
                    "{$source['investment']->reference} is already a {$source['investment']->capital_type->getLabel()} tranche.",
                    422
                );
            }
        }

        $conversion = DB::transaction(function () use ($investor, $direction, $data, $quote, $conversionDate) {
            $conversion = InvestmentConversion::create([
                'investor_id' => $investor->id,
                'direction' => $direction->value,
                'conversion_date' => $conversionDate->toDateString(),
                'status' => InvestmentConversionStatus::pending_approval->value,
                'requested_by_investor' => true,
                'target_contract_term_months' => $data['target_contract_term_months'],
                'target_payout_frequency' => $data['target_payout_frequency'] ?? null,
                'total_principal_rolled' => $quote['total_principal_rolled'],
                'total_interest_rolled' => $quote['total_interest_rolled'],
                'total_amount' => $quote['total_amount'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($quote['sources'] as $source) {
                InvestmentConversionSource::create([
                    'investment_conversion_id' => $conversion->id,
                    'source_investment_id' => $source['investment']->id,
                    'mode' => $source['mode']->value,
                    'principal_at_conversion' => $source['principal_at_conversion'],
                    'interest_at_conversion' => $source['interest_at_conversion'],
                    'amount_rolled' => $source['amount_rolled'],
                    'amount_paid_out' => $source['amount_paid_out'],
                    'remaining_balance_after' => $source['remaining_balance_after'],
                ]);
            }

            return $conversion;
        });

        return $this->success(
            new InvestmentConversionResource($conversion->load(['sources.sourceInvestment', 'targetInvestment'])),
            'Conversion request submitted. Our team will review it and confirm once approved.',
            201
        );
    }

    public function cancel(string $id): JsonResponse
    {
        $conversion = InvestmentConversion::where('investor_id', $this->investorId())->findOrFail($id);

        if ($conversion->status !== InvestmentConversionStatus::pending_approval) {
            return $this->error(
                "This request is {$conversion->status->getLabel()} and can no longer be cancelled.",
                422
            );
        }

        $conversion->update([
            'status' => InvestmentConversionStatus::cancelled->value,
            'notes' => trim(($conversion->notes ?? '')."\nCancelled by the investor on ".now()->toDateTimeString().'.'),
        ]);

        return $this->success(
            new InvestmentConversionResource($conversion->fresh(['sources.sourceInvestment', 'targetInvestment'])),
            'Conversion request cancelled.'
        );
    }

    /**
     * Tranches the investor may currently convert, with the settlement value each
     * would contribute — what the mobile app's selection screen lists.
     */
    public function eligible(Request $request, InvestmentConversionService $service): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'string', 'in:to_loan,to_investment'],
        ]);

        $direction = InvestmentConversionDirection::from($data['direction']);
        $asOf = now();

        $eligible = Investment::where('investor_id', $this->investorId())
            ->excludingConverted()
            ->where('status', 'active')
            ->where('capital_type', '!=', $direction->targetCapitalType()->value)
            ->with('interestPayouts')
            ->orderBy('start_date')
            ->get()
            ->map(function (Investment $investment) use ($service, $asOf) {
                $settlement = $service->settlementValue($investment, $asOf);

                return [
                    'investment_id' => $investment->id,
                    'reference' => $investment->reference,
                    'capital_type' => $investment->capital_type->value,
                    'principal_amount' => (float) $investment->principal_amount,
                    'accrued_interest' => $settlement['interest'],
                    'settlement_value' => round($settlement['principal'] + $settlement['interest'], 2),
                    'maturity_date' => $investment->maturity_date?->toDateString(),
                    'is_contract_due' => $investment->isContractDue(),
                ];
            });

        return $this->success([
            'direction' => $direction->value,
            'target_capital_type' => $direction->targetCapitalType()->value,
            'as_of' => $asOf->toDateString(),
            'modes' => collect(InvestmentConversionSourceMode::cases())
                ->map(fn (InvestmentConversionSourceMode $mode) => [
                    'value' => $mode->value,
                    'label' => $mode->getLabel(),
                ])->all(),
            'partial_minimum' => (float) config('investment.partial_minimum'),
            'minimum_remaining_balance' => (float) config('investment.minimum_remaining_balance'),
            'investments' => $eligible->all(),
        ]);
    }
}
