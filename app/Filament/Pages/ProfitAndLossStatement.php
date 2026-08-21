<?php

namespace App\Filament\Pages;

use App\Exports\ProfitAndLossExport;
use App\Filament\Pages\Concerns\RendersFinancialStatement;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ProfitAndLossStatement extends Page implements HasForms
{
    use InteractsWithForms, RendersFinancialStatement;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Profit & Loss Statement';

    protected static ?string $navigationLabel = 'Profit & Loss';

    protected static string $view = 'filament.pages.profit-and-loss-statement';

    public function form(Form $form): Form
    {
        return $form->schema([$this->yearSelect()]);
    }

    public function statement(): array
    {
        return $this->service()->profitAndLoss($this->selectedYear);
    }

    protected function pdfView(): string
    {
        return 'reports.profit-and-loss-pdf';
    }

    protected function excelExport(array $statement): object
    {
        return new ProfitAndLossExport($statement);
    }

    protected function pdfFilename(): string
    {
        return 'profit-and-loss';
    }
}
