<?php

namespace App\Filament\Investor\Widgets;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentStatus;
use App\Enums\InvestmentWithdrawalStatus;
use App\Filament\Investor\Resources\InvestmentResource;
use App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource;
use App\Models\Investment;
use App\Models\InvestmentWithdrawalRequest;
use App\Service\InvestorPortfolioSummaryService;
use Filament\Widgets\Widget;

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

        $holdings = Investment::where('investor_id', $investorId)
            ->excludingConverted()
            ->whereIn('status', [InvestmentStatus::active->value, InvestmentStatus::pending_payment->value])
            ->orderBy('maturity_date')
            ->get();

        $activeHoldings = $holdings->where('status', InvestmentStatus::active);

        $pendingWithdrawals = InvestmentWithdrawalRequest::where('investor_id', $investorId)
            ->whereIn('status', [
                InvestmentWithdrawalStatus::submitted->value,
                InvestmentWithdrawalStatus::under_review->value,
                InvestmentWithdrawalStatus::approved->value,
                InvestmentWithdrawalStatus::processing->value,
            ])
            ->get();

        return [
            'summary' => app(InvestorPortfolioSummaryService::class)->compute($investorId),
            'holdings' => $holdings,
            'activeCount' => $activeHoldings->count(),
            'nextMaturity' => $activeHoldings->whereNotNull('maturity_date')
                ->filter(fn (Investment $investment) => $investment->maturity_date->isFuture())
                ->min('maturity_date'),
            'nextPayout' => $activeHoldings->where('capital_type', InvestmentCapitalType::loan)
                ->whereNotNull('next_payout_due_date')
                ->min('next_payout_due_date'),
            'pendingWithdrawalCount' => $pendingWithdrawals->count(),
            'pendingWithdrawalAmount' => (float) $pendingWithdrawals->sum('requested_amount'),
            'investmentsUrl' => InvestmentResource::getUrl('index'),
            'withdrawalsUrl' => InvestmentWithdrawalRequestResource::getUrl('index'),
        ];
    }
}
