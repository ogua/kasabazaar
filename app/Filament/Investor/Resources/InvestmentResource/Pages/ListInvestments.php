<?php

namespace App\Filament\Investor\Resources\InvestmentResource\Pages;

use App\Filament\Investor\Resources\InvestmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\URL;

class ListInvestments extends ListRecords
{
    protected static string $resource = InvestmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // A header action has no per-row $record — it's the current investor's
            // own combined agreement across every tranche they hold, not tied to
            // whichever Investment happens to be first in the table.
            Actions\Action::make('downloadAgreement')
                ->label('Download Agreement')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->url(fn (): string => URL::temporarySignedRoute('investment-agreement-combined', now()->addDay(), [
                    'investor' => auth()->user()->investor_id,
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
