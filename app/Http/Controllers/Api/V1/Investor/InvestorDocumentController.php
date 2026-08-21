<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Enums\InvestmentAgreementStatus;
use App\Enums\InvestmentStatus;
use App\Http\Resources\InvestmentResource;
use App\Models\Investment;
use App\Models\InvestmentAnnualStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class InvestorDocumentController extends InvestorBaseController
{
    public function agreement(string $investmentId): JsonResponse
    {
        Investment::where('investor_id', $this->investorId())->findOrFail($investmentId);

        return $this->success([
            'url' => URL::temporarySignedRoute('investment-agreement', now()->addDay(), $investmentId),
            'download_url' => URL::temporarySignedRoute('investment-agreement-download', now()->addDay(), $investmentId),
        ]);
    }

    /**
     * Mirrors the "Upload Signed Agreement" action on the investor Filament panel
     * (InvestmentResource table): same eligibility rules, same fields written.
     */
    public function uploadSignedAgreement(Request $request, string $investmentId): JsonResponse
    {
        $investment = Investment::where('investor_id', $this->investorId())->findOrFail($investmentId);

        if ($investment->status === InvestmentStatus::pending_payment) {
            return $this->error('This investment must be funded before you can upload a signed agreement.', 422);
        }

        if ($investment->agreement_status === InvestmentAgreementStatus::finalized) {
            return $this->error('This agreement has already been finalized.', 422);
        }

        if ($investment->agreement_status === InvestmentAgreementStatus::pending_review) {
            return $this->error('Your signed agreement is already under review.', 422);
        }

        $request->validate([
            'signed_agreement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            // The agreement carries the investor's name already affixed as the
            // signature, so the return upload is the act of assent rather than the
            // signing itself. This explicit confirmation, with the originating IP,
            // is what evidences that assent.
            'acknowledged' => 'accepted',
        ], [
            'acknowledged.accepted' => 'You must confirm that you have read and agree to the terms of this agreement.',
        ]);

        $path = $request->file('signed_agreement')->store('investment-agreements/signed', 'public');

        $investment->update([
            'signed_agreement_path' => $path,
            'agreement_status' => InvestmentAgreementStatus::pending_review,
            'agreement_signed_at' => now(),
            'agreement_acknowledged_ip' => $request->ip(),
        ]);

        return $this->success(new InvestmentResource($investment->fresh()), 'Signed agreement uploaded. Our team will review it and confirm once finalized.');
    }

    public function combinedAgreement(): JsonResponse
    {
        return $this->success([
            'url' => URL::temporarySignedRoute('investment-agreement-combined', now()->addDay(), $this->investorId()),
        ]);
    }

    public function statement(): JsonResponse
    {
        return $this->success([
            'url' => URL::temporarySignedRoute('investment-account-statement', now()->addDay(), $this->investorId()),
            'download_url' => URL::temporarySignedRoute('investment-account-statement-download', now()->addDay(), $this->investorId()),
        ]);
    }

    public function statements(): JsonResponse
    {
        $statements = InvestmentAnnualStatement::where('investor_id', $this->investorId())
            ->orderByDesc('year')
            ->get()
            ->map(fn (InvestmentAnnualStatement $statement) => [
                'id' => $statement->id,
                'year' => $statement->year,
                'sent_at' => $statement->sent_at,
                'url' => URL::temporarySignedRoute('investment-annual-statement', now()->addDay(), $statement->id),
                'download_url' => URL::temporarySignedRoute('investment-annual-statement-download', now()->addDay(), $statement->id),
            ]);

        return $this->success($statements);
    }
}
