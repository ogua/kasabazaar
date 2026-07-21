<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\InvestmentAnnualStatement;
use App\Models\Investor;
use App\Service\AnnualInvestmentStatementService;
use App\Service\InvestmentAgreementService;
use App\Service\InvestmentStatementService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function agreement(Investment $investment, Request $request)
    {
        return InvestmentAgreementService::streamPdf($investment, $this->parseAsOf($request));
    }

    public function agreementDownload(Investment $investment, Request $request)
    {
        return InvestmentAgreementService::downloadPdf($investment, $this->parseAsOf($request));
    }

    public function combinedAgreement(Investor $investor, Request $request)
    {
        return InvestmentAgreementService::streamCombinedPdf($investor, $this->parseAsOf($request));
    }

    private function parseAsOf(Request $request): ?Carbon
    {
        $asOf = $request->query('as_of');

        return $asOf ? Carbon::parse($asOf)->endOfDay() : null;
    }

    public function annualStatement(InvestmentAnnualStatement $statement)
    {
        return AnnualInvestmentStatementService::streamPdf($statement);
    }

    public function annualStatementDownload(InvestmentAnnualStatement $statement)
    {
        return AnnualInvestmentStatementService::downloadPdf($statement);
    }

    public function accountStatement(Investor $investor)
    {
        return InvestmentStatementService::streamPdf($investor);
    }

    public function accountStatementDownload(Investor $investor)
    {
        return InvestmentStatementService::downloadPdf($investor);
    }
}
