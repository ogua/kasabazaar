<?php

namespace App\Http\Resources;

use App\Enums\InvestmentCapitalType;
use App\Service\InvestmentRateResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
{
    /**
     * The annual rate applying this year and where it comes from (a per-tranche
     * override, the investor's default, the company rate or the standard rate).
     *
     * @return array{annual_rate: ?float, rate_source: ?string}
     */
    private function currentRate(): array
    {
        try {
            $resolved = app(InvestmentRateResolver::class)->resolve($this->resource, (int) now()->year);

            return ['annual_rate' => $resolved['rate'], 'rate_source' => $resolved['source']];
        } catch (\Throwable) {
            return ['annual_rate' => null, 'rate_source' => null];
        }
    }

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
            // Interest is posted through this date (Dec 31 catch-up, Actual/365).
            'last_interest_posted_through' => $this->last_interest_posted_through?->toDateString(),
            'payout_frequency' => $this->payout_frequency?->value,
            'payout_frequency_label' => $this->payout_frequency?->getLabel(),
            'next_payout_due_date' => $isLoan ? $this->next_payout_due_date?->toDateString() : null,
            ...$this->currentRate(),
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
