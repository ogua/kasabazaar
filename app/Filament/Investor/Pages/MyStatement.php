<?php

namespace App\Filament\Investor\Pages;

use App\Models\Investor;
use App\Service\InvestmentInterestService;
use App\Service\InvestmentStatementService;
use Filament\Actions\Action;
use Filament\Pages\Page;

class MyStatement extends Page
{
    protected static string $view = 'filament.investor.pages.my-statement';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Statement';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Account Statement';

    public function getInvestor(): Investor
    {
        return Investor::with(['investments.transactions', 'investments.withdrawalRequests'])
            ->findOrFail(auth()->user()->investor_id);
    }

    public function getValuations(): array
    {
        $interestService = app(InvestmentInterestService::class);

        return $this->getInvestor()->investments
            ->mapWithKeys(fn ($investment) => [$investment->id => $interestService->valuationAsOf($investment, now())])
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadStatement')
                ->label('Download Statement')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('downloadPdf'),
        ];
    }

    public function downloadPdf()
    {
        return InvestmentStatementService::downloadPdf($this->getInvestor());
    }
}
