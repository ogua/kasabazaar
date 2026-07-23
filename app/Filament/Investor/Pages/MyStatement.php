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
        $investor = $this->getInvestor();
        $asOfDate = $investor->defaultAsOfDate();

        return $investor->investments
            ->mapWithKeys(fn ($investment) => [$investment->id => $interestService->valuationAsOf($investment, $asOfDate)])
            ->toArray();
    }

    public function getAsOfDate(): \Carbon\Carbon
    {
        return $this->getInvestor()->defaultAsOfDate();
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
        $investor = $this->getInvestor();
        $pdf = InvestmentStatementService::generatePdf($investor);

        // Livewire only intercepts StreamedResponse/BinaryFileResponse as a browser
        // download; DomPDF's own ->download() returns a plain Response, which a
        // wire:click-triggered action silently swallows.
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "investment-statement-{$investor->id}.pdf");
    }
}
