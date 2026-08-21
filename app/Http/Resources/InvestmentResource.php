<?php

namespace App\Http\Resources;

use App\Enums\InvestmentCapitalType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isLoan = $this->capital_type === InvestmentCapitalType::loan;

        // A loan's current_balance never moves — it doesn't compound, by design —
        // so its interest lives entirely in interestPayouts, not in
        // (current_balance - principal_amount). due/processing/paid only:
        // skipped/reversed rows don't represent money owed or earned.
        // Collection::whereIn() loosely compares against the raw strings, which
        // never matches the enum-cast `status` — filter on ->value instead.
        $payouts = $isLoan
            ? $this->interestPayouts->filter(fn ($p) => in_array($p->status->value, ['due', 'processing', 'paid'], true))
            : collect();
        $unpaidPayouts = $isLoan
            ? $payouts->filter(fn ($p) => in_array($p->status->value, ['due', 'processing'], true))
            : collect();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'principal_amount' => (float) $this->principal_amount,
            'principal_amount_ghs' => (float) $this->principal_amount_ghs,
            'current_balance' => (float) $this->current_balance,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'capital_type' => $this->capital_type->value,
            'capital_type_label' => $this->capital_type->getLabel(),
            'agreement_status' => $this->agreement_status->value,
            'agreement_status_label' => $this->agreement_status->getLabel(),
            'has_signed_agreement' => filled($this->signed_agreement_path),
            // The name affixed to the agreement as the investor's signature. Snapshotted
            // when the document is first generated, so it never drifts from the issued copy.
            'signature_name' => $this->agreement_signature_name,
            'deposit_gateway' => $this->deposit_gateway,
            'start_date' => $this->start_date?->toDateString(),
            'contract_term_months' => $this->contract_term_months,
            'maturity_date' => $this->maturity_date?->toDateString(),
            'is_contract_due' => $this->isContractDue(),
            'last_interest_posted_year' => $this->last_interest_posted_year,
            'interest_accrued_total' => $isLoan ? round((float) $payouts->sum('amount'), 2) : null,
            'interest_paid_total' => $isLoan ? round((float) $payouts->sum('amount_paid'), 2) : null,
            'interest_owed' => $isLoan
                ? round((float) $unpaidPayouts->sum(fn ($p) => (float) $p->amount - (float) $p->amount_paid), 2)
                : null,
            'is_converted' => $this->isConverted(),
            // Set when this tranche was issued as the successor of settled tranche(s)
            // rather than funded with new money.
            'converted_from_reference' => $this->whenLoaded(
                'convertedFromConversion',
                fn () => $this->convertedFromConversion?->reference
            ),
            // Set on a settled tranche, pointing at the tranche that now holds its capital.
            'converted_to_reference' => $this->whenLoaded(
                'conversionSources',
                fn () => $this->conversionSources->last()?->conversion?->targetInvestment?->reference
            ),
            'created_at' => $this->created_at,
        ];
    }
}
