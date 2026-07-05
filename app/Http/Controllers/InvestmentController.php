<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\InvestmentAnnualStatement;
use App\Models\Investor;
use App\Service\AnnualInvestmentStatementService;
use App\Service\InvestmentAgreementService;

class InvestmentController extends Controller
{
    public function agreement(Investment $investment)
    {
        return InvestmentAgreementService::streamPdf($investment);
    }

    public function agreementDownload(Investment $investment)
    {
        return InvestmentAgreementService::downloadPdf($investment);
    }

    public function combinedAgreement(Investor $investor)
    {
        return InvestmentAgreementService::streamCombinedPdf($investor);
    }

    public function annualStatement(InvestmentAnnualStatement $statement)
    {
        return AnnualInvestmentStatementService::streamPdf($statement);
    }

    public function annualStatementDownload(InvestmentAnnualStatement $statement)
    {
        return AnnualInvestmentStatementService::downloadPdf($statement);
    }
}
