<?php

namespace App\Filament\Pages;

use App\Exports\BalanceSheetExport;
use App\Filament\Pages\Concerns\RendersFinancialStatement;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class BalanceSheet extends Page implements HasForms
{
    use InteractsWithForms, RendersFinancialStatement;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Balance Sheet';

    protected static ?string $navigationLabel = 'Balance Sheet';

    protected static string $view = 'filament.pages.balance-sheet';

    public function form(Form $form): Form
    {
        return $form->schema([$this->yearSelect()]);
    }

    public function statement(): array
    {
        return $this->service()->balanceSheet($this->selectedYear);
    }

    protected function pdfView(): string
    {
        return 'reports.balance-sheet-pdf';
    }

    protected function excelExport(array $statement): object
    {
        return new BalanceSheetExport($statement);
    }

    protected function pdfFilename(): string
    {
        return 'balance-sheet';
    }
}
