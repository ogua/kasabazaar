<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Enums\InvestmentCapitalType;
use App\Http\Resources\InvestmentResource;
use App\Http\Resources\InvestmentTransactionResource;
use App\Models\Investment;
use App\Service\InvestmentInterestService;
use App\Service\InvestmentPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestorInvestmentController extends InvestorBaseController
{
    public function index(): JsonResponse
    {
        $investments = Investment::where('investor_id', $this->investorId())
            ->with('interestPayouts')
            ->orderByDesc('start_date')
            ->get();

        return $this->success(InvestmentResource::collection($investments));
    }

    public function show(string $id): JsonResponse
    {
        $investment = Investment::where('investor_id', $this->investorId())
            ->with('interestPayouts')
            ->findOrFail($id);

        // A loan's balance never compounds — interest lives entirely in
        // interestPayouts, not the compounding investment-tranche formula, which
        // would misrepresent it (and always read $0 interest, since loan interest
        // never creates an interest_credit transaction). Same distinction already
        // applied to the admin UI, investor panel, and account statement PDF.
        if ($investment->capital_type === InvestmentCapitalType::loan) {
            // Collection::whereIn() loosely compares against the raw strings, which
            // never matches the enum-cast `status` — filter on ->value instead.
            $payouts = $investment->interestPayouts->filter(fn ($p) => in_array($p->status->value, ['due', 'processing', 'paid'], true));
            $unpaidPayouts = $payouts->filter(fn ($p) => in_array($p->status->value, ['due', 'processing'], true));

            $valuation = [
                'capital_type' => 'loan',
                'principal' => (float) $investment->principal_amount,
                'interest_earned_total' => null,
                'compounded_balance' => null,
                'interest_accrued_total' => round((float) $payouts->sum('amount'), 2),
                'interest_paid_total' => round((float) $payouts->sum('amount_paid'), 2),
                'interest_owed' => round((float) $unpaidPayouts->sum(fn ($p) => (float) $p->amount - (float) $p->amount_paid), 2),
                'as_of' => now()->toDateString(),
            ];
        } else {
            $result = app(InvestmentInterestService::class)->valuationAsOf($investment, now());

            $valuation = [
                'capital_type' => 'investment',
                'principal' => $result['principal'],
                'interest_earned_total' => $result['interest_earned_total'],
                'compounded_balance' => $result['compounded_balance'],
                'interest_accrued_total' => null,
                'interest_paid_total' => null,
                'interest_owed' => null,
                'as_of' => $result['as_of']->toDateString(),
            ];
        }

        return $this->success([
            'investment' => new InvestmentResource($investment),
            'valuation' => $valuation,
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

    /**
     * Investor self-serve "Invest Now": creates a pending_payment tranche and
     * initiates a hosted checkout. The mobile app opens the returned url in an
     * in-app browser, then calls verify() once the browser closes.
     */
    public function store(Request $request, InvestmentPaymentService $service): JsonResponse
    {
        $data = $request->validate([
            'principal_amount' => 'required|numeric|min:0.01',
            'gateway' => 'required|in:paystack,stripe',
        ]);

        $investor = $this->investor();

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'principal_amount' => $data['principal_amount'],
            'start_date' => now(),
            'deposit_gateway' => $data['gateway'],
            'recorded_by' => auth()->id(),
        ]);

        $email = $investor->email ?? auth()->user()->email;

        try {
            $result = $data['gateway'] === 'stripe'
                ? $service->initiateStripe($investment, $email, config('app.url'), config('app.url'))
                : $service->initiatePaystack($investment, $email);
        } catch (\Throwable $e) {
            $investment->delete();

            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'investment_id' => $investment->id,
            'gateway' => $result['gateway'],
            'url' => $result['url'],
            'reference' => $result['reference'],
        ], 'Checkout initiated.', 201);
    }

    /**
     * Called by the client once its in-app browser closes, to confirm payment
     * immediately rather than waiting on the webhook (which still runs as the
     * authoritative, idempotent backstop).
     */
    public function verify(Request $request, string $id, InvestmentPaymentService $service): JsonResponse
    {
        $data = $request->validate(['reference' => 'required|string']);

        $investment = Investment::where('investor_id', $this->investorId())->findOrFail($id);

        try {
            $verified = $investment->deposit_gateway === 'stripe'
                ? $service->verifyAndRecordStripe($data['reference'])
                : $service->verifyAndRecordPaystack($data['reference']);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }

        if ($verified->id !== $investment->id) {
            return $this->error('Reference does not match this investment.', 422);
        }

        return $this->success(new InvestmentResource($verified), 'Payment confirmed.');
    }
}
