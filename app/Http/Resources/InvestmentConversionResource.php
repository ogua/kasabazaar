<?php

namespace App\Http\Resources;

use App\Models\InvestmentConversionSource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'direction' => $this->direction->value,
            'direction_label' => $this->direction->getLabel(),
            'target_capital_type' => $this->direction->targetCapitalType()->value,
            'conversion_date' => $this->conversion_date?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'requested_by_investor' => (bool) $this->requested_by_investor,

            'total_principal_rolled' => (float) $this->total_principal_rolled,
            'total_interest_rolled' => (float) $this->total_interest_rolled,
            'total_amount' => (float) $this->total_amount,

            'target_contract_term_months' => $this->target_contract_term_months,
            'target_payout_frequency' => $this->target_payout_frequency?->value,
            'target_payout_frequency_label' => $this->target_payout_frequency?->getLabel(),
            'target_annual_rate' => $this->target_annual_rate !== null ? (float) $this->target_annual_rate : null,

            'target_investment_id' => $this->target_investment_id,
            'target_investment_reference' => $this->whenLoaded('targetInvestment', fn () => $this->targetInvestment?->reference),

            'sources' => $this->whenLoaded('sources', fn () => $this->sources->map(fn (InvestmentConversionSource $source) => [
                'id' => $source->id,
                'investment_id' => $source->source_investment_id,
                'investment_reference' => $source->sourceInvestment?->reference,
                'mode' => $source->mode->value,
                'mode_label' => $source->mode->getLabel(),
                'principal_at_conversion' => (float) $source->principal_at_conversion,
                'interest_at_conversion' => (float) $source->interest_at_conversion,
                'amount_rolled' => (float) $source->amount_rolled,
                'amount_paid_out' => (float) $source->amount_paid_out,
                'remaining_balance_after' => (float) $source->remaining_balance_after,
                'source_fully_closed' => (bool) $source->source_fully_closed,
            ])->all()),

            'rejection_reason' => $this->rejection_reason,
            'executed_at' => $this->executed_at,
            'created_at' => $this->created_at,
        ];
    }
}
