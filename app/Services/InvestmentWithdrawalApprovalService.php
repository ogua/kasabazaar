<?php

namespace App\Services;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentStatus;
use App\Enums\InvestmentTransactionType;
use App\Enums\InvestmentWithdrawalStatus;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\InvestmentWithdrawalRequest;
use App\Models\User;
use App\Notifications\InvestmentWithdrawalStatusUpdated;
use App\Service\InvestmentInterestService;
use App\Service\InvestorNotifier;
use Illuminate\Support\Facades\DB;

class InvestmentWithdrawalApprovalService
{
    public function submit(Investment $investment, array $data): InvestmentWithdrawalRequest
    {
        if ($investment->capital_type === InvestmentCapitalType::loan) {
            throw new \InvalidArgumentException(
                'Loans are not eligible for early withdrawal. Principal is due in full at maturity per your Loan Agreement; contact the Company regarding prepayment.'
            );
        }

        if (! $investment->isContractDue()) {
            $maturityDate = $investment->maturity_date?->format('F j, Y');

            throw new \InvalidArgumentException(
                "This investment's contract is not yet due. Withdrawal requests can be submitted starting {$maturityDate}."
            );
        }

        $isFull = (bool) ($data['is_full_withdrawal'] ?? false);
        $requestedAmount = $isFull ? null : (float) ($data['requested_amount'] ?? 0);
        $remainingAfter = null;

        if (! $isFull) {
            $partialMinimum = config('investment.partial_minimum');

            if ($requestedAmount < $partialMinimum) {
                throw new \InvalidArgumentException('Partial withdrawals must be at least $'.number_format($partialMinimum, 2).'.');
            }

            $remainingAfter = round((float) $investment->current_balance - $requestedAmount, 2);
            $minRemaining = config('investment.minimum_remaining_balance');

            if ($remainingAfter < $minRemaining && ! ($data['exception_approved'] ?? false)) {
                throw new \InvalidArgumentException(
                    'Remaining balance would fall below $'.number_format($minRemaining, 2).'. Requires a staff exception.'
                );
            }
        }

        $noticeDate = now();

        return InvestmentWithdrawalRequest::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'is_full_withdrawal' => $isFull,
            'requested_amount' => $requestedAmount,
            'notice_date' => $noticeDate,
            'required_action_by' => $noticeDate->copy()->addDays(config('investment.notice_days')),
            'status' => InvestmentWithdrawalStatus::submitted->value,
            'remaining_balance_after' => $isFull ? 0 : $remainingAfter,
            'exception_approved' => (bool) ($data['exception_approved'] ?? false),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function markUnderReview(InvestmentWithdrawalRequest $request): void
    {
        $request->update(['status' => InvestmentWithdrawalStatus::under_review->value]);

        InvestorNotifier::notify($request->investor_id, new InvestmentWithdrawalStatusUpdated($request->fresh()));
    }

    public function approve(InvestmentWithdrawalRequest $request, User $approvedBy, array $options = []): InvestmentWithdrawalRequest
    {
        return DB::transaction(function () use ($request, $approvedBy, $options) {
            $deferralDays = (int) ($options['deferral_days'] ?? 0);
            $maxDeferral = config('investment.max_deferral_days');

            if ($deferralDays > $maxDeferral) {
                throw new \InvalidArgumentException("Deferral cannot exceed {$maxDeferral} days.");
            }

            if ($deferralDays > 0 && blank($options['deferral_reason'] ?? null)) {
                throw new \InvalidArgumentException('A deferral reason is required when deferring payment.');
            }

            $paymentWindow = config('investment.payment_window_days');

            $request->update([
                'status' => InvestmentWithdrawalStatus::approved->value,
                'exception_approved' => $options['exception_approved'] ?? $request->exception_approved,
                'deferral_days' => $deferralDays ?: null,
                'deferral_reason' => $options['deferral_reason'] ?? null,
                'payment_due_by' => now()->addDays($paymentWindow + $deferralDays),
                'reviewed_by' => $approvedBy->id,
                'reviewed_at' => now(),
            ]);

            InvestorNotifier::notify($request->investor_id, new InvestmentWithdrawalStatusUpdated($request->fresh()));

            return $request->fresh();
        });
    }

    public function reject(InvestmentWithdrawalRequest $request, User $reviewedBy, string $reason): void
    {
        $request->update([
            'status' => InvestmentWithdrawalStatus::rejected->value,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewedBy->id,
            'reviewed_at' => now(),
        ]);

        InvestorNotifier::notify($request->investor_id, new InvestmentWithdrawalStatusUpdated($request->fresh()));
    }

    /**
     * Record an actual payout (manual or gateway-confirmed) against an approved request.
     * On the payment that closes out a full withdrawal, true-ups accrued interest through
     * today first — the resulting fresh current_balance is the authoritative payout amount,
     * regardless of what the caller passed, since any suggested figure was necessarily
     * computed before this true-up ran.
     */
    public function recordPayment(InvestmentWithdrawalRequest $request, ?float $amount, User $recordedBy, array $paymentData = []): InvestmentWithdrawalRequest
    {
        return DB::transaction(function () use ($request, $amount, $recordedBy, $paymentData) {
            $investment = $request->investment;

            if ($request->is_full_withdrawal) {
                app(InvestmentInterestService::class)->postStubPeriodInterest($investment, now(), $recordedBy);
                $investment->refresh();
                $amount = (float) $investment->current_balance;
            }

            InvestmentTransaction::create([
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'date' => now(),
                'type' => InvestmentTransactionType::withdrawal->value,
                'op_balance' => $investment->current_balance,
                'debit' => $amount,
                'posted' => true,
                'posted_at' => now(),
                'reference_id' => $request->id,
                'description' => "Withdrawal payout for request {$request->id}",
            ]);

            $newBalance = round((float) $investment->current_balance - $amount, 2);
            $investment->update(['current_balance' => $newBalance]);

            $newAmountPaid = round((float) $request->amount_paid + $amount, 2);
            $isFullyPaid = $request->is_full_withdrawal
                ? $newBalance <= 0.01
                : $newAmountPaid >= (float) $request->requested_amount - 0.01;

            $request->update([
                'amount_paid' => $newAmountPaid,
                'paid_at' => $isFullyPaid ? now() : $request->paid_at,
                'status' => $isFullyPaid ? InvestmentWithdrawalStatus::paid->value : InvestmentWithdrawalStatus::processing->value,
                'payout_gateway' => $paymentData['payout_gateway'] ?? $request->payout_gateway ?? 'manual',
                'payment_method' => $paymentData['payment_method'] ?? $request->payment_method,
                'payment_reference' => $paymentData['payment_reference'] ?? $request->payment_reference,
                'receipt_path' => $paymentData['receipt_path'] ?? $request->receipt_path,
            ]);

            if ($isFullyPaid && $request->is_full_withdrawal) {
                $investment->update(['status' => InvestmentStatus::withdrawn->value]);
            }

            InvestorNotifier::notify($request->investor_id, new InvestmentWithdrawalStatusUpdated($request->fresh()));

            return $request->fresh();
        });
    }
}
