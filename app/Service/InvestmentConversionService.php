<?php

namespace App\Service;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentConversionDirection;
use App\Enums\InvestmentConversionSourceMode;
use App\Enums\InvestmentConversionStatus;
use App\Enums\InvestmentInterestPayoutStatus;
use App\Enums\InvestmentStatus;
use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentConversion;
use App\Models\InvestmentConversionSource;
use App\Models\InvestmentRateOverride;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\User;
use App\Notifications\InvestmentConverted;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Moves investor capital between the two instruments the company issues, by
 * novation: the source tranche(s) are settled and the same capital is re-issued as
 * a new tranche of the other capital_type.
 *
 * capital_type is deliberately never mutated in place. The interest engine branches
 * on it everywhere — InvestmentInterestService compounds an investment into
 * current_balance, InvestmentInterestPayoutService pays a loan out in cash off a
 * flat principal — so flipping it on a row that already has posted transactions
 * would silently invalidate that row's interest history, its issued agreement
 * letter and its statement lines all at once.
 */
class InvestmentConversionService
{
    public function __construct(
        protected InvestmentInterestService $interestService,
        protected InvestmentPaymentService $paymentService,
    ) {}

    /**
     * Read-only settlement preview. Called by the admin modal, the investor mobile
     * screen and execute() itself, so the figure quoted is always the figure booked.
     *
     * @param  array<int, array{investment_id: string, mode: string, amount?: float|null}>  $sourceSelections
     * @return array{conversion_date: Carbon, sources: array<int, array<string, mixed>>, total_principal_rolled: float, total_interest_rolled: float, total_amount: float, total_paid_out: float}
     */
    public function quote(Investor $investor, array $sourceSelections, Carbon $conversionDate): array
    {
        $sources = [];

        foreach ($sourceSelections as $selection) {
            $investment = Investment::where('investor_id', $investor->id)
                ->findOrFail($selection['investment_id']);

            $mode = $selection['mode'] instanceof InvestmentConversionSourceMode
                ? $selection['mode']
                : InvestmentConversionSourceMode::from($selection['mode']);

            $settlement = $this->settlementValue($investment, $conversionDate);
            $principal = $settlement['principal'];
            $interest = $settlement['interest'];
            $total = round($principal + $interest, 2);

            [$amountRolled, $amountPaidOut] = match ($mode) {
                InvestmentConversionSourceMode::full => [$total, 0.0],
                InvestmentConversionSourceMode::principal_only => [$principal, $interest],
                InvestmentConversionSourceMode::partial => [round((float) ($selection['amount'] ?? 0), 2), 0.0],
            };

            if ($mode === InvestmentConversionSourceMode::partial && ($amountRolled <= 0 || $amountRolled > $total)) {
                throw new \InvalidArgumentException(sprintf(
                    'Partial conversion amount for %s must be between $0.01 and $%s.',
                    $investment->reference,
                    number_format($total, 2)
                ));
            }

            $sources[] = [
                'investment' => $investment,
                'mode' => $mode,
                'principal_at_conversion' => $principal,
                'interest_at_conversion' => $interest,
                'settlement_value' => $total,
                'amount_rolled' => $amountRolled,
                'amount_paid_out' => $amountPaidOut,
                'remaining_balance_after' => round($total - $amountRolled - $amountPaidOut, 2),
                'source_fully_closed' => $mode->closesSource(),
            ];
        }

        $collection = collect($sources);

        return [
            'conversion_date' => $conversionDate,
            'sources' => $sources,
            'total_principal_rolled' => round($collection->sum(fn (array $source) => min($source['amount_rolled'], $source['principal_at_conversion'])), 2),
            'total_interest_rolled' => round($collection->sum(fn (array $source) => max($source['amount_rolled'] - $source['principal_at_conversion'], 0)), 2),
            'total_amount' => round($collection->sum('amount_rolled'), 2),
            'total_paid_out' => round($collection->sum('amount_paid_out'), 2),
        ];
    }

    /**
     * What a tranche is worth if settled on $asOf, split into the part that is
     * principal and the part that is earned interest — the split matters because
     * principal_only mode rolls one and pays the other out in cash.
     *
     * @return array{principal: float, interest: float}
     */
    public function settlementValue(Investment $investment, Carbon $asOf): array
    {
        if ($investment->capital_type === InvestmentCapitalType::loan) {
            // A loan's balance never compounds, so its earned-but-unsettled value is
            // the unpaid portion of its generated payouts. Interest already paid out
            // is cash that left the company and is not part of the settlement.
            $unpaid = $investment->interestPayouts()
                ->whereIn('status', [
                    InvestmentInterestPayoutStatus::due->value,
                    InvestmentInterestPayoutStatus::processing->value,
                ])
                ->get()
                ->sum(fn ($payout) => (float) $payout->amount - (float) $payout->amount_paid);

            return [
                'principal' => round((float) $investment->principal_amount, 2),
                'interest' => round((float) $unpaid, 2),
            ];
        }

        $valuation = $this->interestService->valuationAsOf($investment, $asOf);

        return [
            'principal' => round((float) $investment->principal_amount, 2),
            'interest' => round($valuation['compounded_balance'] - (float) $investment->principal_amount, 2),
        ];
    }

    /**
     * Execute an approved conversion: settle every source, issue the successor
     * tranche and open its ledger.
     */
    public function execute(InvestmentConversion $conversion, User $staff): Investment
    {
        if ($conversion->status === InvestmentConversionStatus::executed) {
            throw new \RuntimeException("Conversion {$conversion->reference} has already been executed.");
        }

        if (! $conversion->status->isExecutable()) {
            throw new \RuntimeException(sprintf(
                'Conversion %s is %s and cannot be executed. Approve it first.',
                $conversion->reference,
                $conversion->status->getLabel()
            ));
        }

        return DB::transaction(function () use ($conversion, $staff) {
            $conversion->load('sources.sourceInvestment');
            $conversionDate = Carbon::parse($conversion->conversion_date);

            $this->assertTargetTermsPresent($conversion);

            $totalRolled = 0.0;
            $totalPrincipalRolled = 0.0;
            $totalInterestRolled = 0.0;

            foreach ($conversion->sources as $source) {
                $investment = $source->sourceInvestment;

                $this->assertSourceEligible($conversion, $investment, $source);

                // True up interest to the conversion date before anything moves, so the
                // investor is credited for every day they actually held the capital.
                // postStubPeriodInterest() also advances both posting cursors, which
                // stops a later year-end run from re-posting the same period.
                if ($investment->capital_type === InvestmentCapitalType::investment) {
                    $this->interestService->postStubPeriodInterest($investment, $conversionDate, $staff);
                    $investment->refresh();
                }

                $settled = $this->settleSource($conversion, $source, $investment, $staff);

                $totalRolled += $settled['amount_rolled'];
                $totalPrincipalRolled += $settled['principal_rolled'];
                $totalInterestRolled += $settled['interest_rolled'];
            }

            $target = $this->issueTargetTranche($conversion, round($totalRolled, 2), $staff);

            $conversion->update([
                'status' => InvestmentConversionStatus::executed->value,
                'target_investment_id' => $target->id,
                'total_amount' => round($totalRolled, 2),
                'total_principal_rolled' => round($totalPrincipalRolled, 2),
                'total_interest_rolled' => round($totalInterestRolled, 2),
                'executed_by' => $staff->id,
                'executed_at' => now(),
            ]);

            InvestorNotifier::notify(
                $conversion->investor_id,
                new InvestmentConverted($conversion->fresh(['sources.sourceInvestment', 'targetInvestment']))
            );

            return $target->fresh();
        });
    }

    /**
     * Settle one source tranche: pay out any interest the investor elected to take
     * in cash, post the conversion_out row, and either close the tranche or leave it
     * open with the remainder.
     *
     * @return array{amount_rolled: float, principal_rolled: float, interest_rolled: float}
     */
    private function settleSource(
        InvestmentConversion $conversion,
        InvestmentConversionSource $source,
        Investment $investment,
        User $staff,
    ): array {
        $conversionDate = Carbon::parse($conversion->conversion_date);
        $settlement = $this->settlementValue($investment, $conversionDate);
        $principal = $settlement['principal'];
        $interest = $settlement['interest'];
        $total = round($principal + $interest, 2);

        [$amountRolled, $amountPaidOut] = match ($source->mode) {
            InvestmentConversionSourceMode::full => [$total, 0.0],
            InvestmentConversionSourceMode::principal_only => [$principal, $interest],
            InvestmentConversionSourceMode::partial => [round((float) $source->amount_rolled, 2), 0.0],
        };

        $interestRolled = max(round($amountRolled - $principal, 2), 0.0);

        // A loan's unpaid payouts are the interest half of its settlement. Once that
        // interest is rolled it stops being a cash liability and becomes principal, so
        // those rows move to 'converted' — not 'skipped' (which would mean never owed)
        // or 'reversed' (which assumes cash left the company and was clawed back).
        if ($investment->capital_type === InvestmentCapitalType::loan && $interestRolled > 0) {
            $this->markPayoutsConverted($investment, $conversion);
        }

        // Interest taken as cash rather than rolled is an ordinary withdrawal against
        // the tranche — real money leaving the company, recorded as such.
        if ($amountPaidOut > 0) {
            InvestmentTransaction::create([
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'date' => $conversionDate,
                'type' => InvestmentTransactionType::withdrawal->value,
                'op_balance' => $investment->current_balance,
                'debit' => $amountPaidOut,
                'posted' => true,
                'posted_by' => $staff->id,
                'posted_at' => now(),
                'reference_id' => $conversion->id,
                'description' => "Accrued interest paid out on conversion {$conversion->reference} (principal only rolled).",
            ]);

            $investment->update([
                'current_balance' => round((float) $investment->current_balance - $amountPaidOut, 2),
            ]);
            $investment->refresh();
        }

        // InvestmentTransaction::booted() computes cl_balance = op - dr + cr but does
        // not chain op_balance off the previous row, so the caller sets it — the same
        // convention InvestmentWithdrawalApprovalService::recordPayment() follows.
        InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => $conversionDate,
            'type' => InvestmentTransactionType::conversion_out->value,
            'op_balance' => $investment->current_balance,
            'debit' => $amountRolled,
            'posted' => true,
            'posted_by' => $staff->id,
            'posted_at' => now(),
            'reference_id' => $conversion->id,
            'description' => sprintf(
                'Capital converted out under %s to a %s tranche (%s).',
                $conversion->reference,
                $conversion->direction->targetCapitalType()->getLabel(),
                $source->mode->getLabel()
            ),
        ]);

        $remaining = round((float) $investment->current_balance - $amountRolled, 2);
        $closesSource = $source->mode->closesSource() || $remaining <= 0.0;

        $investment->update([
            'current_balance' => $closesSource ? 0 : $remaining,
            'status' => $closesSource ? InvestmentStatus::converted->value : $investment->status->value,
        ]);

        $source->update([
            'principal_at_conversion' => $principal,
            'interest_at_conversion' => $interest,
            'amount_rolled' => $amountRolled,
            'amount_paid_out' => $amountPaidOut,
            'remaining_balance_after' => $closesSource ? 0 : $remaining,
            'source_fully_closed' => $closesSource,
        ]);

        return [
            'amount_rolled' => $amountRolled,
            'principal_rolled' => min($amountRolled, $principal),
            'interest_rolled' => $interestRolled,
        ];
    }

    private function markPayoutsConverted(Investment $investment, InvestmentConversion $conversion): void
    {
        $investment->interestPayouts()
            ->whereIn('status', [
                InvestmentInterestPayoutStatus::due->value,
                InvestmentInterestPayoutStatus::processing->value,
            ])
            ->get()
            ->each(function ($payout) use ($conversion) {
                $payout->update([
                    'status' => InvestmentInterestPayoutStatus::converted->value,
                    'notes' => trim(($payout->notes ?? '')."\nRolled into principal by conversion {$conversion->reference} on ".now()->toDateTimeString().'.'),
                ]);
            });
    }

    /**
     * Create and activate the successor tranche under the target instrument's terms.
     */
    private function issueTargetTranche(InvestmentConversion $conversion, float $amount, User $staff): Investment
    {
        $targetType = $conversion->direction->targetCapitalType();

        $target = Investment::create([
            'investor_id' => $conversion->investor_id,
            'principal_amount' => $amount,
            'start_date' => $conversion->conversion_date,
            'contract_term_months' => $conversion->target_contract_term_months,
            'capital_type' => $targetType->value,
            'payout_frequency' => $targetType === InvestmentCapitalType::loan
                ? $conversion->target_payout_frequency?->value
                : null,
            'status' => InvestmentStatus::pending_payment->value,
            'converted_from_conversion_id' => $conversion->id,
            'recorded_by' => $staff->id,
            'notes' => 'Issued by conversion '.$conversion->reference.' from '.
                $conversion->sources->map(fn (InvestmentConversionSource $source) => $source->sourceInvestment->reference)->implode(', ').'.',
        ]);

        // Pin the rate on the new tranche. For a loan this is not optional:
        // InvestmentInterestPayoutService::periodInterest() resolves the rate once
        // from the start year and reuses it for the life of the loan, so without an
        // override the loan would silently re-price off whatever the company-wide
        // InvestmentRateSetting happens to be. Mirrors CreateInvestment::afterCreate().
        if ($conversion->target_annual_rate !== null) {
            InvestmentRateOverride::updateOrCreate(
                [
                    'investment_id' => $target->id,
                    'year' => Carbon::parse($target->start_date)->year,
                ],
                [
                    'annual_rate' => $conversion->target_annual_rate,
                    'created_by' => $staff->id,
                    'notes' => "Rate agreed on conversion {$conversion->reference}.",
                ]
            );
        }

        return $this->paymentService->recordConversionDeposit($target, $conversion->id, $conversion->reference);
    }

    private function assertTargetTermsPresent(InvestmentConversion $conversion): void
    {
        if ($conversion->sources->isEmpty()) {
            throw new \InvalidArgumentException('A conversion needs at least one source investment.');
        }

        if (! $conversion->target_contract_term_months) {
            throw new \InvalidArgumentException('A contract term is required for the converted tranche.');
        }

        if ($conversion->direction === InvestmentConversionDirection::to_loan && ! $conversion->target_payout_frequency) {
            throw new \InvalidArgumentException(
                'An interest payout frequency is required when converting to a loan — a loan pays interest out on a schedule rather than compounding it.'
            );
        }
    }

    /**
     * The same eligibility rules withdrawals enforce, for the same reasons: capital
     * is committed for the contract term, and a tranche may not be fragmented below
     * the configured floors. Both are overridable by staff, as with withdrawals.
     */
    private function assertSourceEligible(
        InvestmentConversion $conversion,
        Investment $investment,
        InvestmentConversionSource $source,
    ): void {
        if ($investment->status !== InvestmentStatus::active) {
            throw new \InvalidArgumentException(sprintf(
                '%s is %s and cannot be converted. Only active tranches may be converted.',
                $investment->reference,
                $investment->status->getLabel()
            ));
        }

        if ($investment->capital_type === $conversion->direction->targetCapitalType()) {
            throw new \InvalidArgumentException(sprintf(
                '%s is already a %s tranche.',
                $investment->reference,
                $investment->capital_type->getLabel()
            ));
        }

        if (! $investment->isContractDue() && ! $conversion->maturity_exception_approved) {
            throw new \InvalidArgumentException(sprintf(
                "%s's contract is not yet due. It can be converted from %s, or now with a staff exception.",
                $investment->reference,
                $investment->maturity_date?->format('F j, Y') ?? 'its maturity date'
            ));
        }

        // Only a partial roll can fragment a tranche, so only a partial roll is
        // subject to the floors. A principal_only roll settles the tranche completely
        // — the principal converts and the accrued interest leaves as cash, with
        // nothing left behind to fall below the minimum-remaining threshold.
        if ($source->mode !== InvestmentConversionSourceMode::partial || $conversion->threshold_exception_approved) {
            return;
        }

        $settlement = $this->settlementValue($investment, Carbon::parse($conversion->conversion_date));
        $total = round($settlement['principal'] + $settlement['interest'], 2);
        $amountRolled = round((float) $source->amount_rolled, 2);

        $partialMinimum = (float) config('investment.partial_minimum');

        if ($amountRolled < $partialMinimum) {
            throw new \InvalidArgumentException(sprintf(
                'Partial conversions must be at least $%s ($%s requested for %s).',
                number_format($partialMinimum, 2),
                number_format($amountRolled, 2),
                $investment->reference
            ));
        }

        $remaining = round($total - $amountRolled, 2);
        $minRemaining = (float) config('investment.minimum_remaining_balance');

        if ($remaining > 0 && $remaining < $minRemaining) {
            throw new \InvalidArgumentException(sprintf(
                'Converting this amount would leave %s at $%s, below the $%s minimum. Requires a staff exception, or convert the tranche in full.',
                $investment->reference,
                number_format($remaining, 2),
                number_format($minRemaining, 2)
            ));
        }
    }

    /**
     * Unwind a conversion recorded in error. Follows the audit-preserving convention
     * already set by InvestmentInterestService::reverseInterestCredit() and
     * EditInvestment's "Reverse & Close": offsetting ledger rows rather than deletes,
     * because the original postings already moved balances other documents cite.
     */
    public function reverse(InvestmentConversion $conversion, User $staff, string $reason): void
    {
        if ($conversion->status !== InvestmentConversionStatus::executed) {
            throw new \InvalidArgumentException('Only an executed conversion can be reversed.');
        }

        DB::transaction(function () use ($conversion, $staff, $reason) {
            $conversion->load(['sources.sourceInvestment', 'targetInvestment']);

            foreach ($conversion->sources as $source) {
                $investment = $source->sourceInvestment;
                $amount = (float) $source->amount_rolled;

                InvestmentTransaction::create([
                    'investment_id' => $investment->id,
                    'investor_id' => $investment->investor_id,
                    'date' => now(),
                    'type' => InvestmentTransactionType::conversion_out->value,
                    'op_balance' => $investment->current_balance,
                    'credit' => $amount,
                    'posted' => true,
                    'posted_by' => $staff->id,
                    'posted_at' => now(),
                    'reference_id' => $conversion->id,
                    'edited_by' => $staff->id,
                    'edited_at' => now(),
                    'description' => "Reversal of conversion {$conversion->reference}: {$reason}",
                ]);

                $investment->update([
                    'current_balance' => round((float) $investment->current_balance + $amount, 2),
                    'status' => InvestmentStatus::active->value,
                ]);

                $investment->interestPayouts()
                    ->where('status', InvestmentInterestPayoutStatus::converted->value)
                    ->update(['status' => InvestmentInterestPayoutStatus::due->value]);
            }

            $target = $conversion->targetInvestment;

            if ($target) {
                $target->update([
                    'notes' => trim(($target->notes ?? '')."\n[".now()->format('M j, Y').'] Conversion reversed by '.$staff->name.": {$reason}"),
                ]);
                $target->transactions()->delete();
                $target->rateOverrides()->delete();
                $target->interestPayouts()->delete();
                $target->delete();
            }

            $conversion->update([
                'status' => InvestmentConversionStatus::cancelled->value,
                'notes' => trim(($conversion->notes ?? '')."\n[".now()->format('M j, Y').'] Reversed by '.$staff->name.": {$reason}"),
                'reviewed_by' => $staff->id,
                'reviewed_at' => now(),
            ]);
        });
    }
}
