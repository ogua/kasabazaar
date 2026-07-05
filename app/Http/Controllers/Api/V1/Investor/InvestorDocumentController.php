<?php

namespace App\Http\Controllers\Api\V1\Investor;

use App\Models\Investment;
use App\Models\InvestmentAnnualStatement;
use Illuminate\Http\JsonResponse;
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

    public function combinedAgreement(): JsonResponse
    {
        return $this->success([
            'url' => URL::temporarySignedRoute('investment-agreement-combined', now()->addDay(), $this->investorId()),
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
