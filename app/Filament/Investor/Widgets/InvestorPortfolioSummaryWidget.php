<?php

namespace App\Filament\Investor\Widgets;

use App\Filament\Investor\Resources\InvestmentResource;
use App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource;
use App\Service\InvestorPortfolioSummaryService;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class InvestorPortfolioSummaryWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.investor.widgets.investor-portfolio-summary-widget';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $investorId = auth()->user()->investor_id;
        $service = app(InvestorPortfolioSummaryService::class);
        $facts = $service->facts($investorId);

        return [
            'summary' => $service->compute($investorId),
            'holdings' => $facts['holdings'],
            'activeCount' => $facts['active_count'],
            'nextMaturity' => $facts['next_maturity_date'] ? Carbon::parse($facts['next_maturity_date']) : null,
            'nextPayout' => $facts['next_payout_date'] ? Carbon::parse($facts['next_payout_date']) : null,
            'pendingWithdrawalCount' => $facts['pending_withdrawal_count'],
            'pendingWithdrawalAmount' => $facts['pending_withdrawal_amount'],
            'investmentsUrl' => InvestmentResource::getUrl('index'),
            'withdrawalsUrl' => InvestmentWithdrawalRequestResource::getUrl('index'),
        ];
    }
}
